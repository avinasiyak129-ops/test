<?php
session_start();

// --- 1. ACCESS CREDENTIALS ---
define('ACCESS_PASSWORD_HASH', '$2a$12$dZCa4InZXrbwFXmDT5bqm.phIILn2K0AolBIj2ABeGR38GswIjZCi'); 

// --- 2. DATABASE CONFIGURATION ---
$db_config = [
    'host' => '10.8.81.38',
    'name' => 'opal_learninghub_live',
    'user' => 'dbconnectusr',
    'pass' => 'db@Con$ter'
];

// --- 3. AUTHENTICATION LOGIC ---
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['login_pass'])) {
    if (password_verify($_POST['login_pass'], ACCESS_PASSWORD_HASH)) {
        $_SESSION['authenticated'] = true;
    } else {
        $error = "Access Denied.";
    }
}

if (!isset($_SESSION['authenticated'])) {
    die('
    <body style="background:#0d1117; color:#58a6ff; font-family:monospace; display:flex; justify-content:center; align-items:center; height:100vh; margin:0;">
        <form method="post" style="border:1px solid #30363d; padding:30px; background:#161b22; border-radius:8px; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
            <h3 style="margin-top:0; color:#c9d1d9;">V-INTEL GATEWAY</h3>
            <input type="password" name="login_pass" placeholder="Enter System Password" required style="background:#0d1117; color:#c9d1d9; border:1px solid #30363d; padding:10px; width:220px; border-radius:4px;">
            <button type="submit" style="background:#238636; color:#fff; border:none; padding:10px 15px; cursor:pointer; border-radius:4px; font-weight:bold;">UNLOCK</button>
            <p style="color:#f85149; font-size:12px;">'.($error ?? '').'</p>
        </form>
    </body>');
}

// --- 4. DATABASE CONNECTION ---
try {
    $pdo = new PDO("mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8", $db_config['user'], $db_config['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Matrix Connection Error: " . $e->getMessage());
}

$message = "";
$mode = isset($_GET['id']) ? 'edit' : 'list';

// --- 5. UPDATE ACTION ---
if ($mode === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_record'])) {
    $fields = $_POST['data'];
    $pk = $_GET['id'];
    $setParts = [];
    $execute_params = [];

    foreach ($fields as $col => $val) {
        if ($col !== 'rasvehicleregdtls_pk') {
            $setParts[] = "$col = :$col";
            $execute_params[$col] = $val;
        }
    }
    
    $execute_params['pk'] = $pk;
    $sql = "UPDATE rasvehicleregdtls_tbl SET " . implode(', ', $setParts) . " WHERE rvrd_vechicleregno = :pk";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($execute_params);
        $message = "Target $pk re-indexed successfully.";
    } catch (PDOException $e) {
        $message = "Update Failed: " . $e->getMessage();
    }
}

// --- 6. DATA FETCHING (Logic Modified for Reg No Formats) ---
if ($mode === 'edit') {
    $stmt = $pdo->prepare("SELECT * FROM rasvehicleregdtls_tbl WHERE rvrd_vechicleregno = ?");
    $stmt->execute([$_GET['id']]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$record) die("Record purged or non-existent.");
} else {
    $search = trim($_GET['search'] ?? '');
    $query = "SELECT rasvehicleregdtls_pk, rvrd_vechicleregno, rvrd_inspectorname, rvrd_inspectionstatus, rvrd_permitstatus FROM rasvehicleregdtls_tbl";
    
    if ($search !== '') {
        // Strip hyphens from the search term to match the 'cleaned' DB column
       
        $stmt = $pdo->prepare($query);
        $stmt->execute(['search' => $cleanSearch]);
    } else {
        $stmt = $pdo->query($query . " LIMIT 100"); // Safety limit for broad view
    }
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vehicle Registry Terminal</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; margin: 0; padding: 20px; color: #1c1e21; }
        .container { max-width: 1400px; margin: auto; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ddd; margin-bottom: 25px; padding-bottom: 15px; }
        h1 { margin: 0; font-size: 22px; color: #003366; }
        .search-box { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #e1e4e8; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #003366; color: white; padding: 12px; text-align: left; font-size: 13px; text-transform: uppercase; }
        td { padding: 12px; border-bottom: 1px solid #eee; font-size: 14px; }
        tr:hover { background-color: #f0f7ff; cursor: pointer; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 15px; }
        .form-group { display: flex; flex-direction: column; }
        label { font-size: 11px; font-weight: bold; color: #606770; margin-bottom: 4px; text-transform: uppercase; }
        input { padding: 10px; border: 1px solid #ccd0d5; border-radius: 6px; }
        input:focus { border-color: #1877f2; outline: none; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; font-size: 14px; font-weight: 600; }
        .btn-primary { background: #1877f2; color: white; }
        .btn-success { background: #42b72a; color: white; }
        .btn-danger { background: #f02849; color: white; }
        .msg { padding: 15px; background: #e7f3ff; color: #003366; margin-bottom: 20px; border-radius: 6px; border-left: 6px solid #1877f2; }
    </style>
</head>
<body>

<div class="container">
    <header>
        <h1>Vehicle Matrix Registry</h1>
        <a href="?logout=1" class="btn btn-danger">Secure Logout</a>
    </header>

    <?php if ($message): ?> <div class="msg"><?= $message ?></div> <?php endif; ?>

    <?php if ($mode === 'list'): ?>
        <div class="search-box">
            <form method="get">
                <label style="display:block; margin-bottom: 8px;">Lookup by Vehicle Reg No (Formats: 3788-BK, 7390BM):</label>
                <input type="text" name="search" placeholder="Enter Registration Number..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="width: 300px; padding: 12px;">
                <button type="submit" class="btn btn-primary">Execute Search</button>
                <a href="?" class="btn" style="background:#8a8d91; color:white;">Clear</a>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>PK ID</th>
                    <th>Registration No</th>
                    <th>Inspector</th>
                    <th>Status</th>
                    <th>Permit</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($results)): ?>
                    <tr><td colspan="5" style="text-align:center; padding:30px; color:#606770;">No matching records found in the database.</td></tr>
                <?php else: ?>
                    <?php foreach ($results as $row): ?>
                    <tr onclick="window.location='?id=<?= $row['rasvehicleregdtls_pk'] ?>'">
                        <td><strong>#<?= $row['rasvehicleregdtls_pk'] ?></strong></td>
                        <td style="color:#1877f2; font-weight:bold;"><?= htmlspecialchars($row['rvrd_vechicleregno']) ?></td>
                        <td><?= htmlspecialchars($row['rvrd_inspectorname']) ?></td>
                        <td><?= htmlspecialchars($row['rvrd_inspectionstatus']) ?></td>
                        <td><?= htmlspecialchars($row['rvrd_permitstatus']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    <?php else: ?>
        <div style="margin-bottom: 25px;">
            <a href="?" class="btn btn-primary">← Return to Search Results</a>
        </div>
        
        <form method="post" onsubmit="return confirm('Confirm update to live production data?');">
            <div class="form-grid">
                <?php foreach ($record as $column => $value): ?>
                    <div class="form-group">
                        <label><?= $column ?></label>
                        <input type="text" name="data[<?= $column ?>]" value="<?= htmlspecialchars($value ?? '') ?>" 
                            <?= ($column === 'rasvehicleregdtls_pk') ? 'readonly' : '' ?>>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top: 40px; text-align: right; border-top: 2px solid #f0f2f5; padding-top: 25px;">
                <button type="submit" name="update_record" class="btn btn-success">Update Record Attributes</button>
            </div>
        </form>
    <?php endif; ?>
</div>

</body>
</html>

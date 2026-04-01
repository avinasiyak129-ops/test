<?php
session_start();

// --- 1. ACCESS CREDENTIALS (Hardcoded & Separate) ---
define('ACCESS_PASSWORD_HASH', '$2a$12$dZCa4InZXrbwFXmDT5bqm.phIILn2K0AolBIj2ABeGR38GswIjZCi'); // 'sirvenom123'

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
        $error = "Access Denied: Invalid Credentials.";
    }
}

if (!isset($_SESSION['authenticated'])) {
    die('
    <body style="background:#1a1a1a; color:#00ff00; font-family:monospace; display:flex; justify-content:center; align-items:center; height:100vh;">
        <form method="post" style="border:1px solid #00ff00; padding:20px;">
            <h3>SECURE ACCESS GATEWAY</h3>
            <input type="password" name="login_pass" placeholder="Password" required style="background:#000; color:#00ff00; border:1px solid #00ff00; padding:5px;">
            <button type="submit" style="background:#00ff00; color:#000; border:none; padding:5px 10px; cursor:pointer;">UNLOCK</button>
            <p>'.($error ?? '').'</p>
        </form>
    </body>');
}

// --- 4. DATABASE CONNECTION ---
try {
    $pdo = new PDO("mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8", $db_config['user'], $db_config['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
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
    $sql = "UPDATE rasvehicleregdtls_tbl SET " . implode(', ', $setParts) . " WHERE rasvehicleregdtls_pk = :pk";
    
    try {
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($execute_params)) {
            $message = "Record [PK: $pk] updated successfully.";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// --- 6. DATA FETCHING ---
if ($mode === 'edit') {
    $stmt = $pdo->prepare("SELECT * FROM rasvehicleregdtls_tbl WHERE rasvehicleregdtls_pk = ?");
    $stmt->execute([$_GET['id']]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$record) die("Entry not found.");
} else {
    $search = $_GET['search'] ?? '';
    $query = "SELECT rasvehicleregdtls_pk, rvrd_vechicleregno, rvrd_inspectorname, rvrd_inspectionstatus, rvrd_permitstatus FROM rasvehicleregdtls_tbl";
    if ($search) {
        $query .= " WHERE rasvehicleregdtls_pk = :search";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['search' => $search]);
    } else {
        $stmt = $pdo->query($query);
    }
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vehicle Intel Hub | Jarvis</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f4f7f6; margin: 0; padding: 20px; }
        .container { max-width: 1300px; margin: auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; margin-bottom: 20px; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        tr:hover { background-color: #f9f9f9; cursor: pointer; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; }
        .form-group { display: flex; flex-direction: column; }
        label { font-size: 10px; font-weight: bold; color: #7f8c8d; margin-bottom: 4px; text-transform: uppercase; }
        input { padding: 10px; border: 1px solid #dcdde1; border-radius: 4px; font-size: 14px; }
        input[readonly] { background: #f1f2f6; color: #7f8c8d; }
        .btn { padding: 10px 18px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 14px; display: inline-block; }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #2ecc71; color: white; font-weight: bold; }
        .btn-danger { background: #e74c3c; color: white; }
        .msg { padding: 15px; background: #d4edda; color: #155724; margin-bottom: 20px; border-radius: 4px; border-left: 5px solid #28a745; }
    </style>
</head>
<body>

<div class="container">
    <header>
        <h1>Vehicle Matrix Terminal</h1>
        <a href="?logout=1" class="btn btn-danger">Lock Terminal</a>
    </header>

    <?php if ($message): ?> <div class="msg"><?= $message ?></div> <?php endif; ?>

    <?php if ($mode === 'list'): ?>
        <form method="get" style="margin-bottom: 20px; background: #f8f9fa; padding: 15px; border-radius: 8px;">
            <label style="display:block; margin-bottom: 5px;">Filter by Primary Key:</label>
            <input type="number" name="search" placeholder="Enter rasvehicleregdtls_pk..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="width: 250px;">
            <button type="submit" class="btn btn-primary">Search Matrix</button>
            <a href="?" class="btn" style="background:#bdc3c7; color:white;">Reset</a>
        </form>

        <table>
            <thead>
                <tr>
                    <th>PK ID</th>
                    <th>Registration No</th>
                    <th>Inspector</th>
                    <th>Inspection Status</th>
                    <th>Permit Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($results)): ?>
                    <tr><td colspan="5">No entries found.</td></tr>
                <?php else: ?>
                    <?php foreach ($results as $row): ?>
                    <tr onclick="window.location='?id=<?= $row['rasvehicleregdtls_pk'] ?>'">
                        <td><strong><?= $row['rasvehicleregdtls_pk'] ?></strong></td>
                        <td><?= htmlspecialchars($row['rvrd_vechicleregno']) ?></td>
                        <td><?= htmlspecialchars($row['rvrd_inspectorname']) ?></td>
                        <td><?= htmlspecialchars($row['rvrd_inspectionstatus']) ?></td>
                        <td><?= htmlspecialchars($row['rvrd_permitstatus']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    <?php else: ?>
        <div style="margin-bottom: 20px;">
            <a href="?" class="btn btn-primary">← Back to Results</a>
        </div>
        
        <form method="post">
            <div class="form-grid">
                <?php foreach ($record as $column => $value): ?>
                    <div class="form-group">
                        <label><?= $column ?></label>
                        <input type="text" name="data[<?= $column ?>]" value="<?= htmlspecialchars($value ?? '') ?>" 
                            <?= ($column === 'rasvehicleregdtls_pk') ? 'readonly' : '' ?>>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top: 40px; text-align: right; border-top: 1px solid #eee; padding-top: 20px;">
                <button type="submit" name="update_record" class="btn btn-success">Commit Changes to Database</button>
            </div>
        </form>
    <?php endif; ?>
</div>

</body>
</html>

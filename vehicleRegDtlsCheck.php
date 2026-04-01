<?php
session_start();

// --- 1. CONFIGURATION ---
$db_config = [
    'host' => '10.8.81.38',
    'name' => 'opal_learninghub_live',
    'user' => 'dbconnectusr',
    'pass' => 'db@Con$ter',
    
    'access_pass' => '$2a$12$BOzSLzJWsJ9wtW0JidSMeuHNMCEtGBW9BwpIIplXWRxeCh3Q262uG' // 'sirvenom123'
];


if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['login_pass'])) {
    if (password_verify($_POST['login_pass'], $db_config['access_pass'])) {
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

// --- 3. DATABASE CONNECTION ---
try {
    $pdo = new PDO("mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8", $db_config['user'], $db_config['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection Failed: " . $e->getMessage());
}

$message = "";
$mode = isset($_GET['id']) ? 'edit' : 'list';

// --- 4. UPDATE ACTION ---
if ($mode === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_record'])) {
    $fields = $_POST['data'];
    $pk = $_GET['id'];
    $setParts = [];
    foreach ($fields as $col => $val) {
        if ($col !== 'rasvehicleregdtls_pk') {
            $setParts[] = "$col = :$col";
        }
    }
    $sql = "UPDATE rasvehicleregdtls_tbl SET " . implode(', ', $setParts) . " WHERE rasvehicleregdtls_pk = :pk";
    $stmt = $pdo->prepare($sql);
    $fields['pk'] = $pk;
    if ($stmt->execute($fields)) {
        $message = "Record $pk updated successfully.";
    }
}

// --- 5. DATA FETCHING ---
if ($mode === 'edit') {
    $stmt = $pdo->prepare("SELECT * FROM rasvehicleregdtls_tbl WHERE rasvehicleregdtls_pk = ?");
    $stmt->execute([$_GET['id']]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $search = $_GET['search'] ?? '';
    $query = "SELECT rasvehicleregdtls_pk, rvrd_vechicleregno, rvrd_inspectorname, rvrd_inspectionstatus FROM rasvehicleregdtls_tbl";
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
    <title>Vehicle Intel Hub</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f4f7f6; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; margin-bottom: 20px; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        tr:hover { background-color: #f9f9f9; cursor: pointer; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; }
        .form-group { display: flex; flex-direction: column; }
        label { font-size: 11px; font-weight: bold; color: #666; margin-bottom: 4px; text-transform: uppercase; }
        input { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .btn { padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 14px; }
        .btn-primary { background: #2980b9; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-danger { background: #c0392b; color: white; }
        .msg { padding: 10px; background: #d4edda; color: #155724; margin-bottom: 20px; border-radius: 4px; }
    </style>
</head>
<body>

<div class="container">
    <header>
        <h1>Vehicle Registry Matrix</h1>
        <div>
            <a href="?logout=1" class="btn btn-danger">Lock Terminal</a>
        </div>
    </header>

    <?php if ($message): ?> <div class="msg"><?= $message ?></div> <?php endif; ?>

    <?php if ($mode === 'list'): ?>
        <form method="get" style="margin-bottom: 20px;">
            <input type="number" name="search" placeholder="Search by PK ID..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="?" class="btn">Clear</a>
        </form>

        <table>
            <thead>
                <tr>
                    <th>PK ID</th>
                    <th>Registration No</th>
                    <th>Inspector</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                <tr onclick="window.location='?id=<?= $row['rasvehicleregdtls_pk'] ?>'">
                    <td><strong><?= $row['rasvehicleregdtls_pk'] ?></strong></td>
                    <td><?= htmlspecialchars($row['rvrd_vechicleregno']) ?></td>
                    <td><?= htmlspecialchars($row['rvrd_inspectorname']) ?></td>
                    <td><?= htmlspecialchars($row['rvrd_permitstatus']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php else: ?>
        <div style="margin-bottom: 20px;">
            <a href="?" class="btn">← Back to Dashboard</a>
        </div>
        <form method="post">
            <div class="form-grid">
                <?php foreach ($record as $column => $value): ?>
                    <div class="form-group">
                        <label><?= str_replace('rvrd_', '', $column) ?></label>
                        <input type="text" name="data[<?= $column ?>]" value="<?= htmlspecialchars($value ?? '') ?>" 
                            <?= ($column === 'rasvehicleregdtls_pk') ? 'readonly' : '' ?>>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top: 30px; text-align: right;">
                <button type="submit" name="update_record" class="btn btn-success">Execute Update</button>
            </div>
        </form>
    <?php endif; ?>
</div>

</body>
</html>

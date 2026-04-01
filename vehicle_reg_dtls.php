<?php
$host = '10.8.81.38';
$user = 'dbconnectusr';
$pass = 'db@Con$ter';
$db   = 'opal_learninghub_live';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// ── INSERT handler ────────────────────────────────────────────────────────────
$insertMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_action']) && $_POST['_action'] === 'insert') {
    $f = $_POST;

    // Helper: return null for empty strings (needed for nullable INT/date columns)
    $ni = function($k) use ($f) {
    return (isset($f[$k]) && $f[$k] !== '') ? (int)$f[$k] : null;
};

$ns = function($k) use ($f) {
    return (isset($f[$k]) && $f[$k] !== '') ? $f[$k] : null;
};

$dt = function($k) use ($f) {
    return (isset($f[$k]) && $f[$k] !== '') ? str_replace('T', ' ', $f[$k]) : null;
};

    // Required ints
    $appinstinfomain_fk    = (int)$f['rvrd_appinstinfomain_fk'];
    $opalmemberregmst_fk   = (int)$f['rvrd_opalmemberregmst_fk'];
    $rasvehicleownerdtls_fk= (int)$f['rvrd_rasvehicleownerdtls_fk'];
    $vechiclecat           = (int)$f['rvrd_vechiclecat'];
    $applicationtype       = (int)$f['rvrd_applicationtype'];
    $createdby             = (int)$f['rvrd_createdby'];

    // Nullable ints
    $ivmsvehicleregdtls_fk = $ni('rvrd_ivmsvehicleregdtls_fk');
    $odometerreading       = $ni('rvrd_odometerreading');
    $roadtype              = $ni('rvrd_roadtype');
    $inspectorname         = $ni('rvrd_inspectorname');
    $modelyear             = $ni('rvrd_modelyear');

    // Nullable strings
    $contpermailid   = $ns('rvrd_contpermailid');
    $contpermobno    = $ns('rvrd_contpermobno');
    $chassisno       = $ns('rvrd_chassisno');
    $ivmsserialno    = $ns('rvrd_ivmsserialno');
    $ivmsvendorname  = $ns('rvrd_ivmsvendorname');
    $ivmsdevicemodel = $ns('rvrd_ivmsdevicemodel');
    $speedlimitno    = $ns('rvrd_speedlimitno');
    $vechiclefleetno = $ns('rvrd_vechiclefleetno');
    $firstropregdate = $ns('rvrd_firstropregdate');
    $dateofinsp      = $ns('rvrd_dateofinsp');
    $dateofexpiry    = $ns('rvrd_dateofexpiry');
    $applicationrefno= $ns('rvrd_applicationrefno');
    $verificationno  = $ns('rvrd_verificationno');

    // Required string
    $vechicleregno = trim($f['rvrd_vechicleregno']);

    // Datetime-local inputs → MySQL datetime
    $inspstarttime = $dt('rvrd_inspstarttime');
    $inspendtime   = $dt('rvrd_inspendtime');

    // Auto-filled
    $createdon        = date('Y-m-d H:i:s');
    $ipaddress        = $_SERVER['REMOTE_ADDR'] ?? '';
    $inspectionstatus = 1;
    $permitstatus     = 1;
    $isstickerprinted = 2;
    $iscardviewed     = 2;

    $sql = "INSERT INTO rasvehicleregdtls_tbl (
                rvrd_appinstinfomain_fk, rvrd_opalmemberregmst_fk, rvrd_rasvehicleownerdtls_fk,
                rvrd_ivmsvehicleregdtls_fk, rvrd_contpermailid, rvrd_contpermobno,
                rvrd_vechicleregno, rvrd_chassisno, rvrd_odometerreading,
                rvrd_ivmsserialno, rvrd_ivmsvendorname, rvrd_ivmsdevicemodel,
                rvrd_speedlimitno, rvrd_vechiclecat, rvrd_vechiclefleetno,
                rvrd_roadtype, rvrd_firstropregdate, rvrd_modelyear,
                rvrd_dateofinsp, rvrd_inspstarttime, rvrd_inspendtime,
                rvrd_inspectorname, rvrd_dateofexpiry, rvrd_applicationrefno,
                rvrd_verificationno, rvrd_applicationtype,
                rvrd_inspectionstatus, rvrd_permitstatus,
                rvrd_isstickerprinted, rvrd_iscardviewed,
                rvrd_createdon, rvrd_createdby, rvrd_ipaddress
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

    // Type string: 33 params
    // i  rvrd_appinstinfomain_fk        required int
    // i  rvrd_opalmemberregmst_fk       required int
    // i  rvrd_rasvehicleownerdtls_fk    required int
    // i  rvrd_ivmsvehicleregdtls_fk     nullable int
    // s  rvrd_contpermailid             nullable string
    // s  rvrd_contpermobno              nullable string
    // s  rvrd_vechicleregno             required string
    // s  rvrd_chassisno                 nullable string
    // i  rvrd_odometerreading           nullable int
    // s  rvrd_ivmsserialno              nullable string
    // s  rvrd_ivmsvendorname            nullable string
    // s  rvrd_ivmsdevicemodel           nullable string
    // s  rvrd_speedlimitno              nullable string
    // i  rvrd_vechiclecat               required int
    // s  rvrd_vechiclefleetno           nullable string
    // i  rvrd_roadtype                  nullable int
    // s  rvrd_firstropregdate           nullable date
    // i  rvrd_modelyear                 nullable year (stored as int)
    // s  rvrd_dateofinsp                nullable date
    // s  rvrd_inspstarttime             nullable datetime
    // s  rvrd_inspendtime               nullable datetime
    // i  rvrd_inspectorname             nullable int
    // s  rvrd_dateofexpiry              nullable date
    // s  rvrd_applicationrefno          nullable string
    // s  rvrd_verificationno            nullable string
    // i  rvrd_applicationtype           required int
    // i  rvrd_inspectionstatus          auto int
    // i  rvrd_permitstatus              auto int
    // i  rvrd_isstickerprinted          auto int
    // i  rvrd_iscardviewed              auto int
    // s  rvrd_createdon                 auto datetime
    // i  rvrd_createdby                 required int
    // s  rvrd_ipaddress                 auto string

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'iiisssssississiisissssisssiiiissis',
        $appinstinfomain_fk,
        $opalmemberregmst_fk,
        $rasvehicleownerdtls_fk,
        $ivmsvehicleregdtls_fk,
        $contpermailid,
        $contpermobno,
        $vechicleregno,
        $chassisno,
        $odometerreading,
        $ivmsserialno,
        $ivmsvendorname,
        $ivmsdevicemodel,
        $speedlimitno,
        $vechiclecat,
        $vechiclefleetno,
        $roadtype,
        $firstropregdate,
        $modelyear,
        $dateofinsp,
        $inspstarttime,
        $inspendtime,
        $inspectorname,
        $dateofexpiry,
        $applicationrefno,
        $verificationno,
        $applicationtype,
        $inspectionstatus,
        $permitstatus,
        $isstickerprinted,
        $iscardviewed,
        $createdon,
        $createdby,
        $ipaddress
    );

    if ($stmt->execute()) {
        $insertMsg = 'success';
    } else {
        $insertMsg = 'Error: ' . htmlspecialchars($stmt->error);
    }
    $stmt->close();
}

// ── LIST query ────────────────────────────────────────────────────────────────
$search  = isset($_GET['search']) ? trim($_GET['search']) : '';
$page    = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit   = 50;
$offset  = ($page - 1) * $limit;

$where = '';
$params = [];
$types  = '';
if ($search !== '') {
    $where   = "WHERE rvrd_vechicleregno LIKE ?";
    $likeVal = '%' . $search . '%';
    $params[] = &$likeVal;
    $types    = 's';
}

$countSql = "SELECT COUNT(*) FROM rasvehicleregdtls_tbl $where";
$stmt = $conn->prepare($countSql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->bind_result($total);
$stmt->fetch();
$stmt->close();

$totalPages = ceil($total / $limit);

$dataSql = "SELECT rasvehicleregdtls_pk, rvrd_appinstinfomain_fk, rvrd_opalmemberregmst_fk,
                   rvrd_rasvehicleownerdtls_fk, rvrd_ivmsvehicleregdtls_fk,
                   rvrd_contpermailid, rvrd_contpermobno, rvrd_vechicleregno,
                   rvrd_chassisno, rvrd_odometerreading, rvrd_ivmsserialno,
                   rvrd_ivmsvendorname, rvrd_ivmsdevicemodel, rvrd_speedlimitno,
                   rvrd_vechiclecat, rvrd_vechiclefleetno, rvrd_roadtype,
                   rvrd_firstropregdate, rvrd_modelyear, rvrd_dateofinsp,
                   rvrd_inspstarttime, rvrd_inspendtime, rvrd_inspectorname,
                   rvrd_dateofexpiry, rvrd_applicationrefno, rvrd_verificationno,
                   rvrd_applicationtype, rvrd_inspectionstatus, rvrd_permitstatus,
                   rvrd_isstickerprinted, rvrd_iscardviewed, rvrd_firstissuedate,
                   rvrd_lastissuedon, rvrd_printedon, rvrd_printedby,
                   rvrd_createdon, rvrd_createdby, rvrd_updatedon, rvrd_updatedby, rvrd_ipaddress
            FROM rasvehicleregdtls_tbl $where LIMIT ? OFFSET ?";

$stmt = $conn->prepare($dataSql);
if ($types) {
    $limitVal = $limit; $offsetVal = $offset;
    $stmt->bind_param($types . 'ii', ...[...$params, &$limitVal, &$offsetVal]);
} else {
    $stmt->bind_param('ii', $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$columns = [
    'rasvehicleregdtls_pk','rvrd_appinstinfomain_fk','rvrd_opalmemberregmst_fk',
    'rvrd_rasvehicleownerdtls_fk','rvrd_ivmsvehicleregdtls_fk','rvrd_contpermailid',
    'rvrd_contpermobno','rvrd_vechicleregno','rvrd_chassisno','rvrd_odometerreading',
    'rvrd_ivmsserialno','rvrd_ivmsvendorname','rvrd_ivmsdevicemodel','rvrd_speedlimitno',
    'rvrd_vechiclecat','rvrd_vechiclefleetno','rvrd_roadtype','rvrd_firstropregdate',
    'rvrd_modelyear','rvrd_dateofinsp','rvrd_inspstarttime','rvrd_inspendtime',
    'rvrd_inspectorname','rvrd_dateofexpiry','rvrd_applicationrefno','rvrd_verificationno',
    'rvrd_applicationtype','rvrd_inspectionstatus','rvrd_permitstatus','rvrd_isstickerprinted',
    'rvrd_iscardviewed','rvrd_firstissuedate','rvrd_lastissuedon','rvrd_printedon',
    'rvrd_printedby','rvrd_createdon','rvrd_createdby','rvrd_updatedon','rvrd_updatedby','rvrd_ipaddress'
];

function buildUrl($p, $s) {
    return '?' . http_build_query(['page' => $p, 'search' => $s]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Vehicle Registration Details</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; font-size: 13px; background: #f4f6f8; padding: 20px; }
  h2 { margin-bottom: 15px; color: #333; }
  .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px; }
  .toolbar-left { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
  .search-form input[type=text] { padding: 7px 10px; width: 280px; border: 1px solid #ccc; border-radius: 4px; }
  .search-form button { padding: 7px 14px; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; margin-left: 6px; }
  .search-form a { padding: 7px 12px; background: #888; color: #fff; border-radius: 4px; text-decoration: none; margin-left: 4px; }
  .btn-add { padding: 7px 16px; background: #28a745; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; }
  .btn-add:hover { background: #218838; }
  .info { color: #555; font-size: 12px; }
  .table-wrap { overflow-x: auto; background: #fff; border-radius: 6px; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
  table { border-collapse: collapse; width: 100%; min-width: 1200px; }
  th { background: #0066cc; color: #fff; padding: 9px 10px; text-align: left; white-space: nowrap; position: sticky; top: 0; }
  td { padding: 7px 10px; border-bottom: 1px solid #eee; white-space: nowrap; color: #333; }
  tr:hover td { background: #f0f7ff; }
  .pagination { margin-top: 14px; display: flex; gap: 4px; flex-wrap: wrap; align-items: center; }
  .pagination a, .pagination span { padding: 6px 11px; border-radius: 4px; text-decoration: none; font-size: 13px; border: 1px solid #ccc; color: #0066cc; background: #fff; }
  .pagination span.current { background: #0066cc; color: #fff; border-color: #0066cc; }
  .pagination a:hover { background: #e6f0ff; }
  .highlight { background: #fff3cd; font-weight: bold; }

  /* Modal */
  .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1000; overflow-y: auto; }
  .modal-overlay.active { display: flex; align-items: flex-start; justify-content: center; padding: 30px 15px; }
  .modal { background: #fff; border-radius: 8px; width: 100%; max-width: 820px; box-shadow: 0 4px 20px rgba(0,0,0,.2); }
  .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid #e0e0e0; background: #0066cc; border-radius: 8px 8px 0 0; }
  .modal-header h3 { color: #fff; font-size: 15px; }
  .modal-close { background: none; border: none; color: #fff; font-size: 22px; cursor: pointer; line-height: 1; }
  .modal-body { padding: 20px; }
  .autofill-notice { background: #e8f4fd; border: 1px solid #b8d9f5; border-radius: 4px; padding: 10px 14px; margin-bottom: 18px; font-size: 12px; color: #1a5276; }
  .autofill-notice strong { display: block; margin-bottom: 4px; }
  .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 20px; }
  .form-group { display: flex; flex-direction: column; gap: 4px; }
  .form-group.full { grid-column: 1 / -1; }
  .form-group label { font-size: 12px; color: #555; font-weight: bold; }
  .form-group label .req { color: #cc0000; }
  .form-group input, .form-group select { padding: 7px 9px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; }
  .form-group input:focus, .form-group select:focus { outline: none; border-color: #0066cc; box-shadow: 0 0 0 2px rgba(0,102,204,.15); }
  .form-group .hint { font-size: 11px; color: #888; }
  .modal-footer { padding: 14px 20px; border-top: 1px solid #e0e0e0; display: flex; justify-content: flex-end; gap: 10px; }
  .btn-submit { padding: 8px 20px; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; }
  .btn-submit:hover { background: #0052a3; }
  .btn-cancel { padding: 8px 16px; background: #fff; color: #555; border: 1px solid #ccc; border-radius: 4px; cursor: pointer; font-size: 13px; }
  .toast { position: fixed; top: 20px; right: 20px; padding: 12px 20px; border-radius: 6px; font-size: 13px; z-index: 2000; display: none; }
  .toast.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
  .toast.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
  .ts-control { font-size: 13px !important; min-height: 34px !important; padding: 2px 6px !important; }
  .ts-dropdown { font-size: 13px !important; }
</style>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
</head>
<body>

<?php if ($insertMsg === 'success'): ?>
<div class="toast success" id="toast">✔ Record added successfully!</div>
<script>
  var t = document.getElementById('toast');
  t.style.display = 'block';
  setTimeout(function(){ t.style.display = 'none'; }, 3500);
</script>
<?php elseif ($insertMsg !== ''): ?>
<div class="toast error" id="toast"><?= $insertMsg ?></div>
<script>
  var t = document.getElementById('toast');
  t.style.display = 'block';
  setTimeout(function(){ t.style.display = 'none'; }, 5000);
</script>
<?php endif; ?>

<h2>Vehicle Registration Details</h2>
<div class="toolbar">
  <div class="toolbar-left">
    <form class="search-form" method="GET">
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by Vehicle Reg No...">
      <button type="submit">Search</button>
      <?php if ($search): ?><a href="?">Clear</a><?php endif; ?>
    </form>
    <button class="btn-add" onclick="openModal()">+ Add New Item</button>
  </div>
  <span class="info">
    Total: <strong><?= number_format($total) ?></strong> records
    | Page <strong><?= $page ?></strong> of <strong><?= $totalPages ?: 1 ?></strong>
  </span>
</div>

<!-- ── Modal ──────────────────────────────────────────────────────────────── -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal">
    <div class="modal-header">
      <h3>Add New Vehicle Registration</h3>
      <button class="modal-close" onclick="closeModal()">×</button>
    </div>
    <form method="POST">
    <input type="hidden" name="_action" value="insert">
    <div class="modal-body">

      <div class="autofill-notice">
        <strong>⚡ Auto-filled on save:</strong>
        Created On (current timestamp) &nbsp;·&nbsp; IP Address (your IP) &nbsp;·&nbsp;
        Inspection Status → <em>Inspection Pending</em> &nbsp;·&nbsp;
        Permit Status → <em>New</em> &nbsp;·&nbsp;
        Sticker Printed → <em>No</em> &nbsp;·&nbsp;
        Card Viewed → <em>No</em>
      </div>

      <div class="form-grid">

        <!-- FK / Reference IDs -->
        <div class="form-group">
          <label>App Inst Info Main FK <span class="req">*</span></label>
          <input type="number" name="rvrd_appinstinfomain_fk" required min="1">
        </div>
        <div class="form-group">
          <label>OPAL Member Reg MST FK <span class="req">*</span></label>
          <input type="number" name="rvrd_opalmemberregmst_fk" required min="1">
        </div>
        <div class="form-group">
          <label>Vehicle Owner <span class="req">*</span></label>
          <select name="rvrd_rasvehicleownerdtls_fk" id="ts_owner" required><option value=""></option></select>
        </div>
        <div class="form-group">
          <label>IVMS Vehicle Reg</label>
          <select name="rvrd_ivmsvehicleregdtls_fk" id="ts_ivms"><option value=""></option></select>
        </div>

        <!-- Contact -->
        <div class="form-group">
          <label>Contact Person Email</label>
          <input type="email" name="rvrd_contpermailid" placeholder="example@mail.com">
        </div>
        <div class="form-group">
          <label>Contact Person Mobile</label>
          <input type="text" name="rvrd_contpermobno" placeholder="+968XXXXXXXX">
        </div>

        <!-- Vehicle Info -->
        <div class="form-group">
          <label>Vehicle Reg No <span class="req">*</span></label>
          <input type="text" name="rvrd_vechicleregno" required placeholder="e.g. AB1234">
        </div>
        <div class="form-group">
          <label>Chassis No</label>
          <input type="text" name="rvrd_chassisno">
        </div>
        <div class="form-group">
          <label>Odometer Reading (km)</label>
          <input type="number" name="rvrd_odometerreading" min="0">
        </div>
        <div class="form-group">
          <label>Vehicle Fleet No</label>
          <input type="text" name="rvrd_vechiclefleetno">
        </div>
        <div class="form-group">
          <label>Vehicle Category <span class="req">*</span></label>
          <select name="rvrd_vechiclecat" id="ts_cat" required><option value=""></option></select>
        </div>
        <div class="form-group">
          <label>Road Type</label>
          <select name="rvrd_roadtype" id="ts_road"><option value=""></option></select>
        </div>
        <div class="form-group">
          <label>Model Year</label>
          <input type="number" name="rvrd_modelyear" min="1900" max="<?= date('Y') + 1 ?>" placeholder="<?= date('Y') ?>">
        </div>
        <div class="form-group">
          <label>Speed Limit No</label>
          <input type="text" name="rvrd_speedlimitno">
        </div>

        <!-- IVMS -->
        <div class="form-group">
          <label>IVMS Serial No</label>
          <input type="text" name="rvrd_ivmsserialno">
        </div>
        <div class="form-group">
          <label>IVMS Vendor Name</label>
          <input type="text" name="rvrd_ivmsvendorname">
        </div>
        <div class="form-group">
          <label>IVMS Device Model</label>
          <input type="text" name="rvrd_ivmsdevicemodel">
        </div>

        <!-- Dates -->
        <div class="form-group">
          <label>First ROP Reg Date</label>
          <input type="date" name="rvrd_firstropregdate">
        </div>
        <div class="form-group">
          <label>Date of Inspection</label>
          <input type="date" name="rvrd_dateofinsp" id="dateofinsp">
        </div>
        <div class="form-group">
          <label>Inspection Start Time</label>
          <input type="datetime-local" name="rvrd_inspstarttime" id="inspstarttime">
        </div>
        <div class="form-group">
          <label>Inspection End Time</label>
          <input type="datetime-local" name="rvrd_inspendtime">
        </div>
        <div class="form-group">
          <label>Date of Expiry</label>
          <input type="date" name="rvrd_dateofexpiry">
        </div>

        <!-- Application -->
        <div class="form-group">
          <label>Application Ref No</label>
          <input type="text" name="rvrd_applicationrefno" placeholder="RASIC999/001">
        </div>
        <div class="form-group">
          <label>Verification No</label>
          <input type="text" name="rvrd_verificationno" maxlength="50">
        </div>
        <div class="form-group">
          <label>Application Type <span class="req">*</span></label>
          <select name="rvrd_applicationtype" required>
            <option value="1" selected>1 – Initial</option>
            <option value="2">2 – Renewal</option>
          </select>
        </div>

        <!-- Inspector -->
        <div class="form-group">
          <label>Inspector Name</label>
          <select name="rvrd_inspectorname" id="ts_inspector"><option value=""></option></select>
        </div>

        <!-- Created By -->
        <div class="form-group">
          <label>Created By <span class="req">*</span></label>
          <select name="rvrd_createdby" id="ts_createdby" required><option value=""></option></select>
        </div>

      </div><!-- /form-grid -->
    </div><!-- /modal-body -->
    <div class="modal-footer">
      <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
      <button type="submit" class="btn-submit">Save Record</button>
    </div>
    </form>
  </div>
</div>

<!-- ── Table ──────────────────────────────────────────────────────────────── -->
<div class="table-wrap">
<table>
  <thead>
    <tr><?php foreach ($columns as $col): ?><th><?= $col ?></th><?php endforeach; ?></tr>
  </thead>
  <tbody>
  <?php if (empty($rows)): ?>
    <tr><td colspan="<?= count($columns) ?>" style="text-align:center;padding:20px;color:#888;">No records found.</td></tr>
  <?php else: ?>
    <?php foreach ($rows as $row): ?>
    <tr>
      <?php foreach ($columns as $col): ?>
        <td>
          <?php
            $val = htmlspecialchars((string)($row[$col] ?? ''));
            if ($search !== '' && $col === 'rvrd_vechicleregno' && $val !== '') {
                echo preg_replace('/(' . preg_quote(htmlspecialchars($search), '/') . ')/i', '<span class="highlight">$1</span>', $val);
            } else {
                echo $val !== '' ? $val : '<span style="color:#bbb">—</span>';
            }
          ?>
        </td>
      <?php endforeach; ?>
    </tr>
    <?php endforeach; ?>
  <?php endif; ?>
  </tbody>
</table>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination">
  <?php if ($page > 1): ?>
    <a href="<?= buildUrl(1, $search) ?>">« First</a>
    <a href="<?= buildUrl($page - 1, $search) ?>">‹ Prev</a>
  <?php endif; ?>
  <?php
    $start = max(1, $page - 3);
    $end   = min($totalPages, $page + 3);
    for ($i = $start; $i <= $end; $i++):
  ?>
    <?php if ($i === $page): ?>
      <span class="current"><?= $i ?></span>
    <?php else: ?>
      <a href="<?= buildUrl($i, $search) ?>"><?= $i ?></a>
    <?php endif; ?>
  <?php endfor; ?>
  <?php if ($page < $totalPages): ?>
    <a href="<?= buildUrl($page + 1, $search) ?>">Next ›</a>
    <a href="<?= buildUrl($totalPages, $search) ?>">Last »</a>
  <?php endif; ?>
</div>
<?php endif; ?>

<script>
function openModal() {
  // Auto-fill date/time fields with current values
  var now = new Date();
  var pad = n => String(n).padStart(2, '0');
  var dateStr = now.getFullYear() + '-' + pad(now.getMonth()+1) + '-' + pad(now.getDate());
  var dtStr   = dateStr + 'T' + pad(now.getHours()) + ':' + pad(now.getMinutes());

  var di = document.getElementById('dateofinsp');
  var is = document.getElementById('inspstarttime');
  if (di && !di.value) di.value = dateStr;
  if (is && !is.value) is.value = dtStr;

  document.getElementById('modalOverlay').classList.add('active');
  document.body.style.overflow = 'hidden';
}
function closeModal() {
  document.getElementById('modalOverlay').classList.remove('active');
  document.body.style.overflow = '';
}
// Close on backdrop click
document.getElementById('modalOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});

// Searchable AJAX dropdowns
var tsSelects = [];
function makeTomAjax(id, type, placeholder) {
  var el = document.getElementById(id);
  if (!el) return;
  tsSelects.push(new TomSelect(el, {
    valueField: 'value',
    labelField: 'text',
    searchField: 'text',
    placeholder: placeholder,
    preload: true,
    load: function(query, callback) {
      fetch('fk_search_vehicle.php?type=' + type + '&q=' + encodeURIComponent(query))
        .then(r => r.json()).then(callback).catch(() => callback());
    },
    render: {
      no_results: function() { return '<div class="no-results">No results found</div>'; }
    }
  }));
}

function initTomSelects() {
  if (tsSelects.length) return;
  makeTomAjax('ts_owner',     'owner', '— Search Owner —');
  makeTomAjax('ts_ivms',      'ivms',  '— Search IVMS Vehicle —');
  makeTomAjax('ts_cat',       'cat',   '— Search Category —');
  makeTomAjax('ts_road',      'road',  '— Search Road Type —');
  makeTomAjax('ts_inspector', 'user',  '— Search Inspector —');
  makeTomAjax('ts_createdby', 'user',  '— Search User —');
}

var _openModal = openModal;
openModal = function() { _openModal(); initTomSelects(); };
</script>
</body>
</html>

<?php
header('Content-Type: application/json');

$host = '10.8.81.38';
$user = 'dbconnectusr';
$pass = 'db@Con$ter';
$db   = 'opal_learninghub_live';

if ($conn->connect_error) { echo json_encode([]); exit; }


// Whitelist of allowed types — prevents arbitrary query selection
$allowed = ['owner', 'ivms', 'user', 'road', 'cat'];
$type    = $_GET['type'] ?? '';
$q       = trim($_GET['q'] ?? '');

if (!in_array($type, $allowed, true)) {
    echo json_encode([]);
    exit;
}

try {
    $conn = new mysqli($host, $user, $pass, $db);

    $like  = '%' . $q . '%';

    $queries = [
        'owner' => [
            "SELECT rasvehicleownerdtls_pk AS value,
                    CONCAT(rvod_ownername_en, ' (', rvod_crnumber, ')') AS text
             FROM rasvehicleownerdtls_tbl
             WHERE rvod_status = 1 AND (rvod_ownername_en LIKE ? OR rvod_crnumber LIKE ?)
             ORDER BY rvod_ownername_en LIMIT 50",
            'ss', [&$like, &$like]
        ],
        'ivms'  => [
            "SELECT ivmsvehicleregdtls_pk AS value,
                    CONCAT(ivrd_vechicleregno, ' – ', ivrd_chassisno) AS text
             FROM ivmsvehicleregdtls_tbl
             WHERE ivrd_vechicleregno LIKE ? OR ivrd_chassisno LIKE ?
             ORDER BY ivrd_vechicleregno LIMIT 50",
            'ss', [&$like, &$like]
        ],
        'user'  => [
            "SELECT opalusermst_pk AS value,
                    CONCAT(oum_firstname, ' (', IFNULL(oum_loginId,''), ')') AS text
             FROM opalusermst_tbl
             WHERE oum_status = 'A' AND (oum_firstname LIKE ? OR oum_loginId LIKE ?)
             ORDER BY oum_firstname LIMIT 50",
            'ss', [&$like, &$like]
        ],
        'road'  => [
            "SELECT referencemst_pk AS value, rm_name_en AS text
             FROM referencemst_tbl
             WHERE rm_mastertype = 16 AND srm_status = 1 AND rm_name_en LIKE ?
             ORDER BY rm_name_en LIMIT 50",
            's', [&$like]
        ],
        'cat'   => [
            "SELECT rascategorymst_pk AS value,
                    CONCAT(rcm_coursesubcatname_en, ' (', rcm_vehiclecode, ')') AS text
             FROM rascategorymst_tbl
             WHERE rcm_status = 1 AND (rcm_coursesubcatname_en LIKE ? OR rcm_vehiclecode LIKE ?)
             ORDER BY rcm_coursesubcatname_en LIMIT 50",
            'ss', [&$like, &$like]
        ],
    ];

    [$sql, $types, $params] = $queries[$type];

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    $stmt->close();
    $conn->close();

} catch (RuntimeException $e) {
    echo json_encode([]);
}

<?php


include __DIR__ . '/dbconnect.php';

// ── GET: show upload form ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') { ?>
<!DOCTYPE html>
<html>
<head>
    <title>Bulk Learner Insert</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 60px auto; }
        label { display: block; margin-bottom: 8px; font-weight: bold; }
        input[type=file] { margin-bottom: 16px; }
        button { padding: 10px 24px; background: #0066cc; color: #fff; border: none; cursor: pointer; border-radius: 4px; }
        button:hover { background: #0052a3; }
        pre { background: #f4f4f4; padding: 16px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        .hint { color: #666; font-size: 13px; margin-top: 6px; }
    </style>
</head>
<body>
    <h2>Bulk Learner Insert </h2>
    <p>This tool inserts learners across multiple tables following the fetch relationships in i.php.</p>
    <form method="POST" enctype="multipart/form-data">
        <label>Select CSV File</label>
        <input type="file" name="csv_file" accept=".csv" required>
        <p class="hint">
            Required columns (first row = header):<br>
            <code>learner_name_en, learner_name_ar, learner_email, learner_idnumber, learner_dob, learner_gender, learner_nationality, batch_id, verification_no, card_expiry_date, learner_fee, opalmemberregmst_fk, projectmst_fk</code>
        </p>
        <p class="hint">
            Example CSV row:<br>
            <code>Ali Mohammad, علي محمد, ali@example.com, 12345678, 1990-01-15, 1, 2, 5, VER001, 2026-12-31, 150.00, 3, 2</code>
        </p>
        <button type="submit">Upload &amp; Insert</button>
    </form>

    <hr>
    <h3>Test via curl</h3>
    <pre>curl -X POST http://localhost:8000/insert_learner_bulk_reverse.php \
  -F "csv_file=@sample_learners.csv"</pre>
</body>
</html>
<?php
    exit;
}

// ── POST: process CSV ──────────────────────────────────────────────────────
header('Content-Type: application/json');

$response = [
    'status' => 'error',
    'message' => '',
    'data' => [
        'total_rows' => 0,
        'inserted'   => 0,
        'failed'     => 0,
        'errors'     => []
    ]
];

try {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error: code ' . ($_FILES['csv_file']['error'] ?? 'none'));
    }

    $file     = $_FILES['csv_file']['tmp_name'];
    $filename = $_FILES['csv_file']['name'];

    if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'csv') {
        throw new Exception('Invalid file type. Only CSV files allowed.');
    }

    $handle = fopen($file, 'r');
    if (!$handle) {
        throw new Exception('Cannot open uploaded file');
    }

    // Skip header row
    $header = fgetcsv($handle);
    if (!$header) {
        throw new Exception('Empty CSV file');
    }

    $inserted = 0;
    $failed   = 0;
    $errors   = [];
    $row_num  = 1;

    while (($row = fgetcsv($handle)) !== false) {
        $row_num++;
        try {
            if (count($row) < 13) {
                throw new Exception("Insufficient columns (got " . count($row) . ", need at least 13)");
            }

            // Parse CSV row
            $learner_name_en        = trim($row[0] ?? '');
            $learner_name_ar        = trim($row[1] ?? '');
            $learner_email          = trim($row[2] ?? '');
            $learner_idnumber       = trim($row[3] ?? '');
            $learner_dob            = trim($row[4] ?? '');
            $learner_gender         = (int)($row[5] ?? 0);
            $learner_nationality    = (int)($row[6] ?? 0);
            $batch_id               = (int)($row[7] ?? 0);
            $verification_no        = trim($row[8] ?? '');
            $card_expiry_raw        = trim($row[9] ?? '');
            $learner_fee            = (float)($row[10] ?? 0);
            $opalmemberregmst_fk    = !empty($row[11]) ? (int)$row[11] : null;
            $projectmst_fk          = (int)($row[12] ?? 0);

            // Validate required fields
            if (empty($learner_name_en))     throw new Exception("Missing learner_name_en");
            if (empty($learner_name_ar))     throw new Exception("Missing learner_name_ar");
            if (empty($learner_email))       throw new Exception("Missing learner_email");
            if (empty($learner_idnumber))    throw new Exception("Missing learner_idnumber");
            if (empty($learner_dob))         throw new Exception("Missing learner_dob");
            if ($learner_gender <= 0)        throw new Exception("Invalid learner_gender (must be 1 or 2)");
            if ($learner_nationality <= 0)   throw new Exception("Invalid learner_nationality (must be > 0)");
            if ($batch_id <= 0)              throw new Exception("Invalid batch_id (must be > 0)");
            if ($opalmemberregmst_fk <= 0)   throw new Exception("Invalid opalmemberregmst_fk (must be > 0)");
            if ($projectmst_fk <= 0)         throw new Exception("Invalid projectmst_fk (must be > 0)");

            // Format card expiry date
            $card_expiry_date = null;
            if (!empty($card_expiry_raw)) {
                $card_expiry_date = date('Y-m-d', strtotime($card_expiry_raw));
                if ($card_expiry_date === false) {
                    throw new Exception("Invalid card_expiry_date format: $card_expiry_raw");
                }
            }

            // Format DOB
            $dob = date('Y-m-d', strtotime($learner_dob));
            if ($dob === false) {
                throw new Exception("Invalid learner_dob format: $learner_dob");
            }

            // ────────────────────────────────────────────────────────────────
            // STEP 1: INSERT INTO staffinforepo_tbl (Learner basic info)
            // ────────────────────────────────────────────────────────────────
            $stmt_staff = $conn->prepare("
                INSERT INTO staffinforepo_tbl
                (sir_type, sir_idnumber, sir_name_en, sir_name_ar,
                 sir_emailid, sir_dob, sir_gender, sir_nationality, sir_createdby, sir_createdon)
                VALUES (2, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            if (!$stmt_staff) {
                throw new Exception("Prepare failed (staffinforepo_tbl): " . $conn->error);
            }

            $stmt_staff->bind_param(
                "sssssi",
                $learner_idnumber,
                $learner_name_en,
                $learner_name_ar,
                $learner_email,
                $dob,
                $learner_gender,
                $learner_nationality
            );

            if (!$stmt_staff->execute()) {
                throw new Exception("Insert into staffinforepo_tbl failed: " . $stmt_staff->error);
            }

            $staff_id = $conn->insert_id;
            $stmt_staff->close();

            // ────────────────────────────────────────────────────────────────
            // STEP 2: INSERT INTO learnerreghrddtls_tbl (Learner registration header)
            // ────────────────────────────────────────────────────────────────
            $stmt_lrhd = $conn->prepare("
                INSERT INTO learnerreghrddtls_tbl
                (lrhd_opalmemberregmst_fk, lrhd_batchmgmtdtls_fk, lrhd_staffinforepo_fk,
                 Irhd_emailid, Irhd_projectmst_fk, lrhd_learnerfee, lrhd_feestatus,
                 lrhd_isworking, lrhd_status, lrhd_createdby, lrhd_createdon)
                VALUES (?, ?, ?, ?, ?, ?, 1, 2, 1, 1, NOW())
            ");
            if (!$stmt_lrhd) {
                throw new Exception("Prepare failed (learnerreghrddtls_tbl): " . $conn->error);
            }

            $stmt_lrhd->bind_param(
                "iiiidi",
                $opalmemberregmst_fk,
                $batch_id,
                $staff_id,
                $learner_email,
                $projectmst_fk,
                $learner_fee
            );

            if (!$stmt_lrhd->execute()) {
                throw new Exception("Insert into learnerreghrddtls_tbl failed: " . $stmt_lrhd->error);
            }

            $learner_reg_id = $conn->insert_id;
            $stmt_lrhd->close();

            // ────────────────────────────────────────────────────────────────
            // STEP 3: INSERT INTO learnercarddtls_tbl (Learner card details)
            // ────────────────────────────────────────────────────────────────
            // First, get standardcoursedtls_fk from batchmgmtdtls_tbl
            $batch_query = $conn->prepare("
                SELECT bmd_standardcoursedtls_fk FROM batchmgmtdtls_tbl WHERE batchmgmtdtls_pk = ?
            ");
            if (!$batch_query) {
                throw new Exception("Failed to query batch: " . $conn->error);
            }

            $batch_query->bind_param("i", $batch_id);
            $batch_query->execute();
            $batch_result = $batch_query->get_result();

            if ($batch_result->num_rows === 0) {
                throw new Exception("Batch ID not found: $batch_id");
            }

            $batch_row = $batch_result->fetch_assoc();
            $standardcoursedtls_fk = $batch_row['bmd_standardcoursedtls_fk'];
            $batch_query->close();

            // Get course info
            $course_query = $conn->prepare("
                SELECT scd_standardcoursemst_fk, scd_subcoursecategorymst_fk
                FROM standardcoursedtls_tbl
                WHERE standardcoursedtls_pk = ?
            ");
            if (!$course_query) {
                throw new Exception("Failed to query course: " . $conn->error);
            }

            $course_query->bind_param("i", $standardcoursedtls_fk);
            $course_query->execute();
            $course_result = $course_query->get_result();

            if ($course_result->num_rows === 0) {
                throw new Exception("Course details not found for batch: $batch_id");
            }

            $course_row = $course_result->fetch_assoc();
            $standardcoursemst_fk = $course_row['scd_standardcoursemst_fk'];
            $coursecategorymst_fk = $course_row['scd_subcoursecategorymst_fk'];
            $course_query->close();

            // Get category names
            $cat_query = $conn->prepare("
                SELECT ccm_catname_en FROM coursecategorymst_tbl WHERE coursecategorymst_pk = ?
            ");
            if (!$cat_query) {
                throw new Exception("Failed to query category: " . $conn->error);
            }

            $cat_query->bind_param("i", $coursecategorymst_fk);
            $cat_query->execute();
            $cat_result = $cat_query->get_result();

            $category_name = '';
            if ($cat_result->num_rows > 0) {
                $cat_row = $cat_result->fetch_assoc();
                $category_name = $cat_row['ccm_catname_en'];
            }
            $cat_query->close();

            // Insert learner card details
            $stmt_lcd = $conn->prepare("
                INSERT INTO learnercarddtls_tbl
                (lcd_staffinforepo_fk, lcd_batchmgmtdtls_fk, lcd_learnerreghrddtls_fk,
                 lcd_standardcoursemst_fk, lcd_standardcoursedtls_fk, lcd_categoryname,
                 lcd_subcategoryname, lcd_isprinted, lcd_cardexpiry, lcd_verificationno,
                 lcd_status, lcd_createdby, lcd_createdon)
                VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, 1, 1, NOW())
            ");
            if (!$stmt_lcd) {
                throw new Exception("Prepare failed (learnercarddtls_tbl): " . $conn->error);
            }

            $stmt_lcd->bind_param(
                "iiiiissss",
                $staff_id,
                $batch_id,
                $learner_reg_id,
                $standardcoursemst_fk,
                $standardcoursedtls_fk,
                $category_name,
                $category_name,
                $card_expiry_date,
                $verification_no
            );

            if (!$stmt_lcd->execute()) {
                throw new Exception("Insert into learnercarddtls_tbl failed: " . $stmt_lcd->error);
            }

            $stmt_lcd->close();

            $inserted++;

        } catch (Exception $e) {
            $failed++;
            $errors[] = "Row $row_num: " . $e->getMessage();
        }
    }

    fclose($handle);

    $response['status']              = ($failed === 0) ? 'success' : 'partial';
    $response['message']             = "Insert complete. Inserted: $inserted, Failed: $failed";
    $response['data']['total_rows']  = $row_num - 1;
    $response['data']['inserted']    = $inserted;
    $response['data']['failed']      = $failed;
    $response['data']['errors']      = $errors;
    http_response_code(200);

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>

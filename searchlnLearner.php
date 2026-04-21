<?php
include '../wp-config.php';
include '../dbconnect.php';

$message = "";

// --- 1. HANDLE FORM SUBMISSION ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_learner'])) {
    
    $fullName    = mysqli_real_escape_string($conn, $_POST['full_name']);
    $civilId     = mysqli_real_escape_string($conn, $_POST['civil_id']);
    $gender      = intval($_POST['gender']);
    $nationality = intval($_POST['nationality_pk']);
    $batchPk     = intval($_POST['batch_pk']);
    $currentDate = date('Y-m-d');
    $photoPk     = "NULL"; 

    // --- PHOTO UPLOAD LOGIC ---
    if (isset($_FILES['learner_photo']) && $_FILES['learner_photo']['error'] == 0) {
        $fileExt = pathinfo($_FILES['learner_photo']['name'], PATHINFO_EXTENSION);
        $fileName = "LNR_" . time() . "." . $fileExt;
        $uploadDir = "../uploads/learner_photos/"; // Ensure this folder exists and is writable
        
        if (move_uploaded_file($_FILES['learner_photo']['tmp_name'], $uploadDir . $fileName)) {
            // Insert into your file details table to get the PK for the staff repo
            $fileSql = "INSERT INTO memcompfiledtls_tbl (mcfd_file_path, mcfd_uploadedon) 
                        VALUES ('$fileName', '$currentDate')";
            mysqli_query($conn, $fileSql);
            $photoPk = mysqli_insert_id($conn);
        }
    }

    mysqli_begin_transaction($conn);

    try {
        // A. Insert into staffinforepo_tbl (linking the photoPk)
        $sqlStaff = "INSERT INTO staffinforepo_tbl (
            sir_name_en, sir_idnumber, sir_gender, sir_nationality, sir_photo, sir_createdon
        ) VALUES (
            '$fullName', '$civilId', $gender, $nationality, $photoPk, '$currentDate'
        )";
        mysqli_query($conn, $sqlStaff);
        $staffPk = mysqli_insert_id($conn);

        // B. Insert Registration Header
        $sqlHdr = "INSERT INTO learnerreghrddtls_tbl (lrhd_staffinforepo_fk, lrhd_batchmgmtdtls_fk) 
                   VALUES ($staffPk, $batchPk)";
        mysqli_query($conn, $sqlHdr);
        $hdrPk = mysqli_insert_id($conn);

        // C. Insert Card Details
        $verifCode = "LNR-" . strtoupper(bin2hex(random_bytes(3)));
        $expiry = date('Y-m-d', strtotime('+2 years'));
        mysqli_query($conn, "INSERT INTO learnercarddtls_tbl (lcd_learnerreghrddtls_fk, lcd_verificationno, lcd_cardexpiry, lcd_status) 
                            VALUES ($hdrPk, '$verifCode', '$expiry', 1)");

        mysqli_commit($conn);
        $message = "<div style='color:green; padding:10px; border:1px solid green;'>Learner registered successfully with photo.</div>";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $message = "<div style='color:red; padding:10px; border:1px solid red;'>Error: " . $e->getMessage() . "</div>";
    }
}

// Fetch Dropdowns
$countries = mysqli_query($conn, "SELECT opalcountrymst_pk, ocym_countryname_en FROM opalcountrymst_tbl ORDER BY ocym_countryname_en ASC");
$batches   = mysqli_query($conn, "SELECT batchmgmtdtls_pk, bmd_Batchno FROM batchmgmtdtls_tbl WHERE bmd_status NOT IN (7,8)");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Learner Registration</title>
    <style>
        body { font-family: 'IBM Plex Sans', sans-serif; padding: 20px; background: #f4f4f4; }
        .box { background: white; max-width: 600px; margin: auto; padding: 25px; border-radius: 8px; border: 1px solid #ccc; }
        .field { margin-bottom: 15px; display: flex; flex-direction: column; }
        label { font-weight: 600; font-size: 14px; color: #555; }
        input, select { padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { background: #0c4b9a; color: white; border: none; padding: 12px; cursor: pointer; border-radius: 4px; font-weight: bold; width: 100%; margin-top: 10px; }
        .btn:hover { background: #083a75; }
    </style>
</head>
<body>

<div class="box">
    <h3 style="color: #0c4b9a; border-bottom: 1px solid #eee; padding-bottom: 10px;">Add New Learner</h3>
    <?php echo $message; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="field">
            <label>Full Name</label>
            <input type="text" name="full_name" required>
        </div>

        <div class="field">
            <label>Profile Photo</label>
            <input type="file" name="learner_photo" accept="image/png, image/jpeg">
            <small style="color: #888;">Format: JPG/PNG only.</small>
        </div>

        <div class="field">
            <label>Civil ID</label>
            <input type="text" name="civil_id" required>
        </div>

        <div class="field" style="flex-direction: row; gap: 20px;">
            <div style="flex: 1;">
                <label>Gender</label>
                <select name="gender" style="width: 100%;">
                    <option value="1">Male</option>
                    <option value="2">Female</option>
                </select>
            </div>
            <div style="flex: 1;">
                <label>Nationality</label>
                <select name="nationality_pk" style="width: 100%;">
                    <?php while($c = mysqli_fetch_assoc($countries)) echo "<option value='".$c['opalcountrymst_pk']."'>".$c['ocym_countryname_en']."</option>"; ?>
                </select>
            </div>
        </div>

        <div class="field">
            <label>Assign to Batch</label>
            <select name="batch_pk" required>
                <option value="">-- Select Batch --</option>
                <?php while($b = mysqli_fetch_assoc($batches)) echo "<option value='".$b['batchmgmtdtls_pk']."'>".$b['bmd_Batchno']."</option>"; ?>
            </select>
        </div>

        <button type="submit" name="add_learner" class="btn">Register Learner</button>
    </form>
</div>

</body>
</html>

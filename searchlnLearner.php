<?php
/* ===========================
   DATABASE CONNECTION
=========================== */
$pdo = new PDO(
    "mysql:host=10.8.81.38;dbname=opal_learninghub_live;charset=utf8mb4",
    "dbconnectusr",
    'db@Con$ter',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]
);

/* ===========================
   HANDLE FORM SUBMISSION
=========================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        $pdo->beginTransaction();

        /* ===========================
           1️⃣ INSERT INTO staffinforepo_tbl
        ============================ */

        $stmt = $pdo->prepare("
            INSERT INTO staffinforepo_tbl
            (sir_name_en, sir_emailid, sir_idnumber, sir_gender, sir_nationality)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_POST['name'],
            $_POST['email'],
            $_POST['civil_id'],
            $_POST['gender'],
            $_POST['nationality']
        ]);

        $staff_pk = $pdo->lastInsertId();


        /* ===========================
           2️⃣ INSERT INTO learnerreghrddtls_tbl
        ============================ */

        $stmt = $pdo->prepare("
            INSERT INTO learnerreghrddtls_tbl
            (lrhd_staffinforepo_fk, lrhd_batchmgmtdtls_fk)
            VALUES (?, ?)
        ");

        $stmt->execute([
            $staff_pk,
            $_POST['batch_id']
        ]);

        $learner_hdr_pk = $pdo->lastInsertId();


        /* ===========================
           3️⃣ INSERT INTO learnercarddtls_tbl
        ============================ */

        $verification_no = "VRF" . rand(100000,999999);

        $stmt = $pdo->prepare("
            INSERT INTO learnercarddtls_tbl
            (lcd_learnerreghrddtls_fk, lcd_verificationno, lcd_status, lcd_cardexpiry)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $learner_hdr_pk,
            $verification_no,
            $_POST['status'],
            $_POST['expiry_date']
        ]);

        $pdo->commit();

        echo "<div style='color:green;'>Learner Added Successfully!</div>";

    } catch (Exception $e) {

        $pdo->rollBack();
        echo "<div style='color:red;'>Error: " . $e->getMessage() . "</div>";
    }
}


/* ===========================
   FETCH BATCH LIST
=========================== */

$batches = $pdo->query("SELECT batchmgmtdtls_pk, bmd_Batchno FROM batchmgmtdtls_tbl WHERE bmd_status NOT IN (7,8)")
               ->fetchAll(PDO::FETCH_ASSOC);

/* ===========================
   FETCH NATIONALITIES
=========================== */

$countries = $pdo->query("SELECT opalcountrymst_pk, ocym_countryname_en FROM opalcountrymst_tbl")
                 ->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Learner</title>
</head>
<body>

<h2>Add New Learner</h2>

<form method="POST">

    <label>Full Name:</label><br>
    <input type="text" name="name" required><br><br>

    <label>Email:</label><br>
    <input type="email" name="email" required><br><br>

    <label>Civil ID:</label><br>
    <input type="text" name="civil_id" required><br><br>

    <label>Gender:</label><br>
    <select name="gender" required>
        <option value="1">Male</option>
        <option value="2">Female</option>
    </select><br><br>

    <label>Nationality:</label><br>
    <select name="nationality" required>
        <?php foreach ($countries as $country): ?>
            <option value="<?= $country['opalcountrymst_pk']; ?>">
                <?= htmlspecialchars($country['ocym_countryname_en']); ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Select Batch:</label><br>
    <select name="batch_id" required>
        <?php foreach ($batches as $batch): ?>
            <option value="<?= $batch['batchmgmtdtls_pk']; ?>">
                <?= htmlspecialchars($batch['bmd_Batchno']); ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Card Expiry Date:</label><br>
    <input type="date" name="expiry_date" required><br><br>

    <label>Status:</label><br>
    <select name="status" required>
        <option value="1">Active</option>
        <option value="2">Expired</option>
    </select><br><br>

    <button type="submit">Add Learner</button>

</form>

</body>
</html>

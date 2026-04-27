<?php
session_start();

require 'db.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

/* =======================
   NORMALIZE HEADER
======================= */
function normalize($value) {
    return strtolower(trim(str_replace([' ', '-'], '_', $value)));
}

/* =======================
   VALIDATE FILE
======================= */
if (!isset($_FILES['uploaded_file']) || $_FILES['uploaded_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['upload_error'] = "Upload error.";
    header("Location: dashboard.php");
    exit;
}

$fileTmpPath = $_FILES['uploaded_file']['tmp_name'];
$fileName = $_FILES['uploaded_file']['name'];
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

/* =======================
   ✅ GET MONTH FROM FILE NAME
   Example: March 2026.xls → 2026-03
======================= */
$fileBaseName = pathinfo($fileName, PATHINFO_FILENAME);
$timestamp = strtotime($fileBaseName);

if (!$timestamp) {
    $_SESSION['upload_error'] = "Invalid file name format. Use 'March 2026.xls'";
    header("Location: dashboard.php");
    exit;
}

$global_due_month = date('Y-m', $timestamp);

/* =======================
   READ FILE
======================= */
$rows = [];
$headers = [];
$startReading = false;

if ($ext === 'csv' || $ext === 'txt') {

    if (($handle = fopen($fileTmpPath, "r")) !== false) {

        while (($data = fgetcsv($handle)) !== false) {

            $normalizedRow = array_map('normalize', $data);

            if (!$startReading) {
                if (in_array('bpno', $normalizedRow) || in_array('last_name', $normalizedRow)) {
                    $headers = $normalizedRow;
                    $startReading = true;
                }
                continue;
            }

            if (empty(array_filter($data))) continue;

            $row = [];
            foreach ($headers as $i => $key) {
                $row[$key] = $data[$i] ?? null;
            }

            $rows[] = $row;
        }

        fclose($handle);
    }

} else {

    $spreadsheet = IOFactory::load($fileTmpPath);
    $sheetData = $spreadsheet->getActiveSheet()->toArray();

    foreach ($sheetData as $data) {

        $normalizedRow = array_map('normalize', $data);

        if (!$startReading) {
            if (in_array('bpno', $normalizedRow) || in_array('last_name', $normalizedRow)) {
                $headers = $normalizedRow;
                $startReading = true;
            }
            continue;
        }

        if (empty(array_filter($data))) continue;

        $row = [];
        foreach ($headers as $i => $key) {
            $row[$key] = $data[$i] ?? null;
        }

        $rows[] = $row;
    }
}

/* =======================
   VALIDATE DATA
======================= */
if (empty($rows)) {
    $_SESSION['upload_error'] = "No valid data found.";
    header("Location: dashboard.php");
    exit;
}

try {

    $conn->begin_transaction();

    $stmt = $conn->prepare("
        INSERT INTO employees
        (ebcs_transaction, bank_confirmation, paid_on, receipt_no, due_month, bpno, last_name, first_name, middle_initial, salary, ps, gs, ec)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($rows as $data) {

        /* =======================
           MAP FIELDS
        ======================= */
        $ebcs_transaction = (int)($data['office_code'] ?? 0);
        $bank_confirmation = 0;
        $paid_on = null;
        $receipt_no = '';

        // ✅ ALWAYS USE FILE NAME MONTH
        $due_month = $global_due_month;

        $bpno = $data['bpno'] ?? '';
        $last_name = $data['lastname'] ?? '';
        $first_name = $data['firstname'] ?? '';
        $middle_initial = $data['mi'] ?? '';

        $salary = (float)($data['basic_monthly_salary'] ?? 0);
        $ps = (float)($data['ps'] ?? 0);
        $gs = (float)($data['gs'] ?? 0);
        $ec = (float)($data['ec'] ?? 0);

        $stmt->bind_param(
            "iissssssssddd",
            $ebcs_transaction,
            $bank_confirmation,
            $paid_on,
            $receipt_no,
            $due_month,
            $bpno,
            $last_name,
            $first_name,
            $middle_initial,
            $salary,
            $ps,
            $gs,
            $ec
        );

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
    }

    $conn->commit();

    $_SESSION['upload_success'] = "Uploaded File Successfully! (Month: $global_due_month)";
    header("Location: dashboard.php");
    exit;

} catch (Exception $e) {

    $conn->rollback();

    $_SESSION['upload_error'] = "Error: " . $e->getMessage();
    header("Location: dashboard.php");
    exit;
}
?>
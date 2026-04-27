<?php
require 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$employee_id = intval($_POST['employee_id'] ?? 0);
if ($employee_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Employee ID missing']);
    exit;
}

// Get current employee record
$stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Employee not found']);
    exit;
}
$current = $res->fetch_assoc();
$stmt->close();

// Save old record into history
$hist = $conn->prepare("INSERT INTO employee_history 
    (employee_id, ebcs_transaction, bank_confirmation, paid_on, receipt_no, due_month, salary, ps, gs, ec, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

// Make sure numeric values are casted properly
$hist->bind_param(
    "iiisssdddd",
    $current['id'],
    $current['ebcs_transaction'],
    $current['bank_confirmation'],
    $current['paid_on'],
    $current['receipt_no'],
    $current['due_month'],
    $current['salary'],
    $current['ps'],
    $current['gs'],
    $current['ec']
);
$hist->execute();
$hist->close();

// New values from form
$ebcs_transaction  = intval($_POST['ebcs_transaction'] ?? 0);
$bank_confirmation = intval($_POST['bank_confirmation'] ?? 0);
$paid_on           = $_POST['paid_on'] ?? '';
$receipt_no        = intval($_POST['receipt_no'] ?? 0);
$due_month         = $_POST['due_month'] ?? '';
$salary            = floatval($_POST['basic_salary'] ?? 0);
$ps                = floatval($_POST['ps'] ?? 0);
$gs                = floatval($_POST['gs'] ?? 0);
$ec                = floatval($_POST['ec'] ?? 0); 

// Update employee table
$upd = $conn->prepare("UPDATE employees 
    SET ebcs_transaction=?, bank_confirmation=?, paid_on=?, receipt_no=?, due_month=?, salary=?, ps=?, gs=?, ec=?  
    WHERE id=?");

// Corrected parameter types: i i s i s d d d i
$upd->bind_param(
    "iisisddssi",
    $ebcs_transaction,
    $bank_confirmation,
    $paid_on,
    $receipt_no,
    $due_month,
    $salary,
    $ps,
    $gs,
    $ec,
    $employee_id
);

// Execute update
if ($upd->execute()) {
    echo json_encode(['success' => true, 'message' => 'Employee Updated Successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed To Update Employee']);
}

$upd->close();
$conn->close();
?>

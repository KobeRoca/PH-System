<?php
require 'db.php';

// Get JSON input
$data = json_decode(file_get_contents("php://input"));

if (!$data) {
    echo json_encode(["success" => false, "message" => "No input data received."]);
    exit;
}

// Required fields
$requiredFields = [
    'lastName', 'firstName', 'middleInitial', 'ebcsTransaction',
    'bankConfirmation', 'paidOn', 'receiptNo', 'dueMonth', 'bpno',
    'salary', 'ps', 'gs', 'ec'
];

// Check missing fields
foreach ($requiredFields as $field) {
    if (!isset($data->$field) || $data->$field === '') {
        echo json_encode(["success" => false, "message" => "Missing field: $field"]);
        exit;
    }
}

// Middle Initial must be a single letter
if (!preg_match('/^[A-Za-z]$/', $data->middleInitial)) {
    echo json_encode(["success" => false, "message" => "Middle Initial must be a single letter."]);
    exit;
}

// Numeric fields validation
$numericFields = ['ebcsTransaction', 'bankConfirmation', 'receiptNo', 'bpno', 'salary', 'ps', 'gs', 'ec'];
foreach ($numericFields as $field) {
    if (!is_numeric($data->$field)) {
        echo json_encode(["success" => false, "message" => "$field must be a number."]);
        exit;
    }
}

// Convert Middle Initial to uppercase
$data->middleInitial = strtoupper($data->middleInitial);

// Insert employee into database
$sql = "INSERT INTO employees (
    last_name, first_name, middle_initial, ebcs_transaction,
    bank_confirmation, paid_on, receipt_no, due_month, bpno,
    salary, ps, gs, ec
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["success" => false, "message" => "SQL prepare error: " . $conn->error]);
    exit;
}

$stmt->bind_param(
    "ssssssssssddd",
    $data->lastName,
    $data->firstName,
    $data->middleInitial,
    $data->ebcsTransaction,
    $data->bankConfirmation,
    $data->paidOn,
    $data->receiptNo,
    $data->dueMonth,
    $data->bpno,
    $data->salary,
    $data->ps,
    $data->gs,
    $data->ec
);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Employee Added Successfully!"]);
} else {
    echo json_encode(["success" => false, "message" => "Execute failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>

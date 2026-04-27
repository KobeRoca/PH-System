<?php
require 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$employee_id = intval($_POST['employee_id'] ?? 0);
if ($employee_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid employee id.']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
$stmt->bind_param("i", $employee_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Deleted Employee Successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed To Delete Employee']);
}

$stmt->close();
$conn->close();

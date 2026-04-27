<?php
require 'db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

// ✅ Get month (YYYY-MM)
$month = $data['month'] ?? null;

try {

    if (!$month) {
        echo json_encode([
            "success" => false,
            "message" => "Please select a month."
        ]);
        exit;
    }

    // ✅ CHECK using due_month
    $checkStmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM employees 
        WHERE due_month = ?
    ");
    $checkStmt->bind_param("s", $month);
    $checkStmt->execute();

    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['total'] == 0) {
        echo json_encode([
            "success" => false,
            "message" => "No Records Found For Selected Month & Year"
        ]);
        exit;
    }

    // ✅ DELETE using due_month
    $stmt = $conn->prepare("
        DELETE FROM employees 
        WHERE due_month = ?
    ");
    $stmt->bind_param("s", $month);

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Records Deleted Successfully!"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Delete failed."
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>
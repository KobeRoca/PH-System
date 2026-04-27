<?php
require 'db.php';
header('Content-Type: application/json');

try {

    // 1. Check if there are records first
    $check = $conn->query("SELECT COUNT(*) as total FROM employees");
    $row = $check->fetch_assoc();

    if ($row['total'] == 0) {
        echo json_encode([
            "success" => false,
            "message" => "No Displaying Records"
        ]);
        exit;
    }

    // 2. If there are records, delete them
    $sql = "DELETE FROM employees";

    if ($conn->query($sql) === TRUE) {
        echo json_encode([
            "success" => true,
            "message" => "All Records Deleted Successfully!"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Error deleting records"
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
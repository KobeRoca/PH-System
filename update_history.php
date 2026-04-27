<?php
require 'db.php';
header('Content-Type: application/json');

if(!isset($_POST['history_id'])){
    echo json_encode(['success'=>false,'message'=>'Missing record ID']);
    exit;
}

$id = intval($_POST['history_id']);
$ebcs = $_POST['ebcs_transaction'];
$bank = $_POST['bank_confirmation'];
$paid = $_POST['paid_on'];
$receipt = $_POST['receipt_no'];
$due = $_POST['due_month'];
$salary = $_POST['salary'];
$ps = $_POST['ps'];
$gs = $_POST['gs'];
$ec = $_POST['ec'];

$stmt = $conn->prepare("UPDATE employee_history SET ebcs_transaction=?, bank_confirmation=?, paid_on=?, receipt_no=?, due_month=?, salary=?, ps=?, gs=?, ec=? WHERE id=?");
$stmt->bind_param("sssssssssi", $ebcs, $bank, $paid, $receipt, $due, $salary, $ps, $gs, $ec, $id);

if($stmt->execute()){
    echo json_encode(['success'=>true]);
}else{
    echo json_encode(['success'=>false,'message'=>'Database update failed']);
}
$stmt->close();
$conn->close();
?>

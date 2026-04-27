<?php
require 'db.php';

$bpno = "2006106826";
$password = password_hash("Feb1,1996", PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (bpno, password) VALUES (?, ?)");
$stmt->bind_param("ss", $bpno, $password);

if ($stmt->execute()) {
    echo "Admin created!";
} else {
    echo "Error: " . $conn->error;
}
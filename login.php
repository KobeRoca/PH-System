<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $bpno = trim($_POST['bpno']);
    $password = trim($_POST['password']);

    // reset
    $_SESSION['error_bpno'] = false;
    $_SESSION['error_password'] = false;
    $_SESSION['login_message'] = "";

    // empty check
    if ($bpno === "" || $password === "") {
        $_SESSION['error_bpno'] = ($bpno === "");
        $_SESSION['error_password'] = ($password === "");
        $_SESSION['login_message'] = "Please fill all fields";
        header("Location: index.php");
        exit();
    }

    // check BP NO
    $stmt = $conn->prepare("SELECT * FROM users WHERE bpno = ?");
    $stmt->bind_param("s", $bpno);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error_bpno'] = true;
        $_SESSION['login_message'] = "Wrong BPNO";
        header("Location: index.php");
        exit();
    }

    $user = $result->fetch_assoc();

    // check password
    if (!password_verify($password, $user['password'])) {
        $_SESSION['error_password'] = true;
        $_SESSION['login_message'] = "Wrong Password";
        header("Location: index.php");
        exit();
    }

    // SUCCESS
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['bpno'] = $user['bpno'];
    $_SESSION['login_success'] = "Login Admin Successfully!";

    header("Location: dashboard.php");
    exit();
}
?>
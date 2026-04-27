<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login Page</title>

  <style>
    body {
      font-family: Arial, sans-serif;
      background: linear-gradient(135deg, #e0f7e9, #ffffff);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100vh;
      margin: 0;
      overflow: hidden;
    }

    .logo {
      margin-bottom: 30px;
      animation: zoomIn 1.2s ease forwards;
    }

    .logo img {
      width: 250px;
      height: auto;
      border-radius: 50%;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
      transform: scale(0.8);
    }

    .login-box {
      border: 1px solid #ddd;
      padding: 30px 25px;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
      border-radius: 12px;
      width: 320px;
      background-color: #fff;
      opacity: 0;
      transform: translateY(-50px);
      animation: slideFadeIn 1s ease forwards 0.5s;
    }

    .login-box h2 {
      margin-bottom: 20px;
      font-size: 20px;
      font-weight: bold;
      text-align: center;
      color: #333;
    }

    .login-box input {
      width: 100%;
      padding: 12px;
      margin-bottom: 10px;
      border-radius: 6px;
      border: 1px solid #bbb;
      box-sizing: border-box;
      font-size: 14px;
      transition: all 0.3s ease;
    }

    .login-box input:focus {
      border-color: #007b00;
      box-shadow: 0 0 6px rgba(0, 123, 0, 0.5);
      outline: none;
      transform: scale(1.02);
    }

    .login-box button {
      width: 100%;
      background-color: #007b00;
      color: white;
      padding: 12px;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .login-box button:hover {
      background-color: #006400;
      transform: scale(1.05);
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
    }

    .server-error {
      color: red;
      font-size: 13px;
      margin-bottom: 10px;
      text-align: center;
      font-weight: bold;
    }

    @keyframes shake {
      0% { transform: translateX(0); }
      25% { transform: translateX(-6px); }
      50% { transform: translateX(6px); }
      75% { transform: translateX(-6px); }
      100% { transform: translateX(0); }
    }

    .shake {
      animation: shake 0.4s ease;
      border: 2px solid red !important;
      background-color: #ffe6e6;
    }

    @keyframes slideFadeIn {
      from {
        opacity: 0;
        transform: translateY(-50px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes zoomIn {
      from {
        opacity: 0;
        transform: scale(0.5);
      }
      to {
        opacity: 1;
        transform: scale(1);
      }
    }
  </style>
</head>

<body>

<div class="logo">
  <img src="ph_logo.jpg" alt="PH Logo">
</div>

<div class="login-box">

  <h2>Login</h2>

  <form action="login.php" method="POST">

    <!-- BPNO -->
    <input type="text" id="bpno" name="bpno"
      placeholder="<?php
        echo isset($_SESSION['error_bpno']) && $_SESSION['error_bpno']
        ? 'Wrong BPNO'
        : 'Enter your BPNO';
      ?>"
      required>

    <!-- PASSWORD -->
    <input type="password" id="password" name="password"
      placeholder="<?php
        echo isset($_SESSION['error_password']) && $_SESSION['error_password']
        ? 'Wrong Password'
        : 'Enter your password';
      ?>"
      required>

    <button type="submit">Login</button>

  </form>
</div>

<?php if (isset($_SESSION['error_bpno']) || isset($_SESSION['error_password'])): ?>
<script>
window.addEventListener("load", function () {

  const bpno = document.getElementById("bpno");
  const password = document.getElementById("password");

  const bpnoError = <?= !empty($_SESSION['error_bpno']) ? 'true' : 'false'; ?>;
  const passError = <?= !empty($_SESSION['error_password']) ? 'true' : 'false'; ?>;

  bpno.classList.remove("shake");
  password.classList.remove("shake");

  bpno.style.border = "";
  password.style.border = "";

  if (bpnoError && passError) {
    bpno.classList.add("shake");
    password.classList.add("shake");

    bpno.style.border = "2px solid red";
    password.style.border = "2px solid red";

    bpno.focus();
  }

  else if (bpnoError) {
    bpno.classList.add("shake");
    bpno.style.border = "2px solid red";
    bpno.focus();
  }

  else if (passError) {
    password.classList.add("shake");
    password.style.border = "2px solid red";
    password.focus();
  }

});
</script>
<?php
unset($_SESSION['error_bpno']);
unset($_SESSION['error_password']);
endif;
?>

</body>
</html>
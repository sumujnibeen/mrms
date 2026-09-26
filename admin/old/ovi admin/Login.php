<?php
include 'config/db.php';
session_start();

// Already logged in হলে dashboard এ redirect
if(isset($_SESSION['id']) && $_SESSION['role'] == 'admin'){
    header("Location: index.php");
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if(empty($email) || empty($password)){
        $error = 'Email and password are required.';
    } else {
        $email    = mysqli_real_escape_string($conn, $email);
        $password = MD5($password);

        $query = mysqli_query($conn,"
            SELECT User_id, Name, Role
            FROM user
            WHERE Email='$email'
            AND Password='$password'
            AND Role='admin'
            LIMIT 1
        ");

        if(mysqli_num_rows($query) == 1){
            $user = mysqli_fetch_assoc($query);
            $_SESSION['id']   = $user['User_id'];
            $_SESSION['name'] = $user['Name'];
            $_SESSION['role'] = $user['Role'];
            header("Location: index.php");
            exit();
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login — Meghdut MRMS</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>

  <style>
    *, *::before, *::after {
      margin: 0; padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    :root {
      --blue:    #0d5ea8;
      --blue-lt: #dbe7ff;
      --bg:      #f4f1ea;
      --white:   #ffffff;
      --border:  #ddd;
      --text:    #222;
      --muted:   #777;
      --red:     #d9534f;
      --green:   #2e8b57;
    }

    body {
      background: var(--bg);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    /* subtle dot-grid background */
    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background-image: radial-gradient(circle, #c8bfb0 1px, transparent 1px);
      background-size: 28px 28px;
      opacity: .45;
      pointer-events: none;
      z-index: 0;
    }

    .card {
      position: relative;
      z-index: 1;
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: 24px;
      padding: 48px 44px;
      width: 100%;
      max-width: 440px;
      box-shadow: 0 8px 40px rgba(0,0,0,.08);
      animation: slideUp .45s cubic-bezier(.22,.68,0,1.2) both;
    }

    @keyframes slideUp {
      from { opacity:0; transform:translateY(28px); }
      to   { opacity:1; transform:translateY(0); }
    }

    /* LOGO */
    .logo {
      text-align: center;
      margin-bottom: 32px;
    }

    .logo img {
      width: 90px;
      height: 90px;
      object-fit: contain;
    }

    .logo h1 {
      font-size: 22px;
      font-weight: 700;
      color: var(--blue);
      margin-top: 8px;
    }

    .logo p {
      font-size: 13px;
      color: var(--muted);
      margin-top: 2px;
    }

    /* DIVIDER */
    .divider {
      border: none;
      border-top: 1px solid var(--border);
      margin-bottom: 28px;
    }

    /* FORM */
    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: var(--text);
      margin-bottom: 8px;
    }

    .input-wrap {
      position: relative;
    }

    .input-wrap i {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: #aaa;
      font-size: 15px;
      pointer-events: none;
      transition: color .2s;
    }

    .input-wrap input {
      width: 100%;
      padding: 14px 16px 14px 44px;
      border: 1px solid var(--border);
      border-radius: 12px;
      font-size: 15px;
      color: var(--text);
      background: #fafafa;
      outline: none;
      transition: border-color .2s, box-shadow .2s, background .2s;
    }

    .input-wrap input:focus {
      border-color: var(--blue);
      background: var(--white);
      box-shadow: 0 0 0 3px rgba(13,94,168,.1);
    }

    .input-wrap input:focus + i,
    .input-wrap:focus-within i {
      color: var(--blue);
    }

    /* toggle password */
    .toggle-pw {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      color: #aaa;
      font-size: 15px;
      padding: 4px;
      pointer-events: all;
      transition: color .2s;
    }
    .toggle-pw:hover { color: var(--blue); }

    /* ERROR */
    .error-box {
      background: #fff0f0;
      border: 1px solid #f5c6c6;
      border-radius: 10px;
      padding: 12px 16px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 13.5px;
      color: var(--red);
      margin-bottom: 20px;
      animation: shake .35s ease both;
    }

    @keyframes shake {
      0%,100% { transform: translateX(0); }
      20%      { transform: translateX(-6px); }
      40%      { transform: translateX(6px); }
      60%      { transform: translateX(-4px); }
      80%      { transform: translateX(4px); }
    }

    /* SUBMIT */
    .btn-login {
      width: 100%;
      padding: 15px;
      background: var(--blue);
      color: #fff;
      border: none;
      border-radius: 14px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: background .2s, transform .15s, box-shadow .2s;
      margin-top: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn-login:hover {
      background: #0a4d8c;
      box-shadow: 0 4px 18px rgba(13,94,168,.3);
    }

    .btn-login:active {
      transform: scale(.98);
    }

    /* FOOTER NOTE */
    .footer-note {
      text-align: center;
      font-size: 12px;
      color: var(--muted);
      margin-top: 24px;
    }

    @media(max-width:480px){
      .card { padding: 36px 24px; }
    }
  </style>
</head>
<body>

  <div class="card">

    <div class="logo">
      <img src="meghdut_photo-removebg-preview.png" alt="Meghdut Logo"/>
      <h1>Meghdut MRMS</h1>
      <p>Hotel Management System</p>
    </div>

    <hr class="divider"/>

    <?php if($error): ?>
      <div class="error-box">
        <i class="fa-solid fa-circle-exclamation"></i>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">

      <div class="form-group">
        <label for="email">Email Address</label>
        <div class="input-wrap">
          <input
            type="email"
            id="email"
            name="email"
            placeholder="admin@mrms.com"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            required
            autocomplete="email"
          />
          <i class="fa-regular fa-envelope"></i>
        </div>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-wrap">
          <input
            type="password"
            id="password"
            name="password"
            placeholder="••••••••"
            required
            autocomplete="current-password"
          />
          <i class="fa-solid fa-lock"></i>
          <button type="button" class="toggle-pw" onclick="togglePassword()" title="Show/hide password">
            <i class="fa-regular fa-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-login">
        <i class="fa-solid fa-right-to-bracket"></i>
        Sign In
      </button>

    </form>

    <p class="footer-note">
      <i class="fa-solid fa-shield-halved"></i>
      Admin access only &nbsp;·&nbsp; Meghdut Hotel &copy; <?= date('Y') ?>
    </p>

  </div>

  <script>
    function togglePassword() {
      const input   = document.getElementById('password');
      const icon    = document.getElementById('eyeIcon');
      const isHidden = input.type === 'password';
      input.type    = isHidden ? 'text' : 'password';
      icon.className = isHidden ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
    }
  </script>

</body>
</html>
<?php
include('db.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);
$error = "";

// Fetch current user's password hash
$stmt = $conn->prepare("SELECT Password FROM user WHERE User_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle deletion form
if (isset($_POST['confirm_delete'])) {
    $password = trim($_POST['password']);

    // Verify password
    if (!password_verify($password, $user['Password'])) {
        $error = "Incorrect password.";
    } else {
        // Delete reviews
        $stmt = $conn->prepare("DELETE FROM review WHERE User_id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // Delete bookings
        $stmt = $conn->prepare("DELETE FROM booking WHERE user_id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // Delete user account
        $stmt = $conn->prepare("DELETE FROM user WHERE User_id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // Destroy session and redirect
        session_unset();
        session_destroy();
        header("Location: index.php?deleted=1");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Delete Account | Vromon Sathi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background:#f8f9fa; }
.container { max-width:400px; margin:100px auto; padding:30px; background:white; border-radius:10px; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,0.1);}
button { margin-top:10px; }
.delete-btn { background:#b71c1c; color:white; border:none; padding:10px 20px; width:100%; border-radius:5px; font-weight:bold; }
.delete-btn:hover { background:#7f0000; }
</style>
</head>
<body>

<div class="container">
    <h3 class="text-danger mb-3">Delete My Account</h3>
    <p>Enter your password to permanently delete your account. This will remove all your data including reviews and bookings.</p>

    <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

    <form method="POST">
        <input type="password" name="password" class="form-control mb-2" placeholder="Enter your password" required>
        <button type="submit" name="confirm_delete" class="delete-btn">Delete My Account Permanently</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

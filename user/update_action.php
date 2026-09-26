<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../db.php';
include '../auth.php';

if ($_SESSION['role'] !== 'guest') {
    header("Location: ../index.php"); exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: update_profile.php"); exit();
}

$user_id = (int)$_SESSION['user_id'];
$name    = trim($_POST['full_name'] ?? '');
$current_password = $_POST['current_password'] ?? '';

if (empty($name)) {
    $_SESSION['update_error'] = "Name cannot be empty.";
    header("Location: update_profile.php"); exit();
}

$stmt = $conn->prepare("SELECT Password FROM user WHERE User_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($current_password, $user['Password'])) {
    $_SESSION['update_error'] = "Incorrect password.";
    header("Location: update_profile.php"); exit();
}

$upd = $conn->prepare("UPDATE user SET Name = ? WHERE User_id = ?");
$upd->bind_param("si", $name, $user_id);

if ($upd->execute()) {
    $_SESSION['name']           = $name;
    $_SESSION['update_success'] = "Profile updated successfully.";
    header("Location: dashboard.php"); exit();
} else {
    $_SESSION['update_error'] = "Update failed. Please try again.";
    header("Location: update_profile.php"); exit();
}
?>

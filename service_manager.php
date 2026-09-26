<?php
// service_manager.php
// Backend only — no UI

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db.php';
include 'auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$service_id = (int)$_POST['service_id'];
$status     = trim($_POST['status']);
$allowed    = ['Pending', 'Processing', 'Done'];

if (!in_array($status, $allowed)) {
    header("Location: index.php");
    exit();
}

// Only admin or receptionist can update
if (!in_array($_SESSION['role'], ['admin', 'receptionist'])) {
    header("Location: index.php");
    exit();
}

$stmt = $conn->prepare("
    UPDATE service_request SET status = ? WHERE service_id = ?
");
$stmt->bind_param("si", $status, $service_id);
$stmt->execute();
$stmt->close();

// Redirect back
$redirect = $_POST['redirect'] ?? 'admin/manage_services.php';
header("Location: $redirect");
exit();
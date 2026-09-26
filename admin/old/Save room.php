<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../db.php';
include '../auth.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$room_number = $_POST['room_num'];
$floor       = $_POST['floor'];
$type        = $_POST['room_type'];
$capacity    = $_POST['capacity'];
$price       = $_POST['price'];
$status      = $_POST['status'];
$description = $_POST['description'];

$sql = "INSERT INTO room (room_number, floor, type, capacity, price_per_night, status, description)
        VALUES ('$room_number', '$floor', '$type', '$capacity', '$price', '$status', '$description')";

if (mysqli_query($conn, $sql)) {
    header("Location: rooms.php");
    exit;
} else {
    echo "Error: " . mysqli_error($conn);
}
?>
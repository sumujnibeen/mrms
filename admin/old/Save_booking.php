<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../db.php';
include '../auth.php';

if (!in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: ../index.php");
    exit();
}

$guest_name = $_POST['guest_name'];
$room_id    = $_POST['room_id'];
$check_in   = $_POST['check_in'];
$check_out  = $_POST['check_out'];

// Guest আছে কিনা check করো
$check = mysqli_query($conn, "SELECT User_id FROM user WHERE Name='$guest_name' AND Role='guest'");

if (mysqli_num_rows($check) > 0) {
    // আগে থেকে আছে
    $guest = mysqli_fetch_assoc($check);
    $guest_id = $guest['User_id'];
} else {
    // নতুন guest insert করো
    mysqli_query($conn, "INSERT INTO user (Name, Role) VALUES ('$guest_name', 'guest')");
    $guest_id = mysqli_insert_id($conn);
}

// Booking insert করো
$sql = "INSERT INTO booking (guest_id, room_id, check_in, check_out, status)
        VALUES ('$guest_id', '$room_id', '$check_in', '$check_out', 'Confirmed')";

if (mysqli_query($conn, $sql)) {
    header("Location: index.php?success=1");
} else {
    header("Location: index.php?error=1");
}
exit();
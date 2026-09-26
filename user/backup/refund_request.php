<?php
// refund_request.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db.php';
include '../auth.php';

if ($_SESSION['role'] !== 'guest') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: my_stay.php?tab=bookings");
    exit();
}

$guest_id       = $_SESSION['user_id'];
$booking_id     = (int)$_POST['booking_id'];
$refund_percent = (int)$_POST['refund_percent'];
$refund_percent = max(0, min(80, $refund_percent));

// Verify booking belongs to guest, is cancellable, has a payment, and no prior refund request
$stmt = $conn->prepare("
    SELECT booking_id, check_in, pay_amount, due_amount, refund_status, status
    FROM booking
    WHERE booking_id = ? AND guest_id = ?
    AND status IN ('Pending', 'Confirmed')
    AND pay_amount > 0
    AND refund_status = 'none'
");
$stmt->bind_param("ii", $booking_id, $guest_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['booking_msg_error'] = "Refund request is not applicable for this booking.";
    header("Location: my_stay.php?tab=bookings");
    exit();
}

$booking = $result->fetch_assoc();
$stmt->close();

// paid_so_far = grand total minus remaining due
$grand_total  = (float)$booking['pay_amount'];
$paid_so_far  = $grand_total - (float)$booking['due_amount'];

if ($paid_so_far <= 0) {
    $_SESSION['booking_msg_error'] = "No payment has been made for this booking yet.";
    header("Location: my_stay.php?tab=bookings");
    exit();
}

// Re-validate refund percent server-side based on current time
$now        = time();
$check_in   = strtotime($booking['check_in']);
$hours_left = ($check_in - $now) / 3600;

$server_percent = 0;
if      ($hours_left >= 72) $server_percent = 80;
elseif  ($hours_left >= 48) $server_percent = 50;
elseif  ($hours_left >= 36) $server_percent = 25;
elseif  ($hours_left >= 24) $server_percent = 20;
else                        $server_percent = 0;

if ($server_percent === 0) {
    $_SESSION['booking_msg_error'] = "No refund is applicable at this time (less than 24 hours before check-in).";
    header("Location: my_stay.php?tab=bookings");
    exit();
}

$update = $conn->prepare("
    UPDATE booking
    SET refund_status = 'requested', refund_percent = ?
    WHERE booking_id = ?
");
$update->bind_param("ii", $server_percent, $booking_id);
$update->execute();
$update->close();

// Refund is calculated on what the guest actually paid, not the grand total
$refund_amt = round($paid_so_far * $server_percent / 100);
$_SESSION['booking_msg_success'] = "Refund request submitted! You will receive ৳{$refund_amt} ({$server_percent}% of ৳" . number_format($paid_so_far) . " paid). Our team will process it within 2-3 business days.";

header("Location: my_stay.php?tab=bookings");
exit();
?>

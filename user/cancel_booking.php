<?php
// cancel_booking.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db.php';
include '../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: my_stay.php?tab=bookings");
    exit();
}

$booking_id = (int)$_POST['booking_id'];
$guest_id   = $_SESSION['user_id'];

// Fetch booking — need pay_amount and due_amount for refund calculation
$stmt = $conn->prepare("
    SELECT booking_id, room_id, status, pay_amount, due_amount, refund_status, check_in
    FROM booking
    WHERE booking_id = ? AND guest_id = ?
    AND status IN ('Pending', 'Confirmed')
");
$stmt->bind_param("ii", $booking_id, $guest_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: my_stay.php?tab=bookings");
    exit();
}

$booking     = $result->fetch_assoc();
$room_id     = $booking['room_id'];
$grand_total = (float)$booking['pay_amount'];
$paid_so_far = $grand_total - (float)$booking['due_amount'];
$stmt->close();

// Cancel the booking
$update = $conn->prepare("UPDATE booking SET status = 'Cancelled' WHERE booking_id = ?");
$update->bind_param("i", $booking_id);
$update->execute();
$update->close();

// Set room back to Available
$room_update = $conn->prepare("UPDATE room SET status = 'Available' WHERE room_id = ?");
$room_update->bind_param("i", $room_id);
$room_update->execute();
$room_update->close();

// ── Auto-apply refund if guest has paid anything ──────────
if ($paid_so_far > 0 && $booking['refund_status'] === 'none') {
    $now        = time();
    $check_in   = strtotime($booking['check_in']);
    $hours_left = ($check_in - $now) / 3600;

    $refund_percent = 0;
    if      ($hours_left >= 72) $refund_percent = 80;
    elseif  ($hours_left >= 48) $refund_percent = 50;
    elseif  ($hours_left >= 36) $refund_percent = 25;
    elseif  ($hours_left >= 24) $refund_percent = 20;
    else                        $refund_percent = 0;

    if ($refund_percent > 0) {
        $refund_update = $conn->prepare("
            UPDATE booking SET refund_status = 'requested', refund_percent = ?
            WHERE booking_id = ?
        ");
        $refund_update->bind_param("ii", $refund_percent, $booking_id);
        $refund_update->execute();
        $refund_update->close();

        $refund_amt = round($paid_so_far * $refund_percent / 100);
        $_SESSION['booking_msg_success'] = "Booking #$booking_id has been cancelled. A refund of ৳{$refund_amt} ({$refund_percent}% of ৳" . number_format($paid_so_far) . " paid) has been automatically requested. The refund will be returned via SSLCommerz to your original payment method within 2-3 business days.";
    } else {
        $_SESSION['booking_msg_error'] = "Booking #$booking_id has been cancelled. No refund is applicable as the check-in date is less than 24 hours away.";
    }
} else {
    $_SESSION['booking_msg_success'] = "Booking #$booking_id has been cancelled.";
}

header("Location: my_stay.php?tab=bookings");
exit();
?>

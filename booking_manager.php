<?php
// booking_manager.php
// Handles booking form submission, validates dates, prevents double booking

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db.php';
include 'auth.php';

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: rooms.php");
    exit();
}

$guest_id       = $_SESSION['user_id'];
$room_id        = (int)$_POST['room_id'];
$price_per_night = (float)$_POST['price_per_night'];
$check_in       = trim($_POST['check_in']);
$check_out      = trim($_POST['check_out']);

$today    = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// VALIDATE DATES
if (empty($check_in) || empty($check_out)) {
    $_SESSION['booking_error'] = "Both check-in and check-out dates are required.";
    header("Location: room_details.php?id=$room_id");
    exit();
}

if ($check_in < $tomorrow) {
    $_SESSION['booking_error'] = "Check-in date must be at least tomorrow.";
    header("Location: room_details.php?id=$room_id");
    exit();
}

if ($check_out <= $check_in) {
    $_SESSION['booking_error'] = "Check-out date must be after check-in date.";
    header("Location: room_details.php?id=$room_id");
    exit();
}

// CHECK ROOM EXISTS & AVAILABLE
$room_stmt = $conn->prepare("SELECT * FROM room WHERE room_id = ? AND status = 'Available'");
$room_stmt->bind_param("i", $room_id);
$room_stmt->execute();
$room_result = $room_stmt->get_result();

if ($room_result->num_rows === 0) {
    $_SESSION['booking_error'] = "This room is not available for booking.";
    header("Location: room_details.php?id=$room_id");
    exit();
}
$room = $room_result->fetch_assoc();
$room_stmt->close();

// CHECK DOUBLE BOOKING
// Conflict: অন্য কোনো active booking আছে যার advance দেওয়া হয়েছে
// অথবা এখন Checked-In আছে এবং date overlap করে
$conflict_stmt = $conn->prepare("
    SELECT booking_id FROM booking
    WHERE room_id = ?
    AND status IN ('Confirmed', 'Checked-In')
    AND check_in  < ?
    AND check_out > ?
    UNION
    SELECT booking_id FROM booking
    WHERE room_id = ?
    AND payment_status IN ('partial', 'paid')
    AND status IN ('Pending', 'Confirmed')
    AND check_in  < ?
    AND check_out > ?
");
$conflict_stmt->bind_param("ississ", $room_id, $check_out, $check_in, $room_id, $check_out, $check_in);
$conflict_stmt->execute();
$conflict_stmt->store_result();

if ($conflict_stmt->num_rows > 0) {
    $_SESSION['booking_error'] = "This room is already booked for the selected dates. Please choose different dates.";
    header("Location: room_details.php?id=$room_id");
    exit();
}
$conflict_stmt->close();

// CALCULATE TOTAL
$nights    = (int)((strtotime($check_out) - strtotime($check_in)) / 86400);
$pay_amount = $price_per_night * $nights;
$due_amount = $pay_amount; // full due until payment made

// INSERT BOOKING
$insert = $conn->prepare("
    INSERT INTO booking (guest_id, room_id, check_in, check_out, status, pay_amount, due_amount, payment_status)
    VALUES (?, ?, ?, ?, 'Pending', ?, ?, 'unpaid')
");
$insert->bind_param("iissdd", $guest_id, $room_id, $check_in, $check_out, $pay_amount, $due_amount);

if ($insert->execute()) {
    $booking_id = $conn->insert_id;
    $insert->close();

    // NOTE: room status এখানে Booked করা হচ্ছে না।
    // Payment success (sslcommerz/success.php) হলে তখন room Booked হবে।
    // এতে live_status query সঠিকভাবে কাজ করবে।

    // Store in session for payment page
    $_SESSION['booking_id']    = $booking_id;
    $_SESSION['booking_room']  = $room['room_number'];
    $_SESSION['booking_type']  = $room['type'];
    $_SESSION['check_in']      = $check_in;
    $_SESSION['check_out']     = $check_out;
    $_SESSION['nights']        = $nights;
    $_SESSION['pay_amount']    = $pay_amount;

    header("Location: user/payment.php?booking_id=$booking_id");
    exit();
} else {
    $_SESSION['booking_error'] = "Booking failed. Please try again.";
    header("Location: room_details.php?id=$room_id");
    exit();
}
?>

<?php
// sslcommerz/success.php

include '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit();
}

$val_id       = urlencode($_POST['val_id']);
$store_id     = urlencode("vromo69117208552f5");
$store_passwd = urlencode("vromo69117208552f5@ssl");

$validate_url = "https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php?val_id={$val_id}&store_id={$store_id}&store_passwd={$store_passwd}&v=1&format=json";

$handle = curl_init();
curl_setopt($handle, CURLOPT_URL, $validate_url);
curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

$result = curl_exec($handle);
$code   = curl_getinfo($handle, CURLINFO_HTTP_CODE);
curl_close($handle);

if ($code !== 200) {
    die("Failed to connect with SSLCommerz.");
}

$result     = json_decode($result);
$status     = $result->status;
$tran_id    = $result->tran_id;
$amount     = (float)$result->amount;
$booking_id = (int)$result->value_a;

if ($status === 'VALID' || $status === 'VALIDATED') {

    // Fetch current due_amount before updating
    $bk = $conn->prepare("SELECT due_amount FROM booking WHERE booking_id = ?");
    $bk->bind_param("i", $booking_id);
    $bk->execute();
    $booking = $bk->get_result()->fetch_assoc();
    $bk->close();

    $new_due            = max(0, (float)$booking['due_amount'] - $amount);
    $new_payment_status = $new_due <= 0 ? 'paid' : 'partial';

    // CRITICAL FIX: pay_amount = grand total, never update it.
    // Only decrement due_amount by the amount actually received.
    $stmt = $conn->prepare("
        UPDATE booking
        SET transaction_id = ?,
            due_amount     = ?,
            payment_status = ?,
            status         = 'Confirmed'
        WHERE booking_id = ?
    ");
    $stmt->bind_param("sdsi", $tran_id, $new_due, $new_payment_status, $booking_id);
    $stmt->execute();
    $stmt->close();

    // Mark room as Booked
    $room_stmt = $conn->prepare("
        UPDATE room SET status = 'Booked'
        WHERE room_id = (SELECT room_id FROM booking WHERE booking_id = ?)
    ");
    $room_stmt->bind_param("i", $booking_id);
    $room_stmt->execute();
    $room_stmt->close();

    // Record payment
    $now = date('Y-m-d H:i:s');
    $pay = $conn->prepare("
        INSERT INTO payment (booking_id, amount, method, status, transaction_id, paid_at)
        VALUES (?, ?, 'SSLCommerz', 'paid', ?, ?)
    ");
    $pay->bind_param("idss", $booking_id, $amount, $tran_id, $now);
    $pay->execute();
    $pay->close();

    header("Location: ../user/payment_success.php?booking_id=$booking_id");
    exit();

} else {
    header("Location: ../user/payment_fail.php?booking_id=$booking_id");
    exit();
}
?>

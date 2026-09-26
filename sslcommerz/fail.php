<?php
// sslcommerz/fail.php
$booking_id = (int)($_POST['value_a'] ?? 0);
if ($booking_id) {
    header("Location: ../user/payment_fail.php?booking_id=$booking_id");
} else {
    header("Location: ../rooms.php");
}
exit();
?>

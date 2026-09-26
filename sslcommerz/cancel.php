<?php
// sslcommerz/cancel.php
$booking_id = (int)($_POST['value_a'] ?? 0);
if ($booking_id) {
    header("Location: ../user/payment.php?booking_id=$booking_id");
} else {
    header("Location: ../rooms.php");
}
exit();
?>

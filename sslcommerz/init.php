<?php
// sslcommerz/init.php
// Initializes SSLCommerz payment session and redirects to gateway

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db.php';
include '../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../rooms.php");
    exit();
}

$booking_id  = (int)$_POST['booking_id'];
$amount      = (float)$_POST['amount'];
$cus_name    = trim($_POST['cus_name']);
$cus_email   = trim($_POST['cus_email']);
$cus_phone   = trim($_POST['cus_phone']);
$cus_add     = trim($_POST['cus_add']);
$room_number = trim($_POST['room_number']);
$room_type   = trim($_POST['room_type']);

$store_id     = "vromo69117208552f5";
$store_passwd = "vromo69117208552f5@ssl";
$base_url     = "http://localhost/mrms"; // Change for production

$post_data = [
    'store_id'            => $store_id,
    'store_passwd'        => $store_passwd,
    'total_amount'        => $amount,
    'currency'            => 'BDT',
    'tran_id'             => 'MRMS_' . $booking_id . '_' . time(),
    'success_url'         => $base_url . '/sslcommerz/success.php',
    'fail_url'            => $base_url . '/sslcommerz/fail.php',
    'cancel_url'          => $base_url . '/sslcommerz/cancel.php',
    'ipn_url'             => $base_url . '/sslcommerz/ipn.php',
    // Customer info
    'cus_name'            => $cus_name,
    'cus_email'           => $cus_email,
    'cus_add1'            => $cus_add,
    'cus_add2'            => '',
    'cus_city'            => 'Kishoreganj',
    'cus_state'           => 'Dhaka',
    'cus_postcode'        => '2300',
    'cus_country'         => 'Bangladesh',
    'cus_phone'           => $cus_phone,
    'cus_fax'             => '',
    // Shipping info (same as customer for hotel)
    'ship_name'           => $cus_name,
    'ship_add1'           => 'Meghdoot Resort, Kishoreganj',
    'ship_add2'           => '',
    'ship_city'           => 'Kishoreganj',
    'ship_state'          => 'Dhaka',
    'ship_postcode'       => '2300',
    'ship_country'        => 'Bangladesh',
    // Product info
    'product_name'        => 'Room Booking — ' . $room_type . ' Room ' . $room_number,
    'product_category'    => 'Hotel',
    'product_profile'     => 'non-physical-goods',
    // Extra values (passed back on success)
    'value_a'             => $booking_id,
    'value_b'             => $_SESSION['user_id'],
    'value_c'             => '',
    'value_d'             => '',
];

$handle = curl_init();
curl_setopt($handle, CURLOPT_URL, 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php');
curl_setopt($handle, CURLOPT_TIMEOUT, 30);
curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($handle, CURLOPT_POST, 1);
curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($handle);
$err      = curl_error($handle);
curl_close($handle);

if ($err) {
    die("cURL Error: " . $err);
}

$result = json_decode($response, true);

if (isset($result['GatewayPageURL']) && $result['GatewayPageURL'] !== '') {
    header("Location: " . $result['GatewayPageURL']);
    exit();
} else {
    $_SESSION['payment_error'] = "Failed to connect to SSLCommerz. Please try again.";
    header("Location: ../payment.php?booking_id=$booking_id");
    exit();
}
?>

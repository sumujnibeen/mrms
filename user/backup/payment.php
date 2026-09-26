<?php
// payment.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db.php';
include '../auth.php';

$booking_id = (int)($_GET['booking_id'] ?? 0);

if ($booking_id === 0) {
    header("Location: ../rooms.php");
    exit();
}

// Fetch booking with room info
$stmt = $conn->prepare("
    SELECT b.*, r.room_number, r.type, r.price_per_night, r.image
    FROM booking b
    JOIN room r ON b.room_id = r.room_id
    WHERE b.booking_id = ? AND b.guest_id = ?
");
$stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: my_stay.php?tab=bookings");
    exit();
}

$booking = $result->fetch_assoc();
$stmt->close();

$nights = (int)((strtotime($booking['check_out']) - strtotime($booking['check_in'])) / 86400);

// pay_amount stores the grand total (set at booking, never changed).
// due_amount starts equal to pay_amount and is decremented by each payment.
// So: grand total = pay_amount, already paid = pay_amount - due_amount.
$total        = (float)$booking['pay_amount'];
$already_paid = $total - (float)$booking['due_amount'];

// ── Determine payment mode ────────────────────────────────
$is_due_payment = isset($_GET['pay_due']) && $booking['payment_status'] === 'partial';

if ($is_due_payment) {
    $amount_to_pay   = (float)$booking['due_amount'];
    $page_title      = 'Pay Remaining Balance';
    $page_subtitle   = 'Complete your payment before check-in';
    $amount_label    = 'Due Amount';
    $amount_sublabel = 'Remaining balance';
    $info_message    = 'You are paying the <strong>remaining balance</strong> of your booking. After this your booking is fully paid.';
} else {
    // First payment: 30% of the actual grand total
    $amount_to_pay   = round($total * 0.3, 2);
    $page_title      = 'Complete Your Booking';
    $page_subtitle   = 'Review your booking and proceed to payment';
    $amount_label    = 'Advance (30%)';
    $amount_sublabel = '30% Advance';
    $info_message    = 'You will pay <strong>30% advance (৳' . number_format($amount_to_pay) . ')</strong> now via SSLCommerz. The remaining <strong>৳' . number_format($total - $amount_to_pay) . '</strong> will be collected at check-out.';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> — Meghdoot Resort</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/css/bootstrap.min.css"
        onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'">
    <?php include '../global_css.php'; ?>
</head>

<body class="bg-light">

    <?php include '../navbar.php'; ?>

    <!-- PAGE HEADER -->
    <div class="bg-primary text-white py-5 text-center">
        <div class="container">
            <h1 class="fw-bold"><?php echo $page_title; ?></h1>
            <p class="lead fw-light mb-0"><?php echo $page_subtitle; ?></p>
        </div>
    </div>

    <!-- PAYMENT SECTION -->
    <section class="py-5">
        <div class="container">
            <div class="row g-5 justify-content-center">

                <!-- LEFT: Booking Summary -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h5 class="fw-bold mb-4">Booking Summary</h5>

                        <img src="<?php echo htmlspecialchars($booking['image']); ?>"
                            class="img-fluid rounded-3 img-cover mb-4" style="height: 180px; width: 100%;" alt="Room">

                        <ul class="list-unstyled small">
                            <li class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Room</span>
                                <span class="fw-semibold">
                                    <?php echo htmlspecialchars($booking['type']); ?>
                                    — Room <?php echo htmlspecialchars($booking['room_number']); ?>
                                </span>
                            </li>
                            <li class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Check-In</span>
                                <span class="fw-semibold"><?php echo date('d M Y', strtotime($booking['check_in'])); ?></span>
                            </li>
                            <li class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Check-Out</span>
                                <span class="fw-semibold"><?php echo date('d M Y', strtotime($booking['check_out'])); ?></span>
                            </li>
                            <li class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Nights</span>
                                <span class="fw-semibold"><?php echo $nights; ?></span>
                            </li>
                            <li class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Price/Night</span>
                                <span class="fw-semibold">৳<?php echo number_format($booking['price_per_night']); ?></span>
                            </li>
                            <li class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Total Amount</span>
                                <span class="fw-bold text-primary">৳<?php echo number_format($total); ?></span>
                            </li>

                            <?php if ($is_due_payment): ?>
                                <li class="d-flex justify-content-between py-2 border-bottom">
                                    <span class="text-muted">Already Paid</span>
                                    <span class="fw-semibold text-success">৳<?php echo number_format($already_paid); ?></span>
                                </li>
                                <li class="d-flex justify-content-between py-2">
                                    <span class="text-muted">Due Now</span>
                                    <span class="fw-bold text-danger">৳<?php echo number_format($amount_to_pay); ?></span>
                                </li>
                            <?php else: ?>
                                <li class="d-flex justify-content-between py-2 border-bottom">
                                    <span class="text-muted">Advance (30%)</span>
                                    <span class="fw-bold text-success">৳<?php echo number_format($amount_to_pay); ?></span>
                                </li>
                                <li class="d-flex justify-content-between py-2">
                                    <span class="text-muted">Due at Check-Out</span>
                                    <span class="fw-semibold">৳<?php echo number_format($total - $amount_to_pay); ?></span>
                                </li>
                            <?php endif; ?>
                        </ul>

                        <div class="alert alert-info rounded-3 small mt-3 mb-0">
                            <?php echo $info_message; ?>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Payment Form -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h5 class="fw-bold mb-1">Pay via SSLCommerz</h5>
                        <p class="text-muted small mb-4">Secure online payment — Sandbox Mode</p>

                        <form action="sslcommerz/init.php" method="POST">
                            <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                            <input type="hidden" name="amount"     value="<?php echo round($amount_to_pay, 2); ?>">
                            <input type="hidden" name="room_number" value="<?php echo htmlspecialchars($booking['room_number']); ?>">
                            <input type="hidden" name="room_type"   value="<?php echo htmlspecialchars($booking['type']); ?>">
                            <?php if ($is_due_payment): ?>
                            <input type="hidden" name="is_due_payment" value="1">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Your Name</label>
                                <input type="text" name="cus_name" class="form-control rounded-3"
                                    value="<?php echo htmlspecialchars($_SESSION['name']); ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Email</label>
                                <input type="email" name="cus_email" class="form-control rounded-3"
                                    value="<?php echo htmlspecialchars($_SESSION['email']); ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Phone Number</label>
                                <input type="text" name="cus_phone" class="form-control rounded-3"
                                    placeholder="01XXXXXXXXX" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Address</label>
                                <input type="text" name="cus_add" class="form-control rounded-3"
                                    placeholder="Your address" required>
                            </div>

                            <!-- Amount to pay -->
                            <div class="<?php echo $is_due_payment ? 'bg-danger' : 'bg-primary'; ?> text-white rounded-3 p-3 mb-4 text-center">
                                <small class="fw-light">Amount to Pay Now</small>
                                <h3 class="fw-bold mb-0">৳<?php echo number_format($amount_to_pay); ?></h3>
                                <small class="fw-light"><?php echo $amount_sublabel; ?></small>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-success rounded-pill fw-semibold py-2">
                                    Pay Now via SSLCommerz
                                </button>
                            </div>
                        </form>

                        <hr class="my-3">

                        <!-- Cancel booking (only on first payment) -->
                        <?php if (!$is_due_payment): ?>
                        <form action="cancel_booking.php" method="POST">
                            <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                            <div class="d-grid">
                                <button type="submit" class="btn btn-outline-danger rounded-pill btn-sm"
                                    onclick="return confirm('Are you sure you want to cancel this booking?')">
                                    Cancel Booking
                                </button>
                            </div>
                        </form>
                        <?php else: ?>
                        <a href="my_stay.php?tab=bookings" class="btn btn-outline-secondary w-100 rounded-pill btn-sm">
                            ← Back to My Bookings
                        </a>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </div>
    </section>

    <?php include '../footer.php'; ?>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"
        onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js'">
    </script>

</body>
</html>

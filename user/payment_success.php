<?php
// payment_success.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db.php';
include '../auth.php';

$booking_id = (int)($_GET['booking_id'] ?? 0);

$stmt = $conn->prepare("
    SELECT b.*, r.room_number, r.type
    FROM booking b
    JOIN room r ON b.room_id = r.room_id
    WHERE b.booking_id = ? AND b.guest_id = ?
");
$stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful — Meghdoot Resort</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/css/bootstrap.min.css"
        onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'">
    <?php include '../global_css.php'; ?>
</head>

<body class="bg-light">

    <?php include '../navbar.php'; ?>

    <!-- SUCCESS SECTION -->
    <section class="d-flex align-items-center justify-content-center" style="min-height: 80vh;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 p-5 text-center">

                        <div class="fs-1 mb-3"></div>
                        <h3 class="fw-bold text-success mb-2">Payment Successful!</h3>
                        <p class="text-muted mb-4">Your booking has been confirmed. See you at Meghdoot Resort!</p>

                        <?php if ($booking): ?>
                            <ul class="list-unstyled small text-start bg-light rounded-3 p-3 mb-4">
                                <li class="d-flex justify-content-between py-1 border-bottom">
                                    <span class="text-muted">Booking ID</span>
                                    <span class="fw-semibold">#<?php echo $booking['booking_id']; ?></span>
                                </li>
                                <li class="d-flex justify-content-between py-1 border-bottom">
                                    <span class="text-muted">Room</span>
                                    <span class="fw-semibold">
                                        <?php echo htmlspecialchars($booking['type']); ?>
                                        — <?php echo htmlspecialchars($booking['room_number']); ?>
                                    </span>
                                </li>
                                <li class="d-flex justify-content-between py-1 border-bottom">
                                    <span class="text-muted">Check-In</span>
                                    <span class="fw-semibold"><?php echo date('d M Y', strtotime($booking['check_in'])); ?></span>
                                </li>
                                <li class="d-flex justify-content-between py-1 border-bottom">
                                    <span class="text-muted">Check-Out</span>
                                    <span class="fw-semibold"><?php echo date('d M Y', strtotime($booking['check_out'])); ?></span>
                                </li>
                                <li class="d-flex justify-content-between py-1 border-bottom">
                                    <span class="text-muted">Paid Now</span>
                                    <span class="fw-bold text-success">৳<?php echo number_format($booking['pay_amount']); ?></span>
                                </li>
                                <li class="d-flex justify-content-between py-1">
                                    <span class="text-muted">Due at Check-Out</span>
                                    <span class="fw-semibold">৳<?php echo number_format($booking['due_amount']); ?></span>
                                </li>
                            </ul>
                        <?php endif; ?>

                        <div class="d-grid gap-2">
                            <a href="my_stay.php?tab=bookings" class="btn btn-primary rounded-pill fw-semibold">
                                View My Bookings
                            </a>
                            <a href="../index.php" class="btn btn-outline-secondary rounded-pill btn-sm">
                                Back to Home
                            </a>
                        </div>

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

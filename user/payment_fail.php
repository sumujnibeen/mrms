<?php
// payment_fail.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db.php';
include '../auth.php';

$booking_id = (int)($_GET['booking_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed — Meghdoot Resort</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/css/bootstrap.min.css"
        onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'">
    <?php include '../global_css.php'; ?>
</head>

<body class="bg-light">

    <?php include '../navbar.php'; ?>

    <!-- FAIL SECTION -->
    <section class="d-flex align-items-center justify-content-center" style="min-height: 80vh;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 p-5 text-center">

                        <div class="fs-1 mb-3"></div>
                        <h3 class="fw-bold text-danger mb-2">Payment Failed!</h3>
                        <p class="text-muted mb-4">
                            Your payment could not be processed. Your booking is still pending.
                            You can try again or cancel the booking.
                        </p>

                        <div class="d-grid gap-2">
                            <?php if ($booking_id): ?>
                                <a href="payment.php?booking_id=<?php echo $booking_id; ?>"
                                    class="btn btn-primary rounded-pill fw-semibold">
                                    Try Again
                                </a>
                            <?php endif; ?>
                            <a href="my_stay.php?tab=bookings" class="btn btn-outline-secondary rounded-pill btn-sm">
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

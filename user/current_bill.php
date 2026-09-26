<?php
// current_bill.php
// Guest-facing live bill view — shows running total while checked in

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db.php';
include '../auth.php';

if ($_SESSION['role'] !== 'guest') {
    header("Location: ../index.php");
    exit();
}

$booking_id = (int)($_GET['booking_id'] ?? 0);

if ($booking_id === 0) {
    header("Location: my_stay.php");
    exit();
}

// Fetch booking — must belong to this guest and be Checked-In
$stmt = $conn->prepare("
    SELECT b.booking_id, b.check_in, b.check_out, b.pay_amount, b.due_amount,
           b.payment_status, b.status, b.booked_at,
           r.room_number, r.type AS room_type, r.price_per_night, r.floor,
           u.Name AS guest_name, u.Email AS guest_email
    FROM booking b
    JOIN room r ON b.room_id = r.room_id
    JOIN user u ON b.guest_id = u.User_id
    WHERE b.booking_id = ? AND b.guest_id = ? AND b.status = 'Checked-In'
");
$stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    header("Location: my_stay.php");
    exit();
}

// Fetch service charges
$svc_stmt = $conn->prepare("
    SELECT service_id, type, description, charge, requested_at, status, added_by
    FROM service_request
    WHERE booking_id = ?
    ORDER BY requested_at ASC
");
$svc_stmt->bind_param("i", $booking_id);
$svc_stmt->execute();
$services = $svc_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$svc_stmt->close();

// Calculations
$nights       = (int)((strtotime($booking['check_out']) - strtotime($booking['check_in'])) / 86400);
$today        = date('Y-m-d');
$nights_so_far= max(1, (int)((strtotime($today) - strtotime($booking['check_in'])) / 86400));
$grand_total  = (float)$booking['pay_amount'];
$paid_so_far  = $grand_total - (float)$booking['due_amount'];
$svc_total    = array_sum(array_column($services, 'charge'));
$room_charge  = $grand_total; // full room charge as booked
$tax_estimate = round(($room_charge + $svc_total) * 0.05, 2);
$running_total= $room_charge + $svc_total + $tax_estimate;
$balance_due  = max(0, $running_total - $paid_so_far);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Current Bill — Meghdoot Resort</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/css/bootstrap.min.css"
        onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'">
    <?php include '../global_css.php'; ?>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
        }
    </style>
</head>
<body class="bg-light">
<?php include '../navbar.php'; ?>

<div class="bg-primary text-white py-4 text-center">
    <div class="container">
        <h2 class="fw-bold mb-0">Current Bill</h2>
        <p class="fw-light mb-0 small">Live running total for your stay — updated as you go</p>
    </div>
</div>

<section class="py-4">
<div class="container">
<div class="row justify-content-center">
<div class="col-lg-7">

    <!-- Action bar -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <a href="my_stay.php?tab=bookings" class="btn btn-outline-secondary rounded-pill btn-sm">
            Back to My Stay
        </a>
        <button onclick="window.print()" class="btn btn-outline-primary rounded-pill btn-sm">
            Print Bill
        </button>
    </div>

    <!-- Bill card -->
    <div class="card border-0 shadow rounded-4 overflow-hidden">

        <!-- Header -->
        <div class="bg-primary text-white p-4">
            <div class="row align-items-center">
                <div class="col-7">
                    <h4 class="fw-bold mb-0">Meghdoot Resort</h4>
                    <p class="mb-0 small opacity-75">Kishoreganj, Bangladesh</p>
                </div>
                <div class="col-5 text-end">
                    <div class="small opacity-75">CURRENT BILL</div>
                    <div class="fw-bold">Booking #<?php echo str_pad($booking_id, 5, '0', STR_PAD_LEFT); ?></div>
                    <div class="small opacity-75"><?php echo date('d M Y, h:i A'); ?></div>
                    <span class="badge bg-success mt-1">Checked-In</span>
                </div>
            </div>
        </div>

        <div class="card-body p-4">

            <!-- Guest & Room info -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <h6 class="fw-bold text-muted text-uppercase small mb-2">Guest</h6>
                    <p class="fw-bold mb-1"><?php echo htmlspecialchars($booking['guest_name']); ?></p>
                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($booking['guest_email']); ?></p>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-bold text-muted text-uppercase small mb-2">Room</h6>
                    <p class="fw-bold mb-1">
                        <?php echo htmlspecialchars($booking['room_type']); ?> &mdash;
                        Room <?php echo htmlspecialchars($booking['room_number']); ?>
                        <span class="text-muted fw-light small">(Floor <?php echo htmlspecialchars($booking['floor']); ?>)</span>
                    </p>
                    <p class="text-muted small mb-1">
                        Check-In: <strong><?php echo date('d M Y', strtotime($booking['check_in'])); ?></strong>
                    </p>
                    <p class="text-muted small mb-1">
                        Check-Out: <strong><?php echo date('d M Y', strtotime($booking['check_out'])); ?></strong>
                    </p>
                    <p class="text-muted small mb-0">
                        Duration: <strong><?php echo $nights; ?> night(s)</strong>
                        &nbsp;&middot;&nbsp;
                        ৳<?php echo number_format($booking['price_per_night']); ?>/night
                    </p>
                </div>
            </div>

            <hr class="my-3">

            <!-- Charges table -->
            <h6 class="fw-bold text-muted text-uppercase small mb-3">Charges</h6>
            <table class="table table-sm table-bordered mb-4">
                <thead class="table-light">
                    <tr>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Room charge -->
                    <tr>
                        <td>
                            Room Charge &mdash; <?php echo htmlspecialchars($booking['room_type']); ?>
                            <div class="text-muted small">
                                ৳<?php echo number_format($booking['price_per_night']); ?>/night
                                &times; <?php echo $nights; ?> nights
                            </div>
                        </td>
                        <td class="text-end fw-semibold">৳<?php echo number_format($room_charge); ?></td>
                    </tr>

                    <!-- Service charges -->
                    <?php if (empty($services)): ?>
                    <tr>
                        <td class="text-muted small">Service Charges</td>
                        <td class="text-end text-muted small">৳0</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($services as $svc):
                        if ($svc['charge'] <= 0) continue;
                        $type_label = ucwords(str_replace('_', ' ', $svc['type']));
                    ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($type_label); ?>
                            <?php if ($svc['added_by'] === 'receptionist'): ?>
                                <span class="badge bg-secondary rounded-pill ms-1" style="font-size:10px;">Staff</span>
                            <?php endif; ?>
                            <div class="text-muted small"><?php echo htmlspecialchars(substr($svc['description'], 0, 60)); ?></div>
                            <div class="text-muted" style="font-size:11px;"><?php echo date('d M, h:i A', strtotime($svc['requested_at'])); ?></div>
                        </td>
                        <td class="text-end">৳<?php echo number_format($svc['charge']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Tax estimate -->
                    <tr class="table-light">
                        <td class="text-muted small">
                            Estimated Tax (5%)
                            <div style="font-size:11px;" class="text-muted">Final tax calculated at check-out</div>
                        </td>
                        <td class="text-end text-muted">৳<?php echo number_format($tax_estimate); ?></td>
                    </tr>

                    <!-- Running total -->
                    <tr class="table-primary">
                        <td class="fw-bold">Estimated Total</td>
                        <td class="text-end fw-bold fs-5">৳<?php echo number_format($running_total); ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Payment summary -->
            <h6 class="fw-bold text-muted text-uppercase small mb-3">Payment Summary</h6>
            <ul class="list-unstyled small mb-4">
                <li class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Estimated Total</span>
                    <span class="fw-semibold">৳<?php echo number_format($running_total); ?></span>
                </li>
                <li class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">
                        Paid Online (<?php echo ucfirst($booking['payment_status']); ?>)
                    </span>
                    <span class="fw-semibold text-success">৳<?php echo number_format($paid_so_far); ?></span>
                </li>
                <li class="d-flex justify-content-between py-2">
                    <span class="fw-bold <?php echo $balance_due > 0 ? 'text-danger' : 'text-success'; ?>">
                        <?php echo $balance_due > 0 ? 'Estimated Balance Due at Check-Out' : 'Fully Paid'; ?>
                    </span>
                    <span class="fw-bold fs-5 <?php echo $balance_due > 0 ? 'text-danger' : 'text-success'; ?>">
                        ৳<?php echo number_format($balance_due); ?>
                    </span>
                </li>
            </ul>

            <?php if (!empty($services)): ?>
            <!-- Service charge breakdown note -->
            <div class="alert alert-light border rounded-3 small mb-0">
                <strong>Note:</strong> Your bill includes
                <strong><?php echo count(array_filter($services, fn($s) => $s['charge'] > 0)); ?> service charge(s)</strong>
                totalling <strong>৳<?php echo number_format($svc_total); ?></strong>.
                If you have any questions about a charge, please contact the reception desk.
            </div>
            <?php endif; ?>

            <div class="text-center mt-4 pt-3 border-top text-muted small">
                <p class="mb-1">
                    This is an <strong>estimated bill</strong>. The final amount will be calculated at check-out
                    and may differ slightly due to tax rounding or last-minute charges.
                </p>
                <p class="mb-0">For queries: reception desk or info@meghdootresort.com</p>
            </div>

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

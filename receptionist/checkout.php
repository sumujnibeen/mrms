<?php
// receptionist/checkout.php

if (session_status() === PHP_SESSION_NONE) session_start();
include '../db.php';
include '../auth.php';

if ($_SESSION['role'] !== 'receptionist' && $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php"); exit();
}

$msg_success = $msg_error = '';

// ── PROCESS CHECK-OUT ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_booking_id'])) {
    $booking_id      = (int)$_POST['checkout_booking_id'];
    $cash_collected  = (float)($_POST['cash_collected'] ?? 0);

    // Fetch booking
    $bk_stmt = $conn->prepare("
        SELECT b.*, r.room_id AS rid, r.room_number, u.Name AS guest_name
        FROM booking b
        JOIN room r ON b.room_id = r.room_id
        JOIN user u ON b.guest_id = u.User_id
        WHERE b.booking_id = ? AND b.status = 'Checked-In'
    ");
    $bk_stmt->bind_param("i", $booking_id);
    $bk_stmt->execute();
    $bk = $bk_stmt->get_result()->fetch_assoc();
    $bk_stmt->close();

    if (!$bk) {
        $msg_error = "Booking not found or guest is not checked in.";
    } else {
        // Sum service charges for this booking
        $svc_stmt = $conn->prepare("
            SELECT COALESCE(SUM(charge), 0) AS total_svc
            FROM service_request WHERE booking_id = ?
        ");
        $svc_stmt->bind_param("i", $booking_id);
        $svc_stmt->execute();
        $svc_total = (float)$svc_stmt->get_result()->fetch_assoc()['total_svc'];
        $svc_stmt->close();

        $room_charge    = (float)$bk['pay_amount'] + (float)$bk['due_amount'];
        $tax            = round(($room_charge + $svc_total) * 0.05, 2); // 5% tax
        $grand_total    = $room_charge + $svc_total + $tax;
        $already_paid   = (float)$bk['pay_amount'];
        $final_due      = max(0, $grand_total - $already_paid);
        $new_due        = max(0, $final_due - $cash_collected);
        $payment_status = $new_due <= 0 ? 'paid' : 'partial';

        // Update booking
        $upd = $conn->prepare("
            UPDATE booking
            SET status = 'Checked-Out',
                due_amount = ?,
                payment_status = ?,
                updated_at = NOW()
            WHERE booking_id = ?
        ");
        $upd->bind_param("dsi", $new_due, $payment_status, $booking_id);
        $upd->execute();
        $upd->close();

        // Set room back to Available
        $rm = $conn->prepare("UPDATE room SET status = 'Available' WHERE room_id = ?");
        $rm->bind_param("i", $bk['rid']);
        $rm->execute();
        $rm->close();

        // Record cash payment if any
        if ($cash_collected > 0) {
            $pay = $conn->prepare("
                INSERT INTO payment (booking_id, amount, method, status, transaction_id, paid_at)
                VALUES (?, ?, 'Cash', 'paid', ?, NOW())
            ");
            $tran_id = 'CASH_' . $booking_id . '_' . time();
            $pay->bind_param("ids", $booking_id, $cash_collected, $tran_id);
            $pay->execute();
            $pay->close();
        }

        // Generate invoice (INSERT or UPDATE)
        $inv_check = $conn->prepare("SELECT invoice_id FROM invoice WHERE booking_id = ?");
        $inv_check->bind_param("i", $booking_id);
        $inv_check->execute();
        $existing_inv = $inv_check->get_result()->fetch_assoc();
        $inv_check->close();

        if ($existing_inv) {
            $inv = $conn->prepare("
                UPDATE invoice SET room_charge=?, service_charge=?, tax=?, total=?, issued_at=NOW()
                WHERE booking_id=?
            ");
            $inv->bind_param("ddddi", $room_charge, $svc_total, $tax, $grand_total, $booking_id);
        } else {
            $inv = $conn->prepare("
                INSERT INTO invoice (booking_id, room_charge, service_charge, tax, total, issued_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $inv->bind_param("idddd", $booking_id, $room_charge, $svc_total, $tax, $grand_total);
        }
        $inv->execute();
        $inv->close();

        $msg_success = " Check-out complete for <strong>{$bk['guest_name']}</strong> (Room {$bk['room_number']}). Invoice generated. <a href='generate_invoice.php?booking_id={$booking_id}' class='fw-bold'>View Invoice →</a>";
    }
}

// ── FETCH CHECKED-IN GUESTS ───────────────────────────────
$stmt = $conn->prepare("
    SELECT b.booking_id, b.check_in, b.check_out, b.pay_amount, b.due_amount, b.payment_status,
           u.Name AS guest_name, u.Email AS guest_email, u.Phone AS guest_phone,
           r.room_number, r.type AS room_type, r.price_per_night,
           COALESCE((SELECT SUM(charge) FROM service_request WHERE booking_id = b.booking_id), 0) AS svc_total
    FROM booking b
    JOIN user u ON b.guest_id = u.User_id
    JOIN room r ON b.room_id = r.room_id
    WHERE b.status = 'Checked-In'
    ORDER BY b.check_out ASC
");
$stmt->execute();
$checked_in = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-Out — Meghdoot Resort</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/css/bootstrap.min.css"
        onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'">
    <?php include '../global_css.php'; ?>
</head>
<body class="bg-light">
<?php include '../navbar.php'; ?>

<div class="bg-primary text-white py-4 text-center">
    <div class="container">
        <h2 class="fw-bold mb-0"> Check-Out</h2>
        <p class="fw-light mb-0 small">Process guest departures & generate invoices</p>
    </div>
</div>

<section class="py-4">
<div class="container">

<?php if ($msg_success): ?>
    <div class="alert alert-success rounded-3 alert-dismissible fade show">
        <?php echo $msg_success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($msg_error): ?>
    <div class="alert alert-danger rounded-3 alert-dismissible fade show">
         <?php echo htmlspecialchars($msg_error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($checked_in->num_rows === 0): ?>
    <div class="text-center py-5">
        <div class="fs-1"></div>
        <h5 class="fw-bold mt-3">No guests currently checked in</h5>
        <p class="text-muted">When guests check in, they will appear here for checkout processing.</p>
    </div>
<?php else: ?>

<div class="row g-4">
<?php
$today = date('Y-m-d');
while ($b = $checked_in->fetch_assoc()):
    $nights      = (int)((strtotime($b['check_out']) - strtotime($b['check_in'])) / 86400);
    $room_charge = $b['pay_amount'] + $b['due_amount'];
    $svc_total   = (float)$b['svc_total'];
    $tax         = round(($room_charge + $svc_total) * 0.05, 2);
    $grand_total = $room_charge + $svc_total + $tax;
    $already_paid= (float)$b['pay_amount'];
    $cash_needed = max(0, $grand_total - $already_paid);
    $is_overdue  = $b['check_out'] < $today;
    $is_today    = $b['check_out'] === $today;
?>
<div class="col-lg-6">
    <div class="card border-0 shadow-sm rounded-4 <?php echo $is_overdue ? 'border-danger border-2' : ''; ?>">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($b['guest_name']); ?></h6>
                    <small class="text-muted"><?php echo htmlspecialchars($b['guest_email']); ?></small>
                </div>
                <div class="text-end">
                    <?php if ($is_overdue): ?>
                        <span class="badge bg-danger rounded-pill">Overdue</span>
                    <?php elseif ($is_today): ?>
                        <span class="badge bg-warning text-dark rounded-pill">Due Today</span>
                    <?php else: ?>
                        <span class="badge bg-success rounded-pill">In-House</span>
                    <?php endif; ?>
                    <div class="text-muted small mt-1">Booking #<?php echo $b['booking_id']; ?></div>
                </div>
            </div>
        </div>
        <div class="card-body pt-3">
            <!-- Stay info -->
            <div class="row g-2 small mb-3">
                <div class="col-6">
                    <div class="bg-light rounded-3 p-2">
                        <div class="text-muted">Room</div>
                        <div class="fw-semibold"><?php echo htmlspecialchars($b['room_number']); ?> — <?php echo htmlspecialchars($b['room_type']); ?></div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="bg-light rounded-3 p-2">
                        <div class="text-muted">Nights</div>
                        <div class="fw-semibold"><?php echo $nights; ?> night(s)</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="bg-light rounded-3 p-2">
                        <div class="text-muted">Check-In</div>
                        <div class="fw-semibold"><?php echo date('d M Y', strtotime($b['check_in'])); ?></div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="bg-light rounded-3 p-2">
                        <div class="text-muted">Check-Out</div>
                        <div class="fw-semibold <?php echo $is_overdue ? 'text-danger' : ''; ?>">
                            <?php echo date('d M Y', strtotime($b['check_out'])); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bill breakdown -->
            <h6 class="fw-semibold small text-muted text-uppercase mb-2">Bill Breakdown</h6>
            <ul class="list-unstyled small mb-3">
                <li class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Room Charge (<?php echo $nights; ?> nights)</span>
                    <span>৳<?php echo number_format($room_charge); ?></span>
                </li>
                <li class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Service Charges</span>
                    <span>৳<?php echo number_format($svc_total); ?></span>
                </li>
                <li class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Tax (5%)</span>
                    <span>৳<?php echo number_format($tax); ?></span>
                </li>
                <li class="d-flex justify-content-between py-1 border-bottom">
                    <span class="fw-bold">Grand Total</span>
                    <span class="fw-bold text-primary">৳<?php echo number_format($grand_total); ?></span>
                </li>
                <li class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Already Paid (Online)</span>
                    <span class="text-success">৳<?php echo number_format($already_paid); ?></span>
                </li>
                <li class="d-flex justify-content-between py-2">
                    <span class="fw-bold text-danger">Cash to Collect</span>
                    <span class="fw-bold text-danger fs-5">৳<?php echo number_format($cash_needed); ?></span>
                </li>
            </ul>

            <!-- Checkout form -->
            <form method="POST">
                <input type="hidden" name="checkout_booking_id" value="<?php echo $b['booking_id']; ?>">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Cash Collected from Guest (৳)</label>
                    <input type="number" name="cash_collected" class="form-control rounded-3"
                        min="0" max="<?php echo $cash_needed; ?>"
                        value="<?php echo $cash_needed; ?>" step="0.01" required>
                    <div class="form-text">Pre-filled with due amount. Adjust if partial.</div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-danger rounded-pill fw-semibold flex-grow-1">
                         Process Check-Out
                    </button>
                    <a href="generate_invoice.php?booking_id=<?php echo $b['booking_id']; ?>"
                        class="btn btn-outline-secondary rounded-pill btn-sm">
                         Preview Invoice
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endwhile; ?>
</div>
<?php endif; ?>
</div>
</section>

<?php include '../footer.php'; ?>
<script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"
    onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js'">
</script>
</body>
</html>

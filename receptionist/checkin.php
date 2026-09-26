<?php
// receptionist/checkin.php

if (session_status() === PHP_SESSION_NONE) session_start();
include '../db.php';
include '../auth.php';

if ($_SESSION['role'] !== 'receptionist' && $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php"); exit();
}

$msg_success = $msg_error = '';

// ── PROCESS CHECK-IN ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkin_booking_id'])) {
    $booking_id = (int)$_POST['checkin_booking_id'];

    // Verify booking is Confirmed with advance paid
    $verify = $conn->prepare("
        SELECT b.booking_id, b.room_id, b.status, b.payment_status, b.check_in, b.check_out,
               u.Name AS guest_name, r.room_number
        FROM booking b
        JOIN user u ON b.guest_id = u.User_id
        JOIN room r ON b.room_id = r.room_id
        WHERE b.booking_id = ? AND b.status = 'Confirmed' AND b.payment_status IN ('partial','paid')
    ");
    $verify->bind_param("i", $booking_id);
    $verify->execute();
    $bk = $verify->get_result()->fetch_assoc();
    $verify->close();

    if (!$bk) {
        $msg_error = "Booking not found or not eligible for check-in.";
    } else {
        // Update booking status
        $upd = $conn->prepare("UPDATE booking SET status = 'Checked-In', updated_at = NOW() WHERE booking_id = ?");
        $upd->bind_param("i", $booking_id);
        $upd->execute();
        $upd->close();

        // Mark room as Booked (already should be, but ensure)
        $rm = $conn->prepare("UPDATE room SET status = 'Booked' WHERE room_id = ?");
        $rm->bind_param("i", $bk['room_id']);
        $rm->execute();
        $rm->close();

        $msg_success = " Check-in successful! <strong>{$bk['guest_name']}</strong> is now checked into Room {$bk['room_number']}.";
    }
}

// ── FETCH TODAY'S CHECK-INS (Confirmed + today's date) ───
$today = date('Y-m-d');

$stmt = $conn->prepare("
    SELECT b.booking_id, b.check_in, b.check_out, b.status, b.payment_status,
           b.pay_amount, b.due_amount,
           u.Name AS guest_name, u.Email AS guest_email, u.Phone AS guest_phone,
           r.room_number, r.type AS room_type
    FROM booking b
    JOIN user u ON b.guest_id = u.User_id
    JOIN room r ON b.room_id = r.room_id
    WHERE b.status = 'Confirmed'
    AND b.payment_status IN ('partial', 'paid')
    ORDER BY b.check_in ASC
");
$stmt->execute();
$confirmed_bookings = $stmt->get_result();
$stmt->close();

// Today's already checked-in
$today_stmt = $conn->prepare("
    SELECT b.booking_id, b.check_in, b.check_out,
           u.Name AS guest_name, r.room_number, r.type AS room_type, b.updated_at
    FROM booking b
    JOIN user u ON b.guest_id = u.User_id
    JOIN room r ON b.room_id = r.room_id
    WHERE b.status = 'Checked-In'
    ORDER BY b.updated_at DESC
    LIMIT 10
");
$today_stmt->execute();
$checked_in_today = $today_stmt->get_result();
$today_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-In — Meghdoot Resort</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/css/bootstrap.min.css"
        onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'">
    <?php include '../global_css.php'; ?>
</head>
<body class="bg-light">
<?php include '../navbar.php'; ?>

<div class="bg-primary text-white py-4 text-center">
    <div class="container">
        <h2 class="fw-bold mb-0"> Check-In</h2>
        <p class="fw-light mb-0 small">Process guest arrivals</p>
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

<div class="row g-4">

    <!-- LEFT: Pending check-ins -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                <h5 class="fw-bold mb-0">Confirmed Bookings — Awaiting Check-In</h5>
                <p class="text-muted small mb-3">All confirmed bookings with advance payment</p>
            </div>
            <div class="card-body p-0">
                <?php if ($confirmed_bookings->num_rows === 0): ?>
                    <div class="text-center py-5 text-muted">
                        <div class="fs-1"></div>
                        <p class="mb-0">No pending check-ins right now.</p>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Guest</th>
                            <th>Room</th>
                            <th>Check-In</th>
                            <th>Check-Out</th>
                            <th>Due</th>
                            <th class="pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($b = $confirmed_bookings->fetch_assoc()):
                        $is_today   = $b['check_in'] === $today;
                        $is_overdue = $b['check_in'] < $today;
                    ?>
                        <tr class="<?php echo $is_overdue ? 'table-danger' : ($is_today ? 'table-warning' : ''); ?>">
                            <td class="ps-4 small text-muted">#<?php echo $b['booking_id']; ?></td>
                            <td>
                                <div class="fw-semibold small"><?php echo htmlspecialchars($b['guest_name']); ?></div>
                                <div class="text-muted" style="font-size:11px;"><?php echo htmlspecialchars($b['guest_email']); ?></div>
                            </td>
                            <td>
                                <span class="fw-semibold small"><?php echo htmlspecialchars($b['room_number']); ?></span>
                                <div class="text-muted" style="font-size:11px;"><?php echo htmlspecialchars($b['room_type']); ?></div>
                            </td>
                            <td class="small">
                                <?php echo date('d M Y', strtotime($b['check_in'])); ?>
                                <?php if ($is_today): ?>
                                    <span class="badge bg-warning text-dark ms-1">Today</span>
                                <?php elseif ($is_overdue): ?>
                                    <span class="badge bg-danger ms-1">Overdue</span>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?php echo date('d M Y', strtotime($b['check_out'])); ?></td>
                            <td class="small fw-semibold <?php echo $b['due_amount'] > 0 ? 'text-danger' : 'text-success'; ?>">
                                ৳<?php echo number_format($b['due_amount']); ?>
                            </td>
                            <td class="pe-4">
                                <button class="btn btn-primary btn-sm rounded-pill"
                                    data-bs-toggle="modal" data-bs-target="#checkinModal"
                                    data-id="<?php echo $b['booking_id']; ?>"
                                    data-guest="<?php echo htmlspecialchars($b['guest_name']); ?>"
                                    data-room="<?php echo htmlspecialchars($b['room_number']); ?>"
                                    data-type="<?php echo htmlspecialchars($b['room_type']); ?>"
                                    data-checkin="<?php echo date('d M Y', strtotime($b['check_in'])); ?>"
                                    data-checkout="<?php echo date('d M Y', strtotime($b['check_out'])); ?>"
                                    data-due="<?php echo number_format($b['due_amount']); ?>"
                                    data-phone="<?php echo htmlspecialchars($b['guest_phone'] ?? 'N/A'); ?>">
                                    Check-In
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- RIGHT: Currently checked-in -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                <h6 class="fw-bold mb-0"> Currently In-House</h6>
                <p class="text-muted small mb-3">Recent check-ins</p>
            </div>
            <div class="card-body p-0">
                <?php if ($checked_in_today->num_rows === 0): ?>
                    <div class="text-center py-4 text-muted small">No guests currently checked in.</div>
                <?php else: ?>
                <?php while ($ci = $checked_in_today->fetch_assoc()): ?>
                <div class="px-4 py-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold small"><?php echo htmlspecialchars($ci['guest_name']); ?></div>
                            <div class="text-muted" style="font-size:11px;">
                                Room <?php echo htmlspecialchars($ci['room_number']); ?>
                                · <?php echo htmlspecialchars($ci['room_type']); ?>
                            </div>
                            <div class="text-muted" style="font-size:11px;">
                                Out: <?php echo date('d M Y', strtotime($ci['check_out'])); ?>
                            </div>
                        </div>
                        <span class="badge bg-success rounded-pill">In</span>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>
</div>
</section>

<!-- CHECK-IN CONFIRM MODAL -->
<div class="modal fade" id="checkinModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Confirm Check-In</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="list-unstyled small bg-light rounded-3 p-3 mb-3">
                    <li class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Guest</span>
                        <span class="fw-semibold" id="ci_guest"></span>
                    </li>
                    <li class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Phone</span>
                        <span class="fw-semibold" id="ci_phone"></span>
                    </li>
                    <li class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Room</span>
                        <span class="fw-semibold" id="ci_room"></span>
                    </li>
                    <li class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Check-In</span>
                        <span class="fw-semibold" id="ci_checkin"></span>
                    </li>
                    <li class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Check-Out</span>
                        <span class="fw-semibold" id="ci_checkout"></span>
                    </li>
                    <li class="d-flex justify-content-between py-1">
                        <span class="text-muted">Due at Check-Out</span>
                        <span class="fw-bold text-danger" id="ci_due"></span>
                    </li>
                </ul>
                <p class="text-muted small mb-0">Clicking confirm will mark this guest as <strong>Checked-In</strong> and activate room service.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button class="btn btn-outline-secondary rounded-pill btn-sm" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="checkin_booking_id" id="ci_booking_id">
                    <button type="submit" class="btn btn-primary rounded-pill btn-sm fw-semibold px-4">
                         Confirm Check-In
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>
<script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"
    onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js'">
</script>
<script>
document.getElementById('checkinModal').addEventListener('show.bs.modal', function(e) {
    const b = e.relatedTarget;
    document.getElementById('ci_booking_id').value = b.dataset.id;
    document.getElementById('ci_guest').textContent   = b.dataset.guest;
    document.getElementById('ci_phone').textContent   = b.dataset.phone;
    document.getElementById('ci_room').textContent    = b.dataset.room + ' — ' + b.dataset.type;
    document.getElementById('ci_checkin').textContent = b.dataset.checkin;
    document.getElementById('ci_checkout').textContent= b.dataset.checkout;
    document.getElementById('ci_due').textContent     = '৳' + b.dataset.due;
});
</script>
</body>
</html>

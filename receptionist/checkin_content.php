<?php
// checkin_content.php

$msg_success = $_SESSION['rec_checkin_success'] ?? '';
$msg_error   = $_SESSION['rec_checkin_error']   ?? '';
unset($_SESSION['rec_checkin_success'], $_SESSION['rec_checkin_error']);

// ── PROCESS CHECK-IN ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkin_booking_id'])) {
    $booking_id = (int)$_POST['checkin_booking_id'];

    $verify = $conn->prepare("
        SELECT b.booking_id, b.room_id, u.Name AS guest_name, r.room_number
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
        $upd = $conn->prepare("UPDATE booking SET status = 'Checked-In', updated_at = NOW() WHERE booking_id = ?");
        $upd->bind_param("i", $booking_id);
        $upd->execute();
        $upd->close();

        $rm = $conn->prepare("UPDATE room SET status = 'Booked' WHERE room_id = ?");
        $rm->bind_param("i", $bk['room_id']);
        $rm->execute();
        $rm->close();

        $msg_success = "Check-in successful. <strong>" . htmlspecialchars($bk['guest_name']) . "</strong> is now checked into Room " . htmlspecialchars($bk['room_number']) . ".";
    }
}

// ── SEARCH / FILTER ───────────────────────────────────────
$search    = trim($_GET['ci_search'] ?? '');
$today     = date('Y-m-d');

$where  = ["b.status = 'Confirmed'", "b.payment_status IN ('partial','paid')"];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = "(u.Name LIKE ? OR r.room_number LIKE ? OR b.booking_id = ?)";
    $like     = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = (int)$search;
    $types   .= 'ssi';
}

$sql = "
    SELECT b.booking_id, b.check_in, b.check_out, b.pay_amount, b.due_amount,
           u.Name AS guest_name, u.Email AS guest_email, u.Phone AS guest_phone,
           r.room_number, r.type AS room_type
    FROM booking b
    JOIN user u ON b.guest_id = u.User_id
    JOIN room r ON b.room_id = r.room_id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY b.check_in ASC
";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$confirmed_bookings = $stmt->get_result();
$stmt->close();

$today_stmt = $conn->prepare("
    SELECT b.booking_id, b.check_in, b.check_out,
           u.Name AS guest_name, r.room_number, r.type AS room_type, b.updated_at
    FROM booking b
    JOIN user u ON b.guest_id = u.User_id
    JOIN room r ON b.room_id = r.room_id
    WHERE b.status = 'Checked-In'
    ORDER BY b.updated_at DESC LIMIT 10
");
$today_stmt->execute();
$checked_in_now = $today_stmt->get_result();
$today_stmt->close();
?>

<?php if ($msg_success): ?>
    <div class="alert alert-success rounded-3 alert-dismissible fade show mb-4">
        <?php echo $msg_success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($msg_error): ?>
    <div class="alert alert-danger rounded-3 alert-dismissible fade show mb-4">
        <?php echo htmlspecialchars($msg_error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- SEARCH BAR -->
<div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <input type="hidden" name="tab" value="checkin">
        <div class="col-md-8">
            <label class="form-label small fw-semibold mb-1">Search Pending Check-Ins</label>
            <input type="text" name="ci_search" class="form-control rounded-3"
                placeholder="Guest name, room number, or booking ID"
                value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary rounded-pill w-100">Search</button>
        </div>
        <div class="col-md-2">
            <a href="?tab=checkin" class="btn btn-outline-secondary rounded-pill w-100">Clear</a>
        </div>
    </form>
</div>

<div class="row g-4">

    <!-- LEFT: Pending check-ins -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0">Confirmed — Awaiting Check-In</h5>
                        <p class="text-muted small mb-0">Bookings with advance payment received</p>
                    </div>
                    <span class="badge bg-primary rounded-pill"><?php echo $confirmed_bookings->num_rows; ?></span>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if ($confirmed_bookings->num_rows === 0): ?>
                    <div class="text-center py-5 text-muted">
                        <p class="mb-0">No pending check-ins<?php echo $search ? ' matching your search' : ''; ?>.</p>
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
                                <div class="fw-semibold small"><?php echo htmlspecialchars($b['room_number']); ?></div>
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

    <!-- RIGHT: Currently in-house -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                <h6 class="fw-bold mb-0">Currently In-House</h6>
                <p class="text-muted small mb-0">Active checked-in guests</p>
            </div>
            <div class="card-body p-0">
                <?php if ($checked_in_now->num_rows === 0): ?>
                    <div class="text-center py-4 text-muted small">No guests currently checked in.</div>
                <?php else: ?>
                <?php while ($ci = $checked_in_now->fetch_assoc()): ?>
                <div class="px-4 py-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold small"><?php echo htmlspecialchars($ci['guest_name']); ?></div>
                            <div class="text-muted" style="font-size:11px;">
                                Room <?php echo htmlspecialchars($ci['room_number']); ?>
                                &middot; <?php echo htmlspecialchars($ci['room_type']); ?>
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
                        <span class="text-muted">Check-In Date</span>
                        <span class="fw-semibold" id="ci_checkin"></span>
                    </li>
                    <li class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Check-Out Date</span>
                        <span class="fw-semibold" id="ci_checkout"></span>
                    </li>
                    <li class="d-flex justify-content-between py-1">
                        <span class="text-muted">Due at Check-Out</span>
                        <span class="fw-bold text-danger" id="ci_due"></span>
                    </li>
                </ul>
                <p class="text-muted small mb-0">
                    Confirming will mark this guest as <strong>Checked-In</strong> and activate room service for their booking.
                </p>
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

<script>
document.getElementById('checkinModal').addEventListener('show.bs.modal', function(e) {
    const b = e.relatedTarget;
    document.getElementById('ci_booking_id').value   = b.dataset.id;
    document.getElementById('ci_guest').textContent   = b.dataset.guest;
    document.getElementById('ci_phone').textContent   = b.dataset.phone;
    document.getElementById('ci_room').textContent    = b.dataset.room + ' — ' + b.dataset.type;
    document.getElementById('ci_checkin').textContent = b.dataset.checkin;
    document.getElementById('ci_checkout').textContent= b.dataset.checkout;
    document.getElementById('ci_due').textContent     = '৳' + b.dataset.due;
});
</script>

<?php
// my_bookings_content.php
// Pure content partial — included by my_stay.php and my_bookings.php
// Requires: $conn, $_SESSION already set

$guest_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT b.*, r.room_number, r.type, r.image, r.price_per_night
    FROM booking b
    JOIN room r ON b.room_id = r.room_id
    WHERE b.guest_id = ?
    ORDER BY b.booked_at DESC
");
$stmt->bind_param("i", $guest_id);
$stmt->execute();
$bookings = $stmt->get_result();
$stmt->close();

// Flash messages
$msg_success = $_SESSION['booking_msg_success'] ?? '';
$msg_error   = $_SESSION['booking_msg_error']   ?? '';
$msg_info    = $_SESSION['booking_msg']          ?? '';
unset($_SESSION['booking_msg_success'], $_SESSION['booking_msg_error'], $_SESSION['booking_msg']);

// Refund policy tiers
$refund_policy = [
    ['min' => 72,  'max' => PHP_INT_MAX, 'percent' => 80, 'label' => '72+ hours before check-in'],
    ['min' => 48,  'max' => 72,          'percent' => 50, 'label' => '48–72 hours before check-in'],
    ['min' => 36,  'max' => 48,          'percent' => 25, 'label' => '36–48 hours before check-in'],
    ['min' => 24,  'max' => 36,          'percent' => 20, 'label' => '24–36 hours before check-in'],
    ['min' => 0,   'max' => 24,          'percent' => 0,  'label' => 'Less than 24 hours before check-in'],
];

function calc_refund_percent_bc($check_in_date, $refund_policy)
{
    $now        = time();
    $check_in   = strtotime($check_in_date);
    $hours_left = ($check_in - $now) / 3600;
    if ($hours_left <= 0) return 0;
    foreach ($refund_policy as $tier) {
        if ($hours_left >= $tier['min'] && $hours_left < $tier['max']) {
            return $tier['percent'];
        }
    }
    return 0;
}
?>

<!-- FLASH MESSAGES -->
<?php if ($msg_success): ?>
    <div class="alert alert-success rounded-3 alert-dismissible fade show mb-4">
        <?php echo htmlspecialchars($msg_success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($msg_error): ?>
    <div class="alert alert-danger rounded-3 alert-dismissible fade show mb-4">
        <?php echo htmlspecialchars($msg_error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($msg_info): ?>
    <div class="alert alert-info rounded-3 alert-dismissible fade show mb-4">
        <?php echo htmlspecialchars($msg_info); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- BOOKINGS LIST -->
<?php if ($bookings->num_rows === 0): ?>
    <div class="text-center py-5">
        <div class="fs-1 mb-3"></div>
        <h5 class="fw-bold">No bookings yet</h5>
        <p class="text-muted">You haven't made any reservations.</p>
        <a href="../rooms.php" class="btn btn-primary rounded-pill px-5">Browse Rooms</a>
    </div>

<?php else: ?>
    <style>
        .booking-card { transition: transform .15s; }
        .booking-card:hover { transform: translateY(-2px); }
        .refund-tier { border-left: 3px solid transparent; }
        .refund-tier.active-tier { border-left-color: var(--bs-primary); background: #f0f4ff; }
        .refund-tier.zero-tier { border-left-color: var(--bs-danger); }
    </style>

    <div class="row g-4">
        <?php while ($b = $bookings->fetch_assoc()):
            $nights      = (int)((strtotime($b['check_out']) - strtotime($b['check_in'])) / 86400);
            // pay_amount = grand total (set at booking, never changed)
            // due_amount = decremented by each payment
            $total       = (float)$b['pay_amount'];
            $paid_so_far = $total - (float)$b['due_amount'];
            $refund_pct  = calc_refund_percent_bc($b['check_in'], $refund_policy);
            $refund_amt  = round($paid_so_far * $refund_pct / 100);

            $status_badge = match ($b['status']) {
                'Pending'     => 'bg-warning text-dark',
                'Confirmed'   => 'bg-primary',
                'Checked-In'  => 'bg-success',
                'Checked-Out' => 'bg-secondary',
                'Cancelled'   => 'bg-danger',
                default       => 'bg-secondary'
            };
            $pay_badge = match ($b['payment_status']) {
                'paid'    => 'bg-success',
                'partial' => 'bg-warning text-dark',
                'unpaid'  => 'bg-danger',
                default   => 'bg-secondary'
            };
            $refund_status = $b['refund_status'] ?? 'none';
        ?>
        <div class="col-12 booking-card">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="row g-0">

                    <!-- Room Image -->
                    <div class="col-md-3 col-4">
                        <img src="<?php echo htmlspecialchars($b['image']); ?>"
                            class="img-fluid rounded-start-4 img-cover w-100 h-100"
                            style="min-height: 180px; max-height: 240px;"
                            alt="Room <?php echo htmlspecialchars($b['room_number']); ?>">
                    </div>

                    <!-- Booking Details -->
                    <div class="col-md-9 col-8 d-flex flex-column">
                        <div class="card-body p-3 p-md-4 flex-grow-1">

                            <!-- Top row -->
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                                <div>
                                    <span class="badge bg-primary-subtle text-primary mb-1">
                                        <?php echo htmlspecialchars($b['type']); ?>
                                    </span>
                                    <h5 class="fw-bold mb-0">Room <?php echo htmlspecialchars($b['room_number']); ?></h5>
                                    <small class="text-muted">Booking #<?php echo $b['booking_id']; ?></small>
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <span class="badge <?php echo $status_badge; ?> rounded-pill">
                                        <?php echo $b['status']; ?>
                                    </span>
                                    <span class="badge <?php echo $pay_badge; ?> rounded-pill">
                                        <?php echo ucfirst($b['payment_status']); ?>
                                    </span>
                                    <?php if ($refund_status === 'requested'): ?>
                                        <span class="badge bg-info text-dark rounded-pill">Refund Requested</span>
                                    <?php elseif ($refund_status === 'approved'): ?>
                                        <span class="badge bg-success rounded-pill">Refund Approved</span>
                                    <?php elseif ($refund_status === 'rejected'): ?>
                                        <span class="badge bg-danger rounded-pill">Refund Rejected</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Info grid -->
                            <div class="row g-2 small text-muted mb-3">
                                <div class="col-sm-6">
                                    <strong class="text-dark"><?php echo date('d M Y', strtotime($b['check_in'])); ?></strong>
                                    → <strong class="text-dark"><?php echo date('d M Y', strtotime($b['check_out'])); ?></strong>
                                </div>
                                <div class="col-sm-6">
                                    <strong class="text-dark"><?php echo $nights; ?></strong> night(s)
                                    &nbsp;·&nbsp;
                                    ৳<strong class="text-dark"><?php echo number_format($b['price_per_night']); ?></strong>/night
                                </div>
                            </div>

                            <!-- Payment summary -->
                            <div class="row g-2">
                                <div class="col-4">
                                    <div class="bg-light rounded-3 p-2 text-center">
                                        <div class="small text-muted">Total</div>
                                        <div class="fw-bold text-dark">৳<?php echo number_format($total); ?></div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="bg-success bg-opacity-10 rounded-3 p-2 text-center">
                                        <div class="small text-muted">Paid</div>
                                        <div class="fw-bold text-success">৳<?php echo number_format($paid_so_far); ?></div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="bg-danger bg-opacity-10 rounded-3 p-2 text-center">
                                        <div class="small text-muted">Due</div>
                                        <div class="fw-bold text-danger">৳<?php echo number_format($b['due_amount']); ?></div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- ACTION BUTTONS -->
                        <div class="card-footer bg-transparent border-top py-3 px-3 px-md-4 d-flex gap-2 flex-wrap align-items-center">

                            <?php if ($b['status'] === 'Pending' && $b['payment_status'] === 'unpaid'): ?>
                                <a href="payment.php?booking_id=<?php echo $b['booking_id']; ?>"
                                    class="btn btn-primary btn-sm rounded-pill">
                                    Pay Advance (30%)
                                </a>
                            <?php elseif ($b['status'] === 'Confirmed' && $b['payment_status'] === 'partial' && $b['due_amount'] > 0): ?>
                                <a href="payment.php?booking_id=<?php echo $b['booking_id']; ?>&pay_due=1"
                                    class="btn btn-success btn-sm rounded-pill">
                                    Pay Due (৳<?php echo number_format($b['due_amount']); ?>)
                                </a>
                            <?php endif; ?>

                            <?php if ($b['status'] === 'Checked-Out'): ?>
                                <a href="../admin/invoice_view.php?booking_id=<?php echo $b['booking_id']; ?>"
                                    class="btn btn-outline-primary btn-sm rounded-pill">
                                    View Invoice
                                </a>
                            <?php endif; ?>

                            <?php if (in_array($b['status'], ['Pending', 'Confirmed'])): ?>
                                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill"
                                    data-bs-toggle="modal" data-bs-target="#cancelModal"
                                    data-booking-id="<?php echo $b['booking_id']; ?>"
                                    data-room="Room <?php echo htmlspecialchars($b['room_number']); ?>"
                                    data-paid="<?php echo $paid_so_far; ?>"
                                    data-refund-pct="<?php echo $refund_pct; ?>"
                                    data-refund-amt="<?php echo $refund_amt; ?>">
                                    Cancel Booking
                                </button>
                            <?php endif; ?>

                            <?php if ($b['status'] === 'Checked-In'): ?>
                                <a href="current_bill.php?booking_id=<?php echo $b['booking_id']; ?>"
                                    class="btn btn-outline-primary btn-sm rounded-pill">
                                    View Current Bill
                                </a>
                            <?php endif; ?>

                            <?php if ($refund_status === 'requested'): ?>
                                <span class="text-muted small ms-1">
                                    Refund of
                                    <strong>৳<?php echo number_format($paid_so_far * $b['refund_percent'] / 100); ?></strong>
                                    (<?php echo $b['refund_percent']; ?>%) pending review
                                </span>
                            <?php elseif ($refund_status === 'approved'): ?>
                                <span class="text-success small ms-1">
                                    Refund of
                                    <strong>৳<?php echo number_format($paid_so_far * $b['refund_percent'] / 100); ?></strong>
                                    approved
                                </span>
                            <?php elseif ($refund_status === 'rejected'): ?>
                                <span class="text-danger small ms-1">Refund request rejected</span>
                            <?php endif; ?>

                        </div>
                    </div>

                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <!-- CANCEL MODAL -->
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Cancel Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <p class="text-muted small mb-3">
                        Are you sure you want to cancel <strong id="cancelRoomName"></strong>?
                        This action cannot be undone.
                    </p>

                    <!-- Refund section — shown only when guest has paid -->
                    <div id="cancelRefundSection" class="d-none">
                        <hr class="my-3">
                        <h6 class="fw-semibold small text-uppercase text-muted mb-3">Refund Applicable</h6>

                        <ul class="list-unstyled small bg-light rounded-3 p-3 mb-3">
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Amount You Paid</span>
                                <span class="fw-semibold" id="cancelPaidAmt"></span>
                            </li>
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Refund Rate</span>
                                <span class="fw-semibold" id="cancelRefundRate"></span>
                            </li>
                            <li class="d-flex justify-content-between py-2">
                                <span class="fw-bold">You Will Receive</span>
                                <span class="fw-bold text-success fs-6" id="cancelRefundAmt"></span>
                            </li>
                        </ul>

                        <div class="alert alert-info rounded-3 small mb-0">
                            The refund will be returned to your original payment method via
                            <strong>SSLCommerz</strong> within 2-3 business days.
                            The refund request is submitted automatically when you cancel.
                        </div>
                    </div>

                    <!-- No refund notice — shown when nothing was paid -->
                    <div id="cancelNoRefundSection" class="d-none">
                        <div class="alert alert-secondary rounded-3 small mb-0">
                            No advance payment was made for this booking, so no refund will be issued.
                        </div>
                    </div>

                    <!-- No refund due to timing -->
                    <div id="cancelLateSection" class="d-none">
                        <hr class="my-3">
                        <div class="alert alert-warning rounded-3 small mb-0">
                            Your check-in is less than 24 hours away. No refund is applicable per our policy.
                            The paid amount of <strong id="cancelLatePaid"></strong> will not be refunded.
                        </div>
                    </div>

                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill btn-sm"
                        data-bs-dismiss="modal">Keep Booking</button>
                    <form action="cancel_booking.php" method="POST" class="d-inline">
                        <input type="hidden" name="booking_id" id="cancelBookingId">
                        <button type="submit" class="btn btn-danger rounded-pill btn-sm fw-semibold" id="cancelSubmitBtn">
                            Cancel Booking
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('cancelModal').addEventListener('show.bs.modal', function(e) {
        const btn  = e.relatedTarget;
        const paid = parseFloat(btn.dataset.paid) || 0;
        const pct  = parseInt(btn.dataset.refundPct) || 0;
        const amt  = parseFloat(btn.dataset.refundAmt) || 0;

        document.getElementById('cancelBookingId').value       = btn.dataset.bookingId;
        document.getElementById('cancelRoomName').textContent  = btn.dataset.room;

        // Hide all conditional sections first
        document.getElementById('cancelRefundSection').classList.add('d-none');
        document.getElementById('cancelNoRefundSection').classList.add('d-none');
        document.getElementById('cancelLateSection').classList.add('d-none');

        if (paid <= 0) {
            // Nothing paid — just a simple cancel
            document.getElementById('cancelNoRefundSection').classList.remove('d-none');
        } else if (pct === 0) {
            // Paid but within 24h — no refund
            document.getElementById('cancelLateSection').classList.remove('d-none');
            document.getElementById('cancelLatePaid').textContent = '৳' + paid.toLocaleString('en-BD');
        } else {
            // Paid and refund applicable
            document.getElementById('cancelRefundSection').classList.remove('d-none');
            document.getElementById('cancelPaidAmt').textContent   = '৳' + paid.toLocaleString('en-BD');
            document.getElementById('cancelRefundRate').textContent = pct + '% of paid amount';
            document.getElementById('cancelRefundAmt').textContent  = '৳' + amt.toLocaleString('en-BD');
        }
    });
    </script>

<?php endif; ?>

<?php
// checkout_content.php

$msg_success = $_SESSION['rec_checkout_success'] ?? '';
$msg_error   = $_SESSION['rec_checkout_error']   ?? '';
unset($_SESSION['rec_checkout_success'], $_SESSION['rec_checkout_error']);

// ── ADD SERVICE CHARGE ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service_booking_id'])) {
    $booking_id  = (int)$_POST['add_service_booking_id'];
    $svc_type    = trim($_POST['svc_type'] ?? 'other');
    $svc_desc    = trim($_POST['svc_desc'] ?? '');
    $svc_charge  = (float)($_POST['svc_charge'] ?? 0);
    $allowed_types = ['food','housekeeping','extra_towel','laundry','other'];
    if (!in_array($svc_type, $allowed_types)) $svc_type = 'other';

    if ($svc_desc === '' || $svc_charge <= 0) {
        $msg_error = "Please provide a description and a valid charge amount.";
    } else {
        // Verify booking is Checked-In
        $chk = $conn->prepare("SELECT guest_id FROM booking WHERE booking_id = ? AND status = 'Checked-In'");
        $chk->bind_param("i", $booking_id);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();
        $chk->close();

        if (!$row) {
            $msg_error = "Booking not found or not checked in.";
        } else {
            $ins = $conn->prepare("
                INSERT INTO service_request (guest_id, booking_id, type, description, status, charge, added_by)
                VALUES (?, ?, ?, ?, 'Done', ?, 'receptionist')
            ");
            $ins->bind_param("iissd", $row['guest_id'], $booking_id, $svc_type, $svc_desc, $svc_charge);
            $ins->execute();
            $ins->close();
            $msg_success = "Service charge of ৳" . number_format($svc_charge) . " added successfully.";
        }
    }
}

// ── REMOVE SERVICE CHARGE ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_service_id'])) {
    $service_id = (int)$_POST['remove_service_id'];
    $del = $conn->prepare("DELETE FROM service_request WHERE service_id = ?");
    $del->bind_param("i", $service_id);
    $del->execute();
    $del->close();
    $msg_success = "Service charge removed.";
}

// ── PROCESS CHECK-OUT ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_booking_id'])) {
    $booking_id     = (int)$_POST['checkout_booking_id'];
    $cash_collected = (float)($_POST['cash_collected'] ?? 0);
    $discount       = max(0, (float)($_POST['discount'] ?? 0));

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
        $svc_stmt = $conn->prepare("SELECT COALESCE(SUM(charge),0) AS total_svc FROM service_request WHERE booking_id = ?");
        $svc_stmt->bind_param("i", $booking_id);
        $svc_stmt->execute();
        $svc_total = (float)$svc_stmt->get_result()->fetch_assoc()['total_svc'];
        $svc_stmt->close();

        $room_charge  = (float)$bk['pay_amount'] + (float)$bk['due_amount'];
        $subtotal     = $room_charge + $svc_total;
        // Discount capped at subtotal (cannot discount more than what is owed before tax)
        $discount     = min($discount, $subtotal);
        $taxable      = $subtotal - $discount;
        $tax          = round($taxable * 0.05, 2);
        $grand_total  = $taxable + $tax;
        $already_paid = (float)$bk['pay_amount'];
        $amount_due   = max(0, $grand_total - $already_paid);
        // Cash collected cannot exceed what is due
        $cash_collected = min($cash_collected, $amount_due);
        $remaining    = max(0, $amount_due - $cash_collected);

        // Always mark as 'paid' on checkout — receptionist clears the account
        $upd = $conn->prepare("
            UPDATE booking SET status='Checked-Out', due_amount=?, payment_status='paid', updated_at=NOW()
            WHERE booking_id=?
        ");
        $upd->bind_param("di", $remaining, $booking_id);
        $upd->execute();
        $upd->close();

        $rm = $conn->prepare("UPDATE room SET status='Available' WHERE room_id=?");
        $rm->bind_param("i", $bk['rid']);
        $rm->execute();
        $rm->close();

        // Record cash payment (only actual amount received)
        if ($cash_collected > 0) {
            $tran_id = 'CASH_' . $booking_id . '_' . time();
            $pay = $conn->prepare("
                INSERT INTO payment (booking_id, amount, method, status, transaction_id, paid_at)
                VALUES (?, ?, 'Cash', 'paid', ?, NOW())
            ");
            $pay->bind_param("ids", $booking_id, $cash_collected, $tran_id);
            $pay->execute();
            $pay->close();
        }

        // Save invoice with discount
        $inv_check = $conn->prepare("SELECT invoice_id FROM invoice WHERE booking_id = ?");
        $inv_check->bind_param("i", $booking_id);
        $inv_check->execute();
        $existing_inv = $inv_check->get_result()->fetch_assoc();
        $inv_check->close();

        if ($existing_inv) {
            $inv = $conn->prepare("
                UPDATE invoice SET room_charge=?, service_charge=?, discount=?, tax=?, total=?, issued_at=NOW()
                WHERE booking_id=?
            ");
            $inv->bind_param("dddddi", $room_charge, $svc_total, $discount, $tax, $grand_total, $booking_id);
        } else {
            $inv = $conn->prepare("
                INSERT INTO invoice (booking_id, room_charge, service_charge, discount, tax, total, issued_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $inv->bind_param("iddddd", $booking_id, $room_charge, $svc_total, $discount, $tax, $grand_total);
        }
        $inv->execute();
        $inv->close();

        $msg_success = "Check-out complete for <strong>" . htmlspecialchars($bk['guest_name']) . "</strong> (Room " . htmlspecialchars($bk['room_number']) . "). Amount received: ৳" . number_format($cash_collected) . ". <a href='generate_invoice.php?booking_id={$booking_id}' class='fw-bold'>View Invoice</a>";
    }
}

// ── SEARCH / FILTER ───────────────────────────────────────
$search_co = trim($_GET['co_search'] ?? '');
$where_co  = ["b.status = 'Checked-In'"];
$params_co = []; $types_co = '';

if ($search_co !== '') {
    $where_co[] = "(u.Name LIKE ? OR r.room_number LIKE ? OR b.booking_id = ?)";
    $like = "%$search_co%";
    $params_co[] = $like; $params_co[] = $like; $params_co[] = (int)$search_co;
    $types_co   .= 'ssi';
}

$stmt = $conn->prepare("
    SELECT b.booking_id, b.check_in, b.check_out, b.pay_amount, b.due_amount,
           u.Name AS guest_name, u.Email AS guest_email, u.Phone AS guest_phone,
           r.room_number, r.type AS room_type, r.price_per_night
    FROM booking b
    JOIN user u ON b.guest_id = u.User_id
    JOIN room r ON b.room_id = r.room_id
    WHERE " . implode(" AND ", $where_co) . "
    ORDER BY b.check_out ASC
");
if ($params_co) $stmt->bind_param($types_co, ...$params_co);
$stmt->execute();
$checked_in = $stmt->get_result();
$stmt->close();
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
        <input type="hidden" name="tab" value="checkout">
        <div class="col-md-8">
            <label class="form-label small fw-semibold mb-1">Search Checked-In Guests</label>
            <input type="text" name="co_search" class="form-control rounded-3"
                placeholder="Guest name, room number, or booking ID"
                value="<?php echo htmlspecialchars($search_co); ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary rounded-pill w-100">Search</button>
        </div>
        <div class="col-md-2">
            <a href="?tab=checkout" class="btn btn-outline-secondary rounded-pill w-100">Clear</a>
        </div>
    </form>
</div>

<?php if ($checked_in->num_rows === 0): ?>
    <div class="text-center py-5 text-muted">
        <h5 class="fw-bold">No guests currently checked in<?php echo $search_co ? ' matching your search' : ''; ?></h5>
        <p class="mb-0">Guests will appear here after the receptionist processes their check-in.</p>
    </div>
<?php else: ?>

<?php
$today = date('Y-m-d');
while ($b = $checked_in->fetch_assoc()):
    $booking_id  = $b['booking_id'];
    $nights      = (int)((strtotime($b['check_out']) - strtotime($b['check_in'])) / 86400);
    $room_charge = (float)$b['pay_amount'] + (float)$b['due_amount'];
    $already_paid= (float)$b['pay_amount'];
    $is_overdue  = $b['check_out'] < $today;
    $is_today    = $b['check_out'] === $today;

    // Fetch service requests for this booking
    $svc_stmt = $conn->prepare("
        SELECT service_id, type, description, charge, added_by, requested_at
        FROM service_request WHERE booking_id = ? ORDER BY requested_at ASC
    ");
    $svc_stmt->bind_param("i", $booking_id);
    $svc_stmt->execute();
    $services = $svc_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $svc_stmt->close();

    $svc_total = array_sum(array_column($services, 'charge'));
?>
<div class="card border-0 shadow-sm rounded-4 mb-4 <?php echo $is_overdue ? 'border-danger border-2' : ''; ?>">
    <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($b['guest_name']); ?></h5>
                <small class="text-muted"><?php echo htmlspecialchars($b['guest_email']); ?></small>
                <?php if ($b['guest_phone']): ?>
                    <small class="text-muted ms-2">&middot; <?php echo htmlspecialchars($b['guest_phone']); ?></small>
                <?php endif; ?>
            </div>
            <div class="text-end">
                <?php if ($is_overdue): ?>
                    <span class="badge bg-danger rounded-pill">Overdue</span>
                <?php elseif ($is_today): ?>
                    <span class="badge bg-warning text-dark rounded-pill">Due Today</span>
                <?php else: ?>
                    <span class="badge bg-success rounded-pill">In-House</span>
                <?php endif; ?>
                <div class="text-muted small mt-1">Booking #<?php echo $booking_id; ?></div>
            </div>
        </div>
    </div>
    <div class="card-body px-4 pt-3">

        <!-- Stay summary -->
        <div class="row g-2 small mb-4">
            <div class="col-6 col-md-3"><div class="bg-light rounded-3 p-2 text-center"><div class="text-muted">Room</div><div class="fw-semibold"><?php echo htmlspecialchars($b['room_number']); ?></div></div></div>
            <div class="col-6 col-md-3"><div class="bg-light rounded-3 p-2 text-center"><div class="text-muted">Type</div><div class="fw-semibold"><?php echo htmlspecialchars($b['room_type']); ?></div></div></div>
            <div class="col-6 col-md-3"><div class="bg-light rounded-3 p-2 text-center"><div class="text-muted">Check-In</div><div class="fw-semibold"><?php echo date('d M Y', strtotime($b['check_in'])); ?></div></div></div>
            <div class="col-6 col-md-3"><div class="bg-light rounded-3 p-2 text-center"><div class="text-muted">Check-Out</div><div class="fw-semibold <?php echo $is_overdue ? 'text-danger' : ''; ?>"><?php echo date('d M Y', strtotime($b['check_out'])); ?></div></div></div>
        </div>

        <div class="row g-4">

            <!-- LEFT COL: Service charges -->
            <div class="col-lg-6">
                <h6 class="fw-semibold text-muted text-uppercase small mb-3">Service Charges</h6>

                <?php if (empty($services)): ?>
                    <p class="text-muted small mb-3">No service charges recorded yet.</p>
                <?php else: ?>
                <div class="table-responsive mb-3">
                <table class="table table-sm table-bordered rounded-3 mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Type</th>
                            <th>Description</th>
                            <th class="text-end">Charge</th>
                            <th class="text-center">Remove</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($services as $svc): ?>
                        <tr>
                            <td class="small">
                                <?php echo ucwords(str_replace('_', ' ', $svc['type'])); ?>
                                <?php if ($svc['added_by'] === 'receptionist'): ?>
                                    <span class="badge bg-secondary rounded-pill ms-1" style="font-size:10px;">Staff</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?php echo htmlspecialchars(substr($svc['description'], 0, 40)); ?><?php echo strlen($svc['description']) > 40 ? '...' : ''; ?></td>
                            <td class="text-end small fw-semibold">৳<?php echo number_format($svc['charge']); ?></td>
                            <td class="text-center">
                                <form method="POST" onsubmit="return confirm('Remove this charge?');">
                                    <input type="hidden" name="remove_service_id" value="<?php echo $svc['service_id']; ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-2 py-0" style="font-size:11px;">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="2" class="fw-semibold small">Total Service Charges</td>
                            <td class="text-end fw-semibold small">৳<?php echo number_format($svc_total); ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                </div>
                <?php endif; ?>

                <!-- Add service charge form -->
                <div class="border rounded-3 p-3 bg-light">
                    <p class="fw-semibold small mb-2">Add Manual Charge</p>
                    <form method="POST" class="row g-2">
                        <input type="hidden" name="add_service_booking_id" value="<?php echo $booking_id; ?>">
                        <div class="col-12">
                            <select name="svc_type" class="form-select form-select-sm rounded-3">
                                <option value="food">Food</option>
                                <option value="housekeeping">Housekeeping</option>
                                <option value="extra_towel">Extra Towel</option>
                                <option value="laundry">Laundry</option>
                                <option value="other" selected>Other</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <input type="text" name="svc_desc" class="form-control form-control-sm rounded-3"
                                placeholder="Description (e.g. Lunch x2 by phone)" required>
                        </div>
                        <div class="col-8">
                            <input type="number" name="svc_charge" class="form-control form-control-sm rounded-3"
                                placeholder="Amount (৳)" min="1" step="0.01" required>
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-primary btn-sm rounded-pill w-100">Add</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- RIGHT COL: Bill & checkout -->
            <div class="col-lg-6">
                <h6 class="fw-semibold text-muted text-uppercase small mb-3">Bill &amp; Check-Out</h6>

                <form method="POST" id="checkoutForm_<?php echo $booking_id; ?>">
                    <input type="hidden" name="checkout_booking_id" value="<?php echo $booking_id; ?>">

                    <!-- Live bill preview -->
                    <div class="bg-light rounded-3 p-3 mb-3" id="billPreview_<?php echo $booking_id; ?>"
                        data-room-charge="<?php echo $room_charge; ?>"
                        data-svc-total="<?php echo $svc_total; ?>"
                        data-already-paid="<?php echo $already_paid; ?>">
                        <ul class="list-unstyled small mb-0">
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Room Charge (<?php echo $nights; ?> nights)</span>
                                <span>৳<?php echo number_format($room_charge); ?></span>
                            </li>
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Service Charges</span>
                                <span id="svcDisplay_<?php echo $booking_id; ?>">৳<?php echo number_format($svc_total); ?></span>
                            </li>
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Discount</span>
                                <span class="text-success" id="discDisplay_<?php echo $booking_id; ?>">৳0</span>
                            </li>
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Tax (5%)</span>
                                <span id="taxDisplay_<?php echo $booking_id; ?>">৳<?php echo number_format(round(($room_charge + $svc_total) * 0.05, 2)); ?></span>
                            </li>
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="fw-bold">Grand Total</span>
                                <span class="fw-bold text-primary" id="grandDisplay_<?php echo $booking_id; ?>">৳<?php echo number_format(round(($room_charge + $svc_total) * 1.05, 2)); ?></span>
                            </li>
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Already Paid (Online)</span>
                                <span class="text-success">৳<?php echo number_format($already_paid); ?></span>
                            </li>
                            <li class="d-flex justify-content-between py-2">
                                <span class="fw-bold text-danger">Cash to Collect</span>
                                <span class="fw-bold text-danger fs-5" id="cashDisplay_<?php echo $booking_id; ?>">৳<?php echo number_format(max(0, round(($room_charge + $svc_total) * 1.05 - $already_paid, 2))); ?></span>
                            </li>
                        </ul>
                    </div>

                    <!-- Discount input -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Discount (৳)</label>
                        <input type="number" name="discount"
                            id="discInput_<?php echo $booking_id; ?>"
                            class="form-control rounded-3 discount-input"
                            data-booking="<?php echo $booking_id; ?>"
                            data-room-charge="<?php echo $room_charge; ?>"
                            data-svc-total="<?php echo $svc_total; ?>"
                            data-already-paid="<?php echo $already_paid; ?>"
                            min="0" value="0" step="0.01"
                            placeholder="0">
                        <div class="form-text">Applied before tax. Leave 0 for no discount.</div>
                    </div>

                    <!-- Cash collected -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Cash Collected from Guest (৳)</label>
                        <input type="number" name="cash_collected"
                            id="cashInput_<?php echo $booking_id; ?>"
                            class="form-control rounded-3"
                            min="0" step="0.01"
                            value="<?php echo max(0, round(($room_charge + $svc_total) * 1.05 - $already_paid, 2)); ?>"
                            required>
                        <div class="form-text">Actual cash received. Payment will be marked as cleared.</div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger rounded-pill fw-semibold flex-grow-1"
                            onclick="return confirm('Process check-out for <?php echo htmlspecialchars(addslashes($b['guest_name'])); ?>? This cannot be undone.');">
                            Process Check-Out
                        </button>
                        <a href="generate_invoice.php?booking_id=<?php echo $booking_id; ?>"
                            class="btn btn-outline-secondary rounded-pill btn-sm">
                            Preview Invoice
                        </a>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
<?php endwhile; ?>
<?php endif; ?>

<script>
// Live bill calculation when discount changes
document.querySelectorAll('.discount-input').forEach(function(input) {
    input.addEventListener('input', function() {
        const bid        = this.dataset.booking;
        const roomCharge = parseFloat(this.dataset.roomCharge) || 0;
        const svcTotal   = parseFloat(this.dataset.svcTotal)   || 0;
        const alreadyPaid= parseFloat(this.dataset.alreadyPaid)|| 0;
        let   discount   = Math.max(0, parseFloat(this.value)  || 0);
        const subtotal   = roomCharge + svcTotal;
        // Cap discount at subtotal
        if (discount > subtotal) { discount = subtotal; this.value = subtotal; }
        const taxable    = subtotal - discount;
        const tax        = Math.round(taxable * 0.05 * 100) / 100;
        const grand      = taxable + tax;
        const cashNeeded = Math.max(0, grand - alreadyPaid);

        document.getElementById('discDisplay_' + bid).textContent = '৳' + discount.toLocaleString('en-BD');
        document.getElementById('taxDisplay_'  + bid).textContent = '৳' + tax.toLocaleString('en-BD');
        document.getElementById('grandDisplay_'+ bid).textContent = '৳' + grand.toLocaleString('en-BD');
        document.getElementById('cashDisplay_' + bid).textContent = '৳' + cashNeeded.toLocaleString('en-BD');
        document.getElementById('cashInput_'   + bid).value       = cashNeeded.toFixed(2);
    });
});
</script>

<?php
// manage_bookings_content.php

$status_filter  = $_GET['status']  ?? '';
$search         = trim($_GET['search'] ?? '');
$date_filter    = $_GET['date']    ?? '';
$date_type      = $_GET['date_type'] ?? 'check_in';
$allowed_status = ['Pending','Confirmed','Checked-In','Checked-Out','Cancelled'];
if (!in_array($status_filter, $allowed_status)) $status_filter = '';
if (!in_array($date_type, ['check_in','check_out','booked_at'])) $date_type = 'check_in';

$where = []; $params = []; $types = '';
if ($status_filter !== '') { $where[] = "b.status = ?"; $params[] = $status_filter; $types .= 's'; }
if ($search !== '') {
    $where[]  = "(u.Name LIKE ? OR u.Email LIKE ? OR r.room_number LIKE ? OR b.booking_id = ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = (int)$search;
    $types   .= 'sssi';
}
if ($date_filter !== '') {
    $where[]  = "DATE(b.{$date_type}) = ?";
    $params[] = $date_filter;
    $types   .= 's';
}

$sql = "
    SELECT b.booking_id, b.check_in, b.check_out, b.status, b.payment_status,
           b.pay_amount, b.due_amount, b.booked_at, b.refund_status,
           u.Name AS guest_name, u.Email AS guest_email, u.Phone AS guest_phone,
           r.room_number, r.type AS room_type
    FROM booking b
    JOIN user u ON b.guest_id = u.User_id
    JOIN room r ON b.room_id = r.room_id
";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY b.booked_at DESC";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$bookings = $stmt->get_result();
$stmt->close();

$stats = $conn->query("
    SELECT COUNT(*) AS total,
           SUM(status='Pending')     AS pending,
           SUM(status='Confirmed')   AS confirmed,
           SUM(status='Checked-In')  AS checkedin,
           SUM(status='Checked-Out') AS checkedout,
           SUM(status='Cancelled')   AS cancelled
    FROM booking
")->fetch_assoc();
?>

<!-- STATS -->
<div class="row g-3 mb-4">
<?php
$stat_items = [
    ['label'=>'Total',       'val'=>$stats['total'],      'color'=>'primary',  'filter'=>''],
    ['label'=>'Pending',     'val'=>$stats['pending'],    'color'=>'warning',  'filter'=>'Pending'],
    ['label'=>'Confirmed',   'val'=>$stats['confirmed'],  'color'=>'info',     'filter'=>'Confirmed'],
    ['label'=>'Checked-In',  'val'=>$stats['checkedin'],  'color'=>'success',  'filter'=>'Checked-In'],
    ['label'=>'Checked-Out', 'val'=>$stats['checkedout'], 'color'=>'secondary','filter'=>'Checked-Out'],
    ['label'=>'Cancelled',   'val'=>$stats['cancelled'],  'color'=>'danger',   'filter'=>'Cancelled'],
];
foreach ($stat_items as $si):
    $active = $status_filter === $si['filter'] ? 'border-2 border-'.$si['color'] : '';
?>
<div class="col-6 col-md-2">
    <a href="?tab=bookings&status=<?php echo urlencode($si['filter']); ?>" class="text-decoration-none">
        <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100 <?php echo $active; ?>">
            <div class="fw-bold fs-4 text-<?php echo $si['color']; ?>"><?php echo $si['val']; ?></div>
            <div class="text-muted small"><?php echo $si['label']; ?></div>
        </div>
    </a>
</div>
<?php endforeach; ?>
</div>

<!-- FILTER BAR -->
<div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <input type="hidden" name="tab" value="bookings">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Search</label>
            <input type="text" name="search" class="form-control rounded-3"
                placeholder="Name, email, room, booking ID"
                value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Status</label>
            <select name="status" class="form-select rounded-3">
                <option value="">All Statuses</option>
                <?php foreach ($allowed_status as $s): ?>
                <option value="<?php echo $s; ?>" <?php echo $status_filter===$s?'selected':''; ?>><?php echo $s; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Date Type</label>
            <select name="date_type" class="form-select rounded-3">
                <option value="check_in"  <?php echo $date_type==='check_in'  ?'selected':'';?>>Check-In</option>
                <option value="check_out" <?php echo $date_type==='check_out' ?'selected':'';?>>Check-Out</option>
                <option value="booked_at" <?php echo $date_type==='booked_at' ?'selected':'';?>>Booked At</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Date</label>
            <input type="date" name="date" class="form-control rounded-3"
                value="<?php echo htmlspecialchars($date_filter); ?>">
        </div>
        <div class="col-md-3 d-flex gap-2 align-items-end">
            <button type="submit" class="btn btn-primary rounded-pill flex-grow-1">Filter</button>
            <a href="?tab=bookings" class="btn btn-outline-secondary rounded-pill">Clear</a>
        </div>
    </form>
</div>

<!-- TABLE -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <?php if ($bookings->num_rows === 0): ?>
            <div class="text-center py-5 text-muted">
                <p class="mb-0">No bookings found matching your criteria.</p>
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
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th class="pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($b = $bookings->fetch_assoc()):
                $total = $b['pay_amount'] + $b['due_amount'];
                $status_badge = match($b['status']) {
                    'Pending'     => 'bg-warning text-dark',
                    'Confirmed'   => 'bg-primary',
                    'Checked-In'  => 'bg-success',
                    'Checked-Out' => 'bg-secondary',
                    'Cancelled'   => 'bg-danger',
                    default       => 'bg-secondary'
                };
                $pay_badge = match($b['payment_status']) {
                    'paid'    => 'bg-success',
                    'partial' => 'bg-warning text-dark',
                    'unpaid'  => 'bg-danger',
                    default   => 'bg-secondary'
                };
            ?>
            <tr>
                <td class="ps-4 small text-muted">#<?php echo $b['booking_id']; ?></td>
                <td>
                    <div class="fw-semibold small"><?php echo htmlspecialchars($b['guest_name']); ?></div>
                    <div class="text-muted" style="font-size:11px;"><?php echo htmlspecialchars($b['guest_email']); ?></div>
                    <?php if ($b['guest_phone']): ?>
                    <div class="text-muted" style="font-size:11px;"><?php echo htmlspecialchars($b['guest_phone']); ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="fw-semibold small"><?php echo htmlspecialchars($b['room_number']); ?></div>
                    <div class="text-muted" style="font-size:11px;"><?php echo htmlspecialchars($b['room_type']); ?></div>
                </td>
                <td class="small"><?php echo date('d M Y', strtotime($b['check_in'])); ?></td>
                <td class="small"><?php echo date('d M Y', strtotime($b['check_out'])); ?></td>
                <td class="small">
                    <div class="fw-semibold">৳<?php echo number_format($total); ?></div>
                    <div class="text-success" style="font-size:11px;">Paid: ৳<?php echo number_format($b['pay_amount']); ?></div>
                    <?php if ($b['due_amount'] > 0): ?>
                    <div class="text-danger" style="font-size:11px;">Due: ৳<?php echo number_format($b['due_amount']); ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge <?php echo $status_badge; ?> rounded-pill"><?php echo $b['status']; ?></span>
                    <?php if ($b['refund_status'] === 'requested'): ?>
                    <div><span class="badge bg-info text-dark rounded-pill mt-1" style="font-size:10px;">Refund Req.</span></div>
                    <?php endif; ?>
                </td>
                <td><span class="badge <?php echo $pay_badge; ?> rounded-pill"><?php echo ucfirst($b['payment_status']); ?></span></td>
                <td class="pe-4">
                    <div class="d-flex gap-1 flex-wrap">
                        <?php if ($b['status'] === 'Confirmed'): ?>
                        <a href="?tab=checkin" class="btn btn-primary btn-sm rounded-pill" style="font-size:11px;">Check-In</a>
                        <?php endif; ?>
                        <?php if ($b['status'] === 'Checked-In'): ?>
                        <a href="?tab=checkout" class="btn btn-danger btn-sm rounded-pill" style="font-size:11px;">Check-Out</a>
                        <?php endif; ?>
                        <?php if (in_array($b['status'], ['Confirmed','Checked-In','Checked-Out'])): ?>
                        <a href="generate_invoice.php?booking_id=<?php echo $b['booking_id']; ?>"
                            class="btn btn-outline-secondary btn-sm rounded-pill" style="font-size:11px;">Invoice</a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
        <div class="px-4 py-2 text-muted small border-top">
            Showing <?php echo $bookings->num_rows; ?> booking(s)
        </div>
        <?php endif; ?>
    </div>
</div>

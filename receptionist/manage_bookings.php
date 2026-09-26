<?php
// receptionist/manage_bookings.php

if (session_status() === PHP_SESSION_NONE) session_start();
include '../db.php';
include '../auth.php';

if ($_SESSION['role'] !== 'receptionist' && $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php"); exit();
}

// Filters
$status_filter  = $_GET['status']  ?? '';
$search         = trim($_GET['search'] ?? '');
$date_filter    = $_GET['date']    ?? '';

$allowed_status = ['Pending','Confirmed','Checked-In','Checked-Out','Cancelled'];
if (!in_array($status_filter, $allowed_status)) $status_filter = '';

// Build query
$where   = [];
$params  = [];
$types   = '';

if ($status_filter !== '') {
    $where[] = "b.status = ?";
    $params[] = $status_filter;
    $types   .= 's';
}
if ($search !== '') {
    $where[] = "(u.Name LIKE ? OR u.Email LIKE ? OR r.room_number LIKE ? OR b.booking_id = ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = (int)$search;
    $types   .= 'sssi';
}
if ($date_filter !== '') {
    $where[] = "(b.check_in = ? OR b.check_out = ?)";
    $params[] = $date_filter; $params[] = $date_filter;
    $types   .= 'ss';
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
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$bookings = $stmt->get_result();
$stmt->close();

// Stats
$stats = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(status='Pending')    AS pending,
        SUM(status='Confirmed')  AS confirmed,
        SUM(status='Checked-In') AS checkedin,
        SUM(status='Checked-Out')AS checkedout,
        SUM(status='Cancelled')  AS cancelled
    FROM booking
")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Bookings — Meghdoot Resort</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/css/bootstrap.min.css"
        onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'">
    <?php include '../global_css.php'; ?>
</head>
<body class="bg-light">
<?php include '../navbar.php'; ?>

<div class="bg-primary text-white py-4 text-center">
    <div class="container">
        <h2 class="fw-bold mb-0"> All Bookings</h2>
        <p class="fw-light mb-0 small">View and manage every reservation</p>
    </div>
</div>

<section class="py-4">
<div class="container">

<!-- STATS ROW -->
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
    $active = ($status_filter === $si['filter']) ? 'border-2 border-' . $si['color'] : '';
    ?>
    <div class="col-6 col-md-2">
        <a href="?status=<?php echo urlencode($si['filter']); ?>" class="text-decoration-none">
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
        <div class="col-md-4">
            <label class="form-label small fw-semibold mb-1">Search</label>
            <input type="text" name="search" class="form-control rounded-3" placeholder="Guest name, email, room, #ID"
                value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Status</label>
            <select name="status" class="form-select rounded-3">
                <option value="">All Statuses</option>
                <?php foreach ($allowed_status as $s): ?>
                <option value="<?php echo $s; ?>" <?php echo $status_filter === $s ? 'selected' : ''; ?>>
                    <?php echo $s; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Date</label>
            <input type="date" name="date" class="form-control rounded-3"
                value="<?php echo htmlspecialchars($date_filter); ?>">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary rounded-pill flex-grow-1">Filter</button>
            <a href="manage_bookings.php" class="btn btn-outline-secondary rounded-pill">x</a>
        </div>
    </form>
</div>

<!-- BOOKINGS TABLE -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <?php if ($bookings->num_rows === 0): ?>
            <div class="text-center py-5 text-muted">
                <div class="fs-1"></div>
                <p class="mb-0">No bookings found matching your filters.</p>
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
                $nights = (int)((strtotime($b['check_out']) - strtotime($b['check_in'])) / 86400);
                $total  = $b['pay_amount'] + $b['due_amount'];

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
                    <span class="fw-semibold small"><?php echo htmlspecialchars($b['room_number']); ?></span>
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
                    <span class="badge <?php echo $status_badge; ?> rounded-pill">
                        <?php echo $b['status']; ?>
                    </span>
                    <?php if ($b['refund_status'] === 'requested'): ?>
                    <div><span class="badge bg-info text-dark rounded-pill mt-1" style="font-size:10px;">Refund Req.</span></div>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge <?php echo $pay_badge; ?> rounded-pill">
                        <?php echo ucfirst($b['payment_status']); ?>
                    </span>
                </td>
                <td class="pe-4">
                    <div class="d-flex gap-1 flex-wrap">
                        <?php if ($b['status'] === 'Confirmed'): ?>
                        <a href="checkin.php" class="btn btn-primary btn-sm rounded-pill" style="font-size:11px;">
                            Check-In
                        </a>
                        <?php endif; ?>
                        <?php if ($b['status'] === 'Checked-In'): ?>
                        <a href="checkout.php" class="btn btn-danger btn-sm rounded-pill" style="font-size:11px;">
                            Check-Out
                        </a>
                        <?php endif; ?>
                        <?php if (in_array($b['status'], ['Checked-Out','Confirmed','Checked-In'])): ?>
                        <a href="generate_invoice.php?booking_id=<?php echo $b['booking_id']; ?>"
                            class="btn btn-outline-secondary btn-sm rounded-pill" style="font-size:11px;">
                             Invoice
                        </a>
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

</div>
</section>

<?php include '../footer.php'; ?>
<script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"
    onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js'">
</script>
</body>
</html>

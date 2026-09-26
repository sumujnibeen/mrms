<?php
// receptionist/generate_invoice.php
// View, print, or generate invoice for any booking

if (session_status() === PHP_SESSION_NONE) session_start();
include '../db.php';
include '../auth.php';

if ($_SESSION['role'] !== 'receptionist' && $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php"); exit();
}

$booking_id = (int)($_GET['booking_id'] ?? 0);

// If no booking_id given — show search form
if ($booking_id === 0) {
    // Search by booking ID or guest name
    $search  = trim($_GET['search'] ?? '');
    $results = null;
    if ($search !== '') {
        $s = $conn->prepare("
            SELECT b.booking_id, b.check_in, b.check_out, b.status, b.pay_amount, b.due_amount,
                   u.Name AS guest_name, r.room_number, r.type AS room_type
            FROM booking b
            JOIN user u ON b.guest_id = u.User_id
            JOIN room r ON b.room_id = r.room_id
            WHERE u.Name LIKE ? OR b.booking_id = ?
            ORDER BY b.booked_at DESC LIMIT 20
        ");
        $like = "%$search%";
        $id   = (int)$search;
        $s->bind_param("si", $like, $id);
        $s->execute();
        $results = $s->get_result();
        $s->close();
    }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Invoice — Meghdoot Resort</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/css/bootstrap.min.css"
        onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'">
    <?php include '../global_css.php'; ?>
</head>
<body class="bg-light">
<?php include '../navbar.php'; ?>

<div class="bg-primary text-white py-4 text-center">
    <div class="container">
        <h2 class="fw-bold mb-0"> Generate Invoice</h2>
        <p class="fw-light mb-0 small">Search a booking to view or print its invoice</p>
    </div>
</div>

<section class="py-5">
<div class="container">
<div class="row justify-content-center">
<div class="col-lg-7">

    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control rounded-3"
                placeholder="Enter booking ID or guest name..."
                value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary rounded-pill px-4">Search</button>
        </form>
    </div>

    <?php if ($results !== null): ?>
        <?php if ($results->num_rows === 0): ?>
            <div class="alert alert-warning rounded-3">No bookings found for "<strong><?php echo htmlspecialchars($search); ?></strong>".</div>
        <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-0">
            <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">#</th>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Check-Out</th>
                        <th>Total</th>
                        <th class="pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($r = $results->fetch_assoc()): ?>
                <tr>
                    <td class="ps-4 small text-muted">#<?php echo $r['booking_id']; ?></td>
                    <td class="small fw-semibold"><?php echo htmlspecialchars($r['guest_name']); ?></td>
                    <td class="small"><?php echo htmlspecialchars($r['room_number']); ?> — <?php echo htmlspecialchars($r['room_type']); ?></td>
                    <td class="small"><?php echo date('d M Y', strtotime($r['check_out'])); ?></td>
                    <td class="small fw-semibold">৳<?php echo number_format($r['pay_amount'] + $r['due_amount']); ?></td>
                    <td class="pe-4">
                        <a href="generate_invoice.php?booking_id=<?php echo $r['booking_id']; ?>"
                            class="btn btn-outline-primary btn-sm rounded-pill">View Invoice</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>

</div>
</div>
</div>
</section>

<?php include '../footer.php'; ?>
<script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
    <?php
    exit();
}

// ── FETCH BOOKING + INVOICE DATA ─────────────────────────
$stmt = $conn->prepare("
    SELECT b.*, r.room_number, r.type AS room_type, r.price_per_night, r.floor,
           u.Name AS guest_name, u.Email AS guest_email, u.Phone AS guest_phone,
           i.invoice_id, i.room_charge, i.service_charge, i.tax, i.total AS inv_total, i.issued_at
    FROM booking b
    JOIN room r ON b.room_id = r.room_id
    JOIN user u ON b.guest_id = u.User_id
    LEFT JOIN invoice i ON i.booking_id = b.booking_id
    WHERE b.booking_id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    echo "<div class='container py-5 text-center text-muted'>Booking not found.</div>";
    exit();
}

// Service requests
$svc_stmt = $conn->prepare("
    SELECT type, description, charge, requested_at, status
    FROM service_request WHERE booking_id = ? ORDER BY requested_at ASC
");
$svc_stmt->bind_param("i", $booking_id);
$svc_stmt->execute();
$services = $svc_stmt->get_result();
$svc_stmt->close();

// Payment history
$pay_stmt = $conn->prepare("
    SELECT amount, method, status, transaction_id, paid_at
    FROM payment WHERE booking_id = ? ORDER BY paid_at ASC
");
$pay_stmt->bind_param("i", $booking_id);
$pay_stmt->execute();
$payments = $pay_stmt->get_result();
$pay_stmt->close();

$nights      = (int)((strtotime($data['check_out']) - strtotime($data['check_in'])) / 86400);
$room_charge = $data['room_charge'] ?? ($data['pay_amount'] + $data['due_amount']);
$svc_charge  = $data['service_charge'] ?? 0;
$tax         = $data['tax'] ?? round(($room_charge + $svc_charge) * 0.05, 2);
$total       = $data['inv_total'] ?? ($room_charge + $svc_charge + $tax);
$issued      = $data['issued_at'] ?? date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $booking_id; ?> — Meghdoot Resort</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/css/bootstrap.min.css"
        onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'">
    <?php include '../global_css.php'; ?>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .card { box-shadow: none !important; border: 1px solid #ddd !important; }
        }
        .invoice-header { background: linear-gradient(135deg, #0d6efd, #0a58ca); }
    </style>
</head>
<body class="bg-light">
<?php include '../navbar.php'; ?>

<!-- Action Bar -->
<div class="bg-white border-bottom py-3 no-print">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <a href="manage_bookings.php" class="btn btn-outline-secondary rounded-pill btn-sm">
            ← Back to Bookings
        </a>
        <div class="d-flex gap-2">
            <a href="generate_invoice.php" class="btn btn-outline-primary rounded-pill btn-sm">
                 Search Another
            </a>
            <button onclick="window.print()" class="btn btn-primary rounded-pill btn-sm">
                 Print Invoice
            </button>
        </div>
    </div>
</div>

<section class="py-4">
<div class="container">
<div class="row justify-content-center">
<div class="col-lg-8">

    <!-- INVOICE CARD -->
    <div class="card border-0 shadow rounded-4 overflow-hidden">

        <!-- Invoice Header -->
        <div class="invoice-header text-white p-4">
            <div class="row align-items-center">
                <div class="col-7">
                    <h3 class="fw-bold mb-0">Meghdoot Resort</h3>
                    <p class="mb-0 opacity-75 small">Kishoreganj, Bangladesh</p>
                    <p class="mb-0 opacity-75 small">+880 1700-000000 · info@meghdootresort.com</p>
                </div>
                <div class="col-5 text-end">
                    <div class="opacity-75 small">INVOICE</div>
                    <h4 class="fw-bold mb-0">#<?php echo str_pad($booking_id, 5, '0', STR_PAD_LEFT); ?></h4>
                    <div class="opacity-75 small"><?php echo date('d M Y', strtotime($issued)); ?></div>
                    <?php
                    $status_color = match ($data['status']) {
                        'Checked-Out' => 'success',
                        'Checked-In'  => 'warning',
                        default       => 'light'
                    };
                    ?>
                    <span class="badge bg-<?php echo $status_color; ?> text-dark mt-1">
                        <?php echo $data['status']; ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="card-body p-4">

            <!-- Guest & Stay Info -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <h6 class="text-muted text-uppercase small fw-bold mb-2">Guest Details</h6>
                    <p class="fw-bold mb-1"><?php echo htmlspecialchars($data['guest_name']); ?></p>
                    <p class="text-muted small mb-1"><?php echo htmlspecialchars($data['guest_email']); ?></p>
                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($data['guest_phone'] ?? 'N/A'); ?></p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted text-uppercase small fw-bold mb-2">Stay Details</h6>
                    <p class="fw-bold mb-1">
                        <?php echo htmlspecialchars($data['room_type']); ?> — Room <?php echo htmlspecialchars($data['room_number']); ?>
                        <span class="text-muted fw-light small">(Floor <?php echo htmlspecialchars($data['floor']); ?>)</span>
                    </p>
                    <p class="text-muted small mb-1">
                        Check-In: <strong><?php echo date('d M Y', strtotime($data['check_in'])); ?></strong>
                    </p>
                    <p class="text-muted small mb-1">
                        Check-Out: <strong><?php echo date('d M Y', strtotime($data['check_out'])); ?></strong>
                    </p>
                    <p class="text-muted small mb-0">
                        Duration: <strong><?php echo $nights; ?> night(s)</strong>
                    </p>
                </div>
            </div>

            <!-- Charges Table -->
            <h6 class="text-muted text-uppercase small fw-bold mb-3">Charge Breakdown</h6>
            <table class="table table-sm table-bordered rounded-3 overflow-hidden mb-4">
                <thead class="table-light">
                    <tr>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            Room Charge — <?php echo htmlspecialchars($data['room_type']); ?> Room
                            <div class="text-muted small">৳<?php echo number_format($data['price_per_night']); ?>/night × <?php echo $nights; ?> nights</div>
                        </td>
                        <td class="text-end fw-semibold">৳<?php echo number_format($room_charge); ?></td>
                    </tr>

                    <?php
                    // Service line items
                    $services->data_seek(0);
                    while ($svc = $services->fetch_assoc()):
                        if ($svc['charge'] <= 0) continue;
                        $type_label = ucwords(str_replace('_', ' ', $svc['type']));
                    ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($type_label); ?>
                            <div class="text-muted small"><?php echo htmlspecialchars(substr($svc['description'], 0, 60)); ?></div>
                        </td>
                        <td class="text-end">৳<?php echo number_format($svc['charge']); ?></td>
                    </tr>
                    <?php endwhile; ?>

                    <?php if ($svc_charge == 0): ?>
                    <tr>
                        <td class="text-muted small">Service Charges</td>
                        <td class="text-end text-muted">৳0</td>
                    </tr>
                    <?php endif; ?>

                    <tr class="table-light">
                        <td class="text-muted small">Tax (5%)</td>
                        <td class="text-end">৳<?php echo number_format($tax); ?></td>
                    </tr>
                    <tr class="table-primary">
                        <td class="fw-bold">Grand Total</td>
                        <td class="text-end fw-bold fs-5">৳<?php echo number_format($total); ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Payment History -->
            <h6 class="text-muted text-uppercase small fw-bold mb-3">Payment History</h6>
            <?php $payments->data_seek(0); ?>
            <?php if ($payments->num_rows === 0): ?>
                <p class="text-muted small">No payments recorded.</p>
            <?php else: ?>
            <table class="table table-sm table-bordered mb-4">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Transaction ID</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($p = $payments->fetch_assoc()): ?>
                <tr>
                    <td class="small"><?php echo date('d M Y, h:i A', strtotime($p['paid_at'])); ?></td>
                    <td class="small"><?php echo htmlspecialchars($p['method']); ?></td>
                    <td class="small text-muted"><?php echo htmlspecialchars($p['transaction_id'] ?? '—'); ?></td>
                    <td class="text-end small fw-semibold text-success">৳<?php echo number_format($p['amount']); ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- Summary footer -->
            <div class="row">
                <div class="col-md-6 offset-md-6">
                    <ul class="list-unstyled small">
                        <li class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Total Paid</span>
                            <span class="fw-semibold text-success">৳<?php echo number_format($data['pay_amount']); ?></span>
                        </li>
                        <li class="d-flex justify-content-between py-2">
                            <span class="fw-bold <?php echo $data['due_amount'] > 0 ? 'text-danger' : 'text-success'; ?>">
                                <?php echo $data['due_amount'] > 0 ? 'Balance Due' : 'Fully Paid'; ?>
                            </span>
                            <span class="fw-bold <?php echo $data['due_amount'] > 0 ? 'text-danger' : 'text-success'; ?> fs-5">
                                ৳<?php echo number_format($data['due_amount']); ?>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Thank you note -->
            <div class="text-center mt-4 pt-3 border-top text-muted small">
                <p class="mb-1">Thank you for staying at <strong>Meghdoot Resort</strong>! We hope to see you again.</p>
                <p class="mb-0">For queries: info@meghdootresort.com · +880 1700-000000</p>
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

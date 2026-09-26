<?php
// admin/finance.php
// Financial overview — read-only, admin only

if (session_status() === PHP_SESSION_NONE) session_start();
include '../db.php';
include '../auth.php';

if (!in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: ../index.php");
    exit();
}

// ── DATE FILTER ───────────────────────────────────────────
$range      = $_GET['range']   ?? 'all';
$date_from  = $_GET['from']    ?? '';
$date_to    = $_GET['to']      ?? '';

$today      = date('Y-m-d');
$where_pay  = '';
$where_book = '';

switch ($range) {
    case 'today':
        $where_pay  = "WHERE DATE(p.paid_at) = '$today'";
        $where_book = "WHERE DATE(b.booked_at) = '$today'";
        break;
    case 'week':
        $where_pay  = "WHERE p.paid_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $where_book = "WHERE b.booked_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $where_pay  = "WHERE MONTH(p.paid_at) = MONTH(NOW()) AND YEAR(p.paid_at) = YEAR(NOW())";
        $where_book = "WHERE MONTH(b.booked_at) = MONTH(NOW()) AND YEAR(b.booked_at) = YEAR(NOW())";
        break;
    case 'custom':
        if ($date_from && $date_to) {
            $df = $conn->real_escape_string($date_from);
            $dt = $conn->real_escape_string($date_to);
            $where_pay  = "WHERE DATE(p.paid_at) BETWEEN '$df' AND '$dt'";
            $where_book = "WHERE DATE(b.booked_at) BETWEEN '$df' AND '$dt'";
        }
        break;
}

// ── SUMMARY STATS ─────────────────────────────────────────
$stats = $conn->query("
    SELECT
        COALESCE(SUM(p.amount), 0)                                          AS total_received,
        COALESCE(SUM(CASE WHEN p.method = 'SSLCommerz' THEN p.amount END), 0) AS online_received,
        COALESCE(SUM(CASE WHEN p.method = 'Cash'       THEN p.amount END), 0) AS cash_received,
        COUNT(DISTINCT p.booking_id)                                         AS paying_bookings
    FROM payment p
    $where_pay
")->fetch_assoc();

$invoice_stats = $conn->query("
    SELECT
        COALESCE(SUM(i.total), 0)          AS total_billed,
        COALESCE(SUM(i.room_charge), 0)    AS room_revenue,
        COALESCE(SUM(i.service_charge), 0) AS service_revenue,
        COALESCE(SUM(i.tax), 0)            AS tax_collected,
        COALESCE(SUM(i.discount), 0)       AS total_discounts,
        COUNT(*)                            AS invoices_issued
    FROM invoice i
    JOIN booking b ON i.booking_id = b.booking_id
    $where_book
")->fetch_assoc();

$pending_due = $conn->query("
    SELECT COALESCE(SUM(due_amount), 0) AS pending
    FROM booking
    WHERE status IN ('Confirmed','Checked-In')
    AND payment_status IN ('partial','unpaid')
")->fetch_assoc()['pending'];

$refund_stats = $conn->query("
    SELECT
        COALESCE(SUM(CASE WHEN refund_status='requested' THEN pay_amount * refund_percent / 100 END), 0) AS pending_refunds,
        COALESCE(SUM(CASE WHEN refund_status='approved'  THEN pay_amount * refund_percent / 100 END), 0) AS approved_refunds,
        COUNT(CASE WHEN refund_status='requested' THEN 1 END)                                            AS refund_count
    FROM booking
")->fetch_assoc();

// ── PAYMENT TRANSACTIONS LOG ──────────────────────────────
$transactions = $conn->query("
    SELECT p.payment_id, p.booking_id, p.amount, p.method, p.transaction_id, p.paid_at,
           u.Name AS guest_name,
           r.room_number, r.type AS room_type,
           b.check_in, b.check_out
    FROM payment p
    JOIN booking b ON p.booking_id = b.booking_id
    JOIN user u    ON b.guest_id   = u.User_id
    JOIN room r    ON b.room_id    = r.room_id
    " . ($where_pay ?: '') . "
    ORDER BY p.paid_at DESC
    LIMIT 200
");

// ── DISCOUNT LOG (from invoices) ─────────────────────────
$discounts = $conn->query("
    SELECT i.invoice_id, i.booking_id, i.discount, i.room_charge, i.service_charge,
           i.tax, i.total, i.issued_at,
           u.Name AS guest_name,
           r.room_number, r.type AS room_type
    FROM invoice i
    JOIN booking b ON i.booking_id = b.booking_id
    JOIN user u    ON b.guest_id   = u.User_id
    JOIN room r    ON b.room_id    = r.room_id
    WHERE i.discount > 0
    ORDER BY i.issued_at DESC
");

// ── REFUND LOG ────────────────────────────────────────────
$refunds = $conn->query("
    SELECT b.booking_id, b.refund_status, b.refund_percent,
           b.pay_amount, b.due_amount, b.check_in, b.updated_at,
           ROUND((b.pay_amount - b.due_amount) * b.refund_percent / 100, 2) AS refund_amount,
           u.Name AS guest_name,
           r.room_number, r.type AS room_type
    FROM booking b
    JOIN user u ON b.guest_id = u.User_id
    JOIN room r ON b.room_id  = r.room_id
    WHERE b.refund_status IN ('requested','approved','rejected')
    ORDER BY b.updated_at DESC
");

// ── MONTHLY REVENUE BREAKDOWN ─────────────────────────────
$monthly = $conn->query("
    SELECT
        DATE_FORMAT(p.paid_at, '%Y-%m') AS month_key,
        DATE_FORMAT(p.paid_at, '%b %Y') AS month_label,
        SUM(p.amount)                   AS received,
        SUM(CASE WHEN p.method='SSLCommerz' THEN p.amount ELSE 0 END) AS online,
        SUM(CASE WHEN p.method='Cash'       THEN p.amount ELSE 0 END) AS cash,
        COUNT(DISTINCT p.booking_id)    AS bookings
    FROM payment p
    GROUP BY DATE_FORMAT(p.paid_at, '%Y-%m')
    ORDER BY month_key DESC
    LIMIT 12
");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Overview — Meghdoot Resort</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/css/bootstrap.min.css"
        onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'">
    <?php include '../global_css.php'; ?>
    <style>
    .stat-card {
        border-left: 4px solid transparent;
    }

    .stat-card.green {
        border-color: #198754;
    }

    .stat-card.blue {
        border-color: #0d6efd;
    }

    .stat-card.orange {
        border-color: #fd7e14;
    }

    .stat-card.red {
        border-color: #dc3545;
    }

    .stat-card.purple {
        border-color: #6f42c1;
    }

    .section-title {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #6c757d;
        margin-bottom: 1rem;
    }

    @media print {
        .no-print {
            display: none !important;
        }

        body {
            background: white !important;
        }
    }
    </style>
</head>

<body class="bg-light">
    <?php include '../navbar.php'; ?>

    <div class="bg-primary text-white py-4">
        <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="fw-bold mb-0">Finance Overview</h2>
                <p class="fw-light mb-0 small">Payment tracking, revenue breakdown &amp; discount audit</p>
            </div>
            <button onclick="window.print()" class="btn btn-outline-light rounded-pill btn-sm no-print">
                Print Report
            </button>
        </div>
    </div>

    <section class="py-4">
        <div class="container">

            <!-- DATE FILTER -->
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 no-print">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label small fw-semibold mb-1">Period</label>
                        <select name="range" class="form-select rounded-3 form-select-sm" id="rangeSelect">
                            <option value="all" <?php echo $range === 'all'    ? 'selected' : ''; ?>>All Time</option>
                            <option value="today" <?php echo $range === 'today'  ? 'selected' : ''; ?>>Today</option>
                            <option value="week" <?php echo $range === 'week'   ? 'selected' : ''; ?>>Last 7 Days
                            </option>
                            <option value="month" <?php echo $range === 'month'  ? 'selected' : ''; ?>>This Month
                            </option>
                            <option value="custom" <?php echo $range === 'custom' ? 'selected' : ''; ?>>Custom Range
                            </option>
                        </select>
                    </div>
                    <div class="col-auto custom-range <?php echo $range !== 'custom' ? 'd-none' : ''; ?>"
                        id="customRange">
                        <label class="form-label small fw-semibold mb-1">From</label>
                        <input type="date" name="from" class="form-control form-control-sm rounded-3"
                            value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    <div class="col-auto custom-range <?php echo $range !== 'custom' ? 'd-none' : ''; ?>"
                        id="customRangeTo">
                        <label class="form-label small fw-semibold mb-1">To</label>
                        <input type="date" name="to" class="form-control form-control-sm rounded-3"
                            value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary rounded-pill btn-sm px-4">Apply</button>
                        <a href="finance.php" class="btn btn-outline-secondary rounded-pill btn-sm ms-1">Reset</a>
                    </div>
                </form>
            </div>

            <!-- SUMMARY CARDS -->
            <p class="section-title">Summary</p>
            <div class="row g-3 mb-4">

                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card border-0 shadow-sm rounded-4 p-3 stat-card green h-100">
                        <div class="text-muted small mb-1">Total Received</div>
                        <div class="fw-bold fs-5 text-success">৳<?php echo number_format($stats['total_received']); ?>
                        </div>
                        <div class="text-muted" style="font-size:11px;">Cash + Online</div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card border-0 shadow-sm rounded-4 p-3 stat-card blue h-100">
                        <div class="text-muted small mb-1">Online (SSLCommerz)</div>
                        <div class="fw-bold fs-5 text-primary">৳<?php echo number_format($stats['online_received']); ?>
                        </div>
                        <div class="text-muted" style="font-size:11px;">Advance payments</div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card border-0 shadow-sm rounded-4 p-3 stat-card orange h-100">
                        <div class="text-muted small mb-1">Cash at Desk</div>
                        <div class="fw-bold fs-5 text-warning">৳<?php echo number_format($stats['cash_received']); ?>
                        </div>
                        <div class="text-muted" style="font-size:11px;">Collected at check-out</div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card border-0 shadow-sm rounded-4 p-3 stat-card red h-100">
                        <div class="text-muted small mb-1">Pending Due</div>
                        <div class="fw-bold fs-5 text-danger">৳<?php echo number_format($pending_due); ?></div>
                        <div class="text-muted" style="font-size:11px;">Active bookings</div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card border-0 shadow-sm rounded-4 p-3 stat-card purple h-100">
                        <div class="text-muted small mb-1">Total Discounts</div>
                        <div class="fw-bold fs-5 text-purple" style="color:#6f42c1;">
                            ৳<?php echo number_format($invoice_stats['total_discounts']); ?></div>
                        <div class="text-muted" style="font-size:11px;">Given by receptionist</div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card border-0 shadow-sm rounded-4 p-3 stat-card red h-100">
                        <div class="text-muted small mb-1">Refunds Pending</div>
                        <div class="fw-bold fs-5 text-danger">
                            ৳<?php echo number_format($refund_stats['pending_refunds']); ?></div>
                        <div class="text-muted" style="font-size:11px;"><?php echo $refund_stats['refund_count']; ?>
                            request(s)</div>
                    </div>
                </div>

            </div>

            <!-- REVENUE BREAKDOWN -->
            <div class="row g-4 mb-4">
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <p class="section-title mb-3">Revenue Breakdown</p>
                            <ul class="list-unstyled small mb-0">
                                <li class="d-flex justify-content-between py-2 border-bottom">
                                    <span class="text-muted">Room Revenue</span>
                                    <span
                                        class="fw-semibold">৳<?php echo number_format($invoice_stats['room_revenue']); ?></span>
                                </li>
                                <li class="d-flex justify-content-between py-2 border-bottom">
                                    <span class="text-muted">Service Revenue</span>
                                    <span
                                        class="fw-semibold">৳<?php echo number_format($invoice_stats['service_revenue']); ?></span>
                                </li>
                                <li class="d-flex justify-content-between py-2 border-bottom">
                                    <span class="text-muted">Tax Collected</span>
                                    <span
                                        class="fw-semibold">৳<?php echo number_format($invoice_stats['tax_collected']); ?></span>
                                </li>
                                <li class="d-flex justify-content-between py-2 border-bottom">
                                    <span class="text-muted text-danger">Less: Discounts</span>
                                    <span class="fw-semibold text-danger">&minus;
                                        ৳<?php echo number_format($invoice_stats['total_discounts']); ?></span>
                                </li>
                                <li class="d-flex justify-content-between py-2 border-bottom">
                                    <span class="text-muted text-danger">Less: Approved Refunds</span>
                                    <span class="fw-semibold text-danger">&minus;
                                        ৳<?php echo number_format($refund_stats['approved_refunds']); ?></span>
                                </li>
                                <li class="d-flex justify-content-between py-2">
                                    <span class="fw-bold">Total Billed (Invoiced)</span>
                                    <span
                                        class="fw-bold text-primary">৳<?php echo number_format($invoice_stats['total_billed']); ?></span>
                                </li>
                            </ul>
                            <div class="border-top pt-3 mt-2">
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">Invoices Issued</span>
                                    <span class="fw-semibold"><?php echo $invoice_stats['invoices_issued']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between small mt-1">
                                    <span class="text-muted">Paying Bookings</span>
                                    <span class="fw-semibold"><?php echo $stats['paying_bookings']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MONTHLY TABLE -->
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <p class="section-title mb-3">Month-by-Month (Last 12 Months)</p>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Month</th>
                                            <th class="text-end">Online</th>
                                            <th class="text-end">Cash</th>
                                            <th class="text-end fw-bold">Total</th>
                                            <th class="text-end">Bookings</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $grand = 0;
                                        while ($m = $monthly->fetch_assoc()):
                                            $grand += $m['received'];
                                        ?>
                                        <tr>
                                            <td class="fw-semibold small">
                                                <?php echo htmlspecialchars($m['month_label']); ?></td>
                                            <td class="text-end small text-primary">
                                                ৳<?php echo number_format($m['online']); ?></td>
                                            <td class="text-end small text-warning">
                                                ৳<?php echo number_format($m['cash']); ?></td>
                                            <td class="text-end small fw-bold">
                                                ৳<?php echo number_format($m['received']); ?></td>
                                            <td class="text-end small text-muted"><?php echo $m['bookings']; ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <td colspan="3" class="fw-bold small">Total (shown above)</td>
                                            <td class="text-end fw-bold text-success">
                                                ৳<?php echo number_format($grand); ?></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PAYMENT TRANSACTIONS -->
            <p class="section-title">All Payment Transactions</p>
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Payment ID</th>
                                    <th>Booking</th>
                                    <th>Guest</th>
                                    <th>Room</th>
                                    <th>Stay</th>
                                    <th>Method</th>
                                    <th>Transaction ID</th>
                                    <th class="text-end">Amount</th>
                                    <th class="pe-4">Date &amp; Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $txn_total = 0;
                                while ($t = $transactions->fetch_assoc()):
                                    $txn_total += $t['amount'];
                                ?>
                                <tr>
                                    <td class="ps-4 small text-muted">#<?php echo $t['payment_id']; ?></td>
                                    <td class="small text-muted">#<?php echo $t['booking_id']; ?></td>
                                    <td class="small fw-semibold"><?php echo htmlspecialchars($t['guest_name']); ?></td>
                                    <td>
                                        <div class="small fw-semibold">
                                            <?php echo htmlspecialchars($t['room_number']); ?></div>
                                        <div class="text-muted" style="font-size:11px;">
                                            <?php echo htmlspecialchars($t['room_type']); ?></div>
                                    </td>
                                    <td class="small text-muted">
                                        <?php echo date('d M', strtotime($t['check_in'])); ?>
                                        &rarr;
                                        <?php echo date('d M Y', strtotime($t['check_out'])); ?>
                                    </td>
                                    <td>
                                        <span
                                            class="badge rounded-pill <?php echo $t['method'] === 'Cash' ? 'bg-warning text-dark' : 'bg-primary'; ?>">
                                            <?php echo htmlspecialchars($t['method']); ?>
                                        </span>
                                    </td>
                                    <td class="text-muted" style="font-size:11px;">
                                        <?php echo htmlspecialchars($t['transaction_id'] ?? '—'); ?></td>
                                    <td class="text-end fw-semibold text-success small">
                                        ৳<?php echo number_format($t['amount']); ?></td>
                                    <td class="pe-4 small text-muted">
                                        <?php echo date('d M Y, h:i A', strtotime($t['paid_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="7" class="ps-4 fw-bold small">Total Shown</td>
                                    <td class="text-end fw-bold text-success">৳<?php echo number_format($txn_total); ?>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- DISCOUNT LOG -->
            <p class="section-title">Discount Log (Receptionist-Applied)</p>
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-0">
                    <?php if ($discounts->num_rows === 0): ?>
                    <div class="text-center py-4 text-muted small">No discounts have been applied yet.</div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Invoice</th>
                                    <th>Booking</th>
                                    <th>Guest</th>
                                    <th>Room</th>
                                    <th class="text-end">Room Charge</th>
                                    <th class="text-end">Service</th>
                                    <th class="text-end text-danger">Discount</th>
                                    <th class="text-end">Tax</th>
                                    <th class="text-end fw-bold">Final Total</th>
                                    <th class="pe-4">Issued</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $disc_total = 0;
                                    while ($d = $discounts->fetch_assoc()):
                                        $disc_total += $d['discount'];
                                    ?>
                                <tr>
                                    <td class="ps-4 small text-muted">#<?php echo $d['invoice_id']; ?></td>
                                    <td class="small text-muted">#<?php echo $d['booking_id']; ?></td>
                                    <td class="small fw-semibold"><?php echo htmlspecialchars($d['guest_name']); ?></td>
                                    <td>
                                        <div class="small fw-semibold">
                                            <?php echo htmlspecialchars($d['room_number']); ?></div>
                                        <div class="text-muted" style="font-size:11px;">
                                            <?php echo htmlspecialchars($d['room_type']); ?></div>
                                    </td>
                                    <td class="text-end small">৳<?php echo number_format($d['room_charge']); ?></td>
                                    <td class="text-end small">৳<?php echo number_format($d['service_charge']); ?></td>
                                    <td class="text-end fw-bold text-danger">
                                        ৳<?php echo number_format($d['discount']); ?></td>
                                    <td class="text-end small">৳<?php echo number_format($d['tax']); ?></td>
                                    <td class="text-end fw-bold text-primary">৳<?php echo number_format($d['total']); ?>
                                    </td>
                                    <td class="pe-4 small text-muted">
                                        <?php echo date('d M Y, h:i A', strtotime($d['issued_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="6" class="ps-4 fw-bold small">Total Discounts Given</td>
                                    <td class="text-end fw-bold text-danger">৳<?php echo number_format($disc_total); ?>
                                    </td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- REFUND LOG -->
            <p class="section-title">Refund Log</p>
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-0">
                    <?php if ($refunds->num_rows === 0): ?>
                    <div class="text-center py-4 text-muted small">No refund requests on record.</div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Booking</th>
                                    <th>Guest</th>
                                    <th>Room</th>
                                    <th>Check-In</th>
                                    <th class="text-end">Paid</th>
                                    <th class="text-center">Rate</th>
                                    <th class="text-end">Refund Amount</th>
                                    <th class="text-center">Status</th>
                                    <th class="pe-4">Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($rf = $refunds->fetch_assoc()):
                                        $paid_base = (float)$rf['pay_amount'] - (float)$rf['due_amount'];
                                        $refund_val = round($paid_base * $rf['refund_percent'] / 100, 2);
                                        $rs_badge = match ($rf['refund_status']) {
                                            'requested' => 'bg-warning text-dark',
                                            'approved'  => 'bg-success',
                                            'rejected'  => 'bg-danger',
                                            default     => 'bg-secondary'
                                        };
                                    ?>
                                <tr>
                                    <td class="ps-4 small text-muted">#<?php echo $rf['booking_id']; ?></td>
                                    <td class="small fw-semibold"><?php echo htmlspecialchars($rf['guest_name']); ?>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold">
                                            <?php echo htmlspecialchars($rf['room_number']); ?></div>
                                        <div class="text-muted" style="font-size:11px;">
                                            <?php echo htmlspecialchars($rf['room_type']); ?></div>
                                    </td>
                                    <td class="small"><?php echo date('d M Y', strtotime($rf['check_in'])); ?></td>
                                    <td class="text-end small">৳<?php echo number_format($paid_base); ?></td>
                                    <td class="text-center">
                                        <span
                                            class="badge bg-secondary rounded-pill"><?php echo $rf['refund_percent']; ?>%</span>
                                    </td>
                                    <td class="text-end fw-bold text-danger">৳<?php echo number_format($refund_val); ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $rs_badge; ?> rounded-pill">
                                            <?php echo ucfirst($rf['refund_status']); ?>
                                        </span>
                                    </td>
                                    <td class="pe-4 small text-muted">
                                        <?php echo date('d M Y, h:i A', strtotime($rf['updated_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
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
    <script>
    // Show/hide custom date range inputs
    document.getElementById('rangeSelect').addEventListener('change', function() {
        const show = this.value === 'custom';
        document.getElementById('customRange').classList.toggle('d-none', !show);
        document.getElementById('customRangeTo').classList.toggle('d-none', !show);
    });
    </script>
</body>

</html>
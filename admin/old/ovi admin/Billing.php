<?php
// ============================================================
//  MRMS — Billing Management Page
//  Meghdoot Resort Management System | Group 06 | ISD 2026
// ============================================================

include(__DIR__ . '/config/db.php'); // $conn আসবে এখান থেকে

// ── Stats ────────────────────────────────────────────────────

$totalRevenue = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) FROM payment WHERE status IN ('paid','partial')"))[0];
$paidBills    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM booking WHERE payment_status='paid'"))[0];
$pendingBills = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM booking WHERE payment_status='unpaid' AND status NOT IN ('Cancelled','Checked-Out')"))[0];
$overdueBills = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM booking WHERE payment_status='partial' AND status='Checked-Out' AND due_amount>0"))[0];
$todayRevenue = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) FROM payment WHERE DATE(paid_at)=CURDATE()"))[0];

// Payment method breakdown
$methodResult = mysqli_query($conn, "SELECT method, SUM(amount) AS total FROM payment GROUP BY method");
$methodTotals = [];
$grandMethodTotal = 0;
while ($m = mysqli_fetch_assoc($methodResult)) {
    $methodTotals[$m['method']] = $m['total'];
    $grandMethodTotal += $m['total'];
}

// ── Recent Invoices ──────────────────────────────────────────

$invoiceResult = mysqli_query($conn, "
    SELECT i.invoice_id, i.total, i.issued_at,
           b.booking_id, b.payment_status, b.status AS booking_status, b.due_amount,
           u.Name AS guest_name, r.room_number
    FROM invoice i
    JOIN booking b ON b.booking_id = i.booking_id
    JOIN user    u ON u.User_id    = b.guest_id
    JOIN room    r ON r.room_id    = b.room_id
    ORDER BY i.issued_at DESC
    LIMIT 10
");
$invoices = [];
while ($row = mysqli_fetch_assoc($invoiceResult)) $invoices[] = $row;

// ── Pending bookings (no invoice yet) ───────────────────────

$pendingResult = mysqli_query($conn, "
    SELECT b.booking_id, b.pay_amount, b.due_amount,
           b.payment_status, b.status AS booking_status,
           u.Name AS guest_name, r.room_number
    FROM booking b
    JOIN user u ON u.User_id = b.guest_id
    JOIN room r ON r.room_id = b.room_id
    WHERE b.booking_id NOT IN (SELECT booking_id FROM invoice)
    ORDER BY b.booked_at DESC
    LIMIT 10
");
$pendingInvoices = [];
while ($row = mysqli_fetch_assoc($pendingResult)) $pendingInvoices[] = $row;

// ── Helpers ──────────────────────────────────────────────────

function statusBadge($ps, $due, $bs) {
    if ($ps === 'paid')                    return '<span class="status paid">Paid</span>';
    if ($bs === 'Checked-Out' && $due > 0) return '<span class="status overdue">Overdue</span>';
    if ($ps === 'partial')                 return '<span class="status partial">Partial</span>';
    return '<span class="status pending">Pending</span>';
}

function actionBtn($ps, $due, $bs, $id) {
    if ($ps === 'paid')                    return '<button class="pay-btn view-btn"   onclick="viewInvoice('.$id.')">View</button>';
    if ($bs === 'Checked-Out' && $due > 0) return '<button class="pay-btn remind-btn" onclick="remindGuest('.$id.')">Remind</button>';
    return '<button class="pay-btn" onclick="processPayment('.$id.')">Pay</button>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Billing Management — MRMS</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Arial,sans-serif;}
body{background:#f3f1eb;display:flex;}
.sidebar{width:260px;height:100vh;background:white;border-right:1px solid #ddd;position:fixed;left:0;top:0;overflow-y:auto;}
.logo{padding:25px;text-align:center;border-bottom:1px solid #eee;}
.menu{padding:20px;}
.menu-title{color:#999;font-size:13px;margin-bottom:10px;margin-top:20px;}
.menu a{display:flex;align-items:center;gap:10px;text-decoration:none;color:#333;padding:14px;border-radius:12px;margin-bottom:8px;transition:0.3s;}
.menu a:hover{background:#f3f5f9;}
.active{background:#e8f0fb;color:#0d5cab !important;font-weight:bold;}
.main{margin-left:260px;width:calc(100% - 260px);padding:30px;}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;}
.topbar h1{font-size:36px;color:#222;}
.topbar p{color:#777;margin-top:5px;}
.top-btn{background:#0d5cab;color:white;border:none;padding:14px 22px;border-radius:12px;cursor:pointer;font-size:15px;}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:30px;}
.card{background:white;padding:25px;border-radius:18px;border:1px solid #ddd;}
.card h3{color:#666;margin-bottom:15px;font-size:16px;}
.card h1{font-size:38px;color:#222;}
.card p{margin-top:10px;font-size:14px;}
.green{color:green;} .orange{color:orange;} .red{color:red;}
.billing-section{display:grid;grid-template-columns:2fr 1fr;gap:25px;}
.table-box{background:white;border-radius:18px;padding:25px;border:1px solid #ddd;}
.table-header{display:flex;justify-content:space-between;margin-bottom:20px;}
.table-header h2{color:#222;}
.section-tabs{display:flex;gap:10px;margin-bottom:18px;}
.tab-btn{padding:8px 18px;border-radius:20px;border:1px solid #ddd;background:white;cursor:pointer;font-size:14px;}
.tab-btn.active{background:#0d5cab;color:white;border-color:#0d5cab;}
table{width:100%;border-collapse:collapse;}
table th{text-align:left;color:#888;padding:14px 10px;border-bottom:1px solid #eee;font-size:14px;}
table td{padding:14px 10px;border-bottom:1px solid #f2f2f2;font-size:14px;}
.status{padding:6px 12px;border-radius:30px;font-size:12px;font-weight:bold;display:inline-block;}
.paid{background:#e7f7ea;color:green;}
.pending{background:#fff3d9;color:#cc8800;}
.overdue{background:#fde8e8;color:#d11a2a;}
.partial{background:#e8f0fb;color:#0d5cab;}
.pay-btn{background:#0d5cab;color:white;border:none;padding:8px 14px;border-radius:8px;cursor:pointer;font-size:13px;}
.view-btn{background:#28a745;} .remind-btn{background:#dc3545;}
.empty-row td{text-align:center;color:#aaa;padding:30px;}
.summary-box{background:white;border-radius:18px;padding:25px;border:1px solid #ddd;}
.summary-box h2{margin-bottom:25px;}
.summary-item{display:flex;justify-content:space-between;margin-bottom:20px;padding-bottom:15px;border-bottom:1px solid #eee;}
.summary-item h4{color:#666;} .summary-item h3{color:#222;}
.payment-method{margin-top:25px;}
.method{background:#f5f7fb;padding:15px;border-radius:12px;margin-bottom:12px;}
.method-bar-wrap{width:100%;background:#e2e8f0;border-radius:6px;height:6px;margin-top:6px;}
.method-bar{height:6px;border-radius:6px;background:#0d5cab;}
.db-badge{font-size:12px;background:#e8f0fb;color:#0d5cab;padding:4px 10px;border-radius:20px;margin-left:10px;}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:1000;justify-content:center;align-items:center;}
.modal-overlay.open{display:flex;}
.modal{background:white;border-radius:18px;padding:30px;width:400px;max-width:95%;}
.modal h2{margin-bottom:20px;font-size:20px;}
.modal p{margin-bottom:10px;color:#555;}
.modal-close{background:#ddd;color:#333;border:none;padding:10px 18px;border-radius:8px;cursor:pointer;margin-right:10px;}
.modal-confirm{background:#0d5cab;color:white;border:none;padding:10px 18px;border-radius:8px;cursor:pointer;}
@media(max-width:1000px){.stats{grid-template-columns:1fr 1fr;}.billing-section{grid-template-columns:1fr;}}
@media(max-width:700px){.sidebar{display:none;}.main{margin-left:0;width:100%;}.stats{grid-template-columns:1fr;}}
</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="logo">
        <img src="meghdut_photo-removebg-preview.png" width="100" height="100" alt="Logo">
    </div>
    <div class="menu">
        <div class="menu-title">MAIN</div>
        <a href="index.php">📊 Dashboard</a>
        <a href="reservations.php">📅 Reservations</a>
        <a href="room.php">🛏 Rooms</a>
        <a href="guests.php">👤 Guests</a>
        <div class="menu-title">OPERATIONS</div>
        <a href="housekeeping.php">🧹 Housekeeping</a>
        <a href="Billing.php" class="active">🧾 Billing</a>
        <a href="report.php">📈 Reports</a>
        <a href="restaurant.php">🍽 Restaurant</a>
        <div class="menu-title">SYSTEM</div>
        <a href="#">⚙ Settings</a>
        <a href="staff.php">👨‍💼 Staff</a>
    </div>
</div>

<!-- MAIN -->
<div class="main">

    <div class="topbar">
        <div>
            <h1>Billing <span class="db-badge">🟢 Live DB</span></h1>
            <p><?= date('l, F j, Y') ?></p>
        </div>
        <button class="top-btn" onclick="window.location.href='create_invoice.php'">+ Create Invoice</button>
    </div>

    <!-- STATS -->
    <div class="stats">
        <div class="card">
            <h3>Total Revenue</h3>
            <h1>৳<?= number_format($totalRevenue, 0) ?></h1>
            <p class="green">All time payments received</p>
        </div>
        <div class="card">
            <h3>Paid Bills</h3>
            <h1><?= $paidBills ?></h1>
            <p class="green">Fully settled bookings</p>
        </div>
        <div class="card">
            <h3>Pending Bills</h3>
            <h1><?= $pendingBills ?></h1>
            <p class="orange">Awaiting payment</p>
        </div>
        <div class="card">
            <h3>Overdue</h3>
            <h1><?= $overdueBills ?></h1>
            <p class="red">Checked-out with dues</p>
        </div>
    </div>

    <!-- BILLING CONTENT -->
    <div class="billing-section">

        <div class="table-box">
            <div class="table-header">
                <h2>Invoices & Bills</h2>
                <a href="all_invoices.php">View all →</a>
            </div>
            <div class="section-tabs">
                <button class="tab-btn active" onclick="showTab('invoiced',this)">Invoiced</button>
                <button class="tab-btn" onclick="showTab('pending',this)">Pending / Unpaid</button>
            </div>

            <!-- Invoiced -->
            <div id="tab-invoiced">
            <table>
                <tr><th>Guest</th><th>Invoice #</th><th>Room</th><th>Total</th><th>Status</th><th>Action</th></tr>
                <?php if(empty($invoices)): ?>
                <tr class="empty-row"><td colspan="6">No invoices found.</td></tr>
                <?php else: foreach($invoices as $inv): ?>
                <tr>
                    <td><?= htmlspecialchars($inv['guest_name']) ?></td>
                    <td>#INV-<?= str_pad($inv['invoice_id'],4,'0',STR_PAD_LEFT) ?></td>
                    <td><?= htmlspecialchars($inv['room_number']) ?></td>
                    <td>৳<?= number_format($inv['total'],2) ?></td>
                    <td><?= statusBadge($inv['payment_status'],(float)$inv['due_amount'],$inv['booking_status']) ?></td>
                    <td><?= actionBtn($inv['payment_status'],(float)$inv['due_amount'],$inv['booking_status'],$inv['booking_id']) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </table>
            </div>

            <!-- Pending -->
            <div id="tab-pending" style="display:none;">
            <table>
                <tr><th>Guest</th><th>Booking #</th><th>Room</th><th>Paid / Due</th><th>Status</th><th>Action</th></tr>
                <?php if(empty($pendingInvoices)): ?>
                <tr class="empty-row"><td colspan="6">All bookings are invoiced.</td></tr>
                <?php else: foreach($pendingInvoices as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['guest_name']) ?></td>
                    <td>#BK-<?= str_pad($p['booking_id'],4,'0',STR_PAD_LEFT) ?></td>
                    <td><?= htmlspecialchars($p['room_number']) ?></td>
                    <td>৳<?= number_format($p['pay_amount'],0) ?> / ৳<?= number_format($p['due_amount'],0) ?></td>
                    <td><?= statusBadge($p['payment_status'],(float)$p['due_amount'],$p['booking_status']) ?></td>
                    <td><?= actionBtn($p['payment_status'],(float)$p['due_amount'],$p['booking_status'],$p['booking_id']) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </table>
            </div>
        </div>

        <!-- SUMMARY -->
        <div class="summary-box">
            <h2>Payment Summary</h2>
            <div class="summary-item">
                <h4>Today's Revenue</h4>
                <h3>৳<?= number_format($todayRevenue,2) ?></h3>
            </div>
            <?php
            $methodIcons = ['SSLCommerz'=>'📱','Cash'=>'💵','Card'=>'💳'];
            foreach($methodTotals as $method => $amt): ?>
            <div class="summary-item">
                <h4><?= htmlspecialchars($method) ?></h4>
                <h3>৳<?= number_format($amt,2) ?></h3>
            </div>
            <?php endforeach; ?>
            <?php if(empty($methodTotals)): ?>
            <div class="summary-item"><h4>No payment data yet</h4><h3>৳0.00</h3></div>
            <?php endif; ?>

            <div class="payment-method">
                <h2 style="margin-bottom:15px;">Payment Methods</h2>
                <?php foreach($methodTotals as $method => $amt):
                    $icon = $methodIcons[$method] ?? '💰';
                    $pct  = $grandMethodTotal > 0 ? round(($amt/$grandMethodTotal)*100) : 0;
                ?>
                <div class="method">
                    <div style="display:flex;justify-content:space-between;width:100%;margin-bottom:6px;">
                        <span><?= $icon ?> <?= htmlspecialchars($method) ?></span>
                        <strong><?= $pct ?>%</strong>
                    </div>
                    <div class="method-bar-wrap">
                        <div class="method-bar" style="width:<?= $pct ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(empty($methodTotals)): ?>
                <div class="method"><span>📊 No data yet</span></div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /billing-section -->

</div><!-- /main -->

<!-- Modal -->
<div class="modal-overlay" id="actionModal">
    <div class="modal">
        <h2 id="modalTitle">Action</h2>
        <p id="modalBody"></p>
        <div style="margin-top:20px;">
            <button class="modal-close" onclick="closeModal()">Cancel</button>
            <button class="modal-confirm" id="modalConfirm">Confirm</button>
        </div>
    </div>
</div>

<script>
function showTab(tab, btn) {
    document.getElementById('tab-invoiced').style.display = tab==='invoiced' ? '' : 'none';
    document.getElementById('tab-pending').style.display  = tab==='pending'  ? '' : 'none';
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}
function openModal(title, body, cb) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalBody').textContent  = body;
    document.getElementById('modalConfirm').onclick   = cb;
    document.getElementById('actionModal').classList.add('open');
}
function closeModal() { document.getElementById('actionModal').classList.remove('open'); }
function viewInvoice(id)    { window.location.href = 'invoice_detail.php?booking_id='+id; }
function processPayment(id) { openModal('Process Payment','Redirect to payment page for booking #'+id+'?', ()=>{ window.location.href='process_payment.php?booking_id='+id; }); }
function remindGuest(id)    { openModal('Send Reminder','Send payment reminder for booking #'+id+'?', ()=>{ fetch('send_reminder.php?booking_id='+id).then(()=>{ alert('Reminder sent!'); closeModal(); }); }); }
</script>

</body>
</html>
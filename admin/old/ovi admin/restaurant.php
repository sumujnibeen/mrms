<?php
// ============================================================
//  MRMS — Restaurant Management Page
//  Meghdoot Resort Management System | Group 06 | ISD 2026
// ============================================================

include(__DIR__ . '/config/db.php');

// ── Stats ────────────────────────────────────────────────────

$totalOrders = mysqli_fetch_row(mysqli_query($conn, "
    SELECT COUNT(*) FROM service_request WHERE type='food'
"))[0];

$preparing = mysqli_fetch_row(mysqli_query($conn, "
    SELECT COUNT(*) FROM service_request WHERE type='food' AND status='Processing'
"))[0];

$servedToday = mysqli_fetch_row(mysqli_query($conn, "
    SELECT COUNT(*) FROM service_request WHERE type='food' AND status='Done' AND DATE(requested_at)=CURDATE()
"))[0];

$pendingOrders = mysqli_fetch_row(mysqli_query($conn, "
    SELECT COUNT(*) FROM service_request WHERE type='food' AND status='Pending'
"))[0];

// ── Recent Food Orders ───────────────────────────────────────

$ordersResult = mysqli_query($conn, "
    SELECT sr.service_id, sr.description, sr.status, sr.charge, sr.requested_at,
           u.Name AS guest_name,
           b.booking_id, r.room_number
    FROM service_request sr
    JOIN user    u ON u.User_id     = sr.guest_id
    JOIN booking b ON b.booking_id  = sr.booking_id
    JOIN room    r ON r.room_id     = b.room_id
    WHERE sr.type = 'food'
    ORDER BY sr.requested_at DESC
    LIMIT 10
");
$orders = [];
while ($row = mysqli_fetch_assoc($ordersResult)) $orders[] = $row;

// ── Food Menu ────────────────────────────────────────────────

$menuResult = mysqli_query($conn, "
    SELECT menu_id, name, category, price, available
    FROM food_menu
    ORDER BY available DESC, category ASC
");
$menuItems = [];
while ($row = mysqli_fetch_assoc($menuResult)) $menuItems[] = $row;

// ── Total food revenue ───────────────────────────────────────
$foodRevenue = mysqli_fetch_row(mysqli_query($conn, "
    SELECT COALESCE(SUM(charge),0) FROM service_request WHERE type='food' AND status='Done'
"))[0];

// ── Helpers ──────────────────────────────────────────────────
function orderStatusBadge($status) {
    if ($status === 'Done')       return '<span class="status served">Served</span>';
    if ($status === 'Processing') return '<span class="status preparing">Preparing</span>';
    return '<span class="status pending">Pending</span>';
}

function orderActionBtn($status, $id) {
    if ($status === 'Done')       return '<button class="action-btn">Details</button>';
    if ($status === 'Processing') return '<button class="action-btn">Track</button>';
    return '<button class="action-btn" onclick="prepareOrder('.$id.')">Prepare</button>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Restaurant Management — MRMS</title>
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
.top-btn{background:#0d5cab;color:white;border:none;padding:14px 22px;border-radius:12px;cursor:pointer;font-size:15px;transition:background 0.2s;}
.top-btn:hover{background:#0a4a9a;}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:30px;}
.card{background:white;padding:25px;border-radius:18px;border:1px solid #ddd;}
.card h3{color:#666;margin-bottom:15px;font-size:16px;}
.card h1{font-size:36px;color:#222;}
.card p{margin-top:10px;font-size:14px;}
.green{color:green;} .orange{color:orange;} .red{color:red;}
.restaurant-section{display:grid;grid-template-columns:2fr 1fr;gap:25px;}
.table-box{background:white;border-radius:18px;padding:25px;border:1px solid #ddd;}
.table-header{display:flex;justify-content:space-between;margin-bottom:20px;}
.table-header h2{color:#222;}
table{width:100%;border-collapse:collapse;}
table th{text-align:left;color:#888;padding:14px 10px;border-bottom:1px solid #eee;font-size:14px;}
table td{padding:14px 10px;border-bottom:1px solid #f2f2f2;font-size:14px;}
.status{padding:7px 13px;border-radius:30px;font-size:12px;font-weight:bold;display:inline-block;}
.served{background:#e7f7ea;color:green;}
.preparing{background:#fff3d9;color:#cc8800;}
.pending{background:#fde8e8;color:#d11a2a;}
.action-btn{background:#0d5cab;color:white;border:none;padding:8px 14px;border-radius:8px;cursor:pointer;font-size:13px;}
.menu-box{background:white;border-radius:18px;padding:25px;border:1px solid #ddd;}
.menu-box h2{margin-bottom:20px;}
.food-item{display:flex;justify-content:space-between;align-items:center;padding:14px;background:#f7f9fc;border-radius:12px;margin-bottom:12px;}
.food-left{display:flex;flex-direction:column;}
.food-name{font-weight:bold;color:#222;}
.food-type{color:#777;font-size:12px;margin-top:3px;}
.food-price{font-weight:bold;color:#0d5cab;white-space:nowrap;}
.unavailable{opacity:0.45;}
.unavailable .food-price{color:#aaa;}
.unavail-tag{font-size:11px;color:#d11a2a;margin-top:2px;}
.revenue-bar{background:#e8f0fb;border-radius:12px;padding:14px 18px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;}
.revenue-bar span{color:#666;font-size:14px;}
.revenue-bar strong{color:#0d5cab;font-size:18px;}
.db-badge{font-size:12px;background:#e8f0fb;color:#0d5cab;padding:4px 10px;border-radius:20px;margin-left:10px;}
.empty-row td{text-align:center;color:#aaa;padding:30px;}

/* ── Modal ── */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:1000;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal{background:white;border-radius:20px;padding:35px;width:500px;max-width:95vw;max-height:90vh;overflow-y:auto;position:relative;animation:modalIn 0.2s ease;}
@keyframes modalIn{from{opacity:0;transform:translateY(-20px);}to{opacity:1;transform:translateY(0);}}
.modal h2{font-size:22px;margin-bottom:22px;color:#222;}
.modal label{display:block;color:#555;font-size:14px;margin-bottom:6px;margin-top:16px;font-weight:600;}
.modal select,.modal input,.modal textarea{width:100%;padding:12px 14px;border:1px solid #ddd;border-radius:10px;font-size:14px;outline:none;transition:border 0.2s;background:#fff;}
.modal select:focus,.modal input:focus,.modal textarea:focus{border-color:#0d5cab;}
.modal textarea{resize:vertical;min-height:80px;}
.modal-footer{display:flex;gap:12px;margin-top:24px;justify-content:flex-end;}
.btn-cancel{background:#f3f1eb;color:#555;border:none;padding:12px 22px;border-radius:10px;cursor:pointer;font-size:14px;}
.btn-cancel:hover{background:#e8e6e0;}
.btn-submit{background:#0d5cab;color:white;border:none;padding:12px 22px;border-radius:10px;cursor:pointer;font-size:14px;font-weight:bold;}
.btn-submit:hover{background:#0a4a9a;}
.close-btn{position:absolute;top:15px;right:18px;background:none;border:none;font-size:24px;cursor:pointer;color:#aaa;line-height:1;}
.close-btn:hover{color:#333;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.success-msg{background:#e7f7ea;color:green;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:14px;display:none;}

@media(max-width:1000px){.stats{grid-template-columns:1fr 1fr;}.restaurant-section{grid-template-columns:1fr;}}
@media(max-width:700px){.sidebar{display:none;}.main{margin-left:0;width:100%;}.stats{grid-template-columns:1fr;}.form-row{grid-template-columns:1fr;}}
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
        <a href="Billing.php">🧾 Billing</a>
        <a href="report.php">📈 Reports</a>
        <a href="restaurant.php" class="active">🍽 Restaurant</a>
        <div class="menu-title">SYSTEM</div>
        <a href="#">⚙ Settings</a>
        <a href="staff.php">👨‍💼 Staff</a>
    </div>
</div>

<!-- MAIN -->
<div class="main">

    <div class="topbar">
        <div>
            <h1>Restaurant <span class="db-badge">🟢 Live DB</span></h1>
            <p><?= date('l, F j, Y') ?></p>
        </div>
        <button class="top-btn" onclick="openModal()">+ New Order</button>
    </div>

    <!-- STATS -->
    <div class="stats">
        <div class="card">
            <h3>Total Orders</h3>
            <h1><?= $totalOrders ?></h1>
            <p class="green">All food requests</p>
        </div>
        <div class="card">
            <h3>Preparing</h3>
            <h1><?= $preparing ?></h1>
            <p class="orange">Kitchen busy</p>
        </div>
        <div class="card">
            <h3>Served Today</h3>
            <h1><?= $servedToday ?></h1>
            <p class="green">Completed today</p>
        </div>
        <div class="card">
            <h3>Pending Orders</h3>
            <h1><?= $pendingOrders ?></h1>
            <p class="red">Need quick service</p>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="restaurant-section">

        <!-- ORDERS TABLE -->
        <div class="table-box">
            <div class="table-header">
                <h2>Recent Food Orders</h2>
                <a href="#">View all →</a>
            </div>
            <table>
                <tr>
                    <th>Guest</th>
                    <th>Room</th>
                    <th>Order Details</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                <?php if(empty($orders)): ?>
                <tr class="empty-row"><td colspan="6">No food orders found.</td></tr>
                <?php else: foreach($orders as $o): ?>
                <tr>
                    <td><?= htmlspecialchars($o['guest_name']) ?></td>
                    <td>#<?= htmlspecialchars($o['room_number']) ?></td>
                    <td style="max-width:200px;font-size:13px;"><?= htmlspecialchars($o['description']) ?></td>
                    <td>৳<?= number_format($o['charge'], 0) ?></td>
                    <td><?= orderStatusBadge($o['status']) ?></td>
                    <td><?= orderActionBtn($o['status'], $o['service_id']) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </table>
        </div>

        <!-- MENU -->
        <div class="menu-box">
            <h2>Food Menu</h2>
            <div class="revenue-bar">
                <span>🍽 Total Food Revenue</span>
                <strong>৳<?= number_format($foodRevenue, 0) ?></strong>
            </div>
            <?php if(empty($menuItems)): ?>
            <p style="color:#aaa;text-align:center;padding:20px;">No menu items found.</p>
            <?php else: foreach($menuItems as $item): ?>
            <div class="food-item <?= $item['available'] ? '' : 'unavailable' ?>">
                <div class="food-left">
                    <span class="food-name"><?= htmlspecialchars($item['name']) ?></span>
                    <span class="food-type"><?= htmlspecialchars($item['category']) ?></span>
                    <?php if(!$item['available']): ?>
                    <span class="unavail-tag">Unavailable</span>
                    <?php endif; ?>
                </div>
                <span class="food-price">৳<?= number_format($item['price'], 0) ?></span>
            </div>
            <?php endforeach; endif; ?>
        </div>

    </div><!-- /restaurant-section -->

</div><!-- /main -->


<!-- ══════════════════════════════════════════
     NEW ORDER MODAL
═══════════════════════════════════════════ -->
<div class="modal-overlay" id="newOrderModal" onclick="overlayClick(event)">
  <div class="modal">
    <button class="close-btn" onclick="closeModal()">×</button>
    <h2>🍽 New Food Order</h2>

    <div class="success-msg" id="successMsg">✅ Order placed successfully!</div>

    <form method="POST" action="add_order.php" id="orderForm">

      <div class="form-row">
        <div>
          <label>Guest Name *</label>
          <input type="text" name="guest_name" placeholder="e.g. Karim Hossain" required>
        </div>
        <div>
          <label>Room Number *</label>
          <input type="text" name="room_number" placeholder="e.g. 202" required>
        </div>
      </div>

      <label>Quick Select from Menu</label>
      <select id="menuSelect" onchange="fillFromMenu(this)">
        <option value="">-- Pick an item to auto-fill --</option>
        <?php foreach($menuItems as $item): if(!$item['available']) continue; ?>
        <option value="<?= htmlspecialchars($item['name']) ?>" data-price="<?= $item['price'] ?>">
          <?= htmlspecialchars($item['name']) ?> — ৳<?= number_format($item['price'], 0) ?>
        </option>
        <?php endforeach; ?>
      </select>

      <div class="form-row">
        <div>
          <label>Order Details / Description *</label>
          <textarea name="description" id="descInput" placeholder="e.g. Chicken Biryani x1, Extra spicy..." required></textarea>
        </div>
        <div>
          <label>Total Charge (৳) *</label>
          <input type="number" name="charge" id="chargeInput" placeholder="0" min="0" step="1" required>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn-submit">Place Order</button>
      </div>

    </form>
  </div>
</div>


<script>
// ── Modal open / close ───────────────────────────────────────
function openModal() {
    document.getElementById('newOrderModal').classList.add('open');
    document.getElementById('orderForm').reset();
    document.getElementById('successMsg').style.display = 'none';
}

function closeModal() {
    document.getElementById('newOrderModal').classList.remove('open');
}

function overlayClick(e) {
    if (e.target === document.getElementById('newOrderModal')) closeModal();
}

// ── Auto-fill from menu dropdown ─────────────────────────────
function fillFromMenu(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (!opt.value) return;

    // Add item to description
    const desc = document.getElementById('descInput');
    desc.value = desc.value
        ? desc.value + ', ' + opt.value + ' x1'
        : opt.value + ' x1';

    // Add price to charge
    const charge = document.getElementById('chargeInput');
    const existing = parseFloat(charge.value) || 0;
    charge.value = existing + parseFloat(opt.dataset.price);

    // Reset dropdown so same item can be added again
    sel.value = '';
}

// ── Prepare order ────────────────────────────────────────────
function prepareOrder(id) {
    if(confirm('Mark order #' + id + ' as Processing?')) {
        window.location.href = 'update_order.php?service_id=' + id + '&status=Processing';
    }
}

// ── Show success message if redirected back with ?success=1 ──
const params = new URLSearchParams(window.location.search);
if (params.get('success') === '1') {
    showToast('✅ New order placed successfully!', '#e7f7ea', 'green');
    window.history.replaceState({}, '', 'restaurant.php');
}
if (params.get('error') === 'notfound') {
    showToast('❌ Guest or room not found. Please check the name and room number.', '#fde8e8', '#d11a2a');
    window.history.replaceState({}, '', 'restaurant.php');
}
if (params.get('error') === 'invalid') {
    showToast('❌ Invalid input. Please fill all fields.', '#fde8e8', '#d11a2a');
    window.history.replaceState({}, '', 'restaurant.php');
}

function showToast(text, bg, color) {
    const msg = document.createElement('div');
    msg.textContent = text;
    msg.style.cssText = `position:fixed;top:20px;right:20px;background:${bg};color:${color};padding:14px 20px;border-radius:12px;font-size:14px;font-weight:bold;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.15);max-width:350px;`;
    document.body.appendChild(msg);
    setTimeout(() => msg.remove(), 4000);
}
</script>

</body>
</html>
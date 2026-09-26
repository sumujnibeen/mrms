<?php
include(__DIR__ . '/config/db.php');

// ─── Stats ───────────────────────────────────────────────────────────────────
$total_guests = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS cnt FROM user WHERE Role='guest'"))['cnt'];

$in_house = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(DISTINCT b.guest_id) AS cnt
     FROM booking b
     WHERE b.status = 'Checked-In'"))['cnt'];

$total_revenue = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(amount),0) AS total FROM payment WHERE status IN ('paid','partial')")
)['total'];

// ─── Guest List with booking info ────────────────────────────────────────────
$guests_result = mysqli_query($conn, "
    SELECT
        u.User_id,
        u.Name,
        u.Email,
        u.Phone,
        u.Photo,
        u.Created_at,
        b.booking_id,
        b.status        AS booking_status,
        b.check_in,
        b.check_out,
        b.payment_status,
        r.room_number,
        r.type          AS room_type
    FROM user u
    LEFT JOIN booking b ON b.guest_id = u.User_id
        AND b.booking_id = (
            SELECT booking_id FROM booking
            WHERE guest_id = u.User_id
            ORDER BY booked_at DESC
            LIMIT 1
        )
    LEFT JOIN room r ON r.room_id = b.room_id
    WHERE u.Role = 'guest'
    ORDER BY u.Created_at DESC
");

// ─── Handle Add Guest (POST) ──────────────────────────────────────────────────
$success_msg = '';
$error_msg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_guest') {
    $name     = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone    = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Check duplicate email
    $check = mysqli_query($conn, "SELECT User_id FROM user WHERE Email='$email'");
    if (mysqli_num_rows($check) > 0) {
        $error_msg = "Email already exists.";
    } else {
        $sql = "INSERT INTO user (Name, Email, Password, Role, Phone)
                VALUES ('$name', '$email', '$password', 'guest', '$phone')";
        if (mysqli_query($conn, $sql)) {
            $success_msg = "Guest added successfully.";
        } else {
            $error_msg = "Error: " . mysqli_error($conn);
        }
    }
    // Refresh page to show updated list
    if (!$error_msg) {
        header("Location: guests.php?added=1");
        exit;
    }
}

// ─── Handle Delete (GET) ─────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM user WHERE User_id=$del_id AND Role='guest'");
    header("Location: guests.php");
    exit;
}

// ─── Booking status → display label ──────────────────────────────────────────
function booking_label($status) {
    return match($status) {
        'Checked-In'  => ['In House',  '#EAF3DE', '#3B6D11'],
        'Confirmed'   => ['Confirmed', '#EEEDFE', '#534AB7'],
        'Pending'     => ['Pending',   '#FAEEDA', '#854F0B'],
        'Checked-Out' => ['Checked Out','#f3f4f6','#6b7280'],
        'Cancelled'   => ['Cancelled', '#FCEBEB', '#A32D2D'],
        default       => ['No Booking','#f3f4f6','#9ca3af'],
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Guests – MRMS</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: system-ui, -apple-system, sans-serif; background: #f3f4f6; color: #111827; min-height: 100vh; }
.layout { display: flex; min-height: 100vh; }

/* Sidebar */
.sidebar { width: 240px; background: #fff; border-right: 0.5px solid #e5e7eb; display: flex; flex-direction: column; flex-shrink: 0; }
.sidebar-logo { padding: 1.25rem 1.5rem; border-bottom: 0.5px solid #e5e7eb; }
.sidebar-section-label { font-size: 10px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.08em; padding: 1.25rem 1.5rem 0.4rem; }
.sidebar-nav a { display: flex; align-items: center; gap: 10px; padding: 9px 1.5rem; font-size: 14px; color: #374151; text-decoration: none; transition: background 0.1s; }
.sidebar-nav a:hover { background: #f9fafb; }
.sidebar-nav a.active { background: #EFF6FF; color: #185FA5; font-weight: 500; border-right: 2px solid #185FA5; }
.sidebar-nav a .icon { font-size: 16px; opacity: 0.7; }
.sidebar-nav a .badge { margin-left: auto; background: #185FA5; color: #fff; border-radius: 10px; font-size: 11px; padding: 1px 7px; }
.sidebar-bottom { margin-top: auto; padding: 1rem 1.5rem; border-top: 0.5px solid #e5e7eb; display: flex; align-items: center; gap: 10px; }
.avatar-sm { width: 32px; height: 32px; border-radius: 50%; background: #185FA5; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; flex-shrink: 0; }

/* Main */
.main { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
.topbar { background: #fff; border-bottom: 0.5px solid #e5e7eb; padding: 1.25rem 1.5rem 1rem; display: flex; align-items: center; justify-content: space-between; }
.topbar h1 { font-size: 20px; font-weight: 500; }
.topbar-date { font-size: 13px; color: #6b7280; margin-top: 2px; }
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 8px; font-size: 14px; cursor: pointer; text-decoration: none; border: 0.5px solid #e5e7eb; background: #fff; color: #374151; transition: background 0.15s; }
.btn:hover { background: #f9fafb; }
.btn-primary { background: #185FA5; color: #fff; border-color: #185FA5; }
.btn-primary:hover { background: #0C447C; }
.btn-danger  { background: #d63031; color: #fff; border-color: #d63031; font-size: 12px; padding: 5px 10px; }
.btn-danger:hover  { background: #b71c1c; }
.btn-edit   { background: #fdcb6e; color: #333; border-color: #fdcb6e; font-size: 12px; padding: 5px 10px; }
.btn-edit:hover { background: #e6b84e; }

/* Stats */
.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; padding: 1rem 1.5rem; background: #f3f4f6; }
.stat-card { background: #fff; border: 0.5px solid #e5e7eb; border-radius: 10px; padding: 0.875rem 1rem; }
.stat-label { font-size: 12px; color: #9ca3af; margin-bottom: 4px; }
.stat-val { font-size: 24px; font-weight: 500; }
.stat-sub { font-size: 12px; color: #9ca3af; margin-top: 2px; }

/* Alert */
.alert { margin: 0.75rem 1.5rem 0; padding: 10px 16px; border-radius: 8px; font-size: 13px; }
.alert-success { background: #EAF3DE; color: #3B6D11; border: 0.5px solid #b7d9a0; }
.alert-error   { background: #FCEBEB; color: #A32D2D; border: 0.5px solid #f5c0c0; }

/* Content */
.content { flex: 1; overflow: auto; padding: 1rem 1.5rem; background: #f3f4f6; }

/* Search bar */
.toolbar { display: flex; gap: 10px; align-items: center; margin-bottom: 1rem; }
.search-box { display: flex; align-items: center; gap: 8px; background: #fff; border: 0.5px solid #e5e7eb; border-radius: 8px; padding: 7px 12px; flex: 1; max-width: 280px; }
.search-box input { border: none; background: transparent; font-size: 14px; color: #111827; outline: none; width: 100%; }

/* Table */
.list-table { width: 100%; border-collapse: collapse; font-size: 13px; background: #fff; border-radius: 12px; overflow: hidden; border: 0.5px solid #e5e7eb; }
.list-table thead th { text-align: left; font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: .06em; padding: 12px 14px; border-bottom: 0.5px solid #e5e7eb; background: #fafafa; }
.list-table tbody tr { border-bottom: 0.5px solid #f3f4f6; transition: background .1s; }
.list-table tbody tr:last-child { border-bottom: none; }
.list-table tbody tr:hover { background: #f9fafb; }
.list-table td { padding: 12px 14px; vertical-align: middle; }
.guest-name { font-weight: 500; font-size: 13px; }
.guest-email { font-size: 11px; color: #9ca3af; margin-top: 2px; }
.avatar { width: 34px; height: 34px; border-radius: 50%; background: #185FA5; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 600; flex-shrink: 0; }
.status-badge { display: inline-flex; align-items: center; gap: 5px; border-radius: 20px; padding: 3px 10px; font-size: 11px; font-weight: 500; white-space: nowrap; }
.status-dot { width: 5px; height: 5px; border-radius: 50%; display: inline-block; }
.payment-pill { display: inline-block; border-radius: 20px; padding: 2px 8px; font-size: 11px; font-weight: 500; }
.empty-msg { text-align: center; padding: 2.5rem; color: #9ca3af; }

/* Footer */
.footer-bar { background: #fff; border-top: 0.5px solid #e5e7eb; padding: 12px 1.5rem; }
.footer-text { font-size: 12px; color: #9ca3af; }

/* Modal */
.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 200; align-items: center; justify-content: center; }
.modal-overlay.open { display: flex; }
.modal { background: #fff; border-radius: 12px; border: 0.5px solid #e5e7eb; padding: 1.5rem; width: 440px; max-width: 95vw; }
.modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
.modal-title { font-size: 16px; font-weight: 500; }
.modal-close { background: none; border: none; font-size: 22px; color: #9ca3af; cursor: pointer; }
.modal-close:hover { color: #374151; }
.form-row { margin-bottom: 1rem; }
.form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 1rem; }
.form-label { font-size: 12px; color: #6b7280; display: block; margin-bottom: 4px; }
.form-input { width: 100%; background: #f9fafb; border: 0.5px solid #e5e7eb; border-radius: 8px; padding: 8px 12px; font-size: 14px; color: #111827; outline: none; }
.form-input:focus { border-color: #185FA5; background: #fff; }
.modal-footer { display: flex; gap: 8px; justify-content: flex-end; margin-top: 1.25rem; }

@media (max-width: 768px) {
    .sidebar { display: none; }
    .stats-row { grid-template-columns: repeat(2, 1fr); }
}
</style>
</head>
<body>
<div class="layout">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <img src="meghdut_photo-removebg-preview.png" width="100" height="100">
    </div>
    <div class="sidebar-section-label">Main</div>
    <nav class="sidebar-nav">
      <a href="index.php"><span class="icon">⊞</span> Dashboard</a>
      <a href="reservations.php"><span class="icon">📅</span> Reservations <span class="badge">4</span></a>
      <a href="rooms.php"><span class="icon">🛏</span> Rooms</a>
      <a href="guests.php" class="active"><span class="icon">👤</span> Guests</a>
    </nav>
    <div class="sidebar-section-label">Operations</div>
    <nav class="sidebar-nav">
      <a href="housekeeping.php"><span class="icon">🧹</span> Housekeeping</a>
      <a href="billing.php"><span class="icon">🧾</span> Billing</a>
      <a href="report.php"><span class="icon">📊</span> Reports</a>
      <a href="restaurant.php"><span class="icon">🍽</span> Restaurant</a>
    </nav>
    <div class="sidebar-section-label">System</div>
    <nav class="sidebar-nav">
      <a href="settings.php"><span class="icon">⚙</span> Settings</a>
      <a href="staff.php"><span class="icon">👥</span> Staff</a>
    </nav>
    <div class="sidebar-bottom">
      <div class="avatar-sm">AD</div>
      <div>
        <div style="font-size:13px;font-weight:500;">Admin</div>
        <div style="font-size:11px;color:#9ca3af;">Manager</div>
      </div>
    </div>
  </aside>

  <!-- Main -->
  <div class="main">

    <!-- Topbar -->
    <div class="topbar">
      <div>
        <h1>Guests</h1>
        <div class="topbar-date"><?= date('l, d F Y') ?></div>
      </div>
      <button class="btn btn-primary" onclick="openModal()">+ Add Guest</button>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-label">Total Guests</div>
        <div class="stat-val"><?= $total_guests ?></div>
        <div class="stat-sub">Registered users</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">In House</div>
        <div class="stat-val" style="color:#3B6D11;"><?= $in_house ?></div>
        <div class="stat-sub">Currently checked-in</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Bookings</div>
        <div class="stat-val" style="color:#185FA5;">
          <?= mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM booking"))['c'] ?>
        </div>
        <div class="stat-sub">All time</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Revenue</div>
        <div class="stat-val" style="color:#854F0B;">৳<?= number_format($total_revenue, 0) ?></div>
        <div class="stat-sub">Payments received</div>
      </div>
    </div>

    <?php if (isset($_GET['added'])): ?>
      <div class="alert alert-success">✓ Guest added successfully.</div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
      <div class="alert alert-error">✕ <?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>

    <!-- Content -->
    <div class="content">

      <!-- Toolbar -->
      <div class="toolbar">
        <div class="search-box">
          <span style="color:#9ca3af;">🔍</span>
          <input type="text" id="search-input" placeholder="Search guest, email, phone…" oninput="filterTable()" />
        </div>
        <span style="font-size:12px;color:#9ca3af;margin-left:4px;">
          <?= $total_guests ?> guest<?= $total_guests != 1 ? 's' : '' ?>
        </span>
      </div>

      <!-- Table -->
      <table class="list-table" id="guest-table">
        <thead>
          <tr>
            <th>Guest</th>
            <th>Phone</th>
            <th>Room</th>
            <th>Check-in</th>
            <th>Check-out</th>
            <th>Booking Status</th>
            <th>Payment</th>
            <th>Joined</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (mysqli_num_rows($guests_result) === 0): ?>
            <tr><td colspan="9" class="empty-msg">No guests found.</td></tr>
          <?php else: ?>
            <?php while ($g = mysqli_fetch_assoc($guests_result)):
              [$blabel, $bbg, $bcolor] = booking_label($g['booking_status']);
              $initials = strtoupper(implode('', array_map(fn($w) => $w[0], explode(' ', trim($g['Name'])))));
              $initials = substr($initials, 0, 2);

              $pay_style = match($g['payment_status']) {
                  'paid'    => 'background:#EAF3DE;color:#3B6D11;',
                  'partial' => 'background:#FAEEDA;color:#854F0B;',
                  'unpaid'  => 'background:#FCEBEB;color:#A32D2D;',
                  default   => 'background:#f3f4f6;color:#9ca3af;',
              };
            ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:10px;">
                  <div class="avatar"><?= $initials ?></div>
                  <div>
                    <div class="guest-name"><?= htmlspecialchars($g['Name']) ?></div>
                    <div class="guest-email"><?= htmlspecialchars($g['Email']) ?></div>
                  </div>
                </div>
              </td>
              <td style="color:#6b7280;font-size:12px;"><?= htmlspecialchars($g['Phone'] ?? '—') ?></td>
              <td style="font-weight:500;">
                <?= $g['room_number'] ? '🛏 '.$g['room_number'].' <span style="color:#9ca3af;font-size:11px;font-weight:400;">('.$g['room_type'].')</span>' : '<span style="color:#9ca3af;">—</span>' ?>
              </td>
              <td style="font-size:12px;color:#6b7280;">
                <?= $g['check_in'] ? date('d M Y', strtotime($g['check_in'])) : '—' ?>
              </td>
              <td style="font-size:12px;color:#6b7280;">
                <?= $g['check_out'] ? date('d M Y', strtotime($g['check_out'])) : '—' ?>
              </td>
              <td>
                <span class="status-badge" style="background:<?= $bbg ?>;color:<?= $bcolor ?>;">
                  <span class="status-dot" style="background:<?= $bcolor ?>;"></span>
                  <?= $blabel ?>
                </span>
              </td>
              <td>
                <?php if ($g['payment_status']): ?>
                  <span class="payment-pill" style="<?= $pay_style ?>"><?= ucfirst($g['payment_status']) ?></span>
                <?php else: ?>
                  <span style="color:#9ca3af;font-size:12px;">—</span>
                <?php endif; ?>
              </td>
              <td style="font-size:11px;color:#9ca3af;">
                <?= date('d M Y', strtotime($g['Created_at'])) ?>
              </td>
              <td>
                <div style="display:flex;gap:5px;">
                  <a href="guest_detail.php?id=<?= $g['User_id'] ?>" class="btn" style="font-size:12px;padding:5px 10px;" title="View">👁</a>
                  <a href="guests.php?delete=<?= $g['User_id'] ?>"
                     class="btn btn-danger"
                     onclick="return confirm('Delete <?= addslashes($g['Name']) ?>? This will also remove their bookings.')">🗑</a>
                </div>
              </td>
            </tr>
            <?php endwhile; ?>
          <?php endif; ?>
        </tbody>
      </table>

    </div>

    <!-- Footer -->
    <div class="footer-bar">
      <span class="footer-text">Showing <?= $total_guests ?> guest<?= $total_guests != 1 ? 's' : '' ?></span>
    </div>

  </div>
</div>

<!-- Add Guest Modal -->
<div class="modal-overlay" id="modal-overlay" onclick="closeModalOut(event)">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Add New Guest</span>
      <button class="modal-close" onclick="closeModal()" type="button">×</button>
    </div>
    <form method="POST" action="guests.php">
      <input type="hidden" name="action" value="add_guest">
      <div class="form-row-2">
        <div>
          <label class="form-label">Full Name</label>
          <input class="form-input" type="text" name="name" placeholder="e.g. Rahim Uddin" required />
        </div>
        <div>
          <label class="form-label">Phone</label>
          <input class="form-input" type="text" name="phone" placeholder="01XXXXXXXXX" />
        </div>
      </div>
      <div class="form-row">
        <label class="form-label">Email</label>
        <input class="form-input" type="email" name="email" placeholder="guest@email.com" required />
      </div>
      <div class="form-row">
        <label class="form-label">Password</label>
        <input class="form-input" type="password" name="password" placeholder="Minimum 6 characters" required minlength="6" />
      </div>
      <div class="modal-footer">
        <button type="button" class="btn" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Guest</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal()      { document.getElementById('modal-overlay').classList.add('open'); }
function closeModal()     { document.getElementById('modal-overlay').classList.remove('open'); }
function closeModalOut(e) { if (e.target === document.getElementById('modal-overlay')) closeModal(); }

function filterTable() {
    const q = document.getElementById('search-input').value.toLowerCase();
    document.querySelectorAll('#guest-table tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
</body>
</html>
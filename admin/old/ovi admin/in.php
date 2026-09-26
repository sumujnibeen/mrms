<?php
// ============================================================
//  MRMS — Staff Management Page
//  Meghdoot Resort Management System | Group 06 | ISD 2026
// ============================================================

include(__DIR__ . '/config/db.php');

// ── Handle Add Staff POST ────────────────────────────────────
$success_msg = '';
$error_msg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_staff') {
    $name     = trim(mysqli_real_escape_string($conn, $_POST['name']));
    $email    = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $phone    = trim(mysqli_real_escape_string($conn, $_POST['phone']));
    $role     = mysqli_real_escape_string($conn, $_POST['role']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check duplicate email
    $check = mysqli_query($conn, "SELECT User_id FROM user WHERE Email='$email'");
    if (mysqli_num_rows($check) > 0) {
        $error_msg = 'এই Email দিয়ে আগে থেকেই একজন staff আছে!';
    } else {
        $insert = mysqli_query($conn, "
            INSERT INTO user (Name, Email, Phone, Role, Password, Created_at)
            VALUES ('$name', '$email', '$phone', '$role', '$password', NOW())
        ");
        if ($insert) {
            $success_msg = "✅ $name সফলভাবে add হয়েছে!";
        } else {
            $error_msg = 'Database error: ' . mysqli_error($conn);
        }
    }
}

// ── Handle Delete ────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM user WHERE User_id=$del_id AND Role IN ('admin','receptionist')");
    header('Location: staff.php?deleted=1');
    exit;
}

// ── Stats ────────────────────────────────────────────────────
$totalStaff     = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM user WHERE Role IN ('admin','receptionist')"))[0];
$adminCount     = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM user WHERE Role='admin'"))[0];
$receptionCount = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM user WHERE Role='receptionist'"))[0];
$deptCount      = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(DISTINCT Role) FROM user WHERE Role IN ('admin','receptionist')"))[0];

// ── Staff List ───────────────────────────────────────────────
$staffResult = mysqli_query($conn, "
    SELECT User_id, Name, Email, Role, Phone, Created_at
    FROM user
    WHERE Role IN ('admin','receptionist')
    ORDER BY Role ASC, Name ASC
");
$staffList = [];
while ($row = mysqli_fetch_assoc($staffResult)) $staffList[] = $row;

$firstStaff = !empty($staffList) ? $staffList[0] : null;

// ── Helpers ──────────────────────────────────────────────────
function roleBadge($role) {
    if ($role === 'admin')        return '<span class="status active-status">Admin</span>';
    if ($role === 'receptionist') return '<span class="status leave-status">Receptionist</span>';
    return '<span class="status offline-status">' . $role . '</span>';
}
function roleLabel($role) {
    if ($role === 'admin')        return 'System Administrator';
    if ($role === 'receptionist') return 'Receptionist';
    return ucfirst($role);
}
function avatarInitials($name) {
    $parts = explode(' ', trim($name));
    $initials = '';
    foreach ($parts as $p) $initials .= strtoupper(substr($p, 0, 1));
    return substr($initials, 0, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Management — MRMS</title>
<style>
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; font-family: system-ui, -apple-system, sans-serif; }
body { background: #f3f4f6; display: flex; color: #111827; }

/* Sidebar */
.sidebar { width: 260px; height: 100vh; background: white; border-right: 1px solid #ddd; position: fixed; left: 0; top: 0; overflow-y: auto; }
.logo { padding: 25px; text-align: center; border-bottom: 1px solid #eee; }
.menu { padding: 20px; }
.menu-title { color: #999; font-size: 13px; margin-bottom: 10px; margin-top: 20px; text-transform: uppercase; letter-spacing: .06em; }
.menu a { display: flex; align-items: center; gap: 10px; text-decoration: none; color: #333; padding: 14px; border-radius: 12px; margin-bottom: 8px; transition: .2s; font-size: 14px; }
.menu a:hover { background: #f3f5f9; }
.menu a.active { background: #e8f0fb; color: #0d5cab; font-weight: 600; }

/* Main */
.main { margin-left: 260px; width: calc(100% - 260px); padding: 30px; min-height: 100vh; }
.topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
.topbar h1 { font-size: 28px; color: #222; }
.topbar p { color: #777; margin-top: 5px; font-size: 14px; }
.db-badge { font-size: 12px; background: #e8f0fb; color: #0d5cab; padding: 4px 10px; border-radius: 20px; margin-left: 10px; }
.top-btn { background: #185FA5; color: white; border: none; padding: 11px 20px; border-radius: 10px; cursor: pointer; font-size: 14px; font-weight: 500; transition: .2s; }
.top-btn:hover { background: #0C447C; }

/* Stats */
.stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.stat-card { background: white; padding: 20px; border-radius: 14px; border: 0.5px solid #e5e7eb; }
.stat-card h3 { color: #9ca3af; font-size: 12px; font-weight: 500; margin-bottom: 10px; }
.stat-card h1 { font-size: 32px; color: #222; font-weight: 600; }
.stat-card p { margin-top: 6px; font-size: 12px; }
.green { color: #3B6D11; } .orange { color: #cc8800; } .blue { color: #185FA5; }

/* Content */
.staff-section { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
.table-box, .profile-box { background: white; border-radius: 14px; padding: 22px; border: 0.5px solid #e5e7eb; }
.table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
.table-header h2 { font-size: 15px; font-weight: 600; color: #111827; }

table { width: 100%; border-collapse: collapse; font-size: 13px; }
table th { text-align: left; color: #9ca3af; padding: 10px 8px; border-bottom: 0.5px solid #e5e7eb; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; }
table td { padding: 12px 8px; border-bottom: 0.5px solid #f3f4f6; vertical-align: middle; }
table tr:last-child td { border-bottom: none; }
table tr:hover td { background: #f9fafb; }

.status { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
.active-status { background: #EAF3DE; color: #3B6D11; }
.leave-status   { background: #FFF3D9; color: #cc8800; }
.offline-status { background: #FDE8E8; color: #A32D2D; }

.action-btn { background: #185FA5; color: white; border: none; padding: 6px 12px; border-radius: 7px; cursor: pointer; font-size: 12px; transition: .2s; }
.action-btn:hover { background: #0C447C; }
.del-btn { background: #FDE8E8; color: #A32D2D; border: none; padding: 6px 12px; border-radius: 7px; cursor: pointer; font-size: 12px; margin-left: 4px; transition: .2s; }
.del-btn:hover { background: #A32D2D; color: #fff; }

/* Profile Panel */
.profile-box h2 { font-size: 15px; font-weight: 600; margin-bottom: 18px; }
.staff-card { text-align: center; margin-bottom: 20px; }
.avatar { width: 80px; height: 80px; border-radius: 50%; background: #185FA5; color: white; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 600; margin: 0 auto 12px; }
.staff-name { font-size: 18px; font-weight: 600; color: #222; }
.staff-role { color: #9ca3af; margin-top: 4px; font-size: 13px; }
.info-item { display: flex; justify-content: space-between; padding: 11px 0; border-bottom: 0.5px solid #f3f4f6; font-size: 13px; }
.info-item span { color: #9ca3af; }
.info-item strong { color: #111827; }
.no-profile { text-align: center; color: #aaa; padding: 40px 0; font-size: 14px; }

/* Alert */
.alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 13px; font-weight: 500; }
.alert-success { background: #EAF3DE; color: #3B6D11; border: 1px solid #c3e6a1; }
.alert-error   { background: #FDE8E8; color: #A32D2D; border: 1px solid #f5c2c2; }

/* ── Modal Overlay ── */
.modal-overlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.45);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}
.modal-overlay.open { display: flex; }
.modal {
    background: #fff;
    border-radius: 16px;
    padding: 2rem;
    width: 460px;
    max-width: 95vw;
    box-shadow: 0 24px 60px rgba(0,0,0,0.18);
    animation: slideUp .25s ease;
}
@keyframes slideUp { from { transform: translateY(24px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}
.modal-header h2 { font-size: 17px; font-weight: 600; }
.modal-close {
    background: none; border: none;
    font-size: 22px; cursor: pointer;
    color: #9ca3af; line-height: 1;
}
.modal-close:hover { color: #374151; }

.form-group { margin-bottom: 14px; }
.form-group label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px; }
.form-group input,
.form-group select {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 13px;
    outline: none;
    transition: border .15s;
    color: #111827;
}
.form-group input:focus,
.form-group select:focus { border-color: #185FA5; }

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

.modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
.btn-cancel {
    padding: 9px 18px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 13px;
    cursor: pointer;
    background: #fff;
    color: #374151;
}
.btn-cancel:hover { background: #f9fafb; }
.btn-submit {
    padding: 9px 20px;
    background: #185FA5;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: .2s;
}
.btn-submit:hover { background: #0C447C; }

@media (max-width: 1000px) {
    .stats { grid-template-columns: 1fr 1fr; }
    .staff-section { grid-template-columns: 1fr; }
}
@media (max-width: 700px) {
    .sidebar { display: none; }
    .main { margin-left: 0; width: 100%; padding: 16px; }
    .stats { grid-template-columns: 1fr 1fr; }
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="logo">
        <img src="meghdut_photo-removebg-preview.png" width="100" height="100" alt="Logo">
    </div>
    <div class="menu">
        <div class="menu-title">Main</div>
        <a href="index.php">⊞ Dashboard</a>
        <a href="reservations.php">📅 Reservations</a>
        <a href="rooms.php">🛏 Rooms</a>
        <a href="guests.php">👤 Guests</a>
        <div class="menu-title">Operations</div>
        <a href="housekeeping.php">🧹 Housekeeping</a>
        <a href="billing.php">🧾 Billing</a>
        <a href="report.php">📊 Reports</a>
        <a href="restaurant.php">🍽 Restaurant</a>
        <div class="menu-title">System</div>
        <a href="settings.php">⚙ Settings</a>
        <a href="staff.php" class="active">👥 Staff</a>
    </div>
</div>

<!-- MAIN -->
<div class="main">

    <div class="topbar">
        <div>
            <h1>Staff Management <span class="db-badge">🟢 Live DB</span></h1>
            <p><?= date('l, F j, Y') ?></p>
        </div>
        <button class="top-btn" onclick="openModal()">+ Add Staff</button>
    </div>

    <!-- Alerts -->
    <?php if ($success_msg): ?>
    <div class="alert alert-success"><?= $success_msg ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
    <div class="alert alert-error"><?= $error_msg ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success">✅ Staff সফলভাবে delete হয়েছে।</div>
    <?php endif; ?>

    <!-- STATS -->
    <div class="stats">
        <div class="stat-card">
            <h3>Total Staff</h3>
            <h1><?= $totalStaff ?></h1>
            <p class="green">Admin + Receptionist</p>
        </div>
        <div class="stat-card">
            <h3>Admins</h3>
            <h1><?= $adminCount ?></h1>
            <p class="blue">System administrators</p>
        </div>
        <div class="stat-card">
            <h3>Receptionists</h3>
            <h1><?= $receptionCount ?></h1>
            <p class="orange">Front desk staff</p>
        </div>
        <div class="stat-card">
            <h3>Roles</h3>
            <h1><?= $deptCount ?></h1>
            <p class="green">All operational</p>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="staff-section">

        <!-- TABLE -->
        <div class="table-box">
            <div class="table-header">
                <h2>Staff List</h2>
                <span style="font-size:12px;color:#9ca3af;"><?= count($staffList) ?> members</span>
            </div>
            <table>
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($staffList)): ?>
                <tr><td colspan="6" style="text-align:center;color:#aaa;padding:30px;">No staff found. Add your first staff member!</td></tr>
                <?php else: foreach ($staffList as $s): ?>
                <tr style="cursor:pointer;" onclick="showProfile(
                    '<?= addslashes(htmlspecialchars($s['Name'])) ?>',
                    '<?= addslashes(roleLabel($s['Role'])) ?>',
                    '<?= addslashes(htmlspecialchars($s['Email'])) ?>',
                    '<?= addslashes(htmlspecialchars($s['Phone'] ?? '—')) ?>',
                    '#EMP<?= str_pad($s['User_id'], 3, '0', STR_PAD_LEFT) ?>',
                    '<?= addslashes($s['Role']) ?>',
                    '<?= date('d M Y', strtotime($s['Created_at'])) ?>'
                )">
                    <td><strong><?= htmlspecialchars($s['Name']) ?></strong></td>
                    <td style="color:#6b7280;"><?= htmlspecialchars($s['Email']) ?></td>
                    <td style="color:#6b7280;"><?= htmlspecialchars($s['Phone'] ?? '—') ?></td>
                    <td><?= roleBadge($s['Role']) ?></td>
                    <td style="color:#9ca3af;font-size:12px;"><?= date('d M Y', strtotime($s['Created_at'])) ?></td>
                    <td>
                        <button class="action-btn" onclick="event.stopPropagation()">View</button>
                        <a href="staff.php?delete=<?= $s['User_id'] ?>"
                           onclick="event.stopPropagation(); return confirm('<?= htmlspecialchars($s['Name']) ?> কে delete করবেন?')">
                            <button class="del-btn">Delete</button>
                        </a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- PROFILE PANEL -->
        <div class="profile-box" id="profileBox">
            <h2>Staff Profile</h2>
            <?php if ($firstStaff): ?>
            <div class="staff-card">
                <div class="avatar" id="profileAvatar"><?= avatarInitials($firstStaff['Name']) ?></div>
                <div class="staff-name" id="profileName"><?= htmlspecialchars($firstStaff['Name']) ?></div>
                <div class="staff-role" id="profileRole"><?= roleLabel($firstStaff['Role']) ?></div>
            </div>
            <div class="staff-info">
                <div class="info-item"><span>Employee ID</span><strong id="profileId">#EMP<?= str_pad($firstStaff['User_id'], 3, '0', STR_PAD_LEFT) ?></strong></div>
                <div class="info-item"><span>Phone</span><strong id="profilePhone"><?= htmlspecialchars($firstStaff['Phone'] ?? '—') ?></strong></div>
                <div class="info-item"><span>Email</span><strong id="profileEmail"><?= htmlspecialchars($firstStaff['Email']) ?></strong></div>
                <div class="info-item"><span>Department</span><strong id="profileDept"><?= ucfirst($firstStaff['Role']) ?></strong></div>
                <div class="info-item"><span>Joined</span><strong id="profileJoined"><?= date('d M Y', strtotime($firstStaff['Created_at'])) ?></strong></div>
                <div class="info-item"><span>Status</span><strong style="color:#3B6D11;">Active</strong></div>
            </div>
            <?php else: ?>
            <div class="no-profile">👤<br><br>No staff found.<br>Add a staff member to see their profile.</div>
            <?php endif; ?>
        </div>

    </div><!-- /staff-section -->
</div><!-- /main -->

<!-- ══════════════════════════════════════════
     ADD STAFF MODAL
══════════════════════════════════════════ -->
<div class="modal-overlay" id="addStaffModal">
  <div class="modal">
    <div class="modal-header">
      <h2>👤 Add New Staff Member</h2>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>

    <form method="POST" action="staff.php">
      <input type="hidden" name="action" value="add_staff">

      <div class="form-row">
        <div class="form-group">
          <label>Full Name *</label>
          <input type="text" name="name" placeholder="e.g. Rahim Uddin" required>
        </div>
        <div class="form-group">
          <label>Phone *</label>
          <input type="tel" name="phone" placeholder="01XXXXXXXXX" required>
        </div>
      </div>

      <div class="form-group">
        <label>Email Address *</label>
        <input type="email" name="email" placeholder="staff@example.com" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Role *</label>
          <select name="role" required>
            <option value="">— Select Role —</option>
            <option value="admin">Admin</option>
            <option value="receptionist">Receptionist</option>
          </select>
        </div>
        <div class="form-group">
          <label>Password *</label>
          <input type="password" name="password" placeholder="Min. 6 characters" minlength="6" required>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn-submit">+ Add Staff</button>
      </div>
    </form>
  </div>
</div>

<script>
// Modal open/close
function openModal()  { document.getElementById('addStaffModal').classList.add('open'); }
function closeModal() { document.getElementById('addStaffModal').classList.remove('open'); }

// Close on overlay click
document.getElementById('addStaffModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// Auto-open modal if there was a form error
<?php if ($error_msg): ?>
openModal();
<?php endif; ?>

// Profile panel update
function showProfile(name, role, email, phone, empId, dept, joined) {
    var parts    = name.trim().split(' ');
    var initials = parts.map(p => p[0] ? p[0].toUpperCase() : '').join('').substring(0, 2);
    document.getElementById('profileAvatar').textContent = initials;
    document.getElementById('profileName').textContent   = name;
    document.getElementById('profileRole').textContent   = role;
    document.getElementById('profileId').textContent     = empId;
    document.getElementById('profilePhone').textContent  = phone;
    document.getElementById('profileEmail').textContent  = email;
    document.getElementById('profileDept').textContent   = dept.charAt(0).toUpperCase() + dept.slice(1);
    document.getElementById('profileJoined').textContent = joined;
}
</script>
</body>
</html>
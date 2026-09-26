<?php
// ============================================================
//  MRMS — Staff Management Page (Option B - Separate Table)
//  Meghdoot Resort Management System | Group 06 | ISD 2026
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
include '../db.php';
include '../auth.php';

if (!in_array($_SESSION['role'], ['admin', 'manager'])) {
  header("Location: ../index.php");
  exit();
}

// ── Department & Role Map ────────────────────────────────────
$departments = [
  'Management'   => ['General Manager', 'Duty Manager', 'Assistant Manager'],
  'Front Desk'   => ['Receptionist', 'Concierge', 'Bell Boy'],
  'Housekeeping' => ['Head Housekeeper', 'Room Cleaner', 'Laundry Staff', 'Public Area Cleaner'],
  'Kitchen'      => ['Head Chef', 'Sous Chef', 'Cook', 'Kitchen Helper', 'Pastry Chef'],
  'Restaurant'   => ['Restaurant Manager', 'Waiter', 'Waitress', 'Cashier', 'Barista'],
  'Security'     => ['Security Manager', 'Security Guard'],
  'Maintenance'  => ['Maintenance Manager', 'Electrician', 'Plumber', 'AC Technician', 'Carpenter'],
  'Laundry'      => ['Laundry Supervisor', 'Laundry Staff'],
  'Accounts'     => ['Accountant', 'Accounts Assistant'],
  'IT'           => ['IT Manager', 'IT Support'],
];

// ── Handle Add Staff ─────────────────────────────────────────
$success_msg = '';
$error_msg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_staff') {
  $name       = trim(mysqli_real_escape_string($conn, $_POST['name']));
  $email      = trim(mysqli_real_escape_string($conn, $_POST['email']));
  $phone      = trim(mysqli_real_escape_string($conn, $_POST['phone']));
  $department = mysqli_real_escape_string($conn, $_POST['department']);
  $role       = mysqli_real_escape_string($conn, $_POST['role']);
  $salary     = floatval($_POST['salary']);
  $shift      = mysqli_real_escape_string($conn, $_POST['shift']);
  $joined_at  = mysqli_real_escape_string($conn, $_POST['joined_at']);
  $address    = mysqli_real_escape_string($conn, $_POST['address'] ?? '');

  if (!empty($email)) {
    $chk = mysqli_query($conn, "SELECT staff_id FROM staff WHERE email='$email'");
    if (mysqli_num_rows($chk) > 0) {
      $error_msg = "এই Email দিয়ে আগেই একজন staff আছে!";
    }
  }

  if (!$error_msg) {
    $q = mysqli_query($conn, "
            INSERT INTO staff (name, email, phone, department, role, salary, shift, joined_at, address, status, created_at)
            VALUES ('$name','$email','$phone','$department','$role',$salary,'$shift','$joined_at','$address','Active',NOW())
        ");
    if ($q) {
      $success_msg = " $name সফলভাবে add হয়েছে!";
    } else {
      $error_msg = 'Database error: ' . mysqli_error($conn);
    }
  }
}

// ── Handle Delete ────────────────────────────────────────────
if (isset($_GET['delete'])) {
  $del_id = intval($_GET['delete']);
  mysqli_query($conn, "DELETE FROM staff WHERE staff_id=$del_id");
  header('Location: staff.php?deleted=1');
  exit;
}

// ── Handle Status Toggle ─────────────────────────────────────
if (isset($_GET['toggle'])) {
  $tog_id = intval($_GET['toggle']);
  $cur = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM staff WHERE staff_id=$tog_id"));
  $new_status = ($cur['status'] === 'Active') ? 'On Leave' : 'Active';
  mysqli_query($conn, "UPDATE staff SET status='$new_status' WHERE staff_id=$tog_id");
  header('Location: staff.php');
  exit;
}

// ── Stats ────────────────────────────────────────────────────
$totalStaff  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM staff"))[0];
$activeStaff = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM staff WHERE status='Active'"))[0];
$onLeave     = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM staff WHERE status='On Leave'"))[0];
$deptCount   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(DISTINCT department) FROM staff"))[0];

// ── Filter ───────────────────────────────────────────────────
$filter_dept   = $_GET['dept']   ?? '';
$filter_status = $_GET['status'] ?? '';
$where = "WHERE 1=1";
if ($filter_dept)   $where .= " AND department='" . mysqli_real_escape_string($conn, $filter_dept) . "'";
if ($filter_status) $where .= " AND status='"     . mysqli_real_escape_string($conn, $filter_status) . "'";

// ── Staff List ───────────────────────────────────────────────
$staffResult = mysqli_query($conn, "SELECT * FROM staff $where ORDER BY department ASC, name ASC");
$staffList = [];
while ($row = mysqli_fetch_assoc($staffResult)) $staffList[] = $row;
$firstStaff = !empty($staffList) ? $staffList[0] : null;

$deptList = mysqli_query($conn, "SELECT DISTINCT department FROM staff ORDER BY department");

// ── Helpers ──────────────────────────────────────────────────
function statusBadge($s)
{
  if ($s === 'Active')   return '<span class="badge badge-green">Active</span>';
  if ($s === 'On Leave') return '<span class="badge badge-orange">On Leave</span>';
  return '<span class="badge badge-red">Resigned</span>';
}
function shiftBadge($s)
{
  $colors = ['Morning' => 'badge-blue', 'Evening' => 'badge-orange', 'Night' => 'badge-dark', 'Rotating' => 'badge-purple'];
  $c = $colors[$s] ?? 'badge-blue';
  return "<span class=\"badge $c\">$s</span>";
}
function deptIcon($d)
{
  $icons = ['Management' => '', 'Front Desk' => '', 'Housekeeping' => '', 'Kitchen' => '‍', 'Restaurant' => '', 'Security' => '', 'Maintenance' => '', 'Laundry' => '', 'Accounts' => '', 'IT' => ''];
  return $icons[$d] ?? '';
}
function avatarInitials($name)
{
  $parts = explode(' ', trim($name));
  $initials = '';
  foreach ($parts as $p) $initials .= strtoupper(substr($p, 0, 1));
  return substr($initials, 0, 2);
}
function deptColor($d)
{
  $colors = ['Management' => '#6366f1', 'Front Desk' => '#185FA5', 'Housekeeping' => '#3B6D11', 'Kitchen' => '#d97706', 'Restaurant' => '#db2777', 'Security' => '#7c3aed', 'Maintenance' => '#0891b2', 'Laundry' => '#059669', 'Accounts' => '#dc2626', 'IT' => '#0d9488'];
  return $colors[$d] ?? '#6b7280';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management — MRMS</title>
    <style>
    *,
    *::before,
    *::after {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        background: #f3f4f6;
        display: flex;
        font-family: system-ui, -apple-system, sans-serif;
        color: #111827;
    }

    .sidebar {
        width: 240px;
        height: 100vh;
        background: #fff;
        border-right: 0.5px solid #e5e7eb;
        position: fixed;
        left: 0;
        top: 0;
        display: flex;
        flex-direction: column;
        overflow-y: auto;
    }

    .sidebar-logo {
        padding: 1.25rem 1.5rem;
        border-bottom: 0.5px solid #e5e7eb;
    }

    .sidebar-section-label {
        font-size: 10px;
        font-weight: 600;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .08em;
        padding: 1.25rem 1.5rem 0.4rem;
    }

    .sidebar-nav a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 1.5rem;
        font-size: 14px;
        color: #374151;
        text-decoration: none;
        transition: background .1s;
    }

    .sidebar-nav a:hover {
        background: #f9fafb;
    }

    .sidebar-nav a.active {
        background: #EFF6FF;
        color: #185FA5;
        font-weight: 600;
        border-right: 2px solid #185FA5;
    }

    .sidebar-bottom {
        margin-top: auto;
        padding: 1rem 1.5rem;
        border-top: 0.5px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .avatar-sm {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #185FA5;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
    }

    .main {
        margin-left: 240px;
        width: calc(100% - 240px);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .topbar {
        background: #fff;
        border-bottom: 0.5px solid #e5e7eb;
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .topbar h1 {
        font-size: 20px;
        font-weight: 600;
    }

    .topbar-date {
        font-size: 13px;
        color: #6b7280;
        margin-top: 2px;
    }

    .btn-primary {
        background: #185FA5;
        color: #fff;
        border: none;
        padding: 9px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: background .15s;
    }

    .btn-primary:hover {
        background: #0C447C;
    }

    .alert {
        padding: 12px 16px;
        border-radius: 10px;
        margin: 1rem 1.5rem 0;
        font-size: 13px;
        font-weight: 500;
    }

    .alert-success {
        background: #EAF3DE;
        color: #3B6D11;
        border: 1px solid #c3e6a1;
    }

    .alert-error {
        background: #FDE8E8;
        color: #A32D2D;
        border: 1px solid #f5c2c2;
    }

    .stats-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        padding: 1rem 1.5rem;
    }

    .stat-card {
        background: #fff;
        border: 0.5px solid #e5e7eb;
        border-radius: 10px;
        padding: .875rem 1rem;
    }

    .stat-label {
        font-size: 12px;
        color: #9ca3af;
        margin-bottom: 4px;
    }

    .stat-val {
        font-size: 28px;
        font-weight: 600;
    }

    .stat-sub {
        font-size: 12px;
        color: #9ca3af;
        margin-top: 2px;
    }

    .filter-row {
        padding: .75rem 1.5rem;
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }

    .search-box {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #fff;
        border: 0.5px solid #e5e7eb;
        border-radius: 8px;
        padding: 7px 12px;
    }

    .search-box input {
        border: none;
        background: transparent;
        font-size: 13px;
        outline: none;
        width: 200px;
    }

    .filter-select {
        padding: 7px 12px;
        border: 0.5px solid #e5e7eb;
        border-radius: 8px;
        font-size: 13px;
        background: #fff;
        outline: none;
        cursor: pointer;
        color: #374151;
    }

    .content {
        padding: 0 1.5rem 1.5rem;
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 16px;
        margin-top: 12px;
    }

    .table-box {
        background: #fff;
        border: 0.5px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.25rem;
        overflow: hidden;
    }

    .box-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .box-header h2 {
        font-size: 15px;
        font-weight: 600;
    }

    .box-header span {
        font-size: 12px;
        color: #9ca3af;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    table th {
        text-align: left;
        font-size: 11px;
        font-weight: 600;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .06em;
        padding: 10px 8px;
        border-bottom: 0.5px solid #e5e7eb;
    }

    table td {
        padding: 11px 8px;
        border-bottom: 0.5px solid #f3f4f6;
        vertical-align: middle;
    }

    table tr:last-child td {
        border-bottom: none;
    }

    table tr:hover td {
        background: #f9fafb;
    }

    .dept-tag {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
    }

    .badge {
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
    }

    .badge-green {
        background: #EAF3DE;
        color: #3B6D11;
    }

    .badge-orange {
        background: #FFF3D9;
        color: #cc8800;
    }

    .badge-red {
        background: #FDE8E8;
        color: #A32D2D;
    }

    .badge-blue {
        background: #EFF6FF;
        color: #185FA5;
    }

    .badge-dark {
        background: #1f2937;
        color: #f9fafb;
    }

    .badge-purple {
        background: #ede9fe;
        color: #6d28d9;
    }

    .action-btn {
        background: #185FA5;
        color: #fff;
        border: none;
        padding: 5px 10px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 11px;
        transition: .15s;
    }

    .tog-btn {
        background: #FFF3D9;
        color: #cc8800;
        border: none;
        padding: 5px 10px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 11px;
        margin-left: 3px;
    }

    .del-btn {
        background: #FDE8E8;
        color: #A32D2D;
        border: none;
        padding: 5px 10px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 11px;
        margin-left: 3px;
    }

    .del-btn:hover {
        background: #A32D2D;
        color: #fff;
    }

    .profile-box {
        background: #fff;
        border: 0.5px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.25rem;
    }

    .profile-box h2 {
        font-size: 15px;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .profile-avatar {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        font-weight: 700;
        color: #fff;
        margin: 0 auto 10px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 0.5px solid #f3f4f6;
        font-size: 13px;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-row span {
        color: #9ca3af;
    }

    .no-profile {
        text-align: center;
        color: #9ca3af;
        padding: 40px 0;
        font-size: 13px;
    }

    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.45);
        z-index: 999;
        align-items: center;
        justify-content: center;
    }

    .modal-overlay.open {
        display: flex;
    }

    .modal {
        background: #fff;
        border-radius: 16px;
        padding: 1.75rem;
        width: 520px;
        max-width: 96vw;
        max-height: 92vh;
        overflow-y: auto;
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.18);
        animation: slideUp .22s ease;
    }

    @keyframes slideUp {
        from {
            transform: translateY(20px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.25rem;
    }

    .modal-header h2 {
        font-size: 16px;
        font-weight: 700;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        color: #9ca3af;
    }

    .modal-close:hover {
        color: #374151;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .form-group {
        margin-bottom: 12px;
    }

    .form-group label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 4px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 8px 11px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 13px;
        outline: none;
        transition: border .15s;
        color: #111827;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: #185FA5;
    }

    .form-group textarea {
        resize: none;
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 16px;
    }

    .btn-cancel {
        padding: 9px 18px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 13px;
        cursor: pointer;
        background: #fff;
    }

    .section-divider {
        font-size: 11px;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .08em;
        margin: 14px 0 10px;
        border-bottom: 0.5px solid #e5e7eb;
        padding-bottom: 6px;
    }

    @media (max-width: 1100px) {
        .stats-row {
            grid-template-columns: 1fr 1fr;
        }

        .content {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 700px) {
        .sidebar {
            display: none;
        }

        .main {
            margin-left: 0;
            width: 100%;
        }

        .stats-row {
            grid-template-columns: 1fr 1fr;
        }
    }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="sidebar-logo">
            <img src="meghdut_photo-removebg-preview.png" width="100" height="100" alt="Logo">
        </div>
        <div class="sidebar-section-label">Main</div>
        <nav class="sidebar-nav">
            <a href="index.php">⊞ Dashboard</a>
            <a href="reservations.php"> Reservations</a>
            <a href="rooms.php"> Rooms</a>
            <a href="guests.php"> Guests</a>
        </nav>
        <div class="sidebar-section-label">Operations</div>
        <nav class="sidebar-nav">
            <a href="housekeeping.php"> Housekeeping</a>
            <a href="billing.php"> Billing</a>
            <a href="report.php"> Reports</a>
            <a href="restaurant.php"> Restaurant</a>
        </nav>
        <div class="sidebar-section-label">System</div>
        <nav class="sidebar-nav">
            <a href="settings.php"> Settings</a>
            <a href="staff.php" class="active"> Staff</a>
        </nav>
        <div class="sidebar-bottom">
            <div class="avatar-sm">AD</div>
            <div>
                <div style="font-size:13px;font-weight:600;">Admin</div>
                <div style="font-size:11px;color:#9ca3af;">Manager</div>
            </div>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <div>
                <h1>Staff Management</h1>
                <div class="topbar-date"><?= date('l, d F Y') ?></div>
            </div>
            <button class="btn-primary" onclick="openModal()">+ Add Staff</button>
        </div>

        <?php if ($success_msg): ?><div class="alert alert-success"><?= $success_msg ?></div><?php endif; ?>
        <?php if ($error_msg):   ?><div class="alert alert-error"><?= $error_msg ?></div><?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success"> Staff সফলভাবে delete হয়েছে।</div>
        <?php endif; ?>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Total Staff</div>
                <div class="stat-val"><?= $totalStaff ?></div>
                <div class="stat-sub">All departments</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Active</div>
                <div class="stat-val" style="color:#3B6D11;"><?= $activeStaff ?></div>
                <div class="stat-sub" style="color:#3B6D11;">Currently working</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">On Leave</div>
                <div class="stat-val" style="color:#cc8800;"><?= $onLeave ?></div>
                <div class="stat-sub" style="color:#cc8800;">Temporarily absent</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Departments</div>
                <div class="stat-val" style="color:#185FA5;"><?= $deptCount ?></div>
                <div class="stat-sub" style="color:#185FA5;">Active units</div>
            </div>
        </div>

        <div class="filter-row">
            <div class="search-box">
                <span style="color:#9ca3af;"></span>
                <input type="text" id="searchInput" placeholder="Search name, role…" oninput="filterTable()">
            </div>
            <form method="GET" style="display:flex;gap:8px;">
                <select name="dept" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Departments</option>
                    <?php while ($d = mysqli_fetch_row($deptList)): ?>
                    <option value="<?= $d[0] ?>" <?= $filter_dept === $d[0] ? 'selected' : '' ?>><?= deptIcon($d[0]) ?>
                        <?= $d[0] ?></option>
                    <?php endwhile; ?>
                </select>
                <select name="status" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="Active" <?= $filter_status === 'Active'   ? 'selected' : '' ?>>Active</option>
                    <option value="On Leave" <?= $filter_status === 'On Leave' ? 'selected' : '' ?>>On Leave</option>
                    <option value="Resigned" <?= $filter_status === 'Resigned' ? 'selected' : '' ?>>Resigned</option>
                </select>
                <?php if ($filter_dept || $filter_status): ?>
                <a href="staff.php"
                    style="padding:7px 12px;background:#f3f4f6;border-radius:8px;font-size:13px;color:#374151;text-decoration:none;">
                    Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="content">
            <div class="table-box">
                <div class="box-header">
                    <h2> Staff List</h2>
                    <span><?= count($staffList) ?> members</span>
                </div>
                <table id="staffTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Shift</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($staffList)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center;color:#9ca3af;padding:30px;">No staff found. Add
                                your first staff member!</td>
                        </tr>
                        <?php else: foreach ($staffList as $s):
                $clr = deptColor($s['department']);
              ?>
                        <tr style="cursor:pointer;"
                            onclick="showProfile(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)">
                            <td>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div
                                        style="width:32px;height:32px;border-radius:50%;background:<?= $clr ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;">
                                        <?= avatarInitials($s['name']) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($s['name']) ?>
                                        </div>
                                        <div style="font-size:11px;color:#9ca3af;">
                                            #EMP<?= str_pad($s['staff_id'], 3, '0', STR_PAD_LEFT) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="dept-tag" style="background:<?= $clr ?>22;color:<?= $clr ?>;">
                                    <?= deptIcon($s['department']) ?> <?= $s['department'] ?>
                                </span>
                            </td>
                            <td style="color:#374151;"><?= htmlspecialchars($s['role']) ?></td>
                            <td><?= shiftBadge($s['shift']) ?></td>
                            <td style="color:#6b7280;font-size:12px;"><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
                            <td><?= statusBadge($s['status']) ?></td>
                            <td onclick="event.stopPropagation()">
                                <a href="staff.php?toggle=<?= $s['staff_id'] ?>">
                                    <button class="tog-btn" title="Toggle Leave/Active">⇄</button>
                                </a>
                                <a href="staff.php?delete=<?= $s['staff_id'] ?>"
                                    onclick="return confirm('<?= htmlspecialchars($s['name']) ?> কে delete করবেন?')">
                                    <button class="del-btn"></button>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach;
            endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="profile-box">
                <h2>Staff Profile</h2>
                <?php if ($firstStaff): $clr = deptColor($firstStaff['department']); ?>
                <div id="profileCard">
                    <div style="text-align:center;margin-bottom:16px;">
                        <div class="profile-avatar" id="pAvatar" style="background:<?= $clr ?>;">
                            <?= avatarInitials($firstStaff['name']) ?></div>
                        <div style="font-size:17px;font-weight:700;" id="pName">
                            <?= htmlspecialchars($firstStaff['name']) ?></div>
                        <div style="font-size:13px;color:#6b7280;margin-top:3px;" id="pRole">
                            <?= htmlspecialchars($firstStaff['role']) ?></div>
                        <div style="margin:8px 0 16px;">
                            <span class="dept-tag" id="pDeptTag" style="background:<?= $clr ?>22;color:<?= $clr ?>;">
                                <?= deptIcon($firstStaff['department']) ?> <span
                                    id="pDept"><?= $firstStaff['department'] ?></span>
                            </span>
                        </div>
                    </div>
                    <div>
                        <div class="info-row"><span>Employee ID</span><strong
                                id="pId">#EMP<?= str_pad($firstStaff['staff_id'], 3, '0', STR_PAD_LEFT) ?></strong>
                        </div>
                        <div class="info-row"><span>Phone</span><strong
                                id="pPhone"><?= htmlspecialchars($firstStaff['phone'] ?? '—') ?></strong></div>
                        <div class="info-row"><span>Email</span><strong id="pEmail"
                                style="font-size:12px;"><?= htmlspecialchars($firstStaff['email'] ?? '—') ?></strong>
                        </div>
                        <div class="info-row"><span>Shift</span><strong id="pShift"><?= $firstStaff['shift'] ?></strong>
                        </div>
                        <div class="info-row"><span>Salary</span><strong
                                id="pSalary">৳<?= number_format($firstStaff['salary']) ?></strong></div>
                        <div class="info-row"><span>Joined</span><strong
                                id="pJoined"><?= date('d M Y', strtotime($firstStaff['joined_at'])) ?></strong></div>
                        <div class="info-row"><span>Status</span><span
                                id="pStatus"><?= statusBadge($firstStaff['status']) ?></span></div>
                    </div>
                </div>
                <?php else: ?>
                <div class="no-profile"><br><br>No staff found.<br>Add your first staff member!</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ADD STAFF MODAL -->
    <div class="modal-overlay" id="addStaffModal">
        <div class="modal">
            <div class="modal-header">
                <h2> Add New Staff Member</h2>
                <button class="modal-close" onclick="closeModal()"></button>
            </div>
            <form method="POST" action="staff.php">
                <input type="hidden" name="action" value="add_staff">

                <div class="section-divider">Personal Information</div>
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
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="staff@hotel.com">
                    </div>
                    <div class="form-group">
                        <label>Join Date *</label>
                        <input type="date" name="joined_at" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="2" placeholder="Staff home address…"></textarea>
                </div>

                <div class="section-divider">Job Information</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Department *</label>
                        <select name="department" id="deptSelect" required onchange="loadRoles(this.value)">
                            <option value="">— Select Department —</option>
                            <?php foreach ($departments as $dept => $roles): ?>
                            <option value="<?= $dept ?>"><?= deptIcon($dept) ?> <?= $dept ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Role *</label>
                        <select name="role" id="roleSelect" required>
                            <option value="">— Select Department First —</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Shift *</label>
                        <select name="shift" required>
                            <option value="Morning"> Morning</option>
                            <option value="Evening"> Evening</option>
                            <option value="Night"> Night</option>
                            <option value="Rotating"> Rotating</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Monthly Salary (৳)</label>
                        <input type="number" name="salary" placeholder="e.g. 15000" min="0">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary">+ Add Staff</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const deptRoles = <?= json_encode($departments) ?>;
    const deptColors = {
        'Management': '#6366f1',
        'Front Desk': '#185FA5',
        'Housekeeping': '#3B6D11',
        'Kitchen': '#d97706',
        'Restaurant': '#db2777',
        'Security': '#7c3aed',
        'Maintenance': '#0891b2',
        'Laundry': '#059669',
        'Accounts': '#dc2626',
        'IT': '#0d9488'
    };
    const deptIcons = {
        'Management': '',
        'Front Desk': '',
        'Housekeeping': '',
        'Kitchen': '‍',
        'Restaurant': '',
        'Security': '',
        'Maintenance': '',
        'Laundry': '',
        'Accounts': '',
        'IT': ''
    };

    function openModal() {
        document.getElementById('addStaffModal').classList.add('open');
    }

    function closeModal() {
        document.getElementById('addStaffModal').classList.remove('open');
    }
    document.getElementById('addStaffModal').addEventListener('click', e => {
        if (e.target === document.getElementById('addStaffModal')) closeModal();
    });
    <?php if ($error_msg): ?>openModal();
    <?php endif; ?>

    function loadRoles(dept) {
        const sel = document.getElementById('roleSelect');
        sel.innerHTML = '<option value="">— Select Role —</option>';
        if (deptRoles[dept]) deptRoles[dept].forEach(r => {
            sel.innerHTML += `<option value="${r}">${r}</option>`;
        });
    }

    function filterTable() {
        const q = document.getElementById('searchInput').value.toLowerCase();
        document.querySelectorAll('#staffTable tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    }

    function showProfile(s) {
        const clr = deptColors[s.department] || '#6b7280';
        const icon = deptIcons[s.department] || '';
        const initials = s.name.trim().split(' ').map(p => p[0] ? p[0].toUpperCase() : '').join('').substring(0, 2);
        const empId = '#EMP' + String(s.staff_id).padStart(3, '0');
        const salary = '৳' + parseInt(s.salary || 0).toLocaleString();
        const joined = new Date(s.joined_at).toLocaleDateString('en-BD', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });

        document.getElementById('pAvatar').textContent = initials;
        document.getElementById('pAvatar').style.background = clr;
        document.getElementById('pName').textContent = s.name;
        document.getElementById('pRole').textContent = s.role;
        document.getElementById('pDept').textContent = s.department;
        document.getElementById('pDeptTag').style.background = clr + '22';
        document.getElementById('pDeptTag').style.color = clr;
        document.getElementById('pId').textContent = empId;
        document.getElementById('pPhone').textContent = s.phone || '—';
        document.getElementById('pEmail').textContent = s.email || '—';
        document.getElementById('pShift').textContent = s.shift;
        document.getElementById('pSalary').textContent = salary;
        document.getElementById('pJoined').textContent = joined;
        const statusMap = {
            'Active': '<span class="badge badge-green">Active</span>',
            'On Leave': '<span class="badge badge-orange">On Leave</span>',
            'Resigned': '<span class="badge badge-red">Resigned</span>'
        };
        document.getElementById('pStatus').innerHTML = statusMap[s.status] || s.status;
    }
    </script>
</body>

</html>
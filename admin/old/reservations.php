<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../db.php';
include '../auth.php';

if (!in_array($_SESSION['role'], ['admin', 'manager'])) {
  header("Location: ../index.php");
  exit();
}

// ─── Database থেকে reservations আনো ─────────────────────────────────────────
$sql = "
SELECT
    b.booking_id AS id,
    u.Name       AS name,
    u.Phone      AS phone,
    r.room_number AS room,
    r.type,
    b.check_in,
    b.check_out,
    DATEDIFF(b.check_out, b.check_in)                        AS nights,
    (DATEDIFF(b.check_out, b.check_in) * r.price_per_night)  AS amount,
    b.status
FROM booking b
INNER JOIN user u ON b.guest_id = u.User_id
INNER JOIN room r ON b.room_id  = r.room_id
ORDER BY b.booking_id DESC
";

$result       = mysqli_query($conn, $sql);
$reservations = [];

while ($row = mysqli_fetch_assoc($result)) {
  $parts = explode(' ', trim($row['name']));
  $row['initials'] = strtoupper(
    substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : substr($parts[0], 1, 1))
  );

  switch (strtolower($row['status'])) {
    case 'checked-in':
      $row['status'] = 'checkedin';
      break;
    case 'checked-out':
      $row['status'] = 'checkout';
      break;
    case 'confirmed':
      $row['status'] = 'reserved';
      break;
    case 'pending':
      $row['status'] = 'reserved';
      break;
    case 'cancelled':
      $row['status'] = 'cancelled';
      break;
    default:
      $row['status'] = 'reserved';
  }

  $reservations[] = $row;
}

$pendingCount = mysqli_fetch_assoc(
  mysqli_query($conn, "SELECT COUNT(*) AS total FROM booking WHERE status='Pending'")
)['total'];

$countAll       = count($reservations);
$countCheckedin = count(array_filter($reservations, fn($r) => $r['status'] === 'checkedin'));
$countReserved  = count(array_filter($reservations, fn($r) => $r['status'] === 'reserved'));
$countCheckout  = count(array_filter($reservations, fn($r) => $r['status'] === 'checkout'));
$countCancelled = count(array_filter($reservations, fn($r) => $r['status'] === 'cancelled'));

$active_tab    = $_GET['tab']    ?? 'all';
$search        = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';
$type_filter   = $_GET['type']   ?? '';

$filtered = array_filter($reservations, function ($r) use ($active_tab, $search, $status_filter, $type_filter) {
  if ($active_tab !== 'all' && $r['status'] !== $active_tab) return false;
  if ($search && stripos($r['name'], $search) === false && stripos($r['room'], $search) === false) return false;
  if ($status_filter && $r['status'] !== $status_filter) return false;
  if ($type_filter   && $r['type']   !== $type_filter)   return false;
  return true;
});

$status_map = [
  'checkedin' => ['label' => 'Checked in',   'bg' => '#EAF3DE', 'color' => '#3B6D11'],
  'reserved'  => ['label' => 'Reserved',     'bg' => '#E6F1FB', 'color' => '#185FA5'],
  'checkout'  => ['label' => 'Checking out', 'bg' => '#FAEEDA', 'color' => '#854F0B'],
  'cancelled' => ['label' => 'Cancelled',    'bg' => '#FCEBEB', 'color' => '#A32D2D'],
];

$avatar_palette = [
  ['bg' => '#E6F1FB', 'color' => '#185FA5'],
  ['bg' => '#E1F5EE', 'color' => '#0F6E56'],
  ['bg' => '#FAEEDA', 'color' => '#854F0B'],
  ['bg' => '#FAECE7', 'color' => '#993C1D'],
  ['bg' => '#EEEDFE', 'color' => '#534AB7'],
  ['bg' => '#EAF3DE', 'color' => '#3B6D11'],
];

$tabs = [
  ['key' => 'all',       'label' => 'All',          'count' => $countAll],
  ['key' => 'checkedin', 'label' => 'Checked in',   'count' => $countCheckedin],
  ['key' => 'reserved',  'label' => 'Reserved',     'count' => $countReserved],
  ['key' => 'checkout',  'label' => 'Checking out', 'count' => $countCheckout],
  ['key' => 'cancelled', 'label' => 'Cancelled',    'count' => $countCancelled],
];

function fmt_date($d)
{
  return date('d M', strtotime($d));
}
function current_url_with($params)
{
  return '?' . http_build_query(array_merge($_GET, $params));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations — Meghdut MRMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <!-- SheetJS for Excel export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
    *,
    *::before,
    *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Poppins', sans-serif;
    }

    body {
        background: #f3f4f6;
        color: #111827;
        min-height: 100vh;
    }

    .layout {
        display: flex;
        min-height: 100vh;
    }

    /* ── Sidebar ── */
    .sidebar {
        width: 240px;
        background: #fff;
        border-right: 0.5px solid #e5e7eb;
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
    }

    .sidebar-logo {
        padding: 1rem 1.5rem;
        border-bottom: 0.5px solid #e5e7eb;
        text-align: center;
    }

    .sidebar-logo img {
        width: 72px;
        height: 72px;
        object-fit: contain;
    }

    .sidebar-section-label {
        font-size: 10px;
        font-weight: 600;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: 0.08em;
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
        transition: background 0.1s;
    }

    .sidebar-nav a:hover {
        background: #f9fafb;
    }

    .sidebar-nav a.active {
        background: #EFF6FF;
        color: #185FA5;
        font-weight: 500;
        border-right: 2px solid #185FA5;
    }

    .sidebar-nav a .icon {
        font-size: 16px;
        opacity: 0.7;
    }

    .sidebar-nav a .badge {
        margin-left: auto;
        background: #185FA5;
        color: #fff;
        border-radius: 10px;
        font-size: 11px;
        padding: 1px 7px;
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
        flex-shrink: 0;
    }

    .sidebar-user-name {
        font-size: 13px;
        font-weight: 500;
    }

    .sidebar-user-role {
        font-size: 11px;
        color: #9ca3af;
    }

    /* ── Main ── */
    .main {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    /* ── Topbar ── */
    .topbar {
        background: #fff;
        border-bottom: 0.5px solid #e5e7eb;
        padding: 1.25rem 1.5rem 1rem;
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

    .topbar-actions {
        display: flex;
        gap: 8px;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 14px;
        cursor: pointer;
        text-decoration: none;
        border: 0.5px solid #e5e7eb;
        background: #fff;
        color: #374151;
        transition: background 0.15s;
    }

    .btn:hover {
        background: #f9fafb;
    }

    .btn-primary {
        background: #0d5ea8;
        color: #fff;
        border-color: #0d5ea8;
    }

    .btn-primary:hover {
        background: #0a4d8c;
    }

    /* ── Tabs ── */
    .tab-row {
        display: flex;
        background: #fff;
        border-bottom: 0.5px solid #e5e7eb;
        padding: 0 1.5rem;
        overflow-x: auto;
    }

    .tab-row a {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 14px;
        font-size: 13px;
        font-weight: 500;
        color: #6b7280;
        text-decoration: none;
        border-bottom: 2px solid transparent;
        white-space: nowrap;
        transition: color 0.15s;
    }

    .tab-row a:hover {
        color: #111827;
    }

    .tab-row a.active {
        color: #0d5ea8;
        border-bottom-color: #0d5ea8;
    }

    .tab-badge {
        border-radius: 10px;
        font-size: 11px;
        padding: 1px 7px;
        background: #E6F1FB;
        color: #185FA5;
    }

    .tab-row a.active .tab-badge {
        background: #0d5ea8;
        color: #fff;
    }

    /* ── Filters ── */
    .filters {
        background: #fff;
        border-bottom: 0.5px solid #e5e7eb;
        padding: 0.875rem 1.5rem;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }

    .search-box {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #f3f4f6;
        border: 0.5px solid #e5e7eb;
        border-radius: 8px;
        padding: 7px 12px;
        flex: 1;
        min-width: 180px;
        max-width: 260px;
    }

    .search-box input {
        border: none;
        background: transparent;
        font-size: 14px;
        color: #111827;
        outline: none;
        width: 100%;
    }

    .filter-select {
        background: #f3f4f6;
        border: 0.5px solid #e5e7eb;
        border-radius: 8px;
        padding: 7px 12px;
        font-size: 13px;
        color: #374151;
        cursor: pointer;
        outline: none;
    }

    /* ── Table ── */
    .table-wrap {
        flex: 1;
        overflow: auto;
        padding: 0 1.5rem;
        background: #f3f4f6;
    }

    .res-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        margin: 1.25rem 0;
        border: 0.5px solid #e5e7eb;
        min-width: 700px;
    }

    .res-table thead th {
        text-align: left;
        font-size: 11px;
        font-weight: 500;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        padding: 12px 12px 10px;
        border-bottom: 0.5px solid #e5e7eb;
        white-space: nowrap;
        background: #fafafa;
    }

    .res-table tbody tr {
        border-bottom: 0.5px solid #f3f4f6;
        transition: background 0.1s;
    }

    .res-table tbody tr:last-child {
        border-bottom: none;
    }

    .res-table tbody tr:hover {
        background: #f9fafb;
    }

    .res-table td {
        padding: 12px;
        vertical-align: middle;
    }

    .guest-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 500;
        flex-shrink: 0;
    }

    .guest-name {
        font-weight: 500;
        font-size: 13px;
    }

    .guest-sub {
        font-size: 11px;
        color: #9ca3af;
    }

    .room-pill {
        display: inline-block;
        background: #f3f4f6;
        border: 0.5px solid #e5e7eb;
        border-radius: 6px;
        padding: 3px 9px;
        font-size: 12px;
        font-weight: 500;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border-radius: 20px;
        padding: 3px 10px;
        font-size: 12px;
        font-weight: 500;
        white-space: nowrap;
    }

    .status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        display: inline-block;
    }

    .action-btn {
        background: none;
        border: 0.5px solid #e5e7eb;
        border-radius: 8px;
        padding: 5px 10px;
        font-size: 13px;
        color: #6b7280;
        cursor: pointer;
        text-decoration: none;
    }

    .action-btn:hover {
        background: #f3f4f6;
    }

    .cancelled-amount {
        text-decoration: line-through;
        color: #9ca3af;
    }

    /* ── Footer bar ── */
    .footer-bar {
        background: #fff;
        border-top: 0.5px solid #e5e7eb;
        padding: 12px 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .footer-text {
        font-size: 12px;
        color: #9ca3af;
    }

    .pagination {
        display: flex;
        gap: 4px;
    }

    .page-btn {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        border: 0.5px solid #e5e7eb;
        background: transparent;
        color: #6b7280;
        font-size: 13px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: background 0.1s;
    }

    .page-btn:hover {
        background: #f3f4f6;
    }

    .page-btn.active {
        background: #0d5ea8;
        color: #fff;
        border-color: #0d5ea8;
    }

    /* ── Modal (shared) ── */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.4);
        z-index: 200;
        align-items: center;
        justify-content: center;
    }

    .modal-overlay.open {
        display: flex;
    }

    .modal {
        background: #fff;
        border-radius: 12px;
        border: 0.5px solid #e5e7eb;
        padding: 1.5rem;
        width: 440px;
        max-width: 95vw;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.25rem;
    }

    .modal-title {
        font-size: 16px;
        font-weight: 600;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 22px;
        color: #9ca3af;
        cursor: pointer;
        line-height: 1;
    }

    .modal-close:hover {
        color: #374151;
    }

    .form-row {
        margin-bottom: 1rem;
    }

    .form-row-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 1rem;
    }

    .form-label {
        font-size: 12px;
        color: #6b7280;
        display: block;
        margin-bottom: 4px;
        font-weight: 500;
    }

    .form-input {
        width: 100%;
        background: #f9fafb;
        border: 0.5px solid #e5e7eb;
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 14px;
        color: #111827;
        outline: none;
        transition: border-color 0.15s;
    }

    .form-input:focus {
        border-color: #0d5ea8;
        background: #fff;
    }

    .modal-footer {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
        margin-top: 1.25rem;
    }

    /* ── Export Modal specific ── */
    .export-modal {
        width: 480px;
    }

    .exp-section {
        margin-bottom: 1rem;
    }

    .exp-label {
        font-size: 11px;
        font-weight: 600;
        color: #374151;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 6px;
    }

    .exp-sublabel {
        font-size: 11px;
        color: #6b7280;
        margin-bottom: 4px;
    }

    .exp-date-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .exp-divider {
        height: 0.5px;
        background: #e5e7eb;
        margin: 1rem 0;
    }

    .chip-wrap {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }

    .chip {
        padding: 4px 12px;
        border-radius: 20px;
        border: 0.5px solid #e5e7eb;
        font-size: 12px;
        cursor: pointer;
        color: #374151;
        background: #fff;
        transition: all 0.12s;
        user-select: none;
    }

    .chip:hover {
        border-color: #185FA5;
        color: #185FA5;
    }

    .chip.active {
        background: #185FA5;
        color: #fff;
        border-color: #185FA5;
    }

    .fmt-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .fmt-btn {
        border: 0.5px solid #e5e7eb;
        background: #fff;
        border-radius: 8px;
        padding: 10px 8px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
        cursor: pointer;
        transition: all 0.15s;
    }

    .fmt-btn:hover {
        border-color: #185FA5;
        background: #EFF6FF;
    }

    .fmt-btn.active {
        border: 1.5px solid #185FA5;
        background: #EFF6FF;
    }

    .fmt-icon {
        font-size: 20px;
    }

    .fmt-label {
        font-size: 12px;
        font-weight: 500;
        color: #374151;
    }

    .fmt-btn.active .fmt-label {
        color: #185FA5;
    }

    .col-list {
        display: flex;
        flex-direction: column;
        gap: 7px;
    }

    .col-row {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #374151;
        cursor: pointer;
    }

    .col-row input[type="checkbox"] {
        accent-color: #185FA5;
        width: 14px;
        height: 14px;
        cursor: pointer;
    }

    .exp-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .exp-summary {
        font-size: 12px;
        color: #6b7280;
    }

    .exp-summary strong {
        color: #111827;
    }

    .exp-btn-group {
        display: flex;
        gap: 8px;
    }

    .spinner {
        display: inline-block;
        width: 12px;
        height: 12px;
        border: 2px solid rgba(255, 255, 255, 0.4);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* Toast */
    .toast {
        position: fixed;
        bottom: 24px;
        right: 24px;
        padding: 11px 18px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        color: #fff;
        z-index: 9999;
        display: none;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
        animation: fadeUp 0.2s ease;
    }

    .toast.show {
        display: flex;
    }

    @keyframes fadeUp {
        from {
            opacity: 0;
            transform: translateY(8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 768px) {
        .sidebar {
            display: none;
        }

        .topbar-actions .btn:not(.btn-primary) {
            display: none;
        }
    }
    </style>
</head>

<body>

    <div class="layout">

        <!-- ══ Sidebar ══ -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <img src="meghdut_photo-removebg-preview.png" alt="Meghdut Logo" />
            </div>

            <div class="sidebar-section-label">Main</div>
            <nav class="sidebar-nav">
                <a href="index.php"><span class="icon">⊞</span> Dashboard</a>
                <a href="reservations.php" class="active">
                    <span class="icon"></span> Reservations
                    <span class="badge"><?= $pendingCount ?></span>
                </a>
                <a href="rooms.php"><span class="icon"></span> Rooms</a>
                <a href="guests.php"><span class="icon"></span> Guests</a>
            </nav>

            <div class="sidebar-section-label">Operations</div>
            <nav class="sidebar-nav">
                <a href="housekeeping.php"><span class="icon"></span> Housekeeping</a>
                <a href="Billing.php"><span class="icon"></span> Billing</a>
                <a href="report.php"><span class="icon"></span> Reports</a>
                <a href="restaurant.php"><span class="icon"></span> Restaurant</a>
            </nav>

            <div class="sidebar-section-label">System</div>
            <nav class="sidebar-nav">
                <a href="settings.php"><span class="icon"></span> Settings</a>
                <a href="staff.php"><span class="icon"></span> Staff</a>
            </nav>

            <div class="sidebar-bottom">
                <div class="avatar-sm"><?= strtoupper(substr($_SESSION['name'] ?? 'AD', 0, 2)) ?></div>
                <div>
                    <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></div>
                    <div class="sidebar-user-role">Manager</div>
                </div>
            </div>
        </aside>

        <!-- ══ Main Content ══ -->
        <div class="main">

            <!-- Topbar -->
            <div class="topbar">
                <div>
                    <h1>Reservations</h1>
                    <div class="topbar-date"><?= date('l, d F Y') ?></div>
                </div>
                <div class="topbar-actions">
                    <!--  Export button — modal খুলবে -->
                    <button class="btn" onclick="openExportModal()">⬇ Export</button>
                    <button class="btn btn-primary" onclick="openModal()">+ New Booking</button>
                </div>
            </div>

            <!-- Tabs -->
            <div class="tab-row">
                <?php foreach ($tabs as $t): ?>
                <a href="<?= current_url_with(['tab' => $t['key']]) ?>"
                    class="<?= $active_tab === $t['key'] ? 'active' : '' ?>">
                    <?= htmlspecialchars($t['label']) ?>
                    <span class="tab-badge"><?= $t['count'] ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Filters -->
            <form method="GET" action="">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
                <div class="filters">
                    <div class="search-box">
                        <span style="color:#9ca3af;"></span>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                            placeholder="Search guest or room…" />
                    </div>
                    <select name="status" class="filter-select" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        <?php foreach (['checkedin' => 'Checked in', 'reserved' => 'Reserved', 'checkout' => 'Checking out', 'cancelled' => 'Cancelled'] as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $status_filter === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="type" class="filter-select" onchange="this.form.submit()">
                        <option value="">All room types</option>
                        <?php
            $types = mysqli_query($conn, "SELECT DISTINCT type FROM room ORDER BY type");
            while ($t = mysqli_fetch_assoc($types)):
            ?>
                        <option value="<?= htmlspecialchars($t['type']) ?>"
                            <?= $type_filter === $t['type'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['type']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn">Apply</button>
                    <?php if ($search || $status_filter || $type_filter): ?>
                    <a href="?tab=<?= htmlspecialchars($active_tab) ?>" class="btn"> Clear</a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Table -->
            <div class="table-wrap">
                <table class="res-table">
                    <thead>
                        <tr>
                            <th>Guest</th>
                            <th>Room</th>
                            <th>Type</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Nights</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($filtered)): ?>
                        <tr>
                            <td colspan="9" style="text-align:center;padding:2.5rem;color:#9ca3af;">
                                No reservations found.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($filtered as $i => $r):
                $st = $status_map[$r['status']] ?? ['label' => $r['status'], 'bg' => '#f3f4f6', 'color' => '#374151'];
                $av = $avatar_palette[$i % count($avatar_palette)];
              ?>
                        <tr>
                            <td>
                                <div class="guest-cell">
                                    <div class="avatar" style="background:<?= $av['bg'] ?>;color:<?= $av['color'] ?>;">
                                        <?= htmlspecialchars($r['initials']) ?>
                                    </div>
                                    <div>
                                        <div class="guest-name"><?= htmlspecialchars($r['name']) ?></div>
                                        <div class="guest-sub"><?= htmlspecialchars($r['phone'] ?? '—') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="room-pill"><?= htmlspecialchars($r['room']) ?></span></td>
                            <td style="color:#9ca3af;font-size:12px;"><?= htmlspecialchars($r['type']) ?></td>
                            <td><?= fmt_date($r['check_in']) ?></td>
                            <td><?= fmt_date($r['check_out']) ?></td>
                            <td><?= $r['nights'] ?></td>
                            <td>
                                <span class="<?= $r['status'] === 'cancelled' ? 'cancelled-amount' : '' ?>"
                                    style="font-weight:<?= $r['status'] === 'cancelled' ? '400' : '500' ?>;">
                                    ৳<?= number_format($r['amount'] ?? 0) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge"
                                    style="background:<?= $st['bg'] ?>;color:<?= $st['color'] ?>;">
                                    <span class="status-dot" style="background:<?= $st['color'] ?>;"></span>
                                    <?= htmlspecialchars($st['label']) ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <a href="reservation_detail.php?id=<?= $r['id'] ?>" class="action-btn"
                                        title="View"></a>
                                    <a href="reservation_edit.php?id=<?= $r['id'] ?>" class="action-btn"
                                        title="Edit"></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer -->
            <div class="footer-bar">
                <span class="footer-text">Showing <?= count($filtered) ?> of <?= $countAll ?> reservations</span>
                <div class="pagination">
                    <a href="#" class="page-btn">‹</a>
                    <a href="#" class="page-btn active">1</a>
                    <a href="#" class="page-btn">›</a>
                </div>
            </div>

        </div>
    </div>

    <!-- ══════════════════════════════════════════
     New Booking Modal (আগের মতোই)
══════════════════════════════════════════ -->
    <div class="modal-overlay" id="modal-overlay" onclick="closeModalOut(event)">
        <div class="modal">
            <div class="modal-header">
                <span class="modal-title">New Booking</span>
                <button class="modal-close" onclick="closeModal()" type="button">×</button>
            </div>

            <form method="POST" action="save_reservation.php">
                <div class="form-row">
                    <label class="form-label">Guest Name</label>
                    <input class="form-input" type="text" name="guest_name" placeholder="Full name" required />
                </div>
                <div class="form-row">
                    <label class="form-label">Phone Number</label>
                    <input class="form-input" type="text" name="phone" placeholder="+880 …" />
                </div>
                <div class="form-row">
                    <label class="form-label">Email Address</label>
                    <input class="form-input" type="email" name="email" placeholder="guest@email.com" />
                </div>
                <div class="form-row">
                    <label class="form-label">Select Room</label>
                    <select class="form-input" name="room_id" required>
                        <option value="">— Select available room —</option>
                        <?php
            $availRooms = mysqli_query($conn, "
            SELECT room_id, room_number, type, price_per_night
            FROM room WHERE status='Available' ORDER BY room_number
          ");
            while ($rm = mysqli_fetch_assoc($availRooms)):
            ?>
                        <option value="<?= $rm['room_id'] ?>">
                            <?= htmlspecialchars($rm['room_number']) ?> — <?= htmlspecialchars($rm['type']) ?>
                            (৳<?= number_format($rm['price_per_night']) ?>/night)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-row-2">
                    <div>
                        <label class="form-label">Check-in Date</label>
                        <input class="form-input" type="date" name="check_in" required min="<?= date('Y-m-d') ?>" />
                    </div>
                    <div>
                        <label class="form-label">Check-out Date</label>
                        <input class="form-input" type="date" name="check_out" required
                            min="<?= date('Y-m-d', strtotime('+1 day')) ?>" />
                    </div>
                </div>
                <div class="form-row">
                    <label class="form-label">Special Requests</label>
                    <textarea class="form-input" name="notes" rows="2" placeholder="Any notes…"
                        style="resize:vertical;"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Booking</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
     Export Modal  নতুন যোগ করা হয়েছে
══════════════════════════════════════════ -->
    <div class="modal-overlay" id="export-overlay" onclick="closeExportOut(event)">
        <div class="modal export-modal">
            <div class="modal-header">
                <span class="modal-title">⬇ Reservation Export</span>
                <button class="modal-close" onclick="closeExportModal()" type="button">×</button>
            </div>

            <!-- তারিখ সীমা -->
            <div class="exp-section">
                <div class="exp-label">তারিখ সীমা</div>
                <div class="exp-date-row">
                    <div>
                        <div class="exp-sublabel">শুরুর তারিখ</div>
                        <input type="date" id="expDateFrom" class="form-input">
                    </div>
                    <div>
                        <div class="exp-sublabel">শেষ তারিখ</div>
                        <input type="date" id="expDateTo" class="form-input">
                    </div>
                </div>
            </div>

            <!-- Status chips -->
            <div class="exp-section">
                <div class="exp-label">স্ট্যাটাস</div>
                <div class="chip-wrap" id="expStatusChips">
                    <span class="chip active" data-value="all">সব</span>
                    <span class="chip" data-value="checkedin">Checked in</span>
                    <span class="chip" data-value="reserved">Reserved</span>
                    <span class="chip" data-value="checkout">Checking out</span>
                    <span class="chip" data-value="cancelled">Cancelled</span>
                </div>
            </div>

            <!-- Room type -->
            <div class="exp-section">
                <div class="exp-label">রুম টাইপ</div>
                <select id="expRoomType" class="form-input">
                    <option value="all">সব রুম টাইপ</option>
                    <?php
          $expTypes = mysqli_query($conn, "SELECT DISTINCT type FROM room ORDER BY type");
          while ($et = mysqli_fetch_assoc($expTypes)):
          ?>
                    <option value="<?= htmlspecialchars($et['type']) ?>"><?= htmlspecialchars($et['type']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="exp-divider"></div>

            <!-- Format -->
            <div class="exp-section">
                <div class="exp-label">ফাইল ফরম্যাট</div>
                <div class="fmt-grid" id="expFormatBtns">
                    <button class="fmt-btn active" data-format="xlsx" type="button">
                        <span class="fmt-icon"></span>
                        <span class="fmt-label">Excel (.xlsx)</span>
                    </button>
                    <button class="fmt-btn" data-format="csv" type="button">
                        <span class="fmt-icon"></span>
                        <span class="fmt-label">CSV</span>
                    </button>
                </div>
            </div>

            <div class="exp-divider"></div>

            <!-- Columns -->
            <div class="exp-section">
                <div class="exp-label">কলাম সিলেক্ট করুন</div>
                <div class="col-list">
                    <label class="col-row"><input type="checkbox" name="ecol" value="name" checked> গেস্ট নাম</label>
                    <label class="col-row"><input type="checkbox" name="ecol" value="phone" checked> ফোন নম্বর</label>
                    <label class="col-row"><input type="checkbox" name="ecol" value="room" checked> রুম নম্বর</label>
                    <label class="col-row"><input type="checkbox" name="ecol" value="type" checked> রুম টাইপ</label>
                    <label class="col-row"><input type="checkbox" name="ecol" value="check_in" checked> চেক-ইন</label>
                    <label class="col-row"><input type="checkbox" name="ecol" value="check_out" checked> চেক-আউট</label>
                    <label class="col-row"><input type="checkbox" name="ecol" value="nights" checked> মোট রাত</label>
                    <label class="col-row"><input type="checkbox" name="ecol" value="status" checked> স্ট্যাটাস</label>
                    <label class="col-row"><input type="checkbox" name="ecol" value="amount"> মোট বিল (৳)</label>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer exp-footer">
                <span class="exp-summary">মোট <strong id="expCount"><?= $countAll ?></strong> রেকর্ড</span>
                <div class="exp-btn-group">
                    <button type="button" class="btn" onclick="closeExportModal()">বাতিল</button>
                    <button type="button" class="btn btn-primary" id="expBtn" onclick="runExport()">⬇ Export
                        করুন</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast notification -->
    <div class="toast" id="toast"><span id="toastMsg"></span></div>

    <script>
    // ══ New Booking Modal ══
    function openModal() {
        document.getElementById('modal-overlay').classList.add('open');
    }

    function closeModal() {
        document.getElementById('modal-overlay').classList.remove('open');
    }

    function closeModalOut(e) {
        if (e.target === document.getElementById('modal-overlay')) closeModal();
    }

    document.querySelector('input[name="check_in"]').addEventListener('change', function() {
        const co = document.querySelector('input[name="check_out"]');
        co.min = this.value;
        if (co.value && co.value <= this.value) co.value = '';
    });
    document.querySelector('input[name="search"]').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') this.closest('form').submit();
    });

    // ══ Export Modal ══
    const EXPORT_API = 'export.php';
    let expStatus = 'all',
        expFormat = 'xlsx';

    function openExportModal() {
        document.getElementById('export-overlay').classList.add('open');
        updateExpCount();
    }

    function closeExportModal() {
        document.getElementById('export-overlay').classList.remove('open');
    }

    function closeExportOut(e) {
        if (e.target === document.getElementById('export-overlay')) closeExportModal();
    }

    // Status chip toggle
    document.getElementById('expStatusChips').addEventListener('click', function(e) {
        const chip = e.target.closest('.chip');
        if (!chip) return;
        document.querySelectorAll('#expStatusChips .chip').forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        expStatus = chip.dataset.value;
        updateExpCount();
    });

    // Format toggle
    document.getElementById('expFormatBtns').addEventListener('click', function(e) {
        const btn = e.target.closest('.fmt-btn');
        if (!btn) return;
        document.querySelectorAll('.fmt-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        expFormat = btn.dataset.format;
    });

    // Date / room type change
    document.getElementById('expDateFrom').addEventListener('change', updateExpCount);
    document.getElementById('expDateTo').addEventListener('change', updateExpCount);
    document.getElementById('expRoomType').addEventListener('change', updateExpCount);

    // Build query string
    function buildExpParams(fmt) {
        const cols = Array.from(document.querySelectorAll("input[name='ecol']:checked")).map(c => c.value);
        return new URLSearchParams({
            format: fmt || expFormat,
            status: expStatus,
            room_type: document.getElementById('expRoomType').value,
            date_from: document.getElementById('expDateFrom').value,
            date_to: document.getElementById('expDateTo').value,
            cols: cols.join(','),
        }).toString();
    }

    // Fetch count from export.php
    async function updateExpCount() {
        try {
            document.getElementById('expCount').textContent = '…';
            const res = await fetch(EXPORT_API + '?' + buildExpParams('xlsx'));
            const data = await res.json();
            document.getElementById('expCount').textContent = data.count ?? '?';
        } catch (e) {
            document.getElementById('expCount').textContent = '?';
        }
    }

    // Main export function
    async function runExport() {
        const cols = document.querySelectorAll("input[name='ecol']:checked");
        if (!cols.length) {
            showToast('কমপক্ষে একটি কলাম সিলেক্ট করুন!', '#A32D2D');
            return;
        }

        if (expFormat === 'csv') {
            // CSV: PHP সরাসরি download করাবে
            window.location.href = EXPORT_API + '?' + buildExpParams('csv');
            closeExportModal();
            showToast(' CSV ডাউনলোড শুরু হয়েছে!', '#3B6D11');
            return;
        }

        // Excel: PHP থেকে JSON নিয়ে SheetJS দিয়ে .xlsx বানাও
        const btn = document.getElementById('expBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> তৈরি হচ্ছে…';

        try {
            const res = await fetch(EXPORT_API + '?' + buildExpParams('xlsx'));
            const data = await res.json();

            if (!data.success) throw new Error(data.error || 'Server error');
            if (!data.rows || !data.rows.length) {
                showToast('কোনো রেকর্ড পাওয়া যায়নি!', '#854F0B');
                return;
            }

            // Column labels (আপনার DB column name অনুযায়ী)
            const labels = {
                name: 'Guest Name',
                phone: 'Phone',
                room: 'Room',
                type: 'Room Type',
                check_in: 'Check-In',
                check_out: 'Check-Out',
                nights: 'Nights',
                status: 'Status',
                amount: 'Amount (BDT)',
            };

            const keys = Object.keys(data.rows[0]);
            const header = keys.map(k => labels[k] ?? k);
            const rows = data.rows.map(r => keys.map(k => r[k]));

            const ws = XLSX.utils.aoa_to_sheet([header, ...rows]);
            ws['!cols'] = header.map(h => ({
                wch: Math.max(h.length + 4, 12)
            }));
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Reservations');
            XLSX.writeFile(wb, data.filename);

            closeExportModal();
            showToast(' ' + data.filename + ' ডাউনলোড হয়েছে!', '#3B6D11');
        } catch (err) {
            showToast(' Error: ' + err.message, '#A32D2D');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '⬇ Export করুন';
        }
    }

    function showToast(msg, bg) {
        const t = document.getElementById('toast');
        t.style.background = bg || '#3B6D11';
        document.getElementById('toastMsg').textContent = msg;
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3500);
    }

    // ESC key দিয়ে যেকোনো modal বন্ধ
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
            closeExportModal();
        }
    });
    </script>

</body>

</html>
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../db.php';
include '../auth.php';

if (!in_array($_SESSION['role'], ['admin', 'manager'])) {
  header("Location: ../index.php");
  exit();
}
// Staff list for assign modal
// Staff list from staff table
$staff_list = mysqli_query($conn, "
    SELECT staff_id, name, department, role 
    FROM staff 
    WHERE status = 'Active'
    ORDER BY department, name
");

// ─── Room Status Stats from DB ────────────────────────────────────────────────
$total_rooms = mysqli_fetch_assoc(mysqli_query(
  $conn,
  "SELECT COUNT(*) AS c FROM room"
))['c'];

$available = mysqli_fetch_assoc(mysqli_query(
  $conn,
  "SELECT COUNT(*) AS c FROM room WHERE status='Available'"
))['c'];

$booked = mysqli_fetch_assoc(mysqli_query(
  $conn,
  "SELECT COUNT(*) AS c FROM room WHERE status='Booked'"
))['c'];

$maintenance = mysqli_fetch_assoc(mysqli_query(
  $conn,
  "SELECT COUNT(*) AS c FROM room WHERE status='Under Maintenance'"
))['c'];

// ─── Cleaning Schedule: rooms that are Booked or just checked out ─────────────
// Show all rooms with their current booking info
$schedule_result = mysqli_query($conn, "
    SELECT
        r.room_number,
        r.floor,
        r.status AS room_status,
        r.type,
        b.status AS booking_status,
        u.Name   AS guest_name,
        b.check_in,
        b.check_out
    FROM room r
    LEFT JOIN booking b ON b.room_id = r.room_id
        AND b.booking_id = (
            SELECT booking_id FROM booking
            WHERE room_id = r.room_id
            ORDER BY booked_at DESC
            LIMIT 1
        )
    LEFT JOIN user u ON u.User_id = b.guest_id
    ORDER BY r.floor, r.room_number
");

// ─── Room Grid: all rooms ─────────────────────────────────────────────────────
$rooms_result = mysqli_query($conn, "
    SELECT room_number, floor, status, type
    FROM room
    ORDER BY floor, room_number
");

// ─── Cleaning status logic ────────────────────────────────────────────────────
// Available  → needs cleaning (just checked out or empty)
// Booked     → occupied (guest inside)
// Under Maintenance → maintenance
function cleaning_status($room_status, $booking_status)
{
  if ($room_status === 'Under Maintenance') return ['Maintenance', 'dirty'];
  if ($room_status === 'Booked')            return ['Occupied',    'progress'];
  if ($booking_status === 'Checked-Out')    return ['Needs Clean', 'dirty'];
  return ['Clean', 'clean'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Housekeeping – MRMS</title>
    <style>
    *,
    *::before,
    *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        background: #f3f4f6;
        display: flex;
        font-family: system-ui, -apple-system, sans-serif;
        color: #111827;
    }

    /* Sidebar */
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
    }

    /* Main */
    .main {
        margin-left: 240px;
        width: calc(100% - 240px);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    /* Topbar */
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
        font-weight: 500;
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
        cursor: pointer;
    }

    .btn-primary:hover {
        background: #0C447C;
    }

    /* Stats */
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
        padding: 0.875rem 1rem;
    }

    .stat-label {
        font-size: 12px;
        color: #9ca3af;
        margin-bottom: 4px;
    }

    .stat-val {
        font-size: 28px;
        font-weight: 500;
    }

    .stat-sub {
        font-size: 12px;
        color: #9ca3af;
        margin-top: 2px;
    }

    /* Content grid */
    .content {
        padding: 0 1.5rem 1.5rem;
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 16px;
    }

    /* Table box */
    .table-box {
        background: #fff;
        border: 0.5px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.25rem;
    }

    .box-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .box-header h2 {
        font-size: 15px;
        font-weight: 500;
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
        padding: 12px 8px;
        border-bottom: 0.5px solid #f3f4f6;
        vertical-align: middle;
    }

    table tr:last-child td {
        border-bottom: none;
    }

    table tr:hover td {
        background: #f9fafb;
    }

    .status-pill {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }

    .clean {
        background: #EAF3DE;
        color: #3B6D11;
    }

    .progress {
        background: #FAEEDA;
        color: #854F0B;
    }

    .dirty {
        background: #FCEBEB;
        color: #A32D2D;
    }

    .maintenance {
        background: #FCEBEB;
        color: #A32D2D;
    }

    /* Room grid box */
    .room-box {
        background: #fff;
        border: 0.5px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.25rem;
    }

    .room-box h2 {
        font-size: 15px;
        font-weight: 500;
        margin-bottom: 1rem;
    }

    .rooms-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .room-cell {
        padding: 14px;
        border-radius: 10px;
        text-align: center;
        font-weight: 600;
        font-size: 13px;
        color: #fff;
        cursor: pointer;
        transition: transform 0.15s;
    }

    .room-cell:hover {
        transform: scale(1.04);
    }

    .room-available {
        background: #3B6D11;
    }

    .room-booked {
        background: #185FA5;
    }

    .room-maintenance {
        background: #A32D2D;
    }

    /* Legend */
    .legend {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        color: #6b7280;
    }

    .legend-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }

    /* Search */
    .search-row {
        padding: 0.75rem 1.5rem 0;
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .search-box {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #fff;
        border: 0.5px solid #e5e7eb;
        border-radius: 8px;
        padding: 7px 12px;
        max-width: 260px;
    }

    .search-box input {
        border: none;
        background: transparent;
        font-size: 13px;
        outline: none;
        width: 100%;
    }

    @media (max-width: 1000px) {
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

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <img src="meghdut_photo-removebg-preview.png" width="100" height="100">
        </div>
        <div class="sidebar-section-label">Main</div>
        <nav class="sidebar-nav">
            <a href="index.php"><span class="icon">⊞</span> Dashboard</a>
            <a href="reservations.php"><span class="icon"></span> Reservations <span class="badge">4</span></a>
            <a href="rooms.php"><span class="icon"></span> Rooms</a>
            <a href="guests.php"><span class="icon"></span> Guests</a>
        </nav>
        <div class="sidebar-section-label">Operations</div>
        <nav class="sidebar-nav">
            <a href="housekeeping.php" class="active"><span class="icon"></span> Housekeeping</a>
            <a href="billing.php"><span class="icon"></span> Billing</a>
            <a href="report.php"><span class="icon"></span> Reports</a>
            <a href="restaurant.php"><span class="icon"></span> Restaurant</a>
        </nav>
        <div class="sidebar-section-label">System</div>
        <nav class="sidebar-nav">
            <a href="settings.php"><span class="icon"></span> Settings</a>
            <a href="staff.php"><span class="icon"></span> Staff</a>
        </nav>
        <div class="sidebar-bottom">
            <div class="avatar-sm">AD</div>
            <div>
                <div style="font-size:13px;font-weight:500;">Admin</div>
                <div style="font-size:11px;color:#9ca3af;">Manager</div>
            </div>
        </div>
    </div>

    <!-- Main -->
    <div class="main">

        <!-- Topbar -->
        <div class="topbar">
            <div>
                <h1>Housekeeping</h1>
                <div class="topbar-date"><?= date('l, d F Y') ?></div>
            </div>
            <button class="btn-primary" onclick="document.getElementById('assignModal').style.display='flex'">+ Assign
                Staff</button>
        </div>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Total Rooms</div>
                <div class="stat-val"><?= $total_rooms ?></div>
                <div class="stat-sub">All floors</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Available</div>
                <div class="stat-val" style="color:#3B6D11;"><?= $available ?></div>
                <div class="stat-sub" style="color:#3B6D11;">Clean &amp; ready</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Booked / Occupied</div>
                <div class="stat-val" style="color:#185FA5;"><?= $booked ?></div>
                <div class="stat-sub" style="color:#185FA5;">Guest inside</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Under Maintenance</div>
                <div class="stat-val" style="color:#A32D2D;"><?= $maintenance ?></div>
                <div class="stat-sub" style="color:#A32D2D;">Needs attention</div>
            </div>
        </div>

        <!-- Search -->
        <div class="search-row">
            <div class="search-box">
                <span style="color:#9ca3af;"></span>
                <input type="text" id="search-input" placeholder="Search room, guest…" oninput="filterTable()">
            </div>
            <!-- Legend -->
            <div class="legend" style="margin-bottom:0;margin-left:8px;">
                <div class="legend-item">
                    <div class="legend-dot" style="background:#3B6D11;"></div>Available
                </div>
                <div class="legend-item">
                    <div class="legend-dot" style="background:#185FA5;"></div>Occupied
                </div>
                <div class="legend-item">
                    <div class="legend-dot" style="background:#A32D2D;"></div>Maintenance
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="content" style="margin-top:12px;">

            <!-- Cleaning Schedule Table -->
            <div class="table-box">
                <div class="box-header">
                    <h2> Room Status Overview</h2>
                    <span><?= mysqli_num_rows($schedule_result) ?> rooms total</span>
                </div>
                <table id="schedule-table">
                    <thead>
                        <tr>
                            <th>Room</th>
                            <th>Type</th>
                            <th>Floor</th>
                            <th>Guest</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
            mysqli_data_seek($schedule_result, 0);
            while ($row = mysqli_fetch_assoc($schedule_result)):
              [$label, $css] = cleaning_status($row['room_status'], $row['booking_status']);
            ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['room_number']) ?></strong></td>
                            <td style="color:#9ca3af;font-size:12px;"><?= $row['type'] ?></td>
                            <td style="color:#9ca3af;font-size:12px;">Floor <?= $row['floor'] ?></td>
                            <td style="font-size:12px;">
                                <?= $row['guest_name'] ? htmlspecialchars($row['guest_name']) : '<span style="color:#9ca3af;">—</span>' ?>
                            </td>
                            <td style="font-size:12px;color:#6b7280;">
                                <?= $row['check_in'] ? date('d M', strtotime($row['check_in'])) : '—' ?>
                            </td>
                            <td style="font-size:12px;color:#6b7280;">
                                <?= $row['check_out'] ? date('d M', strtotime($row['check_out'])) : '—' ?>
                            </td>
                            <td>
                                <span class="status-pill <?= $css ?>"><?= $label ?></span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Room Grid -->
            <div class="room-box">
                <h2>Room Grid</h2>
                <div class="rooms-grid">
                    <?php
          mysqli_data_seek($rooms_result, 0);
          while ($rm = mysqli_fetch_assoc($rooms_result)):
            $css_class = match ($rm['status']) {
              'Available'         => 'room-available',
              'Booked'            => 'room-booked',
              'Under Maintenance' => 'room-maintenance',
              default             => 'room-available',
            };
            $icon = match ($rm['status']) {
              'Available'         => '',
              'Booked'            => '',
              'Under Maintenance' => '',
              default             => '',
            };
          ?>
                    <div class="room-cell <?= $css_class ?>"
                        title="<?= $rm['type'] ?> · Floor <?= $rm['floor'] ?> · <?= $rm['status'] ?>">
                        <?= $icon ?> <?= htmlspecialchars($rm['room_number']) ?>
                        <div style="font-size:10px;font-weight:400;margin-top:3px;opacity:0.85;"><?= $rm['type'] ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <!-- Grid Legend -->
                <div style="margin-top:16px;display:flex;flex-direction:column;gap:6px;">
                    <div style="display:flex;align-items:center;gap:8px;font-size:11px;color:#6b7280;">
                        <div style="width:12px;height:12px;border-radius:3px;background:#3B6D11;"></div> Available
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;font-size:11px;color:#6b7280;">
                        <div style="width:12px;height:12px;border-radius:3px;background:#185FA5;"></div> Booked /
                        Occupied
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;font-size:11px;color:#6b7280;">
                        <div style="width:12px;height:12px;border-radius:3px;background:#A32D2D;"></div> Under
                        Maintenance
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
    function filterTable() {
        const q = document.getElementById('search-input').value.toLowerCase();
        document.querySelectorAll('#schedule-table tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    }
    </script>
    <!-- Assign Staff Modal -->
    <div id="assignModal"
        style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:1000; align-items:center; justify-content:center;">
        <div
            style="background:#fff; border-radius:14px; padding:2rem; width:420px; max-width:95vw; box-shadow:0 20px 60px rgba(0,0,0,0.2);">

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <h2 style="font-size:16px; font-weight:600;"> Assign Staff to Room</h2>
                <button onclick="document.getElementById('assignModal').style.display='none'"
                    style="background:none; border:none; font-size:20px; cursor:pointer; color:#9ca3af;"></button>
            </div>

            <form method="POST" action="assign_staff.php">

                <!-- Room Select -->
                <div style="margin-bottom:1rem;">
                    <label
                        style="font-size:12px; font-weight:600; color:#374151; display:block; margin-bottom:5px;">Room</label>
                    <select name="room_id" required
                        style="width:100%; padding:9px 12px; border:1px solid #e5e7eb; border-radius:8px; font-size:13px; outline:none;">
                        <option value="">— Select Room —</option>
                        <?php
            $rooms_for_modal = mysqli_query($conn, "SELECT room_id, room_number, status FROM room ORDER BY room_number");
            while ($r = mysqli_fetch_assoc($rooms_for_modal)):
            ?>
                        <option value="<?= $r['room_id'] ?>"><?= htmlspecialchars($r['room_number']) ?>
                            (<?= $r['status'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Staff Select -->
                <div style="margin-bottom:1rem;">
                    <label
                        style="font-size:12px; font-weight:600; color:#374151; display:block; margin-bottom:5px;">Staff
                        Member</label>
                    <select name="staff_id" required
                        style="width:100%; padding:9px 12px; border:1px solid #e5e7eb; border-radius:8px; font-size:13px; outline:none;">
                        <option value="">— Select Staff —</option>
                        <?php
            if ($staff_result && mysqli_num_rows($staff_result) > 0):
              while ($s = mysqli_fetch_assoc($staff_result)):
            ?>
                        <option value="<?= $s['staff_id'] ?>"><?= htmlspecialchars($s['name']) ?> — <?= $s['role'] ?>
                        </option>
                        <?php
              endwhile;
            else: ?>
                        <option disabled>No housekeeping staff found</option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Task -->
                <div style="margin-bottom:1rem;">
                    <label
                        style="font-size:12px; font-weight:600; color:#374151; display:block; margin-bottom:5px;">Task</label>
                    <select name="task"
                        style="width:100%; padding:9px 12px; border:1px solid #e5e7eb; border-radius:8px; font-size:13px; outline:none;">
                        <option value="Cleaning">Cleaning</option>
                        <option value="Inspection">Inspection</option>
                        <option value="Maintenance Check">Maintenance Check</option>
                        <option value="Turndown Service">Turndown Service</option>
                    </select>
                </div>

                <!-- Note -->
                <div style="margin-bottom:1.5rem;">
                    <label
                        style="font-size:12px; font-weight:600; color:#374151; display:block; margin-bottom:5px;">Note
                        (optional)</label>
                    <textarea name="note" rows="2" placeholder="Extra instructions…"
                        style="width:100%; padding:9px 12px; border:1px solid #e5e7eb; border-radius:8px; font-size:13px; outline:none; resize:none;"></textarea>
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" onclick="document.getElementById('assignModal').style.display='none'"
                        style="padding:9px 18px; border:1px solid #e5e7eb; border-radius:8px; font-size:13px; cursor:pointer; background:#fff;">
                        Cancel
                    </button>
                    <button type="submit" class="btn-primary" style="padding:9px 18px;">
                        Assign 
                    </button>
                </div>

            </form>
        </div>
    </div>
</body>

</html>
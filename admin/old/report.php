<?php
// ============================================================
//  MRMS — Reports & Analytics Page
//  Meghdoot Resort Management System | Group 06 | ISD 2026
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();
include '../db.php';
include '../auth.php';

if (!in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: ../index.php");
    exit();
}

// ── Stats ────────────────────────────────────────────────────

// Total Revenue
$totalRevenue = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) FROM payment WHERE status IN ('paid','partial')"))[0];

// Total Bookings
$totalBookings = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM booking"))[0];

// Occupancy Rate = Booked or Checked-In rooms / total rooms * 100
$totalRooms    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM room"))[0];
$occupiedRooms = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM room WHERE status='Booked'"))[0];
$occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;

// Pending bookings (unpaid)
$pendingCount = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM booking WHERE payment_status='unpaid' AND status NOT IN ('Cancelled','Checked-Out')"))[0];

// ── Monthly Revenue for Bar Chart (last 6 months) ────────────

$monthlyResult = mysqli_query($conn, "
    SELECT DATE_FORMAT(paid_at, '%b') AS month_name,
           MONTH(paid_at)             AS month_num,
           YEAR(paid_at)              AS year_num,
           SUM(amount)                AS total
    FROM payment
    WHERE paid_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY year_num, month_num, month_name
    ORDER BY year_num ASC, month_num ASC
");
$monthlyData = [];
while ($row = mysqli_fetch_assoc($monthlyResult)) {
    $monthlyData[] = $row;
}

// Max revenue for bar height scaling
$maxRevenue = 0;
foreach ($monthlyData as $md) {
    if ($md['total'] > $maxRevenue) $maxRevenue = $md['total'];
}

// Best month
$bestMonth      = '';
$highestRevenue = 0;
foreach ($monthlyData as $md) {
    if ($md['total'] > $highestRevenue) {
        $highestRevenue = $md['total'];
        $bestMonth      = $md['month_name'];
    }
}

// ── Average booking value ────────────────────────────────────
$avgBooking = mysqli_fetch_row(mysqli_query($conn, "
    SELECT COALESCE(AVG(pay_amount),0) FROM booking WHERE pay_amount > 0
"))[0];

// ── Total unique guests ──────────────────────────────────────
$totalGuests = mysqli_fetch_row(mysqli_query($conn, "
    SELECT COUNT(DISTINCT guest_id) FROM booking
"))[0];

// ── Recent Bookings as "Reports" ─────────────────────────────

$recentResult = mysqli_query($conn, "
    SELECT b.booking_id, b.check_in, b.check_out, b.status, b.payment_status,
           u.Name AS guest_name, r.type AS room_type, r.room_number
    FROM booking b
    JOIN user u ON u.User_id = b.guest_id
    JOIN room r ON r.room_id = b.room_id
    ORDER BY b.booked_at DESC
    LIMIT 8
");
$recentBookings = [];
while ($row = mysqli_fetch_assoc($recentResult)) $recentBookings[] = $row;

// ── Helper ───────────────────────────────────────────────────
function bookingStatusBadge($status)
{
    $map = [
        'Confirmed'   => ['completed',  'Confirmed'],
        'Checked-In'  => ['processing', 'Checked-In'],
        'Checked-Out' => ['completed',  'Checked-Out'],
        'Pending'     => ['pending',    'Pending'],
        'Cancelled'   => ['processing', 'Cancelled'],
    ];
    $s = $map[$status] ?? ['pending', $status];
    return '<span class="status ' . $s[0] . '">' . $s[1] . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports — MRMS</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: Arial, sans-serif;
    }

    body {
        background: #f3f1eb;
        display: flex;
    }

    .sidebar {
        width: 260px;
        height: 100vh;
        background: white;
        border-right: 1px solid #ddd;
        position: fixed;
        left: 0;
        top: 0;
        overflow-y: auto;
    }

    .logo {
        padding: 25px;
        text-align: center;
        border-bottom: 1px solid #eee;
    }

    .menu {
        padding: 20px;
    }

    .menu-title {
        color: #999;
        font-size: 13px;
        margin-bottom: 10px;
        margin-top: 20px;
    }

    .menu a {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        color: #333;
        padding: 14px;
        border-radius: 12px;
        margin-bottom: 8px;
        transition: 0.3s;
    }

    .menu a:hover {
        background: #f3f5f9;
    }

    .active {
        background: #e8f0fb;
        color: #0d5cab !important;
        font-weight: bold;
    }

    .main {
        margin-left: 260px;
        width: calc(100% - 260px);
        padding: 30px;
    }

    .topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .topbar h1 {
        font-size: 36px;
        color: #222;
    }

    .topbar p {
        color: #777;
        margin-top: 5px;
    }

    .top-btn {
        background: #0d5cab;
        color: white;
        border: none;
        padding: 14px 22px;
        border-radius: 12px;
        cursor: pointer;
        font-size: 15px;
    }

    .stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }

    .card {
        background: white;
        padding: 25px;
        border-radius: 18px;
        border: 1px solid #ddd;
    }

    .card h3 {
        color: #666;
        margin-bottom: 15px;
        font-size: 16px;
    }

    .card h1 {
        font-size: 36px;
        color: #222;
    }

    .card p {
        margin-top: 10px;
        font-size: 14px;
    }

    .green {
        color: green;
    }

    .blue {
        color: #0d5cab;
    }

    .orange {
        color: orange;
    }

    .report-section {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 25px;
    }

    .table-box {
        background: white;
        border-radius: 18px;
        padding: 25px;
        border: 1px solid #ddd;
    }

    .table-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .table-header h2 {
        color: #222;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    table th {
        text-align: left;
        color: #888;
        padding: 14px 10px;
        border-bottom: 1px solid #eee;
        font-size: 14px;
    }

    table td {
        padding: 14px 10px;
        border-bottom: 1px solid #f2f2f2;
        font-size: 14px;
    }

    .status {
        padding: 7px 13px;
        border-radius: 30px;
        font-size: 12px;
        font-weight: bold;
        display: inline-block;
    }

    .completed {
        background: #e7f7ea;
        color: green;
    }

    .processing {
        background: #fff3d9;
        color: #cc8800;
    }

    .pending {
        background: #e8f0fb;
        color: #0d5cab;
    }

    .analytics-box {
        background: white;
        border-radius: 18px;
        padding: 25px;
        border: 1px solid #ddd;
    }

    .analytics-box h2 {
        margin-bottom: 25px;
    }

    .chart {
        height: 220px;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 35px;
        border-bottom: 2px solid #eee;
        padding-bottom: 0;
    }

    .bar-wrap {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
        height: 100%;
        justify-content: flex-end;
    }

    .bar {
        width: 100%;
        background: #0d5cab;
        border-radius: 8px 8px 0 0;
        transition: 0.3s;
        min-height: 4px;
    }

    .bar:hover {
        background: #0947a0;
    }

    .bar-label {
        font-size: 12px;
        color: #666;
        margin-top: 8px;
    }

    .bar-val {
        font-size: 11px;
        color: #0d5cab;
        font-weight: bold;
        margin-bottom: 4px;
    }

    .no-data-bar {
        width: 100%;
        height: 4px;
        background: #eee;
        border-radius: 4px;
    }

    .report-summary {
        margin-top: 10px;
    }

    .summary-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 18px;
        padding-bottom: 12px;
        border-bottom: 1px solid #eee;
    }

    .summary-item h4 {
        color: #666;
    }

    .db-badge {
        font-size: 12px;
        background: #e8f0fb;
        color: #0d5cab;
        padding: 4px 10px;
        border-radius: 20px;
        margin-left: 10px;
    }

    .empty-row td {
        text-align: center;
        color: #aaa;
        padding: 30px;
    }

    @media(max-width:1000px) {
        .stats {
            grid-template-columns: 1fr 1fr;
        }

        .report-section {
            grid-template-columns: 1fr;
        }
    }

    @media(max-width:700px) {
        .sidebar {
            display: none;
        }

        .main {
            margin-left: 0;
            width: 100%;
        }

        .stats {
            grid-template-columns: 1fr;
        }
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
            <div class="menu-title">MAIN</div>
            <a href="index.php"> Dashboard</a>
            <a href="reservations.php"> Reservations</a>
            <a href="room.php"> Rooms</a>
            <a href="guests.php"> Guests</a>
            <div class="menu-title">OPERATIONS</div>
            <a href="housekeeping.php"> Housekeeping</a>
            <a href="Billing.php"> Billing</a>
            <a href="report.php" class="active"> Reports</a>
            <a href="restaurant.php"> Restaurant</a>
            <div class="menu-title">SYSTEM</div>
            <a href="#"> Settings</a>
            <a href="staff.php">‍ Staff</a>
        </div>
    </div>

    <!-- MAIN -->
    <div class="main">

        <div class="topbar">
            <div>
                <h1>Reports & Analytics <span class="db-badge"> Live DB</span></h1>
                <p><?= date('l, F j, Y') ?></p>
            </div>
            <button class="top-btn" onclick="window.print()">⬇ Export Report</button>
        </div>

        <!-- STATS -->
        <div class="stats">
            <div class="card">
                <h3>Total Revenue</h3>
                <h1>৳<?= number_format($totalRevenue, 0) ?></h1>
                <p class="green">↑ All time payments</p>
            </div>
            <div class="card">
                <h3>Total Bookings</h3>
                <h1><?= $totalBookings ?></h1>
                <p class="blue">All bookings recorded</p>
            </div>
            <div class="card">
                <h3>Occupancy Rate</h3>
                <h1><?= $occupancyRate ?>%</h1>
                <p class="green"><?= $occupiedRooms ?>/<?= $totalRooms ?> rooms occupied</p>
            </div>
            <div class="card">
                <h3>Pending Bills</h3>
                <h1><?= $pendingCount ?></h1>
                <p class="orange">Need review</p>
            </div>
        </div>

        <!-- REPORT SECTION -->
        <div class="report-section">

            <!-- TABLE -->
            <div class="table-box">
                <div class="table-header">
                    <h2>Recent Bookings</h2>
                    <a href="reservations.php">View all →</a>
                </div>
                <table>
                    <tr>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Status</th>
                    </tr>
                    <?php if (empty($recentBookings)): ?>
                    <tr class="empty-row">
                        <td colspan="5">No bookings found.</td>
                    </tr>
                    <?php else: foreach ($recentBookings as $b): ?>
                    <tr>
                        <td><?= htmlspecialchars($b['guest_name']) ?></td>
                        <td><?= htmlspecialchars($b['room_type']) ?> #<?= htmlspecialchars($b['room_number']) ?></td>
                        <td><?= date('d M Y', strtotime($b['check_in'])) ?></td>
                        <td><?= date('d M Y', strtotime($b['check_out'])) ?></td>
                        <td><?= bookingStatusBadge($b['status']) ?></td>
                    </tr>
                    <?php endforeach;
                    endif; ?>
                </table>
            </div>

            <!-- ANALYTICS -->
            <div class="analytics-box">
                <h2>Revenue Analytics</h2>

                <!-- Bar Chart -->
                <div class="chart">
                    <?php if (empty($monthlyData)): ?>
                    <p style="color:#aaa;font-size:14px;align-self:center;width:100%;text-align:center;">No payment data
                        yet</p>
                    <?php else: foreach ($monthlyData as $md):
                            $barHeight = $maxRevenue > 0 ? round(($md['total'] / $maxRevenue) * 190) : 4;
                            $barHeight = max($barHeight, 4);
                        ?>
                    <div class="bar-wrap">
                        <div class="bar-val">৳<?= number_format($md['total'] / 1000, 0) ?>K</div>
                        <div class="bar" style="height:<?= $barHeight ?>px;"
                            title="<?= $md['month_name'] ?>: ৳<?= number_format($md['total'], 0) ?>"></div>
                        <div class="bar-label"><?= $md['month_name'] ?></div>
                    </div>
                    <?php endforeach;
                    endif; ?>
                </div>

                <!-- Summary -->
                <div class="report-summary">
                    <div class="summary-item">
                        <h4>Best Month</h4>
                        <strong><?= $bestMonth ?: '—' ?></strong>
                    </div>
                    <div class="summary-item">
                        <h4>Highest Revenue</h4>
                        <strong>৳<?= number_format($highestRevenue, 0) ?></strong>
                    </div>
                    <div class="summary-item">
                        <h4>Avg. Booking Value</h4>
                        <strong>৳<?= number_format($avgBooking, 0) ?></strong>
                    </div>
                    <div class="summary-item">
                        <h4>Total Guests</h4>
                        <strong><?= $totalGuests ?></strong>
                    </div>
                </div>
            </div>

        </div><!-- /report-section -->

    </div><!-- /main -->

</body>

</html>
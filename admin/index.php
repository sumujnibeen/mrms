<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../db.php';
include '../auth.php';

if (!in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Hotel Dashboard</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Poppins', sans-serif;
    }

    body {
        background: #f4f1ea;
        display: flex;
        min-height: 100vh;
        color: #222;
    }

    /* SIDEBAR */
    a {
        color: black;
        text-decoration: none;
    }

    a:hover {
        color: black;
    }

    .sidebar {
        width: 260px;
        background: #fff;
        border-right: 1px solid #ddd;
        padding: 20px 0;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .logo {
        text-align: center;
        margin-bottom: 30px;
    }

    .logo h2 {
        color: #0d5ea8;
    }

    .menu {
        padding: 0 15px;
    }

    .menu-title {
        font-size: 12px;
        color: #999;
        margin: 20px 10px 10px;
        text-transform: uppercase;
    }

    .menu ul {
        list-style: none;
    }

    .menu ul li {
        padding: 14px 15px;
        border-radius: 12px;
        margin-bottom: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: 0.3s;
        font-size: 17px;
    }

    .menu ul li:hover {
        background: #f3f3f3;
    }

    .menu ul li.active {
        background: #efefef;
        font-weight: 600;
    }

    .badge {
        margin-left: auto;
        background: #f9d9d9;
        color: #d33;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 12px;
    }

    .admin {
        border-top: 1px solid #ddd;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .admin-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: #dbe7ff;
        display: flex;
        justify-content: center;
        align-items: center;
        font-weight: 600;
        color: #0d5ea8;
    }

    .admin small {
        color: #777;
    }

    /* MAIN */
    .main {
        flex: 1;
        padding: 30px;
    }

    .topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .topbar h1 {
        font-size: 38px;
        margin-bottom: 5px;
    }

    .topbar p {
        color: #666;
    }

    .top-icons {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .icon-btn {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        background: #fff;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 18px;
        cursor: pointer;
        border: 1px solid #ddd;
    }

    /* CARDS */
    .cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .card {
        background: #fff;
        padding: 25px;
        border-radius: 20px;
        border: 1px solid #ddd;
    }

    .card h4 {
        color: #555;
        font-weight: 500;
        margin-bottom: 15px;
    }

    .card .number {
        font-size: 42px;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .green {
        color: #2e8b57;
    }

    .red {
        color: #d9534f;
    }

    /* TABLE */
    .table-box {
        background: #fff;
        border-radius: 20px;
        padding: 25px;
        border: 1px solid #ddd;
    }

    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .table-header h2 {
        font-size: 28px;
    }

    .table-header a {
        text-decoration: none;
        color: #0d5ea8;
        font-weight: 500;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    table th {
        text-align: left;
        padding: 14px 10px;
        color: #888;
        border-bottom: 1px solid #eee;
    }

    table td {
        padding: 18px 10px;
        border-bottom: 1px solid #f1f1f1;
    }

    .status {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        display: inline-block;
        font-weight: 500;
    }

    .checked {
        background: #e7f7df;
        color: #3c8d0d;
    }

    .reserved {
        background: #dcecff;
        color: #1565c0;
    }

    .checkout {
        background: #fff0d7;
        color: #aa6b00;
    }

    /* Notification */
    .notif-wrapper {
        position: relative;
    }

    .notif-dropdown {
        display: none;
        position: absolute;
        right: 0;
        top: 60px;
        width: 300px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 14px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        z-index: 999;
        padding: 15px;
    }

    .notif-dropdown.open {
        display: block;
    }

    .notif-item {
        padding: 10px 0;
        border-bottom: 1px solid #f1f1f1;
        font-size: 14px;
    }

    .notif-item:last-child {
        border-bottom: none;
    }

    /* Search Bar */
    .search-bar {
        display: none;
        position: absolute;
        right: 0;
        top: 60px;
        width: 300px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 14px;
        padding: 12px 15px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        z-index: 999;
    }

    .search-bar.open {
        display: block;
    }

    .search-bar input {
        width: 100%;
        border: 1px solid #ddd;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 14px;
        outline: none;
    }

    .search-results {
        margin-top: 10px;
        font-size: 14px;
    }

    @media(max-width:1200px) {
        .cards {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media(max-width:768px) {
        body {
            flex-direction: column;
        }

        .sidebar {
            width: 100%;
        }

        .cards {
            grid-template-columns: 1fr;
        }

        .topbar {
            flex-direction: column;
            align-items: flex-start;
            gap: 20px;
        }
    }
    </style>
</head>

<?php

$totalRooms = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM room")
)['total'];

$occupiedRooms = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM booking WHERE status IN ('Confirmed','Checked-In')")
)['total'];

$checkInToday = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM booking WHERE check_in = CURDATE()")
)['total'];

$checkOutToday = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM booking WHERE check_out = CURDATE()")
)['total'];

$revenueToday = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT IFNULL(SUM(amount),0) AS total FROM payment WHERE DATE(paid_at)=CURDATE()")
)['total'];

$revenueYesterday = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT IFNULL(SUM(amount),0) AS total FROM payment WHERE DATE(paid_at) = CURDATE() - INTERVAL 1 DAY")
)['total'];

$checkInYesterday = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM booking WHERE check_in = CURDATE() - INTERVAL 1 DAY")
)['total'];

$checkOutYesterday = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM booking WHERE check_out = CURDATE() - INTERVAL 1 DAY")
)['total'];

$occupancyRate = 0;
if ($totalRooms > 0) {
    $occupancyRate = round(($occupiedRooms / $totalRooms) * 100);
}

$checkInDiff  = $checkInToday - $checkInYesterday;
$checkOutDiff = $checkOutToday - $checkOutYesterday;

$revenueChange = 0;
if ($revenueYesterday > 0) {
    $revenueChange = round((($revenueToday - $revenueYesterday) / $revenueYesterday) * 100);
}

$pendingReservations = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM booking WHERE status='Pending'")
)['total'];

$totalGuests = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM user WHERE Role='guest'")
)['total'];

$totalRevenue = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT IFNULL(SUM(amount),0) AS total FROM payment WHERE status IN ('paid','partial')")
)['total'];

$pendingServices = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM service_request WHERE status='Pending'")
)['total'];

?>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div>
            <div class="logo">
                <h2><img src="meghdut_photo-removebg-preview.png" width="100px" height="100px"></h2>
            </div>

            <div class="menu">

                <div class="menu-title">Main</div>
                <ul>
                    <li class="active">
                        <i class="fa-solid fa-table-columns"></i>
                        Dashboard
                    </li>
                    <li>
                        <i class="fa-regular fa-calendar"></i>
                        <a href="reservations.php">Reservations</a>
                        <span class="badge"><?= $pendingReservations ?></span>
                    </li>
                    <li>
                        <i class="fa-solid fa-bed"></i>
                        <a href="rooms.php">Rooms</a>
                    </li>
                    <li>
                        <i class="fa-regular fa-user"></i>
                        <a href="guests.php">Guest</a>
                    </li>
                </ul>

                <div class="menu-title">Operations</div>
                <ul>
                    <li>
                        <i class="fa-solid fa-broom"></i>
                        <a href="housekeeping.php">Housekeeping</a>
                    </li>
                    <li>
                        <i class="fa-regular fa-file-lines"></i>
                        <a href="Billing.php">Billing</a>
                    </li>
                    <li>
                        <i class="fa-solid fa-chart-column"></i>
                        <a href="report.php">Reports</a>
                    </li>
                    <li>
                        <i class="fa-solid fa-utensils"></i>
                        <a href="restaurant.php">Restaurant</a>
                    </li>
                </ul>

                
            <div class="menu-title">FINANCE</div>
            <a href="finance.php"> Finance</a>
            <div class="menu-title">System</div>
                <ul>
                    <li>
                        <i class="fa-solid fa-gear"></i>
                        <a href="setting.php">Settings</a>
                    </li>
                    <li>
                        <i class="fa-solid fa-users"></i>
                        <a href="staff.php">Staff</a>
                    </li>
                </ul>

            </div>
        </div>

        <div class="admin">
            <div class="admin-left">
                <div class="avatar">
                    <?= strtoupper(substr($_SESSION['name'] ?? 'AD', 0, 2)); ?>
                </div>
                <div>
                    <h4><?= htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></h4>
                    <small>Manager</small>
                </div>
            </div>
            <a href="../logout.php">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </a>
        </div>
    </div>

    <!-- MAIN -->
    <div class="main">

        <div class="topbar">
            <div>
                <h1>Dashboard</h1>
                <p><?= date('l, F d, Y') ?></p>
            </div>

            <div class="top-icons">

                <!-- Notification -->
                <div class="notif-wrapper">
                    <div class="icon-btn" onclick="toggleNotif()">
                        <i class="fa-regular fa-bell"></i>
                    </div>
                    <div class="notif-dropdown" id="notifDropdown">
                        <h4 style="margin-bottom:10px;">Notifications</h4>
                        <?php if ($pendingReservations > 0): ?>
                        <div class="notif-item" onclick="window.location='reservations.php'" style="cursor:pointer;">
                            <?= $pendingReservations ?> pending reservation(s)
                        </div>
                        <?php endif; ?>
                        <?php if ($pendingServices > 0): ?>
                        <div class="notif-item" onclick="window.location='housekeeping.php'" style="cursor:pointer;">
                            <?= $pendingServices ?> pending service request(s)
                        </div>
                        <?php endif; ?>
                        <?php if ($pendingReservations == 0 && $pendingServices == 0): ?>
                        <div class="notif-item" style="color:#888;">No new notifications</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Search -->
                <div class="notif-wrapper">
                    <div class="icon-btn" onclick="toggleSearch()">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                    <div class="search-bar" id="searchBar">
                        <input type="text" id="searchInput" placeholder="Search guest or room..."
                            oninput="doSearch(this.value)">
                        <div class="search-results" id="searchResults"></div>
                    </div>
                </div>

            </div>
        </div>

        <!-- CARDS -->
        <div class="cards">

            <div class="card">
                <h4><i class="fa-solid fa-bed"></i> Occupied Rooms</h4>
                <div class="number"><?= $occupiedRooms ?>/<?= $totalRooms ?></div>
                <p class="green"><?= $occupancyRate ?>% occupancy</p>
            </div>

            <div class="card">
                <h4><i class="fa-regular fa-calendar-check"></i> Check-ins Today</h4>
                <div class="number"><?= $checkInToday ?></div>
                <?php if ($checkInDiff > 0): ?>
                <p class="green">↑ <?= $checkInDiff ?> more than yesterday</p>
                <?php elseif ($checkInDiff < 0): ?>
                <p class="red">↓ <?= abs($checkInDiff) ?> less than yesterday</p>
                <?php else: ?>
                <p style="color:#888;">Same as yesterday</p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h4><i class="fa-regular fa-calendar-xmark"></i> Check-outs Today</h4>
                <div class="number"><?= $checkOutToday ?></div>
                <?php if ($checkOutDiff > 0): ?>
                <p class="green">↑ <?= $checkOutDiff ?> more than yesterday</p>
                <?php elseif ($checkOutDiff < 0): ?>
                <p class="red">↓ <?= abs($checkOutDiff) ?> less than yesterday</p>
                <?php else: ?>
                <p style="color:#888;">Same as yesterday</p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h4><i class="fa-solid fa-sack-dollar"></i> Revenue Today</h4>
                <div class="number">৳<?= number_format($revenueToday, 2) ?></div>
                <?php if ($revenueChange > 0): ?>
                <p class="green">↗ +<?= $revenueChange ?>% vs yesterday</p>
                <?php elseif ($revenueChange < 0): ?>
                <p class="red">↘ <?= abs($revenueChange) ?>% vs yesterday</p>
                <?php else: ?>
                <p style="color:#888;">Same as yesterday</p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h4><i class="fa-regular fa-user"></i> Total Guests</h4>
                <div class="number"><?= $totalGuests ?></div>
                <p style="color:#888;">Registered guests</p>
            </div>

            <div class="card">
                <h4><i class="fa-solid fa-coins"></i> Total Revenue</h4>
                <div class="number" style="font-size:32px;">৳<?= number_format($totalRevenue, 2) ?></div>
                <p class="green">Paid &amp; Partial</p>
            </div>

            <div class="card">
                <h4><i class="fa-solid fa-bell-concierge"></i> Pending Services</h4>
                <div class="number <?= $pendingServices > 0 ? 'red' : 'green' ?>"><?= $pendingServices ?></div>
                <p style="color:#888;">Service requests</p>
            </div>

        </div>

        <!-- TABLE -->
        <div class="table-box">

            <div class="table-header">
                <h2>Recent Reservations</h2>
                <a href="#">View all →</a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $reservationQuery = mysqli_query($conn, "
            SELECT
                booking.booking_id,
                booking.check_in,
                booking.check_out,
                booking.status,
                user.Name,
                room.room_number
            FROM booking
            JOIN user ON booking.guest_id = user.User_id
            JOIN room ON booking.room_id = room.room_id
            ORDER BY booking.booking_id DESC
            LIMIT 5
          ");

                    if (mysqli_num_rows($reservationQuery) == 0) { ?>
                    <tr>
                        <td colspan="5" style="text-align:center;">No Reservations Found</td>
                    </tr>
                    <?php } else {
                        while ($row = mysqli_fetch_assoc($reservationQuery)) { ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Name']) ?></td>
                        <td><?= htmlspecialchars($row['room_number']) ?></td>
                        <td><?= date('d M', strtotime($row['check_in'])) ?></td>
                        <td><?= date('d M', strtotime($row['check_out'])) ?></td>
                        <td>
                            <?php
                                    $status = $row['status'];
                                    if ($status == "Checked-In") {
                                        echo '<span class="status checked">Checked In</span>';
                                    } elseif ($status == "Confirmed") {
                                        echo '<span class="status reserved">Reserved</span>';
                                    } elseif ($status == "Checked-Out") {
                                        echo '<span class="status checkout">Checked Out</span>';
                                    } else {
                                        echo '<span class="status reserved">' . htmlspecialchars($status) . '</span>';
                                    }
                                    ?>
                        </td>
                    </tr>
                    <?php }
                    } ?>
                </tbody>
            </table>

        </div>

    </div>

    <script>
    function toggleNotif() {
        document.getElementById('notifDropdown').classList.toggle('open');
        document.getElementById('searchBar').classList.remove('open');
    }

    function toggleSearch() {
        document.getElementById('searchBar').classList.toggle('open');
        document.getElementById('notifDropdown').classList.remove('open');
    }

    function doSearch(val) {
        const res = document.getElementById('searchResults');
        if (val.length < 2) {
            res.innerHTML = '';
            return;
        }
        fetch('search_ajax.php?q=' + encodeURIComponent(val))
            .then(r => r.json())
            .then(data => {
                if (data.length === 0) {
                    res.innerHTML = '<p style="color:#888;">No results found</p>';
                } else {
                    res.innerHTML = data.map(d =>
                        `<div style="padding:8px 0;border-bottom:1px solid #f1f1f1;">
              <b>${d.name}</b> — Room ${d.room}
            </div>`
                    ).join('');
                }
            });
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.notif-wrapper')) {
            document.getElementById('notifDropdown').classList.remove('open');
            document.getElementById('searchBar').classList.remove('open');
        }
    });
    </script>

</body>

</html>
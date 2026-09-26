<?php
// my_stay.php
// Tabbed shell — includes content partials for each section.
// Navbar highlights "My Stay" since all tabs live here.

include '../db.php';
include '../auth.php';
include '../navbar.php';

if ($_SESSION['role'] !== 'guest') {
    header("Location: ../index.php");
    exit();
}

// Active stay (Checked-In) — needed by service & food tabs
$active_stmt = $conn->prepare("
    SELECT b.booking_id, b.check_in, b.check_out, r.room_number, r.type AS room_type
    FROM booking b
    JOIN room r ON b.room_id = r.room_id
    WHERE b.guest_id = ? AND b.status = 'Checked-In'
    LIMIT 1
");
$active_stmt->bind_param("i", $_SESSION['user_id']);
$active_stmt->execute();
$active_booking = $active_stmt->get_result()->fetch_assoc();
$active_stmt->close();

// Food menu — needed by food tab
$menu_stmt = $conn->prepare("SELECT * FROM food_menu WHERE available = 1 ORDER BY category, name ASC");
$menu_stmt->execute();
$menu_items = $menu_stmt->get_result();
$menu_stmt->close();

// Active tab from URL, default to bookings
$active_tab = $_GET['tab'] ?? 'bookings';
$allowed_tabs = ['bookings', 'service', 'food', 'history'];
if (!in_array($active_tab, $allowed_tabs)) $active_tab = 'bookings';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Stay — Meghdoot Resort</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/css/bootstrap.min.css"
        onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'">
    <?php include '../global_css.php'; ?>
    <style>
        .tab-pill { cursor: pointer; transition: all .2s; }
        .tab-pill.active { background: var(--bs-primary) !important; color: #fff !important; border-color: var(--bs-primary) !important; }
        .section-panel { display: none; }
        .section-panel.active { display: block; }
        .food-row:hover { background: #f8f9ff; }
    </style>
</head>

<body class="bg-light">

    <!-- PAGE HEADER -->
    <div class="bg-primary text-white py-5 text-center">
        <div class="container">
            <h1 class="fw-bold">My Stay</h1>
            <p class="lead fw-light mb-0">Your bookings, services & food orders — all in one place</p>
        </div>
    </div>

    <section class="py-4">
        <div class="container">

            <!-- TAB NAVIGATION -->
            <div class="d-flex flex-wrap gap-2 mb-4">
                <button class="btn btn-outline-primary rounded-pill tab-pill <?php echo $active_tab === 'bookings' ? 'active' : ''; ?>"
                    data-tab="bookings">
                    My Bookings
                </button>
                <button class="btn btn-outline-primary rounded-pill tab-pill <?php echo $active_tab === 'service' ? 'active' : ''; ?>"
                    data-tab="service">
                    Request Service
                    <?php if (!$active_booking): ?>
                        <span class="badge bg-secondary ms-1" title="Check-in required">locked</span>
                    <?php endif; ?>
                </button>
                <button class="btn btn-outline-primary rounded-pill tab-pill <?php echo $active_tab === 'food' ? 'active' : ''; ?>"
                    data-tab="food">
                    Order Food
                    <?php if (!$active_booking): ?>
                        <span class="badge bg-secondary ms-1" title="Check-in required">locked</span>
                    <?php endif; ?>
                </button>
                <button class="btn btn-outline-primary rounded-pill tab-pill <?php echo $active_tab === 'history' ? 'active' : ''; ?>"
                    data-tab="history">
                    Past Requests
                </button>
            </div>

            <!-- TAB 1: MY BOOKINGS -->
            <div class="section-panel <?php echo $active_tab === 'bookings' ? 'active' : ''; ?>" id="tab-bookings">
                <?php include 'my_bookings_content.php'; ?>
            </div>

            <!-- TAB 2: REQUEST SERVICE -->
            <div class="section-panel <?php echo $active_tab === 'service' ? 'active' : ''; ?>" id="tab-service">
                <?php include 'service_content.php'; ?>
            </div>

            <!-- TAB 3: ORDER FOOD -->
            <div class="section-panel <?php echo $active_tab === 'food' ? 'active' : ''; ?>" id="tab-food">
                <?php include 'food_order_content.php'; ?>
            </div>

            <!-- TAB 4: PAST REQUESTS -->
            <div class="section-panel <?php echo $active_tab === 'history' ? 'active' : ''; ?>" id="tab-history">
                <?php include 'past_requests_content.php'; ?>
            </div>

        </div>
    </section>

    <?php include '../footer.php'; ?>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"
        onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js'">
    </script>

    <script>
    // Tab switching — also updates URL so refreshing lands on same tab
    function switchTab(name) {
        document.querySelectorAll('.tab-pill').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.section-panel').forEach(p => p.classList.remove('active'));
        document.querySelector(`[data-tab="${name}"]`).classList.add('active');
        document.getElementById('tab-' + name).classList.add('active');
        history.replaceState(null, '', 'my_stay.php?tab=' + name);
    }

    document.querySelectorAll('.tab-pill').forEach(btn => {
        btn.addEventListener('click', () => switchTab(btn.dataset.tab));
    });
    </script>

</body>
</html>

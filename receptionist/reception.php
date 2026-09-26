<?php
// receptionist/reception.php

if (session_status() === PHP_SESSION_NONE) session_start();
include '../db.php';
include '../auth.php';

if ($_SESSION['role'] !== 'receptionist' && $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$active_tab   = $_GET['tab'] ?? 'checkin';
$allowed_tabs = ['checkin', 'checkout', 'bookings'];
if (!in_array($active_tab, $allowed_tabs)) $active_tab = 'checkin';

$pending_checkins = $conn->query("SELECT COUNT(*) AS c FROM booking WHERE status='Confirmed' AND payment_status IN ('partial','paid')")->fetch_assoc()['c'];
$in_house         = $conn->query("SELECT COUNT(*) AS c FROM booking WHERE status='Checked-In'")->fetch_assoc()['c'];
$due_checkouts    = $conn->query("SELECT COUNT(*) AS c FROM booking WHERE status='Checked-In' AND check_out <= CURDATE()")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reception — Meghdoot Resort</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/css/bootstrap.min.css"
        onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'">
    <?php include '../global_css.php'; ?>
    <style>
    .tab-pill {
        cursor: pointer;
        transition: all .2s;
    }

    .tab-pill.active {
        background: var(--bs-primary) !important;
        color: #fff !important;
        border-color: var(--bs-primary) !important;
    }

    .section-panel {
        display: none;
    }

    .section-panel.active {
        display: block;
    }
    </style>
</head>

<body class="bg-light">
    <?php include '../navbar.php'; ?>

    <div class="bg-primary text-white py-4">
        <div class="container">
            <div class="row align-items-center g-3">
                <div class="col-md-5">
                    <h2 class="fw-bold mb-0">Reception</h2>
                    <p class="fw-light mb-0 small">Signed in as <?php echo htmlspecialchars($_SESSION['name']); ?></p>
                </div>
                <div class="col-md-7">
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="bg-white bg-opacity-15 rounded-3 py-2 px-3 text-warning">
                                <div class="fw-bold fs-4"><?php echo $pending_checkins; ?></div>
                                <div class="small opacity-75">Pending Check-Ins</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-white bg-opacity-15 rounded-3 py-2 px-3 text-success">
                                <div class="fw-bold fs-4"><?php echo $in_house; ?></div>
                                <div class="small opacity-75">In-House</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-white bg-opacity-15 rounded-3 py-2 px-3 text-danger">
                                <div class="fw-bold fs-4 <?php echo $due_checkouts > 0 ? 'text-danger' : ''; ?>">
                                    <?php echo $due_checkouts; ?></div>
                                <div class="small opacity-75">Due Check-Outs</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="py-4">
        <div class="container">

            <div class="d-flex flex-wrap gap-2 mb-4">
                <button
                    class="btn btn-outline-primary rounded-pill tab-pill <?php echo $active_tab === 'checkin'  ? 'active' : ''; ?>"
                    data-tab="checkin">
                    Check-In
                    <?php if ($pending_checkins > 0): ?>
                    <span class="badge bg-warning text-dark ms-1"><?php echo $pending_checkins; ?></span>
                    <?php endif; ?>
                </button>
                <button
                    class="btn btn-outline-primary rounded-pill tab-pill <?php echo $active_tab === 'checkout' ? 'active' : ''; ?>"
                    data-tab="checkout">
                    Check-Out
                    <?php if ($due_checkouts > 0): ?>
                    <span class="badge bg-danger ms-1"><?php echo $due_checkouts; ?></span>
                    <?php endif; ?>
                </button>
                <button
                    class="btn btn-outline-primary rounded-pill tab-pill <?php echo $active_tab === 'bookings' ? 'active' : ''; ?>"
                    data-tab="bookings">
                    All Bookings
                </button>
                <a href="generate_invoice.php" class="btn btn-outline-secondary rounded-pill ms-auto">
                    Generate Invoice
                </a>
            </div>

            <div class="section-panel <?php echo $active_tab === 'checkin'  ? 'active' : ''; ?>" id="tab-checkin">
                <?php include 'checkin_content.php'; ?>
            </div>

            <div class="section-panel <?php echo $active_tab === 'checkout' ? 'active' : ''; ?>" id="tab-checkout">
                <?php include 'checkout_content.php'; ?>
            </div>

            <div class="section-panel <?php echo $active_tab === 'bookings' ? 'active' : ''; ?>" id="tab-bookings">
                <?php include 'manage_bookings_content.php'; ?>
            </div>

        </div>
    </section>

    <?php include '../footer.php'; ?>
    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"
        onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js'">
    </script>
    <script>
    function switchTab(name) {
        document.querySelectorAll('.tab-pill').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.section-panel').forEach(p => p.classList.remove('active'));
        document.querySelector(`[data-tab="${name}"]`).classList.add('active');
        document.getElementById('tab-' + name).classList.add('active');
        history.replaceState(null, '', '?tab=' + name);
    }
    document.querySelectorAll('.tab-pill').forEach(btn => {
        btn.addEventListener('click', () => switchTab(btn.dataset.tab));
    });
    </script>
</body>

</html>
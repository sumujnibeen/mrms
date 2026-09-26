<?php
// manager/sidebar.php — reusable sidebar partial
$current = basename($_SERVER['PHP_SELF']);

$nav = [
    'dashboard.php' => 'Dashboard',
    'bookings.php'  => 'Bookings',
    'rooms.php'     => 'Rooms',
    'billing.php'   => 'Billing',
    'finance.php'   => 'Finance',
];
?>
<div class="mgr-sidebar">
    <div class="mgr-sidebar-header">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?php echo $depth; ?>../index.php">
            <img src="logo_blue.png" alt="Logo" style="height: 60px;">

        </a>
        <div class="mgr-sidebar-role">Manager Panel</div>
    </div>
    <nav class="mgr-sidebar-nav">
        <?php foreach ($nav as $href => $label):
            $active = ($current === $href) ? ' active' : '';
        ?>
            <a href="<?php echo $href; ?>" class="mgr-nav-item<?php echo $active; ?>">
                <?php echo $label; ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="mgr-sidebar-footer">
        <a href="../logout.php" class="mgr-logout">Logout</a>
    </div>
</div>

<style>
    .mgr-layout {
        display: flex;
        min-height: 100vh;
    }

    .mgr-sidebar {
        width: 220px;
        min-height: 100vh;
        background: #1e293b;
        color: #fff;
        display: flex;
        flex-direction: column;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 100;
    }

    .mgr-sidebar-header {
        padding: 24px 20px 16px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .mgr-sidebar-logo {
        font-size: 18px;
        font-weight: 700;
        color: #fff;
    }

    .mgr-sidebar-role {
        font-size: 11px;
        color: rgba(255, 255, 255, 0.5);
        margin-top: 2px;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .mgr-sidebar-nav {
        flex: 1;
        padding: 12px 0;
        overflow-y: auto;
    }

    .mgr-nav-item {
        display: block;
        padding: 10px 20px;
        color: rgba(255, 255, 255, 0.7);
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: background .15s, color .15s;
    }

    .mgr-nav-item:hover {
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
    }

    .mgr-nav-item.active {
        background: #3b82f6;
        color: #fff;
    }

    .mgr-sidebar-footer {
        padding: 16px 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .mgr-logout {
        display: block;
        color: #f87171;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
    }

    .mgr-logout:hover {
        color: #fca5a5;
    }

    .mgr-main {
        margin-left: 220px;
        flex: 1;
        min-height: 100vh;
        background: #f1f5f9;
    }
</style>
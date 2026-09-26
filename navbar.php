<?php
// navbar.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current = basename($_SERVER['PHP_SELF']);
$role    = $_SESSION['role'] ?? 'guest';
$logged  = isset($_SESSION['user_id']);

// Detect subdirectory depth for path prefix
$script_path = $_SERVER['PHP_SELF'];
$depth = '';
if (str_contains($script_path, '/admin/'))        $depth = '../';
elseif (str_contains($script_path, '/receptionist/')) $depth = '../';
elseif (str_contains($script_path, '/user/'))     $depth = '../';

// Profile link per role
$profile_href = match ($role) {
    'admin'        => $depth . 'admin/dashboard.php',
    'receptionist' => $depth . 'receptionist/reception.php',
    'manager'      => $depth . 'admin/dashboard.php',
    default        => $depth . 'user/dashboard.php',
};

function nav_active($page, $current)
{
    return $page === $current ? 'active' : '';
}

// My Stay active: highlight when inside any user/ page
$user_pages = ['my_stay.php', 'my_bookings.php', 'current_bill.php', 'payment.php', 'payment_success.php', 'payment_fail.php', 'dashboard.php', 'update_profile.php', 'my_reviews.php', 'delete_account.php'];
$my_stay_active = in_array($current, $user_pages) ? 'active' : '';

// Receptionist Manage dropdown active
$rec_active = in_array($current, ['checkin.php', 'checkout.php', 'manage_bookings.php', 'generate_invoice.php', 'reception.php']) ? 'active' : '';

// Admin Panel dropdown active
$admin_pages = ['dashboard.php', 'manage_rooms.php', 'manage_bookings.php', 'manage_users.php', 'manage_food.php', 'manage_services.php', 'manage_announcements.php', 'reports.php', 'finance.php'];
$admin_active = in_array($current, $admin_pages) ? 'active' : '';
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
    <div class="container">

        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?php echo $depth; ?>index.php">
            <img src="<?php echo $depth; ?>assets/images/logo_blue.png" alt="Logo" style="height: 36px;">
            Meghdoot Resort
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <li class="nav-item">
                    <a class="nav-link <?php echo nav_active('index.php', $current); ?>"
                        href="<?php echo $depth; ?>index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo nav_active('rooms.php', $current); ?>"
                        href="<?php echo $depth; ?>rooms.php">Rooms</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo nav_active('food_menu.php', $current); ?>"
                        href="<?php echo $depth; ?>food_menu.php">Food Menu</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo nav_active('announcement.php', $current); ?>"
                        href="<?php echo $depth; ?>announcement.php">Announcements</a>
                </li>

                <?php if ($logged && $role === 'guest'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $my_stay_active; ?>" href="<?php echo $depth; ?>user/my_stay.php">My
                            Stay</a>
                    </li>
                <?php endif; ?>

                <?php if ($logged && $role === 'receptionist'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo $rec_active; ?>" href="#" data-bs-toggle="dropdown">
                            Manage
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item <?php echo nav_active('reception.php', $current); ?>"
                                    href="<?php echo $depth; ?>receptionist/reception.php">Reception Dashboard</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item <?php echo nav_active('checkin.php', $current); ?>"
                                    href="<?php echo $depth; ?>receptionist/reception.php?tab=checkin">Check-In</a></li>
                            <li><a class="dropdown-item <?php echo nav_active('checkout.php', $current); ?>"
                                    href="<?php echo $depth; ?>receptionist/reception.php?tab=checkout">Check-Out</a></li>
                            <li><a class="dropdown-item <?php echo nav_active('manage_bookings.php', $current); ?>"
                                    href="<?php echo $depth; ?>receptionist/reception.php?tab=bookings">All Bookings</a>
                            </li>
                            <li><a class="dropdown-item <?php echo nav_active('generate_invoice.php', $current); ?>"
                                    href="<?php echo $depth; ?>receptionist/generate_invoice.php">Generate Invoice</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if ($logged && in_array($role, ['admin', 'manager'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo $admin_active; ?>" href="#" data-bs-toggle="dropdown">
                            Admin Panel
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item <?php echo nav_active('index.php', $current); ?>"
                                    href="<?php echo $depth; ?>admin/index.php">Dashboard</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item <?php echo nav_active('manage_rooms.php', $current); ?>"
                                    href="<?php echo $depth; ?>admin/manage_rooms.php">Rooms</a></li>
                            <li><a class="dropdown-item <?php echo nav_active('manage_bookings.php', $current); ?>"
                                    href="<?php echo $depth; ?>admin/manage_bookings.php">Bookings</a></li>
                            <li><a class="dropdown-item <?php echo nav_active('manage_users.php', $current); ?>"
                                    href="<?php echo $depth; ?>admin/manage_users.php">Users</a></li>
                            <li><a class="dropdown-item <?php echo nav_active('manage_food.php', $current); ?>"
                                    href="<?php echo $depth; ?>admin/manage_food.php">Food Menu</a></li>
                            <li><a class="dropdown-item <?php echo nav_active('manage_services.php', $current); ?>"
                                    href="<?php echo $depth; ?>admin/manage_services.php">Services</a></li>
                            <li><a class="dropdown-item <?php echo nav_active('manage_announcements.php', $current); ?>"
                                    href="<?php echo $depth; ?>admin/manage_announcements.php">Announcements</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item <?php echo nav_active('reports.php', $current); ?>"
                                    href="<?php echo $depth; ?>admin/reports.php">Reports</a></li>
                            <li><a class="dropdown-item <?php echo nav_active('finance.php', $current); ?>"
                                    href="<?php echo $depth; ?>admin/finance.php">Finance</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

            </ul>

            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                <?php if ($logged): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#"
                            data-bs-toggle="dropdown">
                            <?php if (!empty($_SESSION['photo'])): ?>
                                <img src="<?php echo $depth . htmlspecialchars($_SESSION['photo']); ?>" class="rounded-circle"
                                    style="height:30px;width:30px;object-fit:cover;">
                            <?php else: ?>
                                <span
                                    class="rounded-circle bg-white text-primary fw-bold d-flex align-items-center justify-content-center"
                                    style="height:30px;width:30px;font-size:13px;flex-shrink:0;">
                                    <?php echo strtoupper(mb_substr($_SESSION['name'], 0, 1)); ?>
                                </span>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($_SESSION['name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <span class="dropdown-item-text text-muted small"><?php echo ucfirst($role); ?></span>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo $profile_href; ?>">
                                    <?php echo match ($role) {
                                        'admin'        => 'Admin Panel',
                                        'manager'      => 'Admin Panel',
                                        'receptionist' => 'Reception',
                                        default        => 'Dashboard',
                                    }; ?>
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="<?php echo $depth; ?>logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo nav_active('login.php', $current); ?>"
                            href="<?php echo $depth; ?>login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-light text-primary fw-semibold rounded-pill px-4 ms-2"
                            href="<?php echo $depth; ?>register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
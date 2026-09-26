<?php
// service_submit.php
// Guest: new service request or food order submission
// (service_manager.php is for admin/receptionist status updates only)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db.php';
include 'auth.php';

// Only guests
if ($_SESSION['role'] !== 'guest') {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: user/my_stay.php");
    exit();
}

$guest_id   = $_SESSION['user_id'];
$booking_id = (int)$_POST['booking_id'];
$type       = trim($_POST['type'] ?? '');
$redirect   = $_POST['redirect'] ?? 'user/my_stay.php';

$allowed_types = ['food', 'housekeeping', 'extra_towel', 'laundry', 'other'];

if (!in_array($type, $allowed_types)) {
    $_SESSION['service_error'] = "Invalid service type.";
    header("Location: $redirect");
    exit();
}

// Verify booking belongs to this guest and is Checked-In
$verify = $conn->prepare("
    SELECT booking_id FROM booking
    WHERE booking_id = ? AND guest_id = ? AND status = 'Checked-In'
");
$verify->bind_param("ii", $booking_id, $guest_id);
$verify->execute();
$verify->store_result();

if ($verify->num_rows === 0) {
    $_SESSION['service_error'] = "No active checked-in booking found.";
    header("Location: $redirect");
    exit();
}
$verify->close();

// ── FOOD ORDER ────────────────────────────────────────────
if ($type === 'food') {
    // Description built by JS in my_stay.php and sent as hidden textarea
    $desc = trim($_POST['description'] ?? '');

    // Also support food[] array (fallback from service_request.php)
    if (empty($desc) && !empty($_POST['food'])) {
        $ordered      = [];
        $total_charge = 0.0;

        foreach ($_POST['food'] as $menu_id => $qty) {
            $menu_id = (int)$menu_id;
            $qty     = (int)$qty;
            if ($qty <= 0) continue;

            $item_stmt = $conn->prepare("SELECT name, price FROM food_menu WHERE menu_id = ? AND available = 1");
            $item_stmt->bind_param("i", $menu_id);
            $item_stmt->execute();
            $item = $item_stmt->get_result()->fetch_assoc();
            $item_stmt->close();

            if (!$item) continue;
            $ordered[]     = $item['name'] . ' x' . $qty;
            $total_charge += $item['price'] * $qty;
        }

        if (empty($ordered)) {
            $_SESSION['service_error'] = "Please select at least one food item.";
            header("Location: $redirect");
            exit();
        }

        $desc         = implode(', ', $ordered);
        $charge_final = $total_charge;

    } else {
        // Calculate charge from description + food[] prices
        $total_charge = 0.0;
        if (!empty($_POST['food'])) {
            foreach ($_POST['food'] as $menu_id => $qty) {
                $menu_id = (int)$menu_id;
                $qty     = (int)$qty;
                if ($qty <= 0) continue;

                $item_stmt = $conn->prepare("SELECT price FROM food_menu WHERE menu_id = ? AND available = 1");
                $item_stmt->bind_param("i", $menu_id);
                $item_stmt->execute();
                $item = $item_stmt->get_result()->fetch_assoc();
                $item_stmt->close();

                if ($item) $total_charge += $item['price'] * $qty;
            }
        }
        $charge_final = $total_charge;
    }

    if (empty($desc)) {
        $_SESSION['service_error'] = "Please select at least one food item.";
        header("Location: $redirect");
        exit();
    }

    $insert = $conn->prepare("
        INSERT INTO service_request (guest_id, booking_id, type, description, status, charge)
        VALUES (?, ?, 'food', ?, 'Pending', ?)
    ");
    $insert->bind_param("iisd", $guest_id, $booking_id, $desc, $charge_final);
    $insert->execute();
    $insert->close();

    $_SESSION['service_success'] = "Food order placed! Total: ৳" . number_format($charge_final);

// ── OTHER SERVICES ────────────────────────────────────────
} else {
    $desc = trim($_POST['description'] ?? '');

    if (empty($desc)) {
        $_SESSION['service_error'] = "Please describe your request.";
        header("Location: $redirect");
        exit();
    }

    $insert = $conn->prepare("
        INSERT INTO service_request (guest_id, booking_id, type, description, status, charge)
        VALUES (?, ?, ?, ?, 'Pending', 0.00)
    ");
    $insert->bind_param("iiss", $guest_id, $booking_id, $type, $desc);
    $insert->execute();
    $insert->close();

    $labels = [
        'housekeeping' => 'Housekeeping',
        'extra_towel'  => 'Extra Towel',
        'laundry'      => 'Laundry',
        'other'        => 'Service',
    ];
    $_SESSION['service_success'] = ($labels[$type] ?? 'Service') . " request submitted! We'll attend to you shortly.";
}

header("Location: $redirect");
exit();
?>

<?php
// service_request.php

include 'db.php';
include 'auth.php';
include 'navbar.php';

// Only guests can place service requests
if ($_SESSION['role'] !== 'guest') {
    header("Location: index.php");
    exit();
}

$guest_id    = $_SESSION['user_id'];
$type        = $_GET['type'] ?? 'other';
$menu_id     = (int)($_GET['menu_id'] ?? 0);
$error       = '';
$success     = '';

// Fetch guest's active booking (Confirmed or Checked-In)
$booking_stmt = $conn->prepare("
    SELECT b.booking_id, b.check_in, b.check_out, r.room_number, r.type
    FROM booking b
    JOIN room r ON b.room_id = r.room_id
    WHERE b.guest_id = ?
    AND b.status IN ('Confirmed', 'Checked-In')
    AND b.check_out >= CURDATE()
    ORDER BY b.check_in ASC
    LIMIT 1
");
$booking_stmt->bind_param("i", $guest_id);
$booking_stmt->execute();
$booking_result = $booking_stmt->get_result();
$active_booking = $booking_result->fetch_assoc();
$booking_stmt->close();

// Fetch selected food item if type=food
$selected_item = null;
if ($type === 'food' && $menu_id > 0) {
    $item_stmt = $conn->prepare("SELECT * FROM food_menu WHERE menu_id = ? AND available = 1");
    $item_stmt->bind_param("i", $menu_id);
    $item_stmt->execute();
    $selected_item = $item_stmt->get_result()->fetch_assoc();
    $item_stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id  = (int)$_POST['booking_id'];
    $req_type    = trim($_POST['req_type']);
    $description = trim($_POST['description']);
    $charge      = (float)($_POST['charge'] ?? 0);

    if (empty($description)) {
        $error = "Please describe your request.";
    } elseif (!$active_booking) {
        $error = "You have no active booking to place a service request.";
    } else {
        $insert = $conn->prepare("
            INSERT INTO service_request (guest_id, booking_id, type, description, status, charge)
            VALUES (?, ?, ?, ?, 'Pending', ?)
        ");
        $insert->bind_param("iissd", $guest_id, $booking_id, $req_type, $description, $charge);

        if ($insert->execute()) {
            $success = "Your request has been placed successfully!";
        } else {
            $error = "Something went wrong. Please try again.";
        }
        $insert->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Request — Meghdoot Resort</title>
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <link rel="stylesheet" href="bootstrap-5.3.8-dist/css/bootstrap.min.css"
        onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'">
    <?php include 'global_css.php'; ?>
</head>

<body class="bg-light">

    <!-- PAGE HEADER -->
    <div class="bg-primary text-white py-5 text-center">
        <div class="container">
            <h1 class="fw-bold">Service Request</h1>
            <p class="lead fw-light mb-0">Request food or room services during your stay</p>
        </div>
    </div>

    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">

                    <?php if (!$active_booking): ?>
                        <!-- NO ACTIVE BOOKING -->
                        <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                            <div class="fs-1 mb-3"></div>
                            <h5 class="fw-bold">No Active Booking</h5>
                            <p class="text-muted">You need a confirmed booking to place a service request.</p>
                            <a href="rooms.php" class="btn btn-primary rounded-pill px-5">Book a Room</a>
                        </div>

                    <?php else: ?>

                        <!-- ACTIVE BOOKING INFO -->
                        <div class="alert alert-primary rounded-3 small mb-4">
                            <strong>Active Booking:</strong>
                            Room <?php echo htmlspecialchars($active_booking['room_number']); ?>
                            (<?php echo htmlspecialchars($active_booking['type']); ?>)
                            &bull;
                            <?php echo date('d M', strtotime($active_booking['check_in'])); ?>
                            →
                            <?php echo date('d M Y', strtotime($active_booking['check_out'])); ?>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger rounded-3 small"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success rounded-3 small"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <div class="card border-0 shadow-sm rounded-4 p-4">

                            <!-- Selected food item preview -->
                            <?php if ($selected_item): ?>
                                <div class="d-flex align-items-center gap-3 bg-light rounded-3 p-3 mb-4">
                                    <img src="<?php echo htmlspecialchars($selected_item['image']); ?>"
                                        class="rounded-3 img-cover"
                                        style="height: 60px; width: 60px;"
                                        alt="<?php echo htmlspecialchars($selected_item['name']); ?>">
                                    <div>
                                        <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($selected_item['name']); ?></h6>
                                        <small class="text-primary fw-semibold">৳<?php echo number_format($selected_item['price']); ?></small>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="service_request.php">
                                <input type="hidden" name="booking_id" value="<?php echo $active_booking['booking_id']; ?>">
                                <input type="hidden" name="charge" value="<?php echo $selected_item ? $selected_item['price'] : 0; ?>">

                                <!-- Request Type -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Request Type</label>
                                    <select name="req_type" class="form-select rounded-3" required>
                                        <option value="food"         <?php echo $type === 'food'         ? 'selected' : ''; ?>>Food Order</option>
                                        <option value="housekeeping" <?php echo $type === 'housekeeping'  ? 'selected' : ''; ?>>Housekeeping</option>
                                        <option value="extra_towel"  <?php echo $type === 'extra_towel'   ? 'selected' : ''; ?>>Extra Towel</option>
                                        <option value="laundry"      <?php echo $type === 'laundry'       ? 'selected' : ''; ?>>Laundry</option>
                                        <option value="other"        <?php echo $type === 'other'         ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>

                                <!-- Description -->
                                <div class="mb-4">
                                    <label class="form-label fw-semibold small">Description</label>
                                    <textarea name="description" class="form-control rounded-3"
                                        rows="4"
                                        placeholder="Describe your request in detail..."
                                        required><?php
                                        if ($selected_item) {
                                            echo htmlspecialchars($selected_item['name'] . ' x1');
                                        }
                                    ?></textarea>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary rounded-pill fw-semibold py-2">
                                        Submit Request
                                    </button>
                                </div>
                            </form>

                        </div>

                        <div class="text-center mt-3">
                            <a href="food_menu.php" class="btn btn-outline-primary rounded-pill btn-sm px-4">
                                ← Back to Food Menu
                            </a>
                        </div>

                    <?php endif; ?>

                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <?php include 'footer.php'; ?>

    <script src="bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"
        onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js'">
    </script>

</body>

</html>

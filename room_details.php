<?php
// room_details.php

include 'db.php';
include 'navbar.php';

$room_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($room_id === 0) {
    header("Location: rooms.php");
    exit();
}

// live_status: rooms.php-এর মতো একই logic এখানেও ব্যবহার করা হচ্ছে
// যাতে rooms.php ও room_details.php সবসময় একই status দেখায়
$stmt = $conn->prepare("
    SELECT r.*,
        CASE
            WHEN r.status = 'Under Maintenance' THEN 'Under Maintenance'
            WHEN EXISTS (
                SELECT 1 FROM booking b
                WHERE b.room_id = r.room_id AND b.status = 'Checked-In'
            ) THEN 'Booked'
            WHEN EXISTS (
                SELECT 1 FROM booking b
                WHERE b.room_id = r.room_id
                AND b.payment_status IN ('partial', 'paid')
                AND b.status IN ('Pending', 'Confirmed')
                AND b.check_in > CURDATE()
            ) THEN 'Booked'
            WHEN EXISTS (
                SELECT 1 FROM booking b
                WHERE b.room_id = r.room_id
                AND b.payment_status = 'partial'
                AND b.status IN ('Pending', 'Confirmed')
                AND b.check_in <= CURDATE()
            ) THEN 'Available'
            ELSE 'Available'
        END AS live_status
    FROM room r
    WHERE r.room_id = ?
");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: rooms.php");
    exit();
}

$room = $result->fetch_assoc();
$stmt->close();

// Review aggregate for this room
$rv_agg_stmt = $conn->prepare("
    SELECT ROUND(AVG(rating), 1) AS avg_r, COUNT(*) AS total_r
    FROM review WHERE target_type = 'room' AND target_id = ?
");
$rv_agg_stmt->bind_param("i", $room_id);
$rv_agg_stmt->execute();
$rv_agg = $rv_agg_stmt->get_result()->fetch_assoc();
$rv_agg_stmt->close();

$tomorrow = date('Y-m-d', strtotime('+1 day'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room <?php echo htmlspecialchars($room['room_number']); ?> — Meghdoot Resort</title>
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <link rel="stylesheet" href="bootstrap-5.3.8-dist/css/bootstrap.min.css"
        onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'">
    <?php include 'global_css.php'; ?>
</head>

<body class="bg-light">

    <!-- PAGE HEADER -->
    <div class="bg-primary text-white py-5 text-center">
        <div class="container">
            <h1 class="fw-bold">Room <?php echo htmlspecialchars($room['room_number']); ?></h1>
            <p class="lead fw-light mb-0"><?php echo htmlspecialchars($room['type']); ?> Room</p>
        </div>
    </div>

    <!-- ROOM DETAILS -->
    <section class="py-5">
        <div class="container">
            <div class="row g-5">

                <!-- LEFT: Image + Info -->
                <div class="col-lg-7">
                    <img src="<?php echo htmlspecialchars($room['image']); ?>"
                        alt="Room <?php echo htmlspecialchars($room['room_number']); ?>"
                        class="img-fluid rounded-4 shadow w-100 img-cover" style="max-height: 400px;">

                    <div class="mt-4">
                        <h4 class="fw-bold mb-3">Room Details</h4>

                        <div class="row g-3 mb-4">
                            <div class="col-6 col-md-3">
                                <div class="card border-0 bg-white shadow-sm rounded-4 text-center p-3">
                                    <div class="fw-bold text-primary small">Type</div>
                                    <small class="text-muted">Type</small>
                                    <p class="fw-bold mb-0 small"><?php echo htmlspecialchars($room['type']); ?></p>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card border-0 bg-white shadow-sm rounded-4 text-center p-3">
                                    <div class="fw-bold text-primary small">Floor</div>
                                    <small class="text-muted">Floor</small>
                                    <p class="fw-bold mb-0 small"><?php echo htmlspecialchars($room['floor']); ?></p>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card border-0 bg-white shadow-sm rounded-4 text-center p-3">
                                    <div class="fw-bold text-primary small">Guests</div>
                                    <small class="text-muted">Capacity</small>
                                    <p class="fw-bold mb-0 small"><?php echo htmlspecialchars($room['capacity']); ?>
                                        Guest(s)</p>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card border-0 bg-white shadow-sm rounded-4 text-center p-3">
                                    <div class="fw-bold text-primary small">Rate</div>
                                    <small class="text-muted">Per Night</small>
                                    <p class="fw-bold mb-0 small text-primary">
                                        ৳<?php echo number_format($room['price_per_night']); ?></p>
                                </div>
                            </div>
                        </div>

                        <p class="text-muted"><?php echo htmlspecialchars($room['description']); ?></p>

                        <!-- Status -->
                        <?php
                        $badge = match ($room['live_status']) {
                            'Available'         => 'bg-success',
                            'Booked'            => 'bg-danger',
                            'Under Maintenance' => 'bg-warning text-dark',
                            default             => 'bg-secondary'
                        };
                        ?>
                        <span class="badge <?php echo $badge; ?> rounded-pill px-3 py-2">
                            <?php echo htmlspecialchars($room['live_status']); ?>
                        </span>

                        <?php if ($rv_agg['total_r'] > 0): ?>
                        <div class="d-flex align-items-center gap-2 mt-3">
                            <div class="fw-bold fs-5"><?php echo number_format($rv_agg['avg_r'], 1); ?></div>
                            <div>
                                <?php
                                $full  = (int)round($rv_agg['avg_r']);
                                for ($s = 1; $s <= 5; $s++):
                                    $fill = $s <= $full ? '#f59e0b' : '#d1d5db';
                                ?>
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="<?php echo $fill; ?>" xmlns="http://www.w3.org/2000/svg" style="display:inline;">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <?php endfor; ?>
                            </div>
                            <span class="text-muted small">(<?php echo $rv_agg['total_r']; ?> review<?php echo $rv_agg['total_r'] > 1 ? 's' : ''; ?>)</span>
                        </div>
                        <?php else: ?>
                        <p class="text-muted small mt-3 mb-0">No reviews yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- RIGHT: Booking Form -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top: 80px;">

                        <h5 class="fw-bold mb-1">Book This Room</h5>
                        <p class="text-muted small mb-4">Fill in the details to proceed</p>

                        <?php if ($room['live_status'] !== 'Available'): ?>
                            <div class="alert alert-danger rounded-3 small">
                                This room is currently <strong><?php echo $room['live_status']; ?></strong> and cannot be booked.
                            </div>
                        <?php elseif (!isset($_SESSION['user_id'])): ?>
                            <div class="alert alert-warning rounded-3 small">
                                Please <a href="login.php" class="fw-semibold">login</a> to book this room.
                            </div>
                        <?php else: ?>
                            <form action="booking_manager.php" method="POST">
                                <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
                                <input type="hidden" name="price_per_night" value="<?php echo $room['price_per_night']; ?>">

                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Check-In Date</label>
                                    <input type="date" name="check_in" class="form-control rounded-3"
                                        min="<?php echo $tomorrow; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Check-Out Date</label>
                                    <input type="date" name="check_out" class="form-control rounded-3"
                                        min="<?php echo $tomorrow; ?>" required>
                                </div>

                                <!-- Price Preview -->
                                <div class="bg-light rounded-3 p-3 mb-4 small">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Price per night</span>
                                        <span
                                            class="fw-semibold">৳<?php echo number_format($room['price_per_night']); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Total (calculated on next page)</span>
                                        <span class="text-primary fw-bold">SSLCommerz</span>
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary rounded-pill fw-semibold py-2">
                                        Proceed to Booking
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>

                        <hr class="my-3">
                        <a href="rooms.php" class="btn btn-outline-secondary w-100 rounded-pill btn-sm">
                            ← Back to Rooms
                        </a>

                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- REVIEWS -->
    <section class="py-4 bg-light">
        <div class="container">
            <h5 class="fw-bold mb-0">Reviews</h5>
            <p class="text-muted small mb-4">What guests say about this room</p>
            <?php
            $target_type  = 'room';
            $target_id    = $room_id;
            $redirect_url = 'room_details.php?id=' . $room_id;
            include 'review_widget.php';
            ?>
        </div>
    </section>

    <!-- FOOTER -->
    <?php include 'footer.php'; ?>

    <script src="bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"
        onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js'">
    </script>

</body>

</html>
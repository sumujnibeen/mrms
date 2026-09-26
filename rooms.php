<?php
// rooms.php

include 'db.php';
include 'navbar.php';

$type_filter   = $_GET['type'] ?? '';
$allowed_types = ['Single', 'Double', 'Deluxe', 'Suite'];

/*
 LIVE STATUS LOGIC:
 Rule 1: Advance paid + check_in ভবিষ্যতে (চেক-ইন হয়নি)     → Booked
 Rule 2: Advance paid + check_in পেরিয়ে গেছে + full paid নয় → Available (receptionist handle করবে)
 Rule 3: Checked-In (receptionist চেক-ইন দিয়েছে)             → Booked
 Rule 4: Under Maintenance                                    → Under Maintenance
 Rule 5: বাকি সব                                              → Available
*/

$base_query = "
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
        END AS live_status,
        (SELECT ROUND(AVG(rv.rating), 1) FROM review rv WHERE rv.target_type = 'room' AND rv.target_id = r.room_id) AS avg_rating,
        (SELECT COUNT(*) FROM review rv WHERE rv.target_type = 'room' AND rv.target_id = r.room_id) AS review_count
    FROM room r
";

if (in_array($type_filter, $allowed_types)) {
    $stmt = $conn->prepare($base_query . " WHERE r.type = ? ORDER BY r.price_per_night ASC");
    $stmt->bind_param("s", $type_filter);
} else {
    $stmt = $conn->prepare($base_query . " ORDER BY r.price_per_night ASC");
}

$stmt->execute();
$rooms = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms — Meghdoot Resort</title>
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <link rel="stylesheet" href="bootstrap-5.3.8-dist/css/bootstrap.min.css"
        onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'">
    <?php include 'global_css.php'; ?>
</head>

<body class="bg-light">

    <!-- PAGE HEADER -->
    <div class="bg-primary text-white py-5 text-center">
        <div class="container">
            <h1 class="fw-bold">Our Rooms</h1>
            <p class="lead fw-light mb-0">Find the perfect room for your stay</p>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="bg-white border-bottom py-3">
        <div class="container d-flex flex-wrap gap-2 justify-content-center">
            <a href="rooms.php"
                class="btn btn-sm rounded-pill <?php echo $type_filter === '' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                All
            </a>
            <?php foreach ($allowed_types as $t): ?>
                <a href="rooms.php?type=<?php echo $t; ?>"
                    class="btn btn-sm rounded-pill <?php echo $type_filter === $t ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    <?php echo $t; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ROOMS GRID -->
    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <?php if ($rooms->num_rows > 0): ?>
                    <?php while ($r = $rooms->fetch_assoc()): ?>
                        <div class="col-sm-6 col-lg-4">
                            <div class="card h-100 border-0 shadow-sm rounded-4">

                                <!-- Room Image -->
                                <img src="<?php echo htmlspecialchars($r['image']); ?>"
                                    class="card-img-top rounded-top-4 img-cover"
                                    alt="<?php echo htmlspecialchars($r['type']); ?>"
                                    style="height: 200px;">

                                <!-- Status Badge -->
                                <div class="position-absolute mt-3 ms-3">
                                    <?php
                                    $badge = match($r['live_status']) {
                                        'Available'         => 'bg-success',
                                        'Booked'            => 'bg-danger',
                                        'Under Maintenance' => 'bg-warning text-dark',
                                        default             => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badge; ?> rounded-pill px-3">
                                        <?php echo htmlspecialchars($r['live_status']); ?>
                                    </span>
                                </div>

                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-primary-subtle text-primary">
                                            <?php echo htmlspecialchars($r['type']); ?>
                                        </span>
                                        <small class="text-muted">
                                            Floor <?php echo htmlspecialchars($r['floor']); ?>
                                            &bull;
                                            <?php echo htmlspecialchars($r['capacity']); ?> Guest(s)
                                        </small>
                                    </div>

                                    <h5 class="fw-bold mb-1">
                                        Room <?php echo htmlspecialchars($r['room_number']); ?>
                                    </h5>
                                    <p class="text-muted small mb-2">
                                        <?php echo htmlspecialchars(substr($r['description'], 0, 80)) . '...'; ?>
                                    </p>
                                    <?php if ($r['review_count'] > 0): ?>
                                    <div class="d-flex align-items-center gap-1 mb-2">
                                        <?php
                                        $full = (int)round($r['avg_rating']);
                                        for ($s = 1; $s <= 5; $s++):
                                            $fill = $s <= $full ? '#f59e0b' : '#d1d5db';
                                        ?>
                                        <svg width="13" height="13" viewBox="0 0 20 20" fill="<?php echo $fill; ?>" xmlns="http://www.w3.org/2000/svg" style="display:inline;flex-shrink:0;">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        <?php endfor; ?>
                                        <span class="text-muted small ms-1"><?php echo number_format($r['avg_rating'], 1); ?> (<?php echo $r['review_count']; ?>)</span>
                                    </div>
                                    <?php endif; ?>
                                    <h5 class="fw-bold text-primary mb-0">
                                        ৳<?php echo number_format($r['price_per_night']); ?>
                                        <small class="text-muted fw-light fs-6">/night</small>
                                    </h5>
                                </div>

                                <div class="card-footer bg-transparent border-0 pb-3">
                                    <?php if ($r['live_status'] === 'Available'): ?>
                                        <a href="room_details.php?id=<?php echo $r['room_id']; ?>"
                                            class="btn btn-primary w-100 rounded-pill">
                                            View & Book
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary w-100 rounded-pill" disabled>
                                            Not Available
                                        </button>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <p class="text-muted fs-5">No rooms found for this filter.</p>
                        <a href="rooms.php" class="btn btn-outline-primary rounded-pill px-4">View All Rooms</a>
                    </div>
                <?php endif; ?>
                <?php $stmt->close(); ?>
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

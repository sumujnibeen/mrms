<?php
include 'db.php';
include 'navbar.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meghdoot Resort</title>
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <link rel="stylesheet" href="bootstrap-5.3.8-dist/css/bootstrap.min.css"
        onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'">
    <?php include 'global_css.php'; ?>
</head>

<body class="bg-light">

    <!-- HERO SECTION -->
    <section class="position-relative text-white d-flex align-items-center justify-content-center"
        style="min-height: 90vh; background: url('assets/images/hero.jpg') center/cover no-repeat;">

        <div class="position-absolute top-0 start-0 w-100 h-100 hero-overlay"></div>

        <div class="position-relative text-center px-3">
            <img src="assets/images/logo_white.png" alt="Meghdoot Resort Logo" style="max-height: 150px;" class="mb-3">
            <h1 class="display-3 fw-bold">Meghdoot Resort</h1>
            <p class="lead mb-4 fw-light">Where every moment becomes a memory</p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="rooms.php" class="btn btn-primary btn-lg rounded-pill px-5">Book a Room</a>
                <a href="#about" class="btn btn-outline-light btn-lg rounded-pill px-5">Learn More</a>
            </div>
        </div>
    </section>

    <!-- ABOUT SECTION -->
    <section id="about" class="py-5 bg-white">
        <div class="container py-3">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <img src="assets/images/resort.jpg" alt="Resort View" class="img-fluid rounded-4 shadow img-cover">
                </div>
                <div class="col-lg-6">
                    <span class="badge bg-primary mb-2 px-3 py-2">About Us</span>
                    <h2 class="fw-bold mb-3">A Premium Hospitality Experience</h2>
                    <p class="text-muted">
                        Meghdoot Resort offers a serene escape with world-class amenities,
                        breathtaking natural surroundings, and warm hospitality. Whether you're
                        here for leisure or a special occasion, we ensure every stay is unforgettable.
                    </p>
                    <ul type="circle" class="mt-3">
                        <li class="mb-2"> <span class="text-primary">Online room booking</span> with real-time
                            availability</li>
                        <li class="mb-2"><span class="text-primary">In-room food & service</span> requests</li>
                        <li class="mb-2"> <span class="text-primary">Secure online</span> advance payment</li>
                        <li class="mb-2"> <span class="text-primary">Auto-generated</span> billing & invoices</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- ROOMS SECTION -->
    <section class="py-5 bg-light">
        <div class="container py-3">
            <div class="text-center mb-5">
                <span class="badge bg-primary mb-2 px-3 py-2">Our Rooms</span>
                <h2 class="fw-bold">Choose Your Stay</h2>
                <p class="text-muted">Comfort tailored for every guest</p>
            </div>

            <?php
            $stmt = $conn->prepare("SELECT * FROM room WHERE status = 'Available' ORDER BY price_per_night ASC LIMIT 4");
            $stmt->execute();
            $rooms = $stmt->get_result();
            ?>

            <div class="row g-4">
                <?php if ($rooms->num_rows > 0): ?>
                    <?php while ($r = $rooms->fetch_assoc()): ?>
                        <div class="col-sm-6 col-lg-3">
                            <div class="card h-100 shadow-sm border-0 rounded-4">
                                <img src="<?php echo htmlspecialchars($r['image']); ?>"
                                    class="card-img-top rounded-top-4 img-cover"
                                    alt="<?php echo htmlspecialchars($r['type']); ?>" style="height: 180px;">
                                <div class="card-body">
                                    <span class="badge bg-primary-subtle text-primary mb-1">
                                        <?php echo htmlspecialchars($r['type']); ?>
                                    </span>
                                    <h6 class="fw-bold">Room <?php echo htmlspecialchars($r['room_number']); ?></h6>
                                    <p class="text-muted small mb-2">
                                        <?php echo htmlspecialchars(substr($r['description'], 0, 60)) . '...'; ?>
                                    </p>
                                    <p class="fw-bold text-primary mb-0">
                                        ৳<?php echo number_format($r['price_per_night']); ?>/night
                                    </p>
                                </div>
                                <div class="card-footer bg-transparent border-0 pb-3">
                                    <a href="room_details.php?id=<?php echo $r['room_id']; ?>"
                                        class="btn btn-primary w-100 rounded-pill btn-sm">
                                        View & Book
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center text-muted">No rooms available right now.</p>
                <?php endif; ?>
                <?php $stmt->close(); ?>
            </div>

            <div class="text-center mt-4">
                <a href="rooms.php" class="btn btn-outline-primary rounded-pill px-5">View All Rooms</a>
            </div>
        </div>
    </section>

    <!-- STATS SECTION -->
    <section class="py-5 bg-primary text-white">
        <div class="container py-3">
            <?php
            $total_rooms    = $conn->query("SELECT COUNT(*) as cnt FROM room")->fetch_assoc()['cnt'];
            $avail_rooms    = $conn->query("SELECT COUNT(*) as cnt FROM room WHERE status='Available'")->fetch_assoc()['cnt'];
            $total_bookings = $conn->query("SELECT COUNT(*) as cnt FROM booking")->fetch_assoc()['cnt'];
            $total_guests   = $conn->query("SELECT COUNT(*) as cnt FROM user WHERE Role='guest'")->fetch_assoc()['cnt'];
            ?>
            <div class="row text-center g-4">
                <div class="col-6 col-md-3">
                    <h2 class="fw-bold display-5"><?php echo $total_rooms; ?></h2>
                    <p class="mb-0 fw-light">Total Rooms</p>
                </div>
                <div class="col-6 col-md-3">
                    <h2 class="fw-bold display-5"><?php echo $avail_rooms; ?></h2>
                    <p class="mb-0 fw-light">Available Now</p>
                </div>
                <div class="col-6 col-md-3">
                    <h2 class="fw-bold display-5"><?php echo $total_bookings; ?></h2>
                    <p class="mb-0 fw-light">Bookings Made</p>
                </div>
                <div class="col-6 col-md-3">
                    <h2 class="fw-bold display-5"><?php echo $total_guests; ?></h2>
                    <p class="mb-0 fw-light">Happy Guests</p>
                </div>
            </div>
        </div>
    </section>

    <!-- SERVICES SECTION -->
    <section class="py-5 bg-white">
        <div class="container py-3">
            <div class="text-center mb-5">
                <span class="badge bg-primary mb-2 px-3 py-2">Services</span>
                <h2 class="fw-bold">What We Offer</h2>
                <p class="text-muted">Everything you need, right at your fingertips</p>
            </div>
            <div class="row g-4 text-center">
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
                        <div class="fs-1 mb-3"></div>
                        <h5 class="fw-bold">Room Booking</h5>
                        <p class="text-muted small">Online booking with real-time availability and instant confirmation.
                        </p>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
                        <div class="fs-1 mb-3"></div>
                        <h5 class="fw-bold">In-Room Dining</h5>
                        <p class="text-muted small">Browse our menu and order food directly from your room.</p>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
                        <div class="fs-1 mb-3"></div>
                        <h5 class="fw-bold">Online Payment</h5>
                        <p class="text-muted small">Secure advance payment via SSLCommerz. Pay your way.</p>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
                        <div class="fs-1 mb-3"></div>
                        <h5 class="fw-bold">Auto Invoice</h5>
                        <p class="text-muted small">Itemized invoices generated automatically at check-out.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ANNOUNCEMENTS SECTION -->
    <section class="py-5 bg-light">
        <div class="container py-3">
            <div class="text-center mb-5">
                <span class="badge bg-primary mb-2 px-3 py-2">Latest</span>
                <h2 class="fw-bold">Announcements</h2>
            </div>

            <?php
            $today = date('Y-m-d H:i:s');
            $stmt  = $conn->prepare("SELECT * FROM announcement WHERE Start_date <= ? AND End_date >= ? ORDER BY Start_date DESC LIMIT 3");
            $stmt->bind_param("ss", $today, $today);
            $stmt->execute();
            $announcements = $stmt->get_result();
            ?>

            <div class="row g-4">
                <?php if ($announcements->num_rows > 0): ?>
                    <?php while ($a = $announcements->fetch_assoc()): ?>
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm rounded-4">
                                <div class="card-body">
                                    <span class="badge bg-primary mb-2"><?php echo htmlspecialchars($a['Type']); ?></span>
                                    <h6 class="fw-bold"><?php echo htmlspecialchars($a['Title']); ?></h6>
                                    <p class="text-muted small">
                                        <?php echo htmlspecialchars(substr($a['Message'], 0, 100)) . '...'; ?>
                                    </p>
                                </div>
                                <?php if ($a['Link']): ?>
                                    <div class="card-footer bg-transparent border-0 pb-3">
                                        <a href="<?php echo htmlspecialchars($a['Link']); ?>"
                                            class="btn btn-outline-primary btn-sm rounded-pill">
                                            Read More
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center text-muted">No active announcements.</p>
                <?php endif; ?>
                <?php $stmt->close(); ?>
            </div>

            <div class="text-center mt-4">
                <a href="announcement.php" class="btn btn-outline-primary rounded-pill px-5">All Announcements</a>

            </div>
        </div>
    </section>

    <!-- CTA SECTION
    <section class="py-5 bg-primary text-white text-center">
        <div class="container py-3">
            <h2 class="fw-bold mb-3">Ready for Your Stay?</h2>
            <p class="lead fw-light mb-4">Book your room today and enjoy a premium resort experience.</p>
            <a href="rooms.php" class="btn btn-light text-primary fw-bold rounded-pill px-5 btn-lg">
                Book Now
            </a>
        </div>
    </section> -->

    <!-- FOOTER -->
    <?php include 'footer.php'; ?>

    <script src="bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"
        onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js'">
    </script>

</body>

</html>
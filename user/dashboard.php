<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../db.php';
include '../auth.php';

if ($_SESSION['role'] !== 'guest') {
    header("Location: ../index.php"); exit();
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT Name, Email, Photo, Created_at FROM user WHERE User_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$success = $_SESSION['update_success'] ?? '';
unset($_SESSION['update_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Meghdoot Resort</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/css/bootstrap.min.css"
        onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'">
    <?php include '../global_css.php'; ?>
</head>
<body class="bg-light">
<?php include '../navbar.php'; ?>

<div class="bg-primary text-white py-4 text-center">
    <div class="container">
        <h2 class="fw-bold mb-0">Dashboard</h2>
        <p class="fw-light mb-0 small">Welcome back, <?php echo htmlspecialchars($user['Name']); ?></p>
    </div>
</div>

<section class="py-4">
<div class="container">

    <?php if ($success): ?>
    <div class="alert alert-success rounded-3 alert-dismissible fade show mb-4">
        <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <div class="col-lg-3">
            <?php include 'sidebar.php'; ?>
        </div>

        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h6 class="fw-bold text-muted text-uppercase small mb-3">Quick Actions</h6>
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <a href="my_stay.php?tab=bookings"
                            class="btn btn-outline-primary rounded-3 w-100 py-3 small fw-semibold">
                            My Bookings
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="my_stay.php?tab=service"
                            class="btn btn-outline-primary rounded-3 w-100 py-3 small fw-semibold">
                            Request Service
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="my_stay.php?tab=food"
                            class="btn btn-outline-primary rounded-3 w-100 py-3 small fw-semibold">
                            Order Food
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="my_stay.php?tab=history"
                            class="btn btn-outline-primary rounded-3 w-100 py-3 small fw-semibold">
                            Past Requests
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="my_reviews.php"
                            class="btn btn-outline-secondary rounded-3 w-100 py-3 small fw-semibold">
                            My Reviews
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="update_profile.php"
                            class="btn btn-outline-secondary rounded-3 w-100 py-3 small fw-semibold">
                            Update Profile
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="../rooms.php"
                            class="btn btn-primary rounded-3 w-100 py-3 small fw-semibold">
                            Book a Room
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="../food_menu.php"
                            class="btn btn-outline-primary rounded-3 w-100 py-3 small fw-semibold">
                            Food Menu
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
</section>

<?php include '../footer.php'; ?>
<script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"
    onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js'">
</script>
</body>
</html>

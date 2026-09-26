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
    <title>Update Profile — Meghdoot Resort</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/css/bootstrap.min.css"
        onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'">
    <?php include '../global_css.php'; ?>
</head>
<body class="bg-light">
<?php include '../navbar.php'; ?>

<div class="bg-primary text-white py-4 text-center">
    <div class="container">
        <h2 class="fw-bold mb-0">Update Profile</h2>
        <p class="fw-light mb-0 small">Change your name or password</p>
    </div>
</div>

<section class="py-4">
<div class="container">
<div class="row g-4">

    <div class="col-lg-3">
        <?php include 'sidebar.php'; ?>
    </div>

    <div class="col-lg-9">

        <?php
        $upd_error = $_SESSION['update_error'] ?? '';
        unset($_SESSION['update_error']);
        if ($upd_error): ?>
        <div class="alert alert-danger rounded-3 alert-dismissible fade show mb-4">
            <?php echo htmlspecialchars($upd_error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert alert-success rounded-3 alert-dismissible fade show mb-4">
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h5 class="fw-bold mb-4">Profile Information</h5>
            <form method="POST" action="update_action.php" autocomplete="off">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
                <input type="text"     name="fakeusernameremembered" style="display:none">
                <input type="password" name="fakepasswordremembered" style="display:none">

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Full Name</label>
                    <input type="text" name="full_name" class="form-control rounded-3"
                        value="<?php echo htmlspecialchars($user['Name'] ?? ''); ?>"
                        required autocomplete="new-name">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Email Address</label>
                    <input type="email" class="form-control rounded-3 bg-light"
                        value="<?php echo htmlspecialchars($user['Email'] ?? ''); ?>" disabled>
                    <div class="form-text">Email cannot be changed.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold small">Confirm with Current Password</label>
                    <input type="password" name="current_password" class="form-control rounded-3"
                        placeholder="Enter your current password" required autocomplete="new-password">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary rounded-pill fw-semibold px-4">
                        Save Changes
                    </button>
                    <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill px-4">
                        Cancel
                    </a>
                </div>
            </form>
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

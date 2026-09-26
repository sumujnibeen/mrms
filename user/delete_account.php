<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../db.php';
include '../auth.php';

if ($_SESSION['role'] !== 'guest') {
    header("Location: ../index.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$error   = '';

$stmt = $conn->prepare("SELECT Name, Email, Photo, Created_at, Password FROM user WHERE User_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (isset($_POST['confirm_delete'])) {
    if (!password_verify(trim($_POST['password']), $user['Password'])) {
        $error = "Incorrect password.";
    } else {
        $d = $conn->prepare("DELETE FROM review WHERE guest_id = ?");
        $d->bind_param("i", $user_id);
        $d->execute();
        $d->close();

        $d = $conn->prepare("DELETE FROM service_request WHERE guest_id = ?");
        $d->bind_param("i", $user_id);
        $d->execute();
        $d->close();

        $d = $conn->prepare("DELETE FROM booking WHERE guest_id = ?");
        $d->bind_param("i", $user_id);
        $d->execute();
        $d->close();

        $d = $conn->prepare("DELETE FROM user WHERE User_id = ?");
        $d->bind_param("i", $user_id);
        $d->execute();
        $d->close();

        session_unset();
        session_destroy();
        header("Location: ../index.php?deleted=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account — Meghdoot Resort</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/css/bootstrap.min.css"
        onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'">
    <?php include '../global_css.php'; ?>
</head>

<body class="bg-light">
    <?php include '../navbar.php'; ?>

    <div class="bg-primary text-white py-4 text-center">
        <div class="container">
            <h2 class="fw-bold mb-0">Delete Account</h2>
            <p class="fw-light mb-0 small">Permanently remove your account and all data</p>
        </div>
    </div>

    <section class="py-4">
        <div class="container">
            <div class="row g-4">

                <div class="col-lg-3">
                    <?php include 'sidebar.php'; ?>
                </div>

                <div class="col-lg-9">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h5 class="fw-bold text-danger mb-3">Delete My Account</h5>

                        <div class=" rounded-3 small mb-4">
                            <strong>Warning:</strong> This action is permanent and cannot be undone.
                            All your bookings, reviews, and service requests will be deleted.
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-warning rounded-3 small mb-3">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label fw-semibold small">
                                    Enter your password to confirm deletion
                                </label>
                                <input type="password" name="password" class="form-control rounded-3"
                                    placeholder="Your current password" required>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" name="confirm_delete"
                                    class="btn btn-danger rounded-pill fw-semibold px-4">
                                    Delete My Account Permanently
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
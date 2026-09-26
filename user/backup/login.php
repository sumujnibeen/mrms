<?php
// login.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Already logged in → redirect
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM user WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['Password'])) {
            $_SESSION['user_id'] = $user['User_id'];
            $_SESSION['name']    = $user['Name'];
            $_SESSION['email']   = $user['Email'];
            $_SESSION['role']    = $user['Role'];
            $_SESSION['photo']   = $user['Photo'];

            // Redirect by role
            if ($user['Role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } elseif ($user['Role'] === 'receptionist') {
                header("Location: receptionist/manage_bookings.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "No account found with this email.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Meghdoot Resort</title>
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <link rel="stylesheet" href="bootstrap-5.3.8-dist/css/bootstrap.min.css"
        onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'">
    <?php include 'global_css.php'; ?>
</head>

<body class="bg-light">

    <?php include 'navbar.php'; ?>

    <!-- LOGIN SECTION -->
    <section class="d-flex align-items-center justify-content-center" style="min-height: 88vh;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-sm-10 col-md-6 col-lg-4">

                    <div class="card border-0 shadow-sm rounded-4 p-4">

                        <!-- Logo & Title -->
                        <div class="text-center mb-4">
                            <img src="assets/images/favicon.png" alt="Meghdoot Resort" style="height: 56px;"
                                class="mb-3">
                            <h4 class="fw-bold">Welcome Back</h4>
                            <p class="text-muted small">Sign in to your account</p>
                        </div>

                        <!-- Error Alert -->
                        <?php if ($error): ?>
                            <div class="alert alert-danger rounded-3 small py-2"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <!-- Login Form -->
                        <form method="POST" action="login.php">

                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold small">Email Address</label>
                                <input type="email" name="email" id="email" class="form-control rounded-3"
                                    placeholder="you@example.com"
                                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label fw-semibold small">Password</label>
                                <input type="password" name="password" id="password" class="form-control rounded-3"
                                    placeholder="Enter your password" required>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary rounded-pill fw-semibold py-2">
                                    Login
                                </button>
                            </div>

                        </form>

                        <hr class="my-4">

                        <p class="text-center small mb-0">
                            Don't have an account?
                            <a href="register.php" class="text-primary fw-semibold">Register</a>
                        </p>

                    </div>

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
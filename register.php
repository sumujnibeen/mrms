<?php
// register.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Already logged in → redirect
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm_password']);

    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Check email already exists
        $check = $conn->prepare("SELECT User_id FROM user WHERE Email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "An account with this email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $photo  = null;

            // Handle photo upload
            if (!empty($_FILES['photo']['name'])) {
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                $ext     = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $filename = time() . '_' . basename($_FILES['photo']['name']);
                    $target   = 'assets/images/users/' . $filename;
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
                        $photo = $target;
                    }
                }
            }

            $stmt = $conn->prepare("INSERT INTO user (Name, Email, Password, Role, Photo) VALUES (?, ?, ?, 'guest', ?)");
            $stmt->bind_param("ssss", $name, $email, $hashed, $photo);

            if ($stmt->execute()) {
                $success = "Account created successfully! You can now login.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Meghdoot Resort</title>
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <link rel="stylesheet" href="bootstrap-5.3.8-dist/css/bootstrap.min.css"
        onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'">
    <?php include 'global_css.php'; ?>
</head>

<body class="bg-light">

    <?php include 'navbar.php'; ?>

    <!-- REGISTER SECTION -->
    <section class="d-flex align-items-center justify-content-center py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-sm-10 col-md-7 col-lg-5">

                    <div class="card border-0 shadow-sm rounded-4 p-4">

                        <!-- Logo & Title -->
                        <div class="text-center mb-4">
                            <img src="assets/images/favicon.png" alt="Meghdoot Resort" style="height: 56px;"
                                class="mb-3">
                            <h4 class="fw-bold">Create Account</h4>
                            <p class="text-muted small">Register as a guest to book rooms</p>
                        </div>

                        <!-- Error / Success Alert -->
                        <?php if ($error): ?>
                            <div class="alert alert-danger rounded-3 small py-2"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success rounded-3 small py-2">
                                <?php echo $success; ?>
                                <a href="login.php" class="fw-semibold">Login now →</a>
                            </div>
                        <?php endif; ?>

                        <!-- Register Form -->
                        <form method="POST" action="register.php" enctype="multipart/form-data">

                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold small">Full Name</label>
                                <input type="text" name="name" id="name" class="form-control rounded-3"
                                    placeholder="Your full name"
                                    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold small">Email Address</label>
                                <input type="email" name="email" id="email" class="form-control rounded-3"
                                    placeholder="you@example.com"
                                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label fw-semibold small">Password</label>
                                <input type="password" name="password" id="password" class="form-control rounded-3"
                                    placeholder="Minimum 6 characters" required>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label fw-semibold small">Confirm
                                    Password</label>
                                <input type="password" name="confirm_password" id="confirm_password"
                                    class="form-control rounded-3" placeholder="Re-enter your password" required>
                            </div>

                            <div class="mb-3">
                                <label for="photo" class="form-label fw-semibold small">Profile Photo <span
                                        class="text-muted fw-normal">(optional)</span></label>
                                <input type="file" name="photo" id="photo" class="form-control rounded-3"
                                    accept="image/jpg, image/jpeg, image/png, image/webp">
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary rounded-pill fw-semibold py-2">
                                    Create Account
                                </button>
                            </div>

                        </form>

                        <hr class="my-4">

                        <p class="text-center small mb-0">
                            Already have an account?
                            <a href="login.php" class="text-primary fw-semibold">Login</a>
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
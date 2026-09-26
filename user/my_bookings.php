<?php
// my_bookings.php
// Standalone page — reuses the same content partial as my_stay.php tab 1.

include '../db.php';
include '../auth.php';
include '../navbar.php';

if ($_SESSION['role'] !== 'guest') {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings — Meghdoot Resort</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/css/bootstrap.min.css"
        onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'">
    <?php include '../global_css.php'; ?>
</head>

<body class="bg-light">

    <!-- PAGE HEADER -->
    <div class="bg-primary text-white py-5 text-center">
        <div class="container">
            <h1 class="fw-bold">My Bookings</h1>
            <p class="lead fw-light mb-0">Track and manage all your reservations at Meghdoot Resort</p>
        </div>
    </div>

    <section class="py-4">
        <div class="container">
            <?php include 'my_bookings_content.php'; ?>
        </div>
    </section>

    <?php include '../footer.php'; ?>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"
        onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js'">
    </script>

</body>
</html>

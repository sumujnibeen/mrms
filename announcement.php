<?php
// announcement.php

include 'db.php';
include 'navbar.php';

$today = date('Y-m-d H:i:s');

$stmt = $conn->prepare("
    SELECT * FROM announcement
    WHERE Start_date <= ? AND End_date >= ?
    ORDER BY Start_date DESC
");
$stmt->bind_param("ss", $today, $today);
$stmt->execute();
$announcements = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements — Meghdoot Resort</title>
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <link rel="stylesheet" href="bootstrap-5.3.8-dist/css/bootstrap.min.css"
        onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'">
    <?php include 'global_css.php'; ?>
</head>

<body class="bg-light">

    <!-- PAGE HEADER -->
    <div class="bg-primary text-white py-5 text-center">
        <div class="container">
            <h1 class="fw-bold">Announcements</h1>
            <p class="lead fw-light mb-0">Stay updated with the latest news from Meghdoot Resort</p>
        </div>
    </div>

    <!-- ANNOUNCEMENTS -->
    <section class="py-5">
        <div class="container">
            <?php if ($announcements->num_rows === 0): ?>
                <div class="text-center py-5">
                    <div class="fs-1 mb-3"></div>
                    <h5 class="fw-bold">No active announcements</h5>
                    <p class="text-muted">Check back later for updates.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php while ($a = $announcements->fetch_assoc()):
                        $type_badge = match(strtolower($a['Type'])) {
                            'offer'   => 'bg-success',
                            'alert'   => 'bg-danger',
                            'general' => 'bg-primary',
                            default   => 'bg-secondary'
                        };
                    ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 border-0 shadow-sm rounded-4">
                                <div class="card-body p-4">

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="badge <?php echo $type_badge; ?> rounded-pill px-3">
                                            <?php echo htmlspecialchars(ucfirst($a['Type'])); ?>
                                        </span>
                                        <small class="text-muted">
                                            <?php echo date('d M Y', strtotime($a['Start_date'])); ?>
                                        </small>
                                    </div>

                                    <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($a['Title']); ?></h5>
                                    <p class="text-muted small mb-3"><?php echo htmlspecialchars($a['Message']); ?></p>

                                    <small class="text-muted">
                                        Valid until: <?php echo date('d M Y', strtotime($a['End_date'])); ?>
                                    </small>

                                </div>

                                <?php if ($a['Link'] || $a['PDF_File']): ?>
                                    <div class="card-footer bg-transparent border-0 pb-3 px-4 d-flex gap-2">
                                        <?php if ($a['Link']): ?>
                                            <a href="<?php echo htmlspecialchars($a['Link']); ?>"
                                                class="btn btn-primary btn-sm rounded-pill">
                                                Learn More
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($a['PDF_File']): ?>
                                            <a href="<?php echo htmlspecialchars($a['PDF_File']); ?>"
                                                target="_blank"
                                                class="btn btn-outline-secondary btn-sm rounded-pill">
                                                Download PDF
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- FOOTER -->
    <?php include 'footer.php'; ?>

    <script src="bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"
        onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js'">
    </script>

</body>

</html>

<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../db.php';
include '../auth.php';

if ($_SESSION['role'] !== 'guest') {
    header("Location: ../index.php"); exit();
}

$user_id = (int)$_SESSION['user_id'];

// Fetch user for sidebar
$stmt = $conn->prepare("SELECT Name, Email, Photo, Created_at FROM user WHERE User_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Delete review
if (isset($_POST['delete_review'])) {
    $review_id = (int)$_POST['review_id'];
    $del = $conn->prepare("DELETE FROM review WHERE review_id = ? AND guest_id = ?");
    $del->bind_param("ii", $review_id, $user_id);
    $del->execute();
    $del->close();
}

// Fetch all reviews
$stmt = $conn->prepare("
    SELECT r.review_id, r.target_type, r.target_id, r.rating, r.comment, r.created_at,
           CASE
               WHEN r.target_type = 'room' THEN CONCAT('Room ', rm.room_number, ' (', rm.type, ')')
               WHEN r.target_type = 'food' THEN fm.name
               ELSE r.target_type
           END AS target_label
    FROM review r
    LEFT JOIN room rm ON r.target_type = 'room' AND r.target_id = rm.room_id
    LEFT JOIN food_menu fm ON r.target_type = 'food' AND r.target_id = fm.menu_id
    WHERE r.guest_id = ?
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews — Meghdoot Resort</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/css/bootstrap.min.css"
        onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'">
    <?php include '../global_css.php'; ?>
</head>
<body class="bg-light">
<?php include '../navbar.php'; ?>

<div class="bg-primary text-white py-4 text-center">
    <div class="container">
        <h2 class="fw-bold mb-0">My Reviews</h2>
        <p class="fw-light mb-0 small">Reviews you have submitted</p>
    </div>
</div>

<section class="py-4">
<div class="container">
<div class="row g-4">

    <div class="col-lg-3">
        <?php include 'sidebar.php'; ?>
    </div>

    <div class="col-lg-9">
        <?php if (empty($reviews)): ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted">
                <h5 class="fw-bold">No reviews yet</h5>
                <p class="mb-0">You have not submitted any reviews. Stay in a room or order food to leave a review.</p>
            </div>
        <?php else: ?>
        <div class="row g-3">
            <?php foreach ($reviews as $r):
                $stars = '';
                for ($i = 1; $i <= 5; $i++) {
                    $fill = $i <= $r['rating'] ? '#f59e0b' : '#d1d5db';
                    $stars .= '<svg width="14" height="14" viewBox="0 0 20 20" fill="' . $fill . '" xmlns="http://www.w3.org/2000/svg" style="display:inline;">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>';
                }
                $type_label = ucfirst($r['target_type']);
            ?>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="badge bg-primary-subtle text-primary rounded-pill small">
                                <?php echo $type_label; ?>
                            </span>
                            <div class="fw-semibold mt-1">
                                <?php echo htmlspecialchars($r['target_label'] ?? $type_label); ?>
                            </div>
                        </div>
                        <div class="text-end">
                            <div><?php echo $stars; ?></div>
                            <div class="text-muted small mt-1"><?php echo number_format($r['rating'], 1); ?>/5</div>
                        </div>
                    </div>
                    <?php if ($r['comment']): ?>
                    <p class="text-muted small mb-3">
                        <?php echo htmlspecialchars($r['comment']); ?>
                    </p>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between align-items-center mt-auto">
                        <small class="text-muted">
                            <?php echo date('d M Y', strtotime($r['created_at'])); ?>
                        </small>
                        <form method="POST" onsubmit="return confirm('Delete this review?');">
                            <input type="hidden" name="review_id" value="<?php echo $r['review_id']; ?>">
                            <button type="submit" name="delete_review"
                                class="btn btn-outline-danger btn-sm rounded-pill px-3">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
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

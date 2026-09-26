<?php
include('db.php');
session_start();

// if not log in then go to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);

// ====== Delete Review ======
if (isset($_POST['delete_review'])) {
    $review_id = intval($_POST['review_id']);
    $stmt = $conn->prepare("DELETE FROM review WHERE Review_id = ? AND User_id = ?");
    $stmt->bind_param("ii", $review_id, $user_id);
    $stmt->execute();
}

// ====== Add New Review ======
if (isset($_POST['add_review'])) {
    $target_type = trim($_POST['target_type']);
    $target_id = isset($_POST['target_id']) ? intval($_POST['target_id']) : 0; // <-- fixed undefined key
    $rating = floatval($_POST['rating']); // <-- float rating
    $comment = trim($_POST['comment']);

    if (!empty($target_type) && !empty($rating) && !empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO review (User_id, Target_type, Target_id, Rating, Comment, Created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("isdss", $user_id, $target_type, $target_id, $rating, $comment); // 'd' for float
        $stmt->execute();
    }
}

// ====== Fetch All Reviews ======
$stmt = $conn->prepare("SELECT * FROM review WHERE User_id = ? ORDER BY Created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Reviews | Vromon Sathi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .review-card { border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        .rating { font-weight: 600; color: #198754; }
        .no-review { text-align: center; margin-top: 60px; }
        .form-section { margin-top: 50px; background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        label { font-weight: 600; }
        .cancel-btn { background: #dc3545; color: white; border: none; padding: 5px 12px; border-radius: 5px; }
        .cancel-btn:hover { background: #c82333; }
    </style>
</head>
<body>

<div class="container py-5">
    <h3 class="text-center text-success mb-4 fw-bold">My Reviews</h3>

    <?php if ($result->num_rows > 0): ?>
        <div class="row">
            <?php while ($r = $result->fetch_assoc()): ?>
                <div class="col-md-4 mb-4">
                    <div class="card review-card p-3">
                        <h5 class="fw-bold text-dark"><?php echo htmlspecialchars($r['Target_type']); ?></h5>
                        <p class="mb-2 text-muted"><?php echo nl2br(htmlspecialchars($r['Comment'])); ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="rating">⭐ <?php echo $r['Rating']; ?>/5</span>
                            <small class="text-muted"><?php echo date("d M Y", strtotime($r['Created_at'])); ?></small>
                        </div>
                        <form method="POST" class="mt-3 text-center">
                            <input type="hidden" name="review_id" value="<?php echo $r['Review_id']; ?>">
                            <button type="submit" name="delete_review" class="cancel-btn">Delete Review</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="no-review">
            <p class="text-success bg-light p-3 rounded"> You haven’t posted any reviews yet.</p>
        </div>
    <?php endif; ?>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

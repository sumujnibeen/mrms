<?php
// food_menu.php

include 'db.php';
include 'navbar.php';

$category_filter = $_GET['category'] ?? '';

// Get all categories
$cat_result = $conn->query("SELECT DISTINCT category FROM food_menu WHERE available = 1 ORDER BY category ASC");
$categories = [];
while ($c = $cat_result->fetch_assoc()) {
    $categories[] = $c['category'];
}

// Fetch menu items
if (!empty($category_filter)) {
    $stmt = $conn->prepare("
        SELECT fm.*,
            (SELECT ROUND(AVG(rv.rating),1) FROM review rv WHERE rv.target_type='food' AND rv.target_id=fm.menu_id) AS avg_rating,
            (SELECT COUNT(*) FROM review rv WHERE rv.target_type='food' AND rv.target_id=fm.menu_id) AS review_count
        FROM food_menu fm WHERE fm.available = 1 AND fm.category = ? ORDER BY fm.category, fm.name ASC
    ");
    $stmt->bind_param("s", $category_filter);
} else {
    $stmt = $conn->prepare("
        SELECT fm.*,
            (SELECT ROUND(AVG(rv.rating),1) FROM review rv WHERE rv.target_type='food' AND rv.target_id=fm.menu_id) AS avg_rating,
            (SELECT COUNT(*) FROM review rv WHERE rv.target_type='food' AND rv.target_id=fm.menu_id) AS review_count
        FROM food_menu fm WHERE fm.available = 1 ORDER BY fm.category, fm.name ASC
    ");
}
$stmt->execute();
$items = $stmt->get_result();
$stmt->close();

// Check once whether the current guest can review food
$food_review_eligible = false;
if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'guest') {
    $fe = $conn->prepare("SELECT COUNT(*) AS c FROM booking WHERE guest_id=? AND status IN ('Checked-In','Checked-Out')");
    $fe->bind_param("i", $_SESSION['user_id']);
    $fe->execute();
    $food_review_eligible = (int)$fe->get_result()->fetch_assoc()['c'] > 0;
    $fe->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Menu — Meghdoot Resort</title>
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <link rel="stylesheet" href="bootstrap-5.3.8-dist/css/bootstrap.min.css"
        onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'">
    <?php include 'global_css.php'; ?>
</head>

<body class="bg-light">

    <!-- PAGE HEADER -->
    <div class="bg-primary text-white py-5 text-center">
        <div class="container">
            <h1 class="fw-bold">Food Menu</h1>
            <p class="lead fw-light mb-0">Order delicious meals right to your room</p>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="bg-white border-bottom py-3">
        <div class="container d-flex flex-wrap gap-2 justify-content-center">
            <a href="food_menu.php"
                class="btn btn-sm rounded-pill <?php echo $category_filter === '' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                All
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="food_menu.php?category=<?php echo urlencode($cat); ?>"
                    class="btn btn-sm rounded-pill <?php echo $category_filter === $cat ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    <?php echo htmlspecialchars($cat); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- MENU ITEMS -->
    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <?php if ($items->num_rows > 0): ?>
                    <?php while ($item = $items->fetch_assoc()): ?>
                        <div class="col-sm-6 col-lg-4">
                            <div class="card h-100 border-0 shadow-sm rounded-4">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>"
                                    class="card-img-top rounded-top-4 img-cover"
                                    alt="<?php echo htmlspecialchars($item['name']); ?>"
                                    style="height: 200px;">
                                <div class="card-body">
                                    <span class="badge bg-primary-subtle text-primary mb-1">
                                        <?php echo htmlspecialchars($item['category']); ?>
                                    </span>
                                    <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($item['name']); ?></h5>
                                    <p class="text-muted small mb-2">
                                        <?php echo htmlspecialchars($item['description']); ?>
                                    </p>
                                    <?php if ($item['review_count'] > 0): ?>
                                    <div class="d-flex align-items-center gap-1 mb-2">
                                        <?php
                                        $full = (int)round($item['avg_rating']);
                                        for ($s = 1; $s <= 5; $s++):
                                            $fill = $s <= $full ? '#f59e0b' : '#d1d5db';
                                        ?>
                                        <svg width="13" height="13" viewBox="0 0 20 20" fill="<?php echo $fill; ?>" xmlns="http://www.w3.org/2000/svg" style="display:inline;flex-shrink:0;">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        <?php endfor; ?>
                                        <span class="text-muted small ms-1"><?php echo number_format($item['avg_rating'], 1); ?> (<?php echo $item['review_count']; ?>)</span>
                                    </div>
                                    <?php endif; ?>
                                    <h5 class="fw-bold text-primary mb-0">
                                        ৳<?php echo number_format($item['price']); ?>
                                    </h5>
                                </div>
                                <div class="card-footer bg-transparent border-0 pb-3">
                                    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'guest'): ?>
                                    <a href="service_request.php?type=food&menu_id=<?php echo $item['menu_id']; ?>"
                                        class="btn btn-primary w-100 rounded-pill btn-sm mb-2">
                                        Order Now
                                    </a>
                                    <?php if ($food_review_eligible): ?>
                                    <button class="btn btn-outline-secondary w-100 rounded-pill btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#reviewModal_<?php echo $item['menu_id']; ?>">
                                        Leave a Review
                                    </button>
                                    <?php endif; ?>
                                <?php elseif (!isset($_SESSION['user_id'])): ?>
                                    <a href="login.php" class="btn btn-outline-primary w-100 rounded-pill btn-sm">
                                        Login to Order
                                    </a>
                                <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <p class="text-muted fs-5">No items found.</p>
                        <a href="food_menu.php" class="btn btn-outline-primary rounded-pill px-4">View All</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- FOOD REVIEW MODALS -->
    <?php
    // Re-fetch items for modals
    if (!empty($category_filter)) {
        $modal_stmt = $conn->prepare("SELECT menu_id, name FROM food_menu WHERE available=1 AND category=?");
        $modal_stmt->bind_param("s", $category_filter);
    } else {
        $modal_stmt = $conn->prepare("SELECT menu_id, name FROM food_menu WHERE available=1");
    }
    $modal_stmt->execute();
    $modal_items = $modal_stmt->get_result();
    $modal_stmt->close();

    while ($mi = $modal_items->fetch_assoc()):
    ?>
    <div class="modal fade" id="reviewModal_<?php echo $mi['menu_id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Review: <?php echo htmlspecialchars($mi['name']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="review_submit.php" method="POST">
                    <input type="hidden" name="target_type" value="food">
                    <input type="hidden" name="target_id"   value="<?php echo $mi['menu_id']; ?>">
                    <input type="hidden" name="redirect"    value="food_menu.php">
                    <div class="modal-body">
                        <label class="form-label small fw-semibold">Your Rating</label>
                        <div class="d-flex gap-2 star-selector mb-3" id="fstar_<?php echo $mi['menu_id']; ?>">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label class="star-label" style="cursor:pointer;">
                                <input type="radio" name="rating" value="<?php echo $i; ?>" class="d-none star-radio" required>
                                <svg width="28" height="28" viewBox="0 0 20 20" class="star-svg" fill="#d1d5db" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </label>
                            <?php endfor; ?>
                        </div>
                        <label class="form-label small fw-semibold">Comment <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea name="comment" class="form-control rounded-3" rows="3" placeholder="How was it?"></textarea>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-pill btn-sm px-4">Submit Review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endwhile; ?>

    <!-- FOOTER -->
    <?php include 'footer.php'; ?>

    <script src="bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"
        onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js'">
    </script>

<script>
(function() {
    document.querySelectorAll('.star-selector').forEach(function(selector) {
        const labels = selector.querySelectorAll('.star-label');
        const svgs   = selector.querySelectorAll('.star-svg');
        function paint(n) { svgs.forEach(function(svg, i) { svg.setAttribute('fill', i < n ? '#f59e0b' : '#d1d5db'); }); }
        labels.forEach(function(label, idx) {
            label.addEventListener('mouseenter', function() { paint(idx + 1); });
            label.addEventListener('mouseleave', function() { const c = selector.querySelector('.star-radio:checked'); paint(c ? parseInt(c.value) : 0); });
            label.addEventListener('click', function() { label.querySelector('.star-radio').checked = true; paint(idx + 1); });
        });
    });
})();
</script>
</body>

</html>

<?php
// review_widget.php
// Reusable partial — include wherever you need ratings + review form
//
// Required variables before include:
//   $conn         — DB connection
//   $target_type  — 'room' or 'food'
//   $target_id    — room_id or menu_id
//   $redirect_url — where to send after submit
//
// Optional:
//   $show_form    — bool, default true (set false to show stats only)

$show_form   = $show_form ?? true;
$guest_id    = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$guest_role  = $_SESSION['role'] ?? '';

// Guest must be currently Checked-In or have a Checked-Out booking to submit a review
$guest_eligible = false;
if ($guest_id && $guest_role === 'guest') {
    if ($target_type === 'room') {
        $el = $conn->prepare("SELECT COUNT(*) AS c FROM booking WHERE guest_id=? AND room_id=? AND status IN ('Checked-In','Checked-Out')");
        $el->bind_param("ii", $guest_id, $target_id);
    } else {
        $el = $conn->prepare("SELECT COUNT(*) AS c FROM booking WHERE guest_id=? AND status IN ('Checked-In','Checked-Out')");
        $el->bind_param("i", $guest_id);
    }
    $el->execute();
    $guest_eligible = (int)$el->get_result()->fetch_assoc()['c'] > 0;
    $el->close();
}
// Fetch aggregate stats
$stats_stmt = $conn->prepare("
    SELECT
        COUNT(*)            AS total,
        ROUND(AVG(rating), 1) AS avg_rating,
        SUM(rating = 5)     AS five,
        SUM(rating = 4)     AS four,
        SUM(rating = 3)     AS three,
        SUM(rating = 2)     AS two,
        SUM(rating = 1)     AS one
    FROM review
    WHERE target_type = ? AND target_id = ?
");
$stats_stmt->bind_param("si", $target_type, $target_id);
$stats_stmt->execute();
$rv_stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

// Fetch recent reviews (latest 5)
$rv_stmt = $conn->prepare("
    SELECT r.rating, r.comment, r.created_at, u.Name AS guest_name
    FROM review r
    JOIN user u ON r.guest_id = u.User_id
    WHERE r.target_type = ? AND r.target_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
");
$rv_stmt->bind_param("si", $target_type, $target_id);
$rv_stmt->execute();
$reviews = $rv_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$rv_stmt->close();

// Check if current guest already reviewed this
$already_reviewed = false;
$existing_review  = null;
if ($guest_id) {
    $ex_stmt = $conn->prepare("
        SELECT rating, comment FROM review
        WHERE guest_id = ? AND target_type = ? AND target_id = ?
    ");
    $ex_stmt->bind_param("isi", $guest_id, $target_type, $target_id);
    $ex_stmt->execute();
    $existing_review = $ex_stmt->get_result()->fetch_assoc();
    $already_reviewed = (bool)$existing_review;
    $ex_stmt->close();
}

// Helper: render stars as filled/empty blocks (no emojis, pure CSS)
function render_stars(float $rating, string $size = 'md'): string {
    $px = $size === 'sm' ? '12px' : ($size === 'lg' ? '20px' : '16px');
    $html = '<span class="d-inline-flex gap-1" title="' . number_format($rating, 1) . ' out of 5">';
    for ($i = 1; $i <= 5; $i++) {
        $fill = $i <= round($rating) ? '#f59e0b' : '#d1d5db';
        $html .= '<svg width="' . $px . '" height="' . $px . '" viewBox="0 0 20 20" fill="' . $fill . '" xmlns="http://www.w3.org/2000/svg">'
               . '<path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>'
               . '</svg>';
    }
    $html .= '</span>';
    return $html;
}

$rv_msg_success = $_SESSION['review_success'] ?? '';
$rv_msg_error   = $_SESSION['review_error']   ?? '';
unset($_SESSION['review_success'], $_SESSION['review_error']);
?>

<div class="review-widget mt-4">

    <?php if ($rv_msg_success): ?>
        <div class="alert alert-success rounded-3 small alert-dismissible fade show">
            <?php echo htmlspecialchars($rv_msg_success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($rv_msg_error): ?>
        <div class="alert alert-danger rounded-3 small alert-dismissible fade show">
            <?php echo htmlspecialchars($rv_msg_error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- RATING SUMMARY -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <div class="row align-items-center g-4">

            <!-- Left: score -->
            <div class="col-md-3 text-center">
                <?php if ($rv_stats['total'] > 0): ?>
                    <div class="fw-bold" style="font-size:3rem;line-height:1;">
                        <?php echo number_format($rv_stats['avg_rating'], 1); ?>
                    </div>
                    <div class="my-1"><?php echo render_stars((float)$rv_stats['avg_rating'], 'lg'); ?></div>
                    <div class="text-muted small"><?php echo $rv_stats['total']; ?> review(s)</div>
                <?php else: ?>
                    <div class="fw-bold text-muted" style="font-size:2rem;">-</div>
                    <div class="text-muted small mt-1">No reviews yet</div>
                <?php endif; ?>
            </div>

            <!-- Right: breakdown bars -->
            <div class="col-md-9">
                <?php
                $bar_keys = [5 => 'five', 4 => 'four', 3 => 'three', 2 => 'two', 1 => 'one'];
                foreach ($bar_keys as $num => $key):
                    $count = (int)($rv_stats[$key] ?? 0);
                    $pct   = $rv_stats['total'] > 0 ? round($count / $rv_stats['total'] * 100) : 0;
                ?>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="text-muted small" style="width:10px;"><?php echo $num; ?></div>
                    <div class="flex-grow-1 bg-light rounded-pill" style="height:8px;">
                        <div class="rounded-pill bg-warning" style="height:8px;width:<?php echo $pct; ?>%;"></div>
                    </div>
                    <div class="text-muted small" style="width:24px;"><?php echo $count; ?></div>
                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>

    <!-- REVIEW FORM -->
    <?php if ($show_form && $guest_role === 'guest' && $guest_eligible): ?>
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <h6 class="fw-bold mb-3">
            <?php echo $already_reviewed ? 'Update Your Review' : 'Leave a Review'; ?>
        </h6>
        <form action="review_submit.php" method="POST">
            <input type="hidden" name="target_type" value="<?php echo htmlspecialchars($target_type); ?>">
            <input type="hidden" name="target_id"   value="<?php echo $target_id; ?>">
            <input type="hidden" name="redirect"    value="<?php echo htmlspecialchars($redirect_url); ?>">

            <!-- Star selector -->
            <div class="mb-3">
                <label class="form-label small fw-semibold">Your Rating</label>
                <div class="d-flex gap-2 star-selector" id="starSelector_<?php echo $target_id; ?>">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <label class="star-label" style="cursor:pointer;" title="<?php echo $i; ?> star">
                        <input type="radio" name="rating" value="<?php echo $i; ?>"
                            class="d-none star-radio"
                            <?php echo (isset($existing_review['rating']) && $existing_review['rating'] == $i) ? 'checked' : ''; ?>>
                        <svg width="28" height="28" viewBox="0 0 20 20" class="star-svg"
                            data-val="<?php echo $i; ?>"
                            fill="<?php echo (isset($existing_review['rating']) && $existing_review['rating'] >= $i) ? '#f59e0b' : '#d1d5db'; ?>"
                            xmlns="http://www.w3.org/2000/svg">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    </label>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Comment <span class="text-muted fw-normal">(optional)</span></label>
                <textarea name="comment" class="form-control rounded-3" rows="3"
                    placeholder="Share your experience..."><?php echo htmlspecialchars($existing_review['comment'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary rounded-pill btn-sm px-4">
                <?php echo $already_reviewed ? 'Update Review' : 'Submit Review'; ?>
            </button>
        </form>
    </div>
    <?php elseif ($show_form && $guest_role === 'guest' && !$guest_eligible): ?>
    <div class="alert alert-light border rounded-3 small">
        You can leave a review after check-in.
    </div>
    <?php elseif ($show_form && !$guest_id): ?>
    <div class="alert alert-light border rounded-3 small">
        <a href="login.php" class="fw-semibold">Log in</a> to leave a review.
    </div>
    <?php endif; ?>

    <!-- RECENT REVIEWS -->
    <?php if (!empty($reviews)): ?>
    <h6 class="fw-semibold text-muted text-uppercase small mb-3">Guest Reviews</h6>
    <div class="d-flex flex-column gap-3">
        <?php foreach ($reviews as $rv): ?>
        <div class="card border-0 bg-white shadow-sm rounded-4 px-4 py-3">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <div>
                    <span class="fw-semibold small"><?php echo htmlspecialchars($rv['guest_name']); ?></span>
                    <span class="text-muted small ms-2"><?php echo date('d M Y', strtotime($rv['created_at'])); ?></span>
                </div>
                <?php echo render_stars((float)$rv['rating'], 'sm'); ?>
            </div>
            <?php if ($rv['comment']): ?>
            <p class="text-muted small mb-0"><?php echo htmlspecialchars($rv['comment']); ?></p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<style>
.star-label:hover .star-svg, .star-label:hover ~ .star-label .star-svg { fill: #d1d5db; }
</style>
<script>
(function() {
    document.querySelectorAll('.star-selector').forEach(function(selector) {
        const labels = selector.querySelectorAll('.star-label');
        const svgs   = selector.querySelectorAll('.star-svg');

        function paint(upTo) {
            svgs.forEach(function(svg, i) {
                svg.setAttribute('fill', i < upTo ? '#f59e0b' : '#d1d5db');
            });
        }

        // Init from checked radio
        const checked = selector.querySelector('.star-radio:checked');
        if (checked) paint(parseInt(checked.value));

        labels.forEach(function(label, idx) {
            label.addEventListener('mouseenter', function() { paint(idx + 1); });
            label.addEventListener('mouseleave', function() {
                const c = selector.querySelector('.star-radio:checked');
                paint(c ? parseInt(c.value) : 0);
            });
            label.addEventListener('click', function() {
                const radio = label.querySelector('.star-radio');
                radio.checked = true;
                paint(idx + 1);
            });
        });
    });
})();
</script>

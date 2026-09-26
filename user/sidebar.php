<?php
// user/sidebar.php
// Reusable sidebar partial
// Requires: $conn, $_SESSION already set
// $user array must be fetched by the including page, OR we fetch it here if not available

if (!isset($user)) {
    $uid = (int)$_SESSION['user_id'];
    $s = $conn->prepare("SELECT Name, Email, Photo, Created_at FROM user WHERE User_id = ?");
    $s->bind_param("i", $uid);
    $s->execute();
    $user = $s->get_result()->fetch_assoc();
    $s->close();
}

$photo = $user['Photo'] ?? '';
$photo_src = $photo ? '../' . $photo : '';
$current_page = basename($_SERVER['PHP_SELF']);

$nav_items = [
    'dashboard.php'      => 'Dashboard',
    'my_stay.php'        => 'My Stay',
    'update_profile.php' => 'Update Profile',
    'my_reviews.php'     => 'My Reviews',
    'delete_account.php' => 'Delete Account',
];
?>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">

    <!-- Profile header -->
    <div class="bg-primary text-white text-center py-4 px-3">
        <?php if ($photo_src): ?>
            <img src="<?php echo htmlspecialchars($photo_src); ?>"
                class="rounded-circle mb-2"
                style="width:72px;height:72px;object-fit:cover;border:3px solid rgba(255,255,255,0.5);">
        <?php else: ?>
            <div class="rounded-circle bg-white text-primary fw-bold mx-auto mb-2 d-flex align-items-center justify-content-center"
                style="width:72px;height:72px;font-size:28px;">
                <?php echo strtoupper(mb_substr($user['Name'], 0, 1)); ?>
            </div>
        <?php endif; ?>
        <div class="fw-bold"><?php echo htmlspecialchars($user['Name']); ?></div>
        <div class="small opacity-75"><?php echo htmlspecialchars($user['Email']); ?></div>
        <div class="small opacity-75 mt-1">
            Member since <?php echo date('M Y', strtotime($user['Created_at'])); ?>
        </div>
    </div>

    <!-- Nav links -->
    <div class="d-flex flex-column p-3 gap-1">
        <?php foreach ($nav_items as $href => $label):
            $is_active = ($current_page === $href);
            $is_danger = ($href === 'delete_account.php');
            if ($is_active) {
                $cls = 'bg-primary text-white';
            } elseif ($is_danger) {
                $cls = 'text-danger';
            } else {
                $cls = 'text-dark';
            }
        ?>
        <a href="<?php echo $href; ?>"
            class="rounded-3 px-3 py-2 text-decoration-none fw-semibold small <?php echo $cls; ?>">
            <?php echo $label; ?>
        </a>
        <?php endforeach; ?>
        <hr class="my-1">
        <a href="../logout.php"
            class="rounded-3 px-3 py-2 text-decoration-none fw-semibold small text-danger">
            Logout
        </a>
    </div>

</div>

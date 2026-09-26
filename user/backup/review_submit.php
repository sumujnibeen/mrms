<?php
// review_submit.php

if (session_status() === PHP_SESSION_NONE) session_start();
include '../db.php';
include '../auth.php';

if ($_SESSION['role'] !== 'guest' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php"); exit();
}

$guest_id    = (int)$_SESSION['user_id'];
$target_type = trim($_POST['target_type'] ?? '');
$target_id   = (int)($_POST['target_id'] ?? 0);
$rating      = (int)($_POST['rating'] ?? 0);
$comment     = trim($_POST['comment'] ?? '');
$redirect    = $_POST['redirect'] ?? 'index.php';

if (!in_array($target_type, ['room', 'food']) || $target_id === 0 || $rating < 1 || $rating > 5) {
    $_SESSION['review_error'] = "Invalid review data.";
    header("Location: $redirect"); exit();
}

// Eligibility: guest must be currently Checked-In OR have Checked-Out from this room.
// For food: guest must be Checked-In or have any Checked-Out booking.
if ($target_type === 'room') {
    $chk = $conn->prepare("
        SELECT COUNT(*) AS cnt FROM booking
        WHERE guest_id = ? AND room_id = ?
        AND status IN ('Checked-In', 'Checked-Out')
    ");
    $chk->bind_param("ii", $guest_id, $target_id);
    $chk->execute();
    $eligible = (int)$chk->get_result()->fetch_assoc()['cnt'] > 0;
    $chk->close();
} else {
    // Food review allowed once they are checked in or have stayed before
    $chk = $conn->prepare("
        SELECT COUNT(*) AS cnt FROM booking
        WHERE guest_id = ? AND status IN ('Checked-In', 'Checked-Out')
    ");
    $chk->bind_param("i", $guest_id);
    $chk->execute();
    $eligible = (int)$chk->get_result()->fetch_assoc()['cnt'] > 0;
    $chk->close();
}

if (!$eligible) {
    $_SESSION['review_error'] = "You can only submit a review during or after your stay.";
    header("Location: $redirect"); exit();
}

// Insert or update existing review
$stmt = $conn->prepare("
    INSERT INTO review (guest_id, target_type, target_id, rating, comment)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment)
");
$stmt->bind_param("issis", $guest_id, $target_type, $target_id, $rating, $comment);

if ($stmt->execute()) {
    $_SESSION['review_success'] = "Your review has been submitted. Thank you.";
} else {
    $_SESSION['review_error'] = "Could not submit review. Please try again.";
}
$stmt->close();

header("Location: $redirect");
exit();
?>

<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../db.php';
include '../auth.php';

if (!in_array($_SESSION['role'], ['admin', 'manager'])) {
  header("Location: ../index.php");
  exit();
}
$q = '%' . $_GET['q'] . '%';
$result = mysqli_query($conn, "
  SELECT user.User_id as guest_id, user.Name as name, room.room_number as room
  FROM booking
  JOIN user ON booking.guest_id = user.User_id
  JOIN room ON booking.room_id = room.room_id
  WHERE user.Name LIKE '$q' OR room.room_number LIKE '$q'
  LIMIT 5
");
$data = [];
while ($row = mysqli_fetch_assoc($result)) {
  $data[] = $row;
}
echo json_encode($data);
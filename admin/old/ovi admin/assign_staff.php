<?php
include(__DIR__ . '/config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id  = intval($_POST['room_id']);
    $staff_id = intval($_POST['staff_id']);
    $task     = mysqli_real_escape_string($conn, $_POST['task']);
    $note     = mysqli_real_escape_string($conn, $_POST['note'] ?? '');

    mysqli_query($conn, "
        INSERT INTO staff_assignment (room_id, staff_id, task, note, assigned_at)
        VALUES ($room_id, $staff_id, '$task', '$note', NOW())
    ");
}

header('Location: housekeeping.php?assigned=1');
exit;
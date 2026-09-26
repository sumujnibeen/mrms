<?php
// ============================================================
//  MRMS — Add New Food Order
//  Meghdoot Resort Management System | Group 06 | ISD 2026
// ============================================================

include(__DIR__ . '/config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $guest_name  = mysqli_real_escape_string($conn, trim($_POST['guest_name']));
    $room_number = mysqli_real_escape_string($conn, trim($_POST['room_number']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $charge      = (float) $_POST['charge'];

    if ($guest_name !== '' && $room_number !== '' && $description !== '' && $charge >= 0) {

        // Find active booking by guest name + room number
        $res = mysqli_query($conn, "
            SELECT b.booking_id, b.guest_id
            FROM booking b
            JOIN user u ON u.User_id = b.guest_id
            JOIN room r ON r.room_id = b.room_id
            WHERE u.Name = '$guest_name'
              AND r.room_number = '$room_number'
              AND b.status = 'Active'
            LIMIT 1
        ");
        $row = mysqli_fetch_assoc($res);

        if ($row) {
            $booking_id = (int) $row['booking_id'];
            $guest_id   = (int) $row['guest_id'];

            mysqli_query($conn, "
                INSERT INTO service_request
                    (guest_id, booking_id, type, description, status, charge, requested_at)
                VALUES
                    ($guest_id, $booking_id, 'food', '$description', 'Pending', $charge, NOW())
            ");

            header('Location: restaurant.php?success=1');
            exit;
        } else {
            // Guest/room not found — redirect with error
            header('Location: restaurant.php?error=notfound');
            exit;
        }
    }
}

header('Location: restaurant.php?error=invalid');
exit;
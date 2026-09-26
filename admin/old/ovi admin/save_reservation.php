<?php
include(__DIR__ . '/config/db.php');
session_start();

if(!isset($_SESSION['id']) || strtolower($_SESSION['role']) != 'admin'){
    header("Location: login.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header("Location: reservations.php");
    exit();
}

$name      = trim($_POST['guest_name'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$email     = trim($_POST['email'] ?? '');
$room_id   = intval($_POST['room_id'] ?? 0);
$check_in  = $_POST['check_in'] ?? '';
$check_out = $_POST['check_out'] ?? '';

// Basic validation
if(!$name || !$room_id || !$check_in || !$check_out){
    header("Location: reservations.php?error=missing_fields");
    exit();
}

if($check_out <= $check_in){
    header("Location: reservations.php?error=invalid_dates");
    exit();
}

/* ================= STEP 1: FIND OR CREATE USER ================= */

$guest_id = null;

if($email){
    $stmt = mysqli_prepare($conn,"SELECT User_id FROM user WHERE Email=? LIMIT 1");
    mysqli_stmt_bind_param($stmt,"s",$email);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if($row = mysqli_fetch_assoc($res)){
        $guest_id = $row['User_id'];
    }
}

if(!$guest_id){

    $defaultPass = password_hash('guest123', PASSWORD_BCRYPT);

    $stmt = mysqli_prepare($conn,
        "INSERT INTO user (Name, Email, Phone, Password, Role)
         VALUES (?, ?, ?, ?, 'guest')"
    );

    mysqli_stmt_bind_param($stmt,"ssss",$name,$email,$phone,$defaultPass);
    mysqli_stmt_execute($stmt);

    $guest_id = mysqli_insert_id($conn);
}

/* ================= STEP 2: INSERT BOOKING ================= */

$stmt = mysqli_prepare($conn,
    "INSERT INTO booking (guest_id, room_id, check_in, check_out, status)
     VALUES (?, ?, ?, ?, 'Pending')"
);

mysqli_stmt_bind_param($stmt,"iiss",$guest_id,$room_id,$check_in,$check_out);
mysqli_stmt_execute($stmt);

/* ================= STEP 3: UPDATE ROOM ================= */

$stmt = mysqli_prepare($conn,
    "UPDATE room SET status='Booked' WHERE room_id=?"
);

mysqli_stmt_bind_param($stmt,"i",$room_id);
mysqli_stmt_execute($stmt);

header("Location: reservations.php?success=1");
exit();
?>
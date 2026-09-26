<?php
include 'config.php';

// Add Booking
if(isset($_POST['add_booking'])){
  $guest_id = $_POST['guest_id'];
  $room_id = $_POST['room_id'];
  $check_in = $_POST['check_in'];
  $check_out = $_POST['check_out'];
  $status = $_POST['status'];

  $query = "INSERT INTO booking (guest_id, room_id, check_in, check_out, status, booked_at)
            VALUES ('$guest_id','$room_id','$check_in','$check_out','$status',NOW())";
  mysqli_query($conn,$query);
  echo "<script>alert('Booking Added Successfully!');window.location='bookings.php';</script>";
}

// Cancel Booking
if(isset($_GET['cancel'])){
  $id = $_GET['cancel'];
  mysqli_query($conn,"UPDATE booking SET status='Cancelled' WHERE booking_id='$id'");
  echo "<script>alert('Booking Cancelled!');window.location='bookings.php';</script>";
}

// Fetch Bookings
$result = mysqli_query($conn,"SELECT b.*, u.Name, r.room_number 
FROM booking b 
JOIN user u ON b.guest_id=u.User_id 
JOIN room r ON b.room_id=r.room_id 
ORDER BY b.check_in DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bookings</title>
  <style>
    body {font-family:'Segoe UI',Arial;background:#f9f9f9;margin:0;padding:20px;}
    h1 {color:#28a745;text-align:center;margin-bottom:20px;}
    .summary {display:flex;gap:20px;justify-content:center;margin-bottom:30px;}
    .card {background:#fff;padding:15px;border-radius:10px;box-shadow:0 0 10px rgba(0,0,0,0.1);width:200px;text-align:center;}
    .card h2 {margin:0;color:#28a745;}
    .btn-add {background:#28a745;color:#fff;border:none;padding:10px 20px;border-radius:5px;cursor:pointer;margin-bottom:20px;}
    .btn-add:hover {background:#218838;}
    table {width:100%;border-collapse:collapse;background:#fff;box-shadow:0 0 10px rgba(0,0,0,0.1);}
    th,td {padding:12px;border:1px solid #ddd;text-align:center;}
    th {background:#28a745;color:#fff;}
    .status-pending {color:#ffc107;font-weight:bold;}
    .status-confirmed {color:#28a745;font-weight:bold;}
    .status-cancelled {color:#dc3545;font-weight:bold;}
    .btn-edit {background:#007BFF;color:#fff;border:none;padding:5px 10px;border-radius:5px;cursor:pointer;}
    .btn-cancel {background:#dc3545;color:#fff;border:none;padding:5px 10px;border-radius:5px;cursor:pointer;}
    .add-form {background:#fff;padding:20px;border-radius:10px;box-shadow:0 0 10px rgba(0,0,0,0.1);margin-bottom:30px;display:none;}
    input,select {width:100%;padding:8px;margin:5px 0;border:1px solid #ccc;border-radius:5px;}
    button {cursor:pointer;}
  </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="mgr-layout">
<div class="mgr-main" style="padding:24px;">
  <h1>Booking Management</h1>

  <!-- Summary Cards -->
  <div class="summary">
    <?php
      $total = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS total FROM booking"));
      $pending = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS pending FROM booking WHERE status='Pending'"));
      $confirmed = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS confirmed FROM booking WHERE status='Confirmed'"));
      $cancelled = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS cancelled FROM booking WHERE status='Cancelled'"));
    ?>
    <div class="card"><h2><?php echo $total['total']; ?></h2><p>Total Bookings</p></div>
    <div class="card"><h2><?php echo $pending['pending']; ?></h2><p>Pending</p></div>
    <div class="card"><h2><?php echo $confirmed['confirmed']; ?></h2><p>Confirmed</p></div>
    <div class="card"><h2><?php echo $cancelled['cancelled']; ?></h2><p>Cancelled</p></div>
  </div>

  <!-- Add Booking Button -->
  <button class="btn-add" onclick="document.getElementById('addForm').style.display='block'"> Add Booking</button>

  <!-- Add Booking Form -->
  <div class="add-form" id="addForm">
    <h2>Add New Booking</h2>
    <form method="POST">
      <input type="number" name="guest_id" placeholder="Guest ID" required>
      <input type="number" name="room_id" placeholder="Room ID" required>
      <input type="date" name="check_in" required>
      <input type="date" name="check_out" required>
      <select name="status">
        <option value="Pending">Pending</option>
        <option value="Confirmed">Confirmed</option>
      </select>
      <button type="submit" name="add_booking">Add Booking</button>
    </form>
  </div>

  <!-- Booking Table -->
  <table>
    <tr>
      <th>ID</th><th>Guest</th><th>Room</th><th>Status</th><th>Check-in</th><th>Check-out</th><th>Booked At</th><th>Actions</th>
    </tr>
    <?php while($row=mysqli_fetch_assoc($result)){ ?>
      <tr>
        <td><?php echo $row['booking_id']; ?></td>
        <td><?php echo $row['Name']; ?></td>
        <td><?php echo $row['room_number']; ?></td>
        <td>
          <?php 
            if($row['status']=='Pending') echo "<span class='status-pending'>Pending</span>";
            elseif($row['status']=='Confirmed') echo "<span class='status-confirmed'>Confirmed</span>";
            elseif($row['status']=='Cancelled') echo "<span class='status-cancelled'>Cancelled</span>";
          ?>
        </td>
        <td><?php echo $row['check_in']; ?></td>
        <td><?php echo $row['check_out']; ?></td>
        <td><?php echo $row['booked_at']; ?></td>
        <td>
          <a href="edit_booking.php?id=<?php echo $row['booking_id']; ?>"><button class="btn-edit">Edit</button></a>
          <a href="bookings.php?cancel=<?php echo $row['booking_id']; ?>" onclick="return confirm('Cancel this booking?');"><button class="btn-cancel">Cancel</button></a>
        </td>
      </tr>
    <?php } ?>
  </table>
</div></div>
</body>
</html>

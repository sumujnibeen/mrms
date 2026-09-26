<?php
include 'config.php';

// Add Room
if(isset($_POST['add_room'])){
  $room_number = $_POST['room_number'];
  $type = $_POST['type'];
  $price = $_POST['price'];
  $status = $_POST['status'];
  $floor = $_POST['floor'];
  $capacity = $_POST['capacity'];
  $desc = $_POST['description'];

  $query = "INSERT INTO room (room_number, type, price_per_night, status, floor, capacity, description)
            VALUES ('$room_number','$type','$price','$status','$floor','$capacity','$desc')";
  mysqli_query($conn,$query);
  echo "<script>alert('Room Added Successfully!');window.location='rooms.php';</script>";
}

// Delete Room
if(isset($_GET['delete'])){
  $id = $_GET['delete'];
  mysqli_query($conn,"DELETE FROM room WHERE room_id='$id'");
  echo "<script>alert('Room Deleted!');window.location='rooms.php';</script>";
}

// Fetch Rooms
$result = mysqli_query($conn,"SELECT * FROM room ORDER BY floor, room_number");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rooms</title>
  <style>
    body {
      font-family:'Segoe UI',Arial;
      background:linear-gradient(135deg,#f4f6f9,#e3f2fd);
      margin:0;
      padding:20px;
    }
    h1 {color:#007BFF;text-align:center;margin-bottom:10px;}
    .topbar {display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;}
    .btn-add {
      background:#007BFF;color:#fff;border:none;padding:10px 20px;border-radius:50px;
      font-size:16px;cursor:pointer;display:flex;align-items:center;gap:8px;
      box-shadow:0 4px 6px rgba(0,0,0,0.1);transition:0.3s;
    }
    .btn-add:hover {background:#0056b3;transform:scale(1.05);}
    .summary {display:flex;gap:20px;justify-content:center;margin-bottom:30px;}
    .card {
      background:#fff;padding:15px;border-radius:10px;box-shadow:0 0 10px rgba(0,0,0,0.1);
      width:200px;text-align:center;transition:0.3s;
    }
    .card:hover {transform:scale(1.05);}
    .search-bar {display:flex;justify-content:center;gap:10px;margin-bottom:20px;}
    .search-bar input,.search-bar select {padding:8px;border-radius:5px;border:1px solid #ccc;}
    .search-bar button {background:#007BFF;color:#fff;border:none;padding:8px 15px;border-radius:5px;cursor:pointer;}
    .grid {display:flex;flex-wrap:wrap;gap:20px;justify-content:center;}
    .room-card {
      background:#fff;border-radius:10px;box-shadow:0 0 10px rgba(0,0,0,0.1);
      padding:15px;width:220px;transition:0.3s;position:relative;
    }
    .room-card:hover {transform:translateY(-5px);}
    .room-card h3 {margin:0;color:#007BFF;}
    .room-card p {margin:5px 0;color:#555;}
    .actions {position:absolute;top:10px;right:10px;}
    .btn-edit,.btn-delete {
      border:none;padding:5px 10px;border-radius:5px;cursor:pointer;font-size:13px;
    }
    .btn-edit {background:#28a745;color:#fff;}
    .btn-delete {background:#dc3545;color:#fff;}
    .btn-edit:hover {background:#218838;}
    .btn-delete:hover {background:#c82333;}
    .add-form {
      background:#fff;padding:20px;border-radius:10px;box-shadow:0 0 10px rgba(0,0,0,0.1);
      margin-bottom:30px;width:60%;margin:auto;display:none;
    }
    input,select,textarea {width:100%;padding:8px;margin:5px 0;border:1px solid #ccc;border-radius:5px;}
    button {background:#007BFF;color:#fff;border:none;padding:10px 15px;border-radius:5px;cursor:pointer;}
    button:hover {background:#0056b3;}
  </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="mgr-layout">
<div class="mgr-main" style="padding:24px;">
  <div class="topbar">
    <h1>Room Management</h1>
    <button class="btn-add" onclick="document.getElementById('addForm').style.display='block'"> Add Room</button>
  </div>

  <!-- Summary Cards -->
  <div class="summary">
    <?php
      $total = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS total FROM room"));
      $available = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS available FROM room WHERE status='Available'"));
      $occupied = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS occupied FROM room WHERE status='Booked'"));
      $maintenance = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS maintenance FROM room WHERE status='Under Maintenance'"));
    ?>
    <div class="card"><h2><?php echo $total['total']; ?></h2><p>Total Rooms</p></div>
    <div class="card"><h2><?php echo $available['available']; ?></h2><p>Available</p></div>
    <div class="card"><h2><?php echo $occupied['occupied']; ?></h2><p>Occupied</p></div>
    <div class="card"><h2><?php echo $maintenance['maintenance']; ?></h2><p>Maintenance</p></div>
  </div>

  <!-- Search Bar -->
  <div class="search-bar">
    <input type="text" placeholder="Search room number...">
    <select><option>All Types</option><option>Single</option><option>Double</option><option>Deluxe</option><option>Suite</option></select>
    <select><option>All Status</option><option>Available</option><option>Booked</option><option>Under Maintenance</option></select>
    <button>Apply</button>
  </div>

  <!-- Add Room Form -->
  <div class="add-form" id="addForm">
    <h2>Add New Room</h2>
    <form method="POST">
      <input type="text" name="room_number" placeholder="Room Number" required>
      <select name="type">
        <option value="Single">Single</option>
        <option value="Double">Double</option>
        <option value="Deluxe">Deluxe</option>
        <option value="Suite">Suite</option>
      </select>
      <input type="number" name="price" placeholder="Price per Night" required>
      <select name="status">
        <option value="Available">Available</option>
        <option value="Booked">Booked</option>
        <option value="Under Maintenance">Under Maintenance</option>
      </select>
      <input type="number" name="floor" placeholder="Floor Number" required>
      <input type="number" name="capacity" placeholder="Capacity (persons)" required>
      <textarea name="description" placeholder="Description"></textarea>
      <button type="submit" name="add_room">Add Room</button>
    </form>
  </div>

  <!-- Room Grid -->
  <div class="grid">
    <?php while($row=mysqli_fetch_assoc($result)){ ?>
      <div class="room-card">
        <div class="actions">
          <a href="edit_room.php?id=<?php echo $row['room_id']; ?>"><button class="btn-edit">Edit</button></a>
          <a href="rooms.php?delete=<?php echo $row['room_id']; ?>" onclick="return confirm('Delete this room?');"><button class="btn-delete">Delete</button></a>
        </div>
        <h3>Room <?php echo $row['room_number']; ?></h3>
        <p>Type: <?php echo $row['type']; ?></p>
        <p>Status: <?php echo $row['status']; ?></p>
        <p>Price: ৳<?php echo $row['price_per_night']; ?>/night</p>
        <p>Floor: <?php echo $row['floor']; ?></p>
        <p>Capacity: <?php echo $row['capacity']; ?> person(s)</p>
        <p><?php echo $row['description']; ?></p>
      </div>
    <?php } ?>
  </div>
</div></div>
</body>
</html>

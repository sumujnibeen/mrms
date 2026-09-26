<?php
include('../db.php');
session_start();

// check login
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];

// 🔹 bring user information
$stmt = $conn->prepare("SELECT * FROM user WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
  die("User not found in database for ID = $user_id");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Update Profile</title>
  <link rel="stylesheet" href="bootstrap-5.3.8-dist/css/bootstrap.min.css">
</head>

<body class="bg-light">
  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card shadow border-success">
          <div class="card-header bg-success text-white fw-bold text-center">
            Update Profile
          </div>
          <div class="card-body">
            <!-- ✅ Autofill-proof form -->
            <form method="POST" action="update_action.php" autocomplete="off">
              <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">

              <!-- hidden fake fields -->
              <input type="text" name="fakeusernameremembered" style="display:none">
              <input type="password" name="fakepasswordremembered" style="display:none">

              <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="full_name" class="form-control"
                  value="<?php echo htmlspecialchars($user['Name'] ?? ''); ?>" required
                  autocomplete="new-name">
              </div>

              <div class="mb-3">
                <label class="form-label">Confirm with Password</label>
                <input type="password" name="current_password" class="form-control"
                  placeholder="Enter your current password" required autocomplete="new-password">
              </div>

              <div class="text-center">
                <button type="submit" class="btn btn-success">Update</button>
                <a href="my_stay.php" class="btn btn-secondary">Cancel</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
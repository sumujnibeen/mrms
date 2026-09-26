<?php
// ======================= DB CONNECTION =======================
if (session_status() === PHP_SESSION_NONE) session_start();
include '../db.php';
include '../auth.php';

if (!in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: ../index.php");
    exit();
}
// ======================= GET SETTINGS =======================
$sql = "SELECT * FROM system_settings LIMIT 1";
$result = $conn->query($sql);
$data = $result->fetch_assoc();

// ======================= UPDATE SETTINGS =======================
if (isset($_POST['update'])) {

    $resort_name = $_POST['resort_name'];
    $resort_email = $_POST['resort_email'];
    $resort_phone = $_POST['resort_phone'];
    $resort_address = $_POST['resort_address'];
    $currency = $_POST['currency'];
    $timezone = $_POST['timezone'];
    $smtp_host = $_POST['smtp_host'];
    $smtp_port = $_POST['smtp_port'];
    $smtp_email = $_POST['smtp_email'];
    $smtp_password = $_POST['smtp_password'];
    $maintenance_mode = $_POST['maintenance_mode'];

    // LOGO UPLOAD
    $logo = $data['logo'];
    if (!empty($_FILES['logo']['name'])) {
        $logo = "uploads/" . time() . "_" . $_FILES['logo']['name'];
        move_uploaded_file($_FILES['logo']['tmp_name'], $logo);
    }

    $update = "UPDATE settings SET 
        resort_name='$resort_name',
        resort_email='$resort_email',
        resort_phone='$resort_phone',
        resort_address='$resort_address',
        currency='$currency',
        timezone='$timezone',
        smtp_host='$smtp_host',
        smtp_port='$smtp_port',
        smtp_email='$smtp_email',
        smtp_password='$smtp_password',
        maintenance_mode='$maintenance_mode',
        logo='$logo'
        WHERE setting_id=1";

    $conn->query($update);

    echo "<script>alert('Settings Updated Successfully'); window.location.href='settings.php';</script>";
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Settings - MRMS</title>

    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: Arial;
    }

    body {
        min-height: 100vh;
        background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)),
            url('https://images.unsplash.com/photo-1542314831-068cd1dbfeeb');
        background-size: cover;
        background-position: center;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 30px;
    }

    .container {
        width: 100%;
        max-width: 1200px;
        background: rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(12px);
        border-radius: 20px;
        padding: 20px;
        color: white;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }

    .header h1 {
        font-size: 28px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-top: 20px;
    }

    .box {
        background: rgba(255, 255, 255, 0.10);
        padding: 15px;
        border-radius: 15px;
    }

    input,
    select,
    textarea {
        width: 100%;
        padding: 10px;
        margin-top: 5px;
        border: none;
        border-radius: 8px;
    }

    label {
        font-size: 14px;
    }

    button {
        margin-top: 20px;
        padding: 12px;
        width: 100%;
        border: none;
        border-radius: 10px;
        background: #00b894;
        color: white;
        font-size: 16px;
        cursor: pointer;
    }

    button:hover {
        background: #019875;
    }

    @media(max-width:800px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>

</head>

<body>

    <div class="container">

        <div class="header">
            <h1>Settings Panel</h1>
        </div>

        <form method="POST" enctype="multipart/form-data">

            <div class="form-grid">

                <div class="box">
                    <label>Resort Name</label>
                    <input type="text" name="resort_name" value="<?php echo $data['resort_name']; ?>">

                    <label>Email</label>
                    <input type="email" name="resort_email" value="<?php echo $data['resort_email']; ?>">

                    <label>Phone</label>
                    <input type="text" name="resort_phone" value="<?php echo $data['resort_phone']; ?>">

                    <label>Address</label>
                    <textarea name="resort_address"><?php echo $data['resort_address']; ?></textarea>
                </div>

                <div class="box">
                    <label>Currency</label>
                    <input type="text" name="currency" value="<?php echo $data['currency']; ?>">

                    <label>Timezone</label>
                    <input type="text" name="timezone" value="<?php echo $data['timezone']; ?>">

                    <label>SMTP Host</label>
                    <input type="text" name="smtp_host" value="<?php echo $data['smtp_host']; ?>">

                    <label>SMTP Port</label>
                    <input type="text" name="smtp_port" value="<?php echo $data['smtp_port']; ?>">

                    <label>SMTP Email</label>
                    <input type="text" name="smtp_email" value="<?php echo $data['smtp_email']; ?>">

                    <label>SMTP Password</label>
                    <input type="password" name="smtp_password" value="<?php echo $data['smtp_password']; ?>">
                </div>

                <div class="box">
                    <label>Maintenance Mode</label>
                    <select name="maintenance_mode">
                        <option value="OFF" <?php if ($data['maintenance_mode'] == "OFF") echo "selected"; ?>>OFF
                        </option>
                        <option value="ON" <?php if ($data['maintenance_mode'] == "ON") echo "selected"; ?>>ON</option>
                    </select>

                    <label>Logo</label>
                    <input type="file" name="logo">

                    <?php if (!empty($data['logo'])) { ?>
                    <img src="<?php echo $data['logo']; ?>" width="120">
                    <?php } ?>
                </div>

            </div>

            <button type="submit" name="update">Save Settings</button>

        </form>

    </div>

</body>

</html>
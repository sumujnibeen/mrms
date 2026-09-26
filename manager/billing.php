<?php
include 'config.php';

// Total Revenue
$revenueQuery = mysqli_query($conn,"SELECT SUM(pay_amount) AS total_revenue FROM booking WHERE payment_status IN ('paid','partial')");
$revenue = mysqli_fetch_assoc($revenueQuery);
$total_revenue = $revenue['total_revenue'] ?? 0;

// Paid Bills
$paidBills = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS paid_count FROM booking WHERE payment_status='paid'"));
$paid_count = $paidBills['paid_count'] ?? 0;

// Pending Bills
$pendingBills = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS pending_count FROM booking WHERE payment_status='partial'"));
$pending_count = $pendingBills['pending_count'] ?? 0;

// Overdue Bills (যদি due_amount > 0)
$overdueBills = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS overdue_count FROM booking WHERE due_amount > 0"));
$overdue_count = $overdueBills['overdue_count'] ?? 0;

// Recent Invoices
$invoices = mysqli_query($conn,"
SELECT b.booking_id, u.Name, r.room_id, b.pay_amount, b.due_amount, b.payment_status, b.status, b.transaction_id
FROM booking b
JOIN user u ON b.guest_id=u.User_id
JOIN room r ON b.room_id=r.room_id
ORDER BY b.booking_id DESC LIMIT 5
");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Billing Dashboard</title>
  <style>
    body {font-family:'Segoe UI',Arial;background:#f4f6f9;margin:0;padding:20px;}
    h1 {color:#007BFF;text-align:center;margin-bottom:10px;}
    .summary {display:flex;gap:20px;justify-content:center;margin-bottom:30px;}
    .card {
      background:#fff;padding:15px;border-radius:10px;box-shadow:0 0 10px rgba(0,0,0,0.1);
      width:220px;text-align:center;transition:0.3s;
    }
    .card:hover {transform:scale(1.05);}
    .card h2 {margin:0;color:#007BFF;}
    .card p {color:#555;}
    .section {background:#fff;padding:20px;border-radius:10px;box-shadow:0 0 10px rgba(0,0,0,0.1);margin-bottom:30px;}
    table {width:100%;border-collapse:collapse;}
    th,td {padding:10px;border-bottom:1px solid #ddd;text-align:center;}
    th {background:#007BFF;color:#fff;}
    .status-paid {color:#28a745;font-weight:bold;}
    .status-partial {color:#ffc107;font-weight:bold;}
    .status-due {color:#dc3545;font-weight:bold;}
    .btn-view, .btn-pay {
      border:none;padding:5px 10px;border-radius:5px;cursor:pointer;font-size:13px;
    }
    .btn-view {background:#28a745;color:#fff;}
    .btn-pay {background:#007BFF;color:#fff;}
    .btn-view:hover {background:#218838;}
    .btn-pay:hover {background:#0056b3;}
  </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="mgr-layout">
<div class="mgr-main" style="padding:24px;">
  <h1>Billing Dashboard</h1>

  <!-- Summary Cards -->
  <div class="summary">
    <div class="card">
      <h2>৳<?php echo number_format($total_revenue); ?></h2>
      <p>Total Revenue</p>
    </div>
    <div class="card">
      <h2><?php echo $paid_count; ?></h2>
      <p>Paid Bills</p>
    </div>
    <div class="card">
      <h2><?php echo $pending_count; ?></h2>
      <p>Pending Bills</p>
    </div>
    <div class="card">
      <h2><?php echo $overdue_count; ?></h2>
      <p>Overdue Bills</p>
    </div>
  </div>

  <!-- Recent Invoices -->
  <div class="section">
    <h2>Recent Invoices</h2>
    <table>
      <tr><th>Guest</th><th>Invoice ID</th><th>Room</th><th>Amount</th><th>Status</th><th>Action</th></tr>
      <?php while($row=mysqli_fetch_assoc($invoices)){ ?>
        <tr>
          <td><?php echo $row['Name']; ?></td>
          <td>#INV-<?php echo str_pad($row['booking_id'],4,'0',STR_PAD_LEFT); ?></td>
          <td><?php echo $row['room_id']; ?></td>
          <td>৳<?php echo number_format($row['pay_amount']); ?></td>
          <td>
            <?php 
              if($row['payment_status']=='paid') echo "<span class='status-paid'>Paid</span>";
              elseif($row['payment_status']=='partial') echo "<span class='status-partial'>Pending</span>";
              else echo "<span class='status-due'>Due</span>";
            ?>
          </td>
          <td>
            <?php if($row['payment_status']=='paid'){ ?>
              <button class="btn-view">View</button>
            <?php } else { ?>
              <button class="btn-pay">Pay</button>
            <?php } ?>
          </td>
        </tr>
      <?php } ?>
    </table>
  </div>

  <!-- Payment Summary -->
  <div class="section">
    <h2>Payment Summary</h2>
    <p><strong>Today's Revenue (Estimate):</strong> ৳<?php echo number_format($total_revenue/30); ?></p>
    <p><strong>Cash Payments:</strong> ৳<?php echo number_format($total_revenue*0.4); ?></p>
    <p><strong>Card Payments:</strong> ৳<?php echo number_format($total_revenue*0.35); ?></p>
    <p><strong>Online Payments:</strong> ৳<?php echo number_format($total_revenue*0.25); ?></p>
  </div>
</div></div>
</body>
</html>

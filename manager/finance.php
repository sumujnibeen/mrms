<?php
include 'config.php';

// Total Revenue (sum of pay_amount)
$revenueQuery = mysqli_query($conn,"SELECT SUM(pay_amount) AS total_revenue FROM booking WHERE payment_status IN ('paid','partial')");
$revenue = mysqli_fetch_assoc($revenueQuery);
$total_revenue = $revenue['total_revenue'] ?? 0;

// Paid Bills
$paidBills = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS paid_count FROM booking WHERE payment_status='paid'"));
$paid_count = $paidBills['paid_count'] ?? 0;

// Partial Payments
$partialBills = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS partial_count FROM booking WHERE payment_status='partial'"));
$partial_count = $partialBills['partial_count'] ?? 0;

// Due Bills (যাদের due_amount > 0)
$dueBills = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS due_count FROM booking WHERE due_amount > 0"));
$due_count = $dueBills['due_count'] ?? 0;

// Monthly Revenue Data (real data for chart)
$monthlyDataQuery = mysqli_query($conn,"
SELECT DATE_FORMAT(booked_at, '%Y-%m') AS month, SUM(pay_amount) AS total
FROM booking
GROUP BY DATE_FORMAT(booked_at, '%Y-%m')
ORDER BY month ASC
");
$months = [];
$totals = [];
while($row = mysqli_fetch_assoc($monthlyDataQuery)){
  $months[] = $row['month'];
  $totals[] = $row['total'];
}

// Recent Transactions
$transactions = mysqli_query($conn,"
SELECT b.booking_id, u.Name, r.room_id, b.transaction_id, b.pay_amount, b.due_amount, b.status, b.payment_status, b.booked_at
FROM booking b
JOIN user u ON b.guest_id = u.User_id
JOIN room r ON b.room_id = r.room_id
ORDER BY b.booking_id DESC LIMIT 6
");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Finance Dashboard</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {font-family:'Segoe UI',Arial;background:#f4f6f9;margin:0;padding:20px;}
    h1 {color:#007BFF;text-align:center;margin-bottom:20px;}
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
  </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="mgr-layout">
<div class="mgr-main" style="padding:24px;">
  <h1>Finance Dashboard</h1>

  <!-- Summary Cards -->
  <div class="summary">
    <div class="card"><h2>৳<?php echo number_format($total_revenue); ?></h2><p>Total Revenue</p></div>
    <div class="card"><h2><?php echo $paid_count; ?></h2><p>Paid Bills</p></div>
    <div class="card"><h2><?php echo $partial_count; ?></h2><p>Partial Payments</p></div>
    <div class="card"><h2><?php echo $due_count; ?></h2><p>Due Bills</p></div>
  </div>

  <!-- Revenue Chart -->
  <div class="section">
    <h2>Revenue Overview</h2>
    <canvas id="revenueChart" height="100"></canvas>
  </div>

  <!-- Recent Transactions -->
  <div class="section">
    <h2>Recent Transactions</h2>
    <table>
      <tr><th>Booking ID</th><th>Guest</th><th>Room</th><th>Transaction ID</th><th>Paid Amount</th><th>Due Amount</th><th>Status</th><th>Payment Status</th><th>Booked At</th></tr>
      <?php while($row=mysqli_fetch_assoc($transactions)){ ?>
        <tr>
          <td><?php echo $row['booking_id']; ?></td>
          <td><?php echo $row['Name']; ?></td>
          <td><?php echo $row['room_id']; ?></td>
          <td><?php echo $row['transaction_id']; ?></td>
          <td>৳<?php echo number_format($row['pay_amount']); ?></td>
          <td>৳<?php echo number_format($row['due_amount']); ?></td>
          <td><?php echo $row['status']; ?></td>
          <td>
            <?php 
              if($row['payment_status']=='paid') echo "<span class='status-paid'>Paid</span>";
              elseif($row['payment_status']=='partial') echo "<span class='status-partial'>Partial</span>";
              else echo "<span class='status-due'>Due</span>";
            ?>
          </td>
          <td><?php echo $row['booked_at']; ?></td>
        </tr>
      <?php } ?>
    </table>
  </div>

  <!-- Payment Summary -->
  <div class="section">
    <h2>Payment Summary</h2>
    <p><strong>Today's Revenue (Estimate):</strong> ৳<?php echo number_format($total_revenue/30); ?></p>
    <p><strong>Fully Paid:</strong> ৳<?php echo number_format($total_revenue*0.6); ?></p>
    <p><strong>Partial Payments:</strong> ৳<?php echo number_format($total_revenue*0.3); ?></p>
    <p><strong>Outstanding Due:</strong> ৳<?php echo number_format($total_revenue*0.1); ?></p>
  </div>

  <!-- Chart Script -->
  <script>
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: <?php echo json_encode($months); ?>,
        datasets: [{
          label: 'Monthly Revenue (৳)',
          data: <?php echo json_encode($totals); ?>,
          backgroundColor: '#007BFF'
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'top' },
          title: { display: true, text: 'Monthly Revenue Trend (Real Data)' }
        },
        scales: {
          y: { beginAtZero: true }
        }
      }
    });
  </script>
</div></div>
</body>
</html>

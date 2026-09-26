<?php
include 'config.php';

// ── OCCUPANCY ────────────────────────────────────────────
$rooms_res  = $conn->query("SELECT COUNT(*) AS total FROM room");
$total_rooms = (int)$rooms_res->fetch_assoc()['total'];

$occupied_res = $conn->query("SELECT COUNT(*) AS c FROM room WHERE status='Booked'");
$occupied_rooms = (int)$occupied_res->fetch_assoc()['c'];

$available_rooms = $total_rooms - $occupied_rooms;
$occupancy_rate  = $total_rooms > 0 ? round(($occupied_rooms / $total_rooms) * 100, 1) : 0;

// ── QUICK STATS ──────────────────────────────────────────
$total_bookings = (int)$conn->query("SELECT COUNT(*) AS c FROM booking")->fetch_assoc()['c'];

$active_guests  = (int)$conn->query(
    "SELECT COUNT(*) AS c FROM booking WHERE status='Checked-In'"
)->fetch_assoc()['c'];

$pending_checkins = (int)$conn->query(
    "SELECT COUNT(*) AS c FROM booking WHERE status='Confirmed' AND payment_status IN ('partial','paid')"
)->fetch_assoc()['c'];

$pending_refunds = (int)$conn->query(
    "SELECT COUNT(*) AS c FROM booking WHERE refund_status='requested'"
)->fetch_assoc()['c'];

// Actually collected revenue = pay_amount - due_amount (what guest has paid so far)
$revenue_res = $conn->query(
    "SELECT COALESCE(SUM(pay_amount - due_amount), 0) AS total FROM booking"
);
$total_revenue = (float)$revenue_res->fetch_assoc()['total'];

// ── MONTHLY REVENUE (current year, actually received) ────
$monthly_res = $conn->query("
    SELECT MONTHNAME(booked_at) AS month,
           MONTH(booked_at)     AS month_num,
           SUM(pay_amount - due_amount) AS revenue
    FROM booking
    WHERE YEAR(booked_at) = YEAR(CURDATE())
    GROUP BY MONTH(booked_at), MONTHNAME(booked_at)
    ORDER BY MONTH(booked_at)
");
$monthly_labels  = [];
$monthly_revenue = [];
while ($row = $monthly_res->fetch_assoc()) {
    $monthly_labels[]  = $row['month'];
    $monthly_revenue[] = (float)$row['revenue'];
}

// ── SERVICE REQUEST BREAKDOWN ────────────────────────────
$service_res = $conn->query("
    SELECT type, COUNT(*) AS count
    FROM service_request
    GROUP BY type
    ORDER BY count DESC
");
$srv_labels = [];
$srv_counts = [];
while ($row = $service_res->fetch_assoc()) {
    $srv_labels[] = ucwords(str_replace('_', ' ', $row['type']));
    $srv_counts[] = (int)$row['count'];
}

// ── BOOKING STATUS BREAKDOWN ─────────────────────────────
$status_res = $conn->query("
    SELECT status, COUNT(*) AS c FROM booking GROUP BY status
");
$status_labels = [];
$status_counts = [];
$status_colors = [
    'Pending'     => '#f59e0b',
    'Confirmed'   => '#3b82f6',
    'Checked-In'  => '#22c55e',
    'Checked-Out' => '#94a3b8',
    'Cancelled'   => '#ef4444',
];
while ($row = $status_res->fetch_assoc()) {
    $status_labels[] = $row['status'];
    $status_counts[] = (int)$row['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard — Meghdoot Resort</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f1f5f9; }
        .page-header {
            background: #1e293b;
            color: #fff;
            padding: 20px 28px;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -.01em;
        }
        .page-header span {
            display: block;
            font-size: 12px;
            font-weight: 400;
            color: #94a3b8;
            margin-top: 2px;
        }
        .content { padding: 24px 28px; }

        /* KPI Cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }
        .kpi-card {
            background: #fff;
            border-radius: 10px;
            padding: 18px 20px;
            border-left: 4px solid #3b82f6;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .kpi-card .kpi-label {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-bottom: 6px;
        }
        .kpi-card .kpi-val {
            font-size: 26px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1;
        }
        .kpi-card .kpi-sub {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 4px;
        }

        /* Charts */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .charts-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .chart-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px 24px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .chart-card h3 {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 16px;
        }
        .chart-card.full { grid-column: 1 / -1; }
        canvas { max-height: 240px; }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="mgr-layout">
<div class="mgr-main">

    <div class="page-header">
        Manager Dashboard
        <span>Hotel operations overview — <?php echo date('l, d F Y'); ?></span>
    </div>

    <div class="content">

        <!-- KPI CARDS -->
        <div class="kpi-grid">
            <div class="kpi-card" style="border-color:#3b82f6;">
                <div class="kpi-label">Total Rooms</div>
                <div class="kpi-val"><?php echo $total_rooms; ?></div>
                <div class="kpi-sub"><?php echo $available_rooms; ?> available</div>
            </div>
            <div class="kpi-card" style="border-color:#22c55e;">
                <div class="kpi-label">Occupancy Rate</div>
                <div class="kpi-val"><?php echo $occupancy_rate; ?>%</div>
                <div class="kpi-sub"><?php echo $occupied_rooms; ?> of <?php echo $total_rooms; ?> rooms booked</div>
            </div>
            <div class="kpi-card" style="border-color:#06b6d4;">
                <div class="kpi-label">Active Guests</div>
                <div class="kpi-val"><?php echo $active_guests; ?></div>
                <div class="kpi-sub">Currently checked in</div>
            </div>
            <div class="kpi-card" style="border-color:#f59e0b;">
                <div class="kpi-label">Pending Check-Ins</div>
                <div class="kpi-val"><?php echo $pending_checkins; ?></div>
                <div class="kpi-sub">Confirmed, advance paid</div>
            </div>
            <div class="kpi-card" style="border-color:#8b5cf6;">
                <div class="kpi-label">Total Bookings</div>
                <div class="kpi-val"><?php echo $total_bookings; ?></div>
                <div class="kpi-sub">All time</div>
            </div>
            <div class="kpi-card" style="border-color:#16a34a;">
                <div class="kpi-label">Revenue Received</div>
                <div class="kpi-val" style="font-size:20px;">&#2547;<?php echo number_format($total_revenue); ?></div>
                <div class="kpi-sub">Total collected (all time)</div>
            </div>
            <div class="kpi-card" style="border-color:#ef4444;">
                <div class="kpi-label">Pending Refunds</div>
                <div class="kpi-val"><?php echo $pending_refunds; ?></div>
                <div class="kpi-sub">Awaiting approval</div>
            </div>
        </div>

        <!-- CHARTS ROW 1 -->
        <div class="charts-grid">
            <div class="chart-card">
                <h3>Occupancy — Current</h3>
                <canvas id="occupancyChart"></canvas>
            </div>
            <div class="chart-card">
                <h3>Booking Status Distribution</h3>
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <!-- CHARTS ROW 2 -->
        <div class="charts-row">
            <div class="chart-card full">
                <h3>Monthly Revenue — <?php echo date('Y'); ?> (Amount Received)</h3>
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- CHARTS ROW 3 -->
        <?php if (!empty($srv_labels)): ?>
        <div style="margin-top:20px;">
            <div class="chart-card" style="max-width:480px;">
                <h3>Service Request Types</h3>
                <canvas id="serviceChart"></canvas>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- .content -->
</div>
</div>

<script>
// Occupancy donut
new Chart(document.getElementById('occupancyChart'), {
    type: 'doughnut',
    data: {
        labels: ['Booked', 'Available'],
        datasets: [{
            data: [<?php echo $occupied_rooms; ?>, <?php echo $available_rooms; ?>],
            backgroundColor: ['#3b82f6', '#e2e8f0'],
            borderWidth: 0,
        }]
    },
    options: {
        cutout: '72%',
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 12 } } }
        }
    }
});

// Booking status doughnut
const statusColors = {
    'Pending':     '#f59e0b',
    'Confirmed':   '#3b82f6',
    'Checked-In':  '#22c55e',
    'Checked-Out': '#94a3b8',
    'Cancelled':   '#ef4444',
};
const sLabels = <?php echo json_encode($status_labels); ?>;
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: sLabels,
        datasets: [{
            data: <?php echo json_encode($status_counts); ?>,
            backgroundColor: sLabels.map(l => statusColors[l] || '#ccc'),
            borderWidth: 0,
        }]
    },
    options: {
        cutout: '65%',
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 } } }
        }
    }
});

// Monthly revenue bar
new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($monthly_labels); ?>,
        datasets: [{
            label: 'Revenue (BDT)',
            data: <?php echo json_encode($monthly_revenue); ?>,
            backgroundColor: '#3b82f6',
            borderRadius: 5,
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: v => '৳' + v.toLocaleString('en-BD')
                }
            }
        },
        plugins: { legend: { display: false } }
    }
});

<?php if (!empty($srv_labels)): ?>
// Service breakdown
new Chart(document.getElementById('serviceChart'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($srv_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($srv_counts); ?>,
            backgroundColor: ['#3b82f6','#f59e0b','#22c55e','#ef4444','#8b5cf6'],
            borderWidth: 0,
        }]
    },
    options: {
        cutout: '60%',
        plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } }
    }
});
<?php endif; ?>
</script>

</body>
</html>

<?php
// Export.php — Meghdut MRMS
// সরাসরি config/db.php ব্যবহার করে, কোনো model লাগবে না

if (session_status() === PHP_SESSION_NONE) session_start();
include '../db.php';
include '../auth.php';

if (!in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: ../index.php");
    exit();
}

// ── Input ─────────────────────────────────────
$format   = in_array($_GET['format'] ?? '', ['xlsx', 'csv']) ? $_GET['format'] : 'csv';
$status   = $_GET['status']    ?? 'all';
$roomType = $_GET['room_type'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to']   ?? '';

// Allowed columns
$allowedCols = ['name', 'phone', 'room', 'type', 'check_in', 'check_out', 'nights', 'status', 'amount'];
$reqCols     = !empty($_GET['cols']) ? explode(',', $_GET['cols']) : $allowedCols;
$cols        = array_filter($reqCols, fn($c) => in_array($c, $allowedCols));
if (empty($cols)) $cols = $allowedCols;

// Allowed statuses (আপনার DB এর normalized value)
$allowedStatuses = ['all', 'checkedin', 'reserved', 'checkout', 'cancelled'];
if (!in_array($status, $allowedStatuses)) $status = 'all';

// ── Query Build ───────────────────────────────
$where  = ['1=1'];
$params = [];
$types  = '';

// Status filter — DB এ আসল value গুলো map করো
if ($status !== 'all') {
    $dbStatusMap = [
        'checkedin' => ['Checked-In'],
        'reserved'  => ['Confirmed', 'Pending'],
        'checkout'  => ['Checked-Out'],
        'cancelled' => ['Cancelled'],
    ];
    $dbStatuses = $dbStatusMap[$status] ?? [];
    if (!empty($dbStatuses)) {
        $placeholders = implode(',', array_fill(0, count($dbStatuses), '?'));
        $where[] = "b.status IN ($placeholders)";
        foreach ($dbStatuses as $s) {
            $params[] = $s;
            $types   .= 's';
        }
    }
}

if ($roomType !== 'all') {
    $where[]  = 'r.type = ?';
    $params[] = $roomType;
    $types   .= 's';
}

if ($dateFrom) {
    $where[]  = 'b.check_in >= ?';
    $params[] = $dateFrom;
    $types   .= 's';
}

if ($dateTo) {
    $where[]  = 'b.check_in <= ?';
    $params[] = $dateTo;
    $types   .= 's';
}

$whereSQL = implode(' AND ', $where);

$sql = "
    SELECT
        u.Name                                               AS name,
        u.Phone                                              AS phone,
        r.room_number                                        AS room,
        r.type,
        b.check_in,
        b.check_out,
        DATEDIFF(b.check_out, b.check_in)                   AS nights,
        b.status,
        (DATEDIFF(b.check_out, b.check_in) * r.price_per_night) AS amount
    FROM booking b
    INNER JOIN user u ON b.guest_id = u.User_id
    INNER JOIN room r ON b.room_id  = r.room_id
    WHERE $whereSQL
    ORDER BY b.booking_id DESC
";

// ── Execute ───────────────────────────────────
$stmt = mysqli_prepare($conn, $sql);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Status normalize করো (display এর জন্য)
    switch (strtolower($row['status'])) {
        case 'checked-in':
            $row['status'] = 'Checked In';
            break;
        case 'checked-out':
            $row['status'] = 'Checked Out';
            break;
        case 'confirmed':
        case 'pending':
            $row['status'] = 'Reserved';
            break;
        case 'cancelled':
            $row['status'] = 'Cancelled';
            break;
    }
    // শুধু selected columns রাখো
    $filtered = [];
    foreach ($cols as $c) {
        $filtered[$c] = $row[$c] ?? '';
    }
    $rows[] = $filtered;
}

$filename = 'reservations_' . date('Ymd_His');

// ── Output ────────────────────────────────────
$labelMap = [
    'name'      => 'Guest Name',
    'phone'     => 'Phone',
    'room'      => 'Room',
    'type'      => 'Room Type',
    'check_in'  => 'Check-In',
    'check_out' => 'Check-Out',
    'nights'    => 'Nights',
    'status'    => 'Status',
    'amount'    => 'Amount (BDT)',
];

if ($format === 'csv') {
    // ── CSV: সরাসরি stream ─────────────────────
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Cache-Control: no-cache');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

    // Header row
    fputcsv($out, array_map(fn($c) => $labelMap[$c] ?? $c, array_values($cols)));

    // Data rows
    foreach ($rows as $r) {
        fputcsv($out, array_values($r));
    }

    fclose($out);
    exit;
} else {
    // ── Excel: JSON পাঠাও → frontend SheetJS বানাবে ─
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success'  => true,
        'filename' => $filename . '.xlsx',
        'rows'     => $rows,
        'count'    => count($rows),
    ]);
    exit;
}
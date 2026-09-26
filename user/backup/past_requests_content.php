<?php
// past_requests_content.php
// Pure content partial for past service requests tab
// Requires: $conn, $_SESSION already set

$guest_id = $_SESSION['user_id'];

$requests_stmt = $conn->prepare("
    SELECT sr.*, r.room_number
    FROM service_request sr
    JOIN booking b ON sr.booking_id = b.booking_id
    JOIN room r    ON b.room_id = r.room_id
    WHERE sr.guest_id = ?
    ORDER BY sr.requested_at DESC
    LIMIT 20
");
$requests_stmt->bind_param("i", $guest_id);
$requests_stmt->execute();
$past_requests = $requests_stmt->get_result();
$requests_stmt->close();
?>

<?php if ($past_requests->num_rows === 0): ?>
    <div class="text-center py-5">
        <div class="fs-1 mb-3"></div>
        <h5 class="fw-bold">No requests yet</h5>
        <p class="text-muted">Your service and food order history will appear here.</p>
    </div>
<?php else: ?>
    <div class="d-flex flex-column gap-3">
        <?php while ($sr = $past_requests->fetch_assoc()):
            $sr_badge = match ($sr['status']) {
                'Pending'    => 'bg-warning text-dark',
                'Processing' => 'bg-primary',
                'Done'       => 'bg-success',
                default      => 'bg-secondary'
            };
            $type_icon = match ($sr['type']) {
                'food'         => '',
                'housekeeping' => '',
                'extra_towel'  => '',
                'laundry'      => '',
                default        => ''
            };
        ?>
        <div class="card border-0 shadow-sm rounded-4 px-4 py-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="fw-semibold">
                        <?php echo $type_icon; ?>
                        <?php echo ucwords(str_replace('_', ' ', $sr['type'])); ?>
                        <span class="text-muted fw-light small ms-1">
                            Room <?php echo htmlspecialchars($sr['room_number']); ?>
                        </span>
                    </div>
                    <?php if ($sr['description']): ?>
                    <p class="text-muted small mb-1 mt-1">
                        <?php echo htmlspecialchars(substr($sr['description'], 0, 100)); ?>
                        <?php echo strlen($sr['description']) > 100 ? '...' : ''; ?>
                    </p>
                    <?php endif; ?>
                    <small class="text-muted">
                        <?php echo date('d M Y, h:i A', strtotime($sr['requested_at'])); ?>
                        <?php if ($sr['charge'] > 0): ?>
                        &nbsp;·&nbsp;
                        <span class="text-primary fw-semibold">৳<?php echo number_format($sr['charge']); ?></span>
                        <?php endif; ?>
                    </small>
                </div>
                <span class="badge <?php echo $sr_badge; ?> rounded-pill mt-1">
                    <?php echo $sr['status']; ?>
                </span>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
<?php endif; ?>

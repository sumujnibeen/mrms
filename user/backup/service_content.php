<?php
// service_content.php
// Pure content partial for service request tab
// Requires: $conn, $_SESSION, $active_booking already fetched

// Flash messages
$service_msg = $_SESSION['service_success'] ?? '';
$service_err = $_SESSION['service_error']   ?? '';
unset($_SESSION['service_success'], $_SESSION['service_error']);
?>

<?php if ($service_msg): ?>
    <div class="alert alert-success rounded-3 alert-dismissible fade show mb-4">
         <?php echo htmlspecialchars($service_msg); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($service_err): ?>
    <div class="alert alert-danger rounded-3 alert-dismissible fade show mb-4">
         <?php echo htmlspecialchars($service_err); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!$active_booking): ?>
    <div class="text-center py-5">
        <div class="fs-1 mb-3"></div>
        <h5 class="fw-bold">You're not checked in yet</h5>
        <p class="text-muted">Service requests are available once the receptionist checks you in.</p>
    </div>
<?php else: ?>

    <!-- Active stay banner -->
    <div class="card border-0 bg-primary text-white rounded-4 p-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="fs-2"></div>
            <div>
                <p class="mb-0 fw-bold">
                    <?php echo htmlspecialchars($active_booking['room_type']); ?> —
                    Room <?php echo htmlspecialchars($active_booking['room_number']); ?>
                </p>
                <small class="opacity-75">
                    <?php echo date('d M', strtotime($active_booking['check_in'])); ?> →
                    <?php echo date('d M Y', strtotime($active_booking['check_out'])); ?>
                    &nbsp;|&nbsp; Booking #<?php echo $active_booking['booking_id']; ?>
                </small>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-4">
        <h6 class="fw-bold mb-4">What do you need?</h6>
        <form action="../service_submit.php" method="POST">
            <input type="hidden" name="booking_id" value="<?php echo $active_booking['booking_id']; ?>">
            <input type="hidden" name="redirect" value="user/my_stay.php">

            <!-- Service type cards -->
            <div class="row g-2 mb-4">
                <?php
                $srv_types = [
                    'housekeeping' => ['', 'Housekeeping',  'Room cleaning'],
                    'extra_towel'  => ['', 'Extra Towel',   'Towels & linen'],
                    'laundry'      => ['', 'Laundry',       'Wash & fold'],
                    'other'        => ['', 'Other',         'Custom request'],
                ];
                foreach ($srv_types as $val => [$icon, $label, $sub]):
                ?>
                <div class="col-6 col-md-3">
                    <input type="radio" class="btn-check" name="type" id="stype_<?php echo $val; ?>"
                        value="<?php echo $val; ?>" <?php echo $val === 'housekeeping' ? 'checked' : ''; ?>>
                    <label class="btn btn-outline-primary w-100 rounded-3 py-3 text-start"
                        for="stype_<?php echo $val; ?>">
                        <div class="fs-4 mb-1"><?php echo $icon; ?></div>
                        <div class="fw-semibold small"><?php echo $label; ?></div>
                        <div class="text-muted" style="font-size:11px;"><?php echo $sub; ?></div>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold small">Describe your request</label>
                <textarea name="description" class="form-control rounded-3" rows="3"
                    placeholder="E.g. Need 2 extra pillows and blanket..." required></textarea>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary rounded-pill fw-semibold py-2">
                    Submit Request 
                </button>
            </div>
        </form>
    </div>

<?php endif; ?>

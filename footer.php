<?php
// footer.php
// $depth is set by navbar.php before this is included
// Root pages: $depth = ''
// Subfolders (user/, admin/, receptionist/): $depth = '../'
$depth = $depth ?? '';
?>

<footer class="bg-primary text-white pt-5 pb-3 mt-5">
    <div class="container">
        <div class="row g-4">

            <!-- BRAND -->
            <div class="col-md-4">
                <img src="<?php echo $depth; ?>assets/images/logo_white.png" alt="Meghdoot Resort" style="height: 48px;" class="mb-3">
                <h5 class="fw-bold">Meghdoot Resort</h5>
                <p class="small fw-light">
                    A premium hospitality destination offering seamless online booking,
                    in-room dining, and world-class guest services.
                </p>
            </div>

            <!-- QUICK LINKS -->
            <div class="col-md-2">
                <h6 class="fw-bold mb-3 text-white-50 text-uppercase small">Quick Links</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?php echo $depth; ?>index.php" class="text-white text-decoration-none small">Home</a></li>
                    <li class="mb-2"><a href="<?php echo $depth; ?>rooms.php" class="text-white text-decoration-none small">Rooms</a></li>
                    <li class="mb-2"><a href="<?php echo $depth; ?>food_menu.php" class="text-white text-decoration-none small">Food Menu</a></li>
                    <li class="mb-2"><a href="<?php echo $depth; ?>announcement.php" class="text-white text-decoration-none small">Announcements</a></li>
                    <li class="mb-2"><a href="<?php echo $depth; ?>login.php" class="text-white text-decoration-none small">Login</a></li>
                </ul>
            </div>

            <!-- CONTACT -->
            <div class="col-md-3">
                <h6 class="fw-bold mb-3 text-white-50 text-uppercase small">Contact</h6>
                <ul class="list-unstyled small fw-light">
                    <li class="mb-2">Meghdoot Resort, Sylhet, Bangladesh</li>
                    <li class="mb-2">+880 1700-000000</li>
                    <li class="mb-2">info@meghdootresort.com</li>
                </ul>
            </div>

            <!-- GROUP INFO -->
            <div class="col-md-3">
                <h6 class="fw-bold mb-3 text-white-50 text-uppercase small">Information Systems Design</h6>
                <p class="small fw-light mb-1">Made by</p>
                <p class="small fw-light mb-2">
                    <span class="fw-semibold">Group:</span> 06
                </p>
                <ul class="list-unstyled small fw-light">
                    <li>01 &mdash; Prothoma Akter &nbsp;<span class="text-white-50">202223104002</span></li>
                    <li>02 &mdash; Nadira Khanom &nbsp;<span class="text-white-50">202223104003</span></li>
                    <li>03 &mdash; Forhadurzzaman &nbsp;<span class="text-white-50">202223104022</span></li>
                    <li>04 &mdash; Shafiul Mujnibeen &nbsp;<span class="text-white-50">202223104030</span></li>
                </ul>
            </div>

        </div>
    </div>
</footer>

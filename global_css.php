<?php
// global_css.php
// Only styles that Bootstrap cannot handle
?>

<style>
    /* Hero background overlay — Bootstrap has no utility for this */
    .hero-overlay {
        background: rgba(0, 0, 0, 0.55);
    }

    /* Object-fit cover for card images — Bootstrap 5.3 has this but older browsers need it */
    .img-cover {
        object-fit: cover;
    }
</style>
<?php
/*
|--------------------------------------------------------------------------
| Shared Footer
|--------------------------------------------------------------------------
| Set $base before including this file depending on folder depth, same as
| includes/navbar.php.
*/
if (!isset($base)) {
    $base = "";
}
$isLoggedIn = isset($_SESSION['user_id']);
?>
<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <a href="<?= $base ?>index.php" class="logo">
                <span class="blue-letter">B</span>lue<span class="blue-letter">C</span>o<img
                src="<?= $base ?>assets/images/blue-tie.png" class="tie-logo" alt=""><img
                src="<?= $base ?>assets/images/blue-tie.png" class="tie-logo" alt="">ar<span class="visually-hidden">-Hire</span>
            </a>
            <p>A simple way to find and hire trusted skilled workers for the jobs that need doing.</p>
        </div>

        <div class="footer-links">
            <div class="footer-col">
                <h4>Explore</h4>
                <a href="<?= $base ?>index.php">Home</a>
                <a href="<?= $base ?>browse.php">Browse Workers</a>
            </div>
            <div class="footer-col">
                <h4>Account</h4>
                <?php if ($isLoggedIn): ?>
                    <a href="<?= $base ?>user/dashboard.php">Dashboard</a>
                    <a href="<?= $base ?>logout.php">Logout</a>
                <?php else: ?>
                    <a href="<?= $base ?>login.php">Login</a>
                    <a href="<?= $base ?>register.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; <?= date("Y") ?> BlueCollar-Hire. All rights reserved.
    </div>
</footer>

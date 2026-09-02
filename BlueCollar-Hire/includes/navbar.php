<?php
/*
|--------------------------------------------------------------------------
| Shared Navbar
|--------------------------------------------------------------------------
| Set $base before including this file depending on folder depth:
|   root pages        -> $base = "";
|   user/ or admin/   -> $base = "../";
| session.php must already be included so $_SESSION is available.
| Optionally set $activePage (e.g. "home", "browse", "dashboard") to
| highlight the current nav item.
*/
if (!isset($base)) {
    $base = "";
}
if (!isset($activePage)) {
    $activePage = "";
}
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin    = $isLoggedIn && $_SESSION['role'] === "admin";
$isWorker   = $isLoggedIn && $_SESSION['role'] === "worker";
?>
<a href="#main-content" class="skip-link">Skip to main content</a>
<header class="site-header">
    <nav class="navbar" aria-label="Main navigation">
        <a href="<?= $base ?>index.php" class="logo">
            <span class="blue-letter">B</span>lue<span class="blue-letter">C</span>o<img
                src="<?= $base ?>assets/images/blue-tie.png" class="tie-logo" alt=""><img
                src="<?= $base ?>assets/images/blue-tie.png" class="tie-logo" alt="">ar<span class="visually-hidden">-Hire</span>
        </a>

        <button type="button" class="nav-toggle" id="nav-toggle" aria-expanded="false" aria-controls="nav-menu">
            <span class="visually-hidden">Toggle menu</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>

        <ul class="nav-menu" id="nav-menu">
            <li><a href="<?= $base ?>index.php" class="nav-link"<?= $activePage === "home" ? ' aria-current="page"' : '' ?>>Home</a></li>

            <li>
                <?php if ($isWorker): ?>
                    <a href="<?= $base ?>browse.php" class="nav-link"<?= $activePage === "browse" ? ' aria-current="page"' : '' ?>>Other Workers</a>
                <?php else: ?>
                    <a href="<?= $base ?>browse.php" class="nav-link"<?= $activePage === "browse" ? ' aria-current="page"' : '' ?>>Browse</a>
                <?php endif; ?>
            </li>

            <?php if ($isLoggedIn): ?>
                <li class="nav-divider" aria-hidden="true"></li>
                <li>
                    <?php if ($isAdmin): ?>
                        <a href="<?= $base ?>admin/dashboard.php" class="nav-link"<?= $activePage === "dashboard" ? ' aria-current="page"' : '' ?>>Admin Panel</a>
                    <?php else: ?>
                        <a href="<?= $base ?>user/dashboard.php" class="nav-link"<?= $activePage === "dashboard" ? ' aria-current="page"' : '' ?>>Dashboard</a>
                    <?php endif; ?>
                </li>
                <li><a href="<?= $base ?>logout.php" class="nav-btn logout-btn">Logout</a></li>
            <?php else: ?>
                <li class="nav-divider" aria-hidden="true"></li>
                <li><a href="<?= $base ?>login.php" class="nav-btn login-btn">Login</a></li>
                <li><a href="<?= $base ?>register.php" class="nav-btn register-btn">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

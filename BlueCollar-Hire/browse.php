<?php
require_once "config/session.php";

// Load categories for the filter dropdown
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name ASC");

// Optional category filter
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;

if ($category_id > 0) {

    $stmt = mysqli_prepare($conn, "
        SELECT
            worker_profiles.*,
            users.name,
            users.avatar,

            GROUP_CONCAT(
                DISTINCT categories.name
                ORDER BY categories.name ASC
                SEPARATOR ', '
            ) AS categories

        FROM worker_profiles

        JOIN users
            ON worker_profiles.user_id = users.id

        JOIN worker_categories
            ON worker_profiles.id = worker_categories.worker_profile_id

        JOIN categories
            ON worker_categories.category_id = categories.id

        WHERE
            worker_profiles.status = 'Approved'
            AND categories.id = ?

        GROUP BY worker_profiles.id

        ORDER BY worker_profiles.created_at DESC
    ");

    mysqli_stmt_bind_param($stmt, "i", $category_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

} else {

    $result = mysqli_query($conn, "
        SELECT
            worker_profiles.*,
            users.name,
            users.avatar,

            GROUP_CONCAT(
                DISTINCT categories.name
                ORDER BY categories.name ASC
                SEPARATOR ', '
            ) AS categories

        FROM worker_profiles

        JOIN users
            ON worker_profiles.user_id = users.id

        JOIN worker_categories
            ON worker_profiles.id = worker_categories.worker_profile_id

        JOIN categories
            ON worker_categories.category_id = categories.id

        WHERE worker_profiles.status = 'Approved'

        GROUP BY worker_profiles.id

        ORDER BY worker_profiles.created_at DESC
    ");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Workers | BlueCollar-Hire</title>
    <meta name="description" content="Browse verified skilled workers by category and find the right professional for your job.">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
</head>

<body>

    <?php $base = ""; $activePage = "browse"; include "includes/navbar.php"; ?>

    <main id="main-content">
        <div class="page-wrap">

            <div class="page-head">
                <h1>Find Skilled Workers</h1>
                <p>Browse verified professionals, filtered by category.</p>
            </div>

            <form method="GET" class="filter-bar">
                <label for="category-filter">Category</label>
                <select name="category" id="category-filter" onchange="this.form.submit()">
                    <option value="0">All Categories</option>
                    <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? "selected" : "" ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <noscript><button type="submit" class="btn secondary small">Filter</button></noscript>
            </form>

            <div class="worker-container">

                <?php if (mysqli_num_rows($result) > 0): ?>

                    <?php while ($worker = mysqli_fetch_assoc($result)):
                        $availability = $worker['availability'];
                        $availClass = strtolower($availability) === "available" ? "available" : "booked";
                    ?>

                        <div class="worker-card">
<div class="avatar-circle">
    <?php if (
        !empty($worker['avatar']) &&
        strpos($worker['avatar'], 'uploads/profile/') === 0
    ): ?>

        <img
            src="<?= e($worker['avatar']) ?>"
            alt="<?= e($worker['name']) ?>'s profile photo"
        >

    <?php elseif (!empty($worker['avatar'])): ?>

        <span class="avatar-emoji">
            <?= e($worker['avatar']) ?>
        </span>

    <?php else: ?>

        <i class="fa-solid fa-user"></i>

    <?php endif; ?>
</div>

<h3><?= e($worker['name']) ?></h3>
                            <div class="worker-professions">
    <?php
    $workerCategories = array_map(
        'trim',
        explode(',', $worker['categories'] ?? '')
    );
    ?>

    <?php foreach ($workerCategories as $profession): ?>
        <?php if ($profession !== ""): ?>
            <span class="category-tag">
                <?= e($profession) ?>
            </span>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

                            <div class="meta-list">
                                <p><i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i> Rs. <?= e($worker['daily_rate']) ?>/day</p>
                                <p><i class="fa-solid fa-star" aria-hidden="true"></i> <?= e($worker['experience']) ?> years experience</p>
                                <p><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <?= e($worker['address']) ?></p>
                                <p><span class="badge <?= $availClass ?>"><?= e($availability) ?></span></p>
                            </div>

                            <a class="btn primary" href="worker.php?id=<?= $worker['id'] ?>">View Profile</a>
                        </div>

                    <?php endwhile; ?>

                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-user-slash" aria-hidden="true"></i>
                        <h3>No workers available</h3>
                        <p>Try a different category, or check back soon.</p>
                    </div>
                <?php endif; ?>

            </div>

        </div>
    </main>

    <?php $base = ""; include "includes/footer.php"; ?>
    <script src="assets/js/main.js" defer></script>

</body>

</html>

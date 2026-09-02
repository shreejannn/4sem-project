<?php
require_once "config/session.php";

if (!isset($_GET['id'])) {
    header("Location: browse.php");
    exit();
}

$id = intval($_GET['id']);

$stmt = mysqli_prepare($conn, "
    SELECT
        worker_profiles.*,
        users.name,
        users.avatar,
        users.phone,

        GROUP_CONCAT(
            DISTINCT categories.name
            ORDER BY categories.name
            SEPARATOR ', '
        ) AS category

    FROM worker_profiles

    JOIN users
        ON worker_profiles.user_id = users.id

    JOIN worker_categories
        ON worker_profiles.id =
           worker_categories.worker_profile_id

    JOIN categories
        ON worker_categories.category_id =
           categories.id

    WHERE worker_profiles.id = ?
      AND worker_profiles.status = 'Approved'

    GROUP BY worker_profiles.id
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: browse.php");
    exit();
}

$worker = mysqli_fetch_assoc($result);
$isAvailable = $worker['availability'] == "Available";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($worker['name']) ?> | BlueCollar-Hire</title>
    <meta name="description" content="View <?= e($worker['name']) ?>'s profile, rate, experience and availability on BlueCollar-Hire.">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
</head>

<body>

    <?php $base = ""; $activePage = "browse"; include "includes/navbar.php"; ?>

    <main id="main-content">
        <div class="page-wrap" style="max-width: 900px;">

            <p class="breadcrumb"><a href="browse.php">&larr; Back to Browse</a></p>

            <div class="worker-details">

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

                <div class="worker-info">

                    <span class="category-tag"><?= e($worker['category']) ?></span>
                    <h1><?= e($worker['name']) ?></h1>

                    <?php if (!empty($worker['bio'])): ?>
                        <p class="bio"><?= nl2br(e($worker['bio'])) ?></p>
                    <?php endif; ?>

                    <p class="rate">Rs. <?= e($worker['daily_rate']) ?> <small>/ day</small></p>

                    <dl class="info-list">
                        <div>
                            <dt>Experience</dt>
                            <dd><?= e($worker['experience']) ?> years</dd>
                        </div>
                        <div>
                            <dt>Availability</dt>
                            <dd>
                                <?php if ($isAvailable): ?>
                                    <span class="badge available">Available</span>
                                <?php else: ?>
                                    <span class="badge booked">Busy</span>
                                <?php endif; ?>
                            </dd>
                        </div>
                        <div>
                            <dt>Address</dt>
                            <dd><?= e($worker['address']) ?></dd>
                        </div>
                        <div>
                            <dt>Phone</dt>
                            <dd><?= e($worker['phone']) ?></dd>
                        </div>
                    </dl>

                    <?php if (!isset($_SESSION['user_id'])): ?>

                        <a class="btn primary" href="login.php">Login to Hire</a>

                    <?php elseif ($_SESSION['role'] == "client"): ?>

                        <a class="btn primary" href="hire_worker.php?id=<?= $worker['id'] ?>">Hire Worker</a>

                         

                    <?php else: ?>

                        <button class="btn primary is-disabled" disabled aria-disabled="true">
                            Only clients can hire workers
                        </button>

                    <?php endif; ?>

                </div>

            </div>

        </div>
    </main>

    <?php $base = ""; include "includes/footer.php"; ?>
    <script src="assets/js/main.js" defer></script>

</body>

</html>

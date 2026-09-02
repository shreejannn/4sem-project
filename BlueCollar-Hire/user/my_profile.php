<?php
require_once "../config/session.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "worker") {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = mysqli_prepare($conn, "
    SELECT
        worker_profiles.*,
        users.name,
        users.email,
        users.phone,
        users.avatar,
        GROUP_CONCAT(
            DISTINCT categories.name
            ORDER BY categories.name
            SEPARATOR ', '
        ) AS category

    FROM worker_profiles

    JOIN users
        ON worker_profiles.user_id = users.id

    LEFT JOIN worker_categories
        ON worker_profiles.id = worker_categories.worker_profile_id

    LEFT JOIN categories
        ON worker_categories.category_id = categories.id

    WHERE worker_profiles.user_id = ?

    GROUP BY worker_profiles.id
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: create_worker_profile.php");
    exit();
}

$worker = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | BlueCollar-Hire</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
</head>

<body>

    <?php $base = "../"; $activePage = "dashboard"; include "../includes/navbar.php"; ?>

    <main id="main-content">
        <div class="page-wrap" style="max-width: 900px;">

            <div class="worker-details">

<div class="avatar-circle">
    <?php if (!empty($worker['avatar'])): ?>

        <img
            src="../<?= e($worker['avatar']) ?>"
            alt="<?= e($worker['name']) ?>'s profile photo"
        >

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

                    <p class="breadcrumb"><a href="dashboard.php">&larr; Back to Dashboard</a></p>

                    <p class="rate">Rs. <?= e($worker['daily_rate']) ?> <small>/ day</small></p>

                    <dl class="info-list">
                        <div>
                            <dt>Experience</dt>
                            <dd><?= e($worker['experience']) ?> years</dd>
                        </div>
                        <div>
                            <dt>Email</dt>
                            <dd><?= e($worker['email']) ?></dd>
                        </div>
                        <div>
                            <dt>Phone</dt>
                            <dd><?= e($worker['phone']) ?></dd>
                        </div>
                        <div>
                            <dt>Address</dt>
                            <dd><?= e($worker['address']) ?></dd>
                        </div>
                        <div>
                            <dt>Availability</dt>
                            <dd>
                                <?php if ($worker['availability'] == "Available"): ?>
                                    <span class="badge available">Available</span>
                                <?php else: ?>
                                    <span class="badge booked">Busy</span>
                                <?php endif; ?>
                            </dd>
                        </div>
                        <div>
                            <dt>Approval Status</dt>
                            <dd>
                                <?php if ($worker['status'] == "Approved"): ?>
                                    <span class="badge approved">Approved</span>
                                <?php elseif ($worker['status'] == "Rejected"): ?>
                                    <span class="badge rejected">Rejected</span>
                                <?php else: ?>
                                    <span class="badge pending">Pending Approval</span>
                                <?php endif; ?>
                            </dd>
                        </div>
                    </dl>

                    <a class="btn primary" href="edit_profile.php">Edit Profile</a>

                </div>

            </div>

        </div>
    </main>

    <?php $base = "../"; include "../includes/footer.php"; ?>
    <script src="../assets/js/main.js" defer></script>

</body>

</html>

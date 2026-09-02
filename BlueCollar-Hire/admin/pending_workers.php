<?php
require_once "../config/session.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: ../login.php");
    exit();
}

$result = mysqli_query($conn, "

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
        ON worker_profiles.id =
           worker_categories.worker_profile_id

    LEFT JOIN categories
        ON worker_categories.category_id =
           categories.id

    WHERE worker_profiles.status = 'Pending'

    GROUP BY worker_profiles.id

    ORDER BY worker_profiles.created_at ASC

");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Workers | BlueCollar-Hire Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
</head>

<body>

    <?php $base = "../"; $activePage = "dashboard"; include "../includes/navbar.php"; ?>

    <main id="main-content">
        <div class="page-wrap" style="max-width: 900px;">

            <div class="page-head" style="text-align:left;">
                <h1>Pending Worker Approvals</h1>
                <p>Review new worker profiles before they go live on the platform.</p>
            </div>

            <p class="breadcrumb"><a href="dashboard.php">&larr; Back to Dashboard</a></p>


            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert success" role="status"><?= e($_SESSION['success']) ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (mysqli_num_rows($result) > 0): ?>

                <?php while ($worker = mysqli_fetch_assoc($result)): ?>

                    <div class="request-card">
                        <div class="request-head">
                            <h3>
    <?php if (!empty($worker['avatar']) && strpos($worker['avatar'], 'uploads/profile/') === 0): ?>

        <img
            src="../<?= e($worker['avatar']) ?>"
            alt="<?= e($worker['name']) ?>"
            style="
                width:60px;
                height:60px;
                border-radius:50%;
                object-fit:cover;
                vertical-align:middle;
                margin-right:8px;
            "
        >

    <?php else: ?>

        <span
            style="
                font-size:30px;
                vertical-align:middle;
                margin-right:8px;
            "
            aria-hidden="true"
        >
            <?= e($worker['avatar'] ?? '👤') ?>
        </span>

    <?php endif; ?>
    <br>

    <?= e($worker['name']) ?> — <?= e($worker['category'] ?? 'Worker') ?>
</h3>
                        </div>
                        <p class="meta"><strong>Email:</strong> <?= e($worker['email']) ?></p>
                        <p class="meta"><strong>Phone:</strong> <?= e($worker['phone']) ?></p>
                        <p class="meta"><strong>Experience:</strong> <?= e($worker['experience']) ?> years</p>
                        <p class="meta"><strong>Daily Rate:</strong> Rs. <?= e($worker['daily_rate']) ?></p>
                        <p class="meta"><strong>Address:</strong> <?= e($worker['address']) ?></p>
                        <?php if (!empty($worker['bio'])): ?>
                            <p class="meta"><strong>Bio:</strong> <?= nl2br(e($worker['bio'])) ?></p>
                        <?php endif; ?>

                        <div class="request-actions">
                            <a class="btn-accept" href="approve_worker.php?id=<?= $worker['id'] ?>">Approve</a>
                            <a class="btn-reject" href="reject_worker.php?id=<?= $worker['id'] ?>" onclick="return confirm('Reject this worker profile?');">Reject</a>
                        </div>
                    </div>

                <?php endwhile; ?>

            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    <h3>All caught up</h3>
                    <p>No pending worker profiles right now.</p>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <?php $base = "../"; include "../includes/footer.php"; ?>
    <script src="../assets/js/main.js" defer></script>

</body>

</html>

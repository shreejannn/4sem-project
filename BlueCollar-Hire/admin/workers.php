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

    GROUP BY worker_profiles.id

    ORDER BY worker_profiles.created_at DESC

");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Workers | BlueCollar-Hire Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
</head>

<body>

    <?php $base = "../"; $activePage = "dashboard"; include "../includes/navbar.php"; ?>

    <main id="main-content">
        <div class="page-wrap">

            <div class="page-head" style="text-align:left;">
                <h1>All Worker Profiles</h1>
                <p>Every worker profile on the platform, regardless of status.</p>
            </div>
            <p class="breadcrumb"><a href="dashboard.php">&larr; Back to Dashboard</a></p>

            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Category</th>
                                <th scope="col">Daily Rate</th>
                                <th scope="col">Experience</th>
                                <th scope="col">Availability</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($worker = mysqli_fetch_assoc($result)):
                                $availClass = strtolower($worker['availability']) === "available" ? "available" : "booked";
                            ?>
                                <tr>
                                    <td><?= e($worker['name']) ?></td>
                                    <td><?= e($worker['category']) ?></td>
                                    <td>Rs. <?= e($worker['daily_rate']) ?></td>
                                    <td><?= e($worker['experience']) ?> yrs</td>
                                    <td><span class="badge <?= $availClass ?>"><?= e($worker['availability']) ?></span></td>
                                    <td>
                                        <?php if ($worker['status'] == "Approved"): ?>
                                            <span class="badge approved">Approved</span>
                                        <?php elseif ($worker['status'] == "Rejected"): ?>
                                            <span class="badge rejected">Rejected</span>
                                        <?php else: ?>
                                            <span class="badge pending">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-users-slash" aria-hidden="true"></i>
                    <h3>No worker profiles yet</h3>
                    <p>Worker profiles will appear here once created.</p>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <?php $base = "../"; include "../includes/footer.php"; ?>
    <script src="../assets/js/main.js" defer></script>

</body>

</html>

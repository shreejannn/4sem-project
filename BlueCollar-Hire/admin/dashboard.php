<?php
require_once "../config/session.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: ../login.php");
    exit();
}

$total_users    = mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role != 'admin'")->fetch_assoc()['c'];
$total_workers  = mysqli_query($conn, "SELECT COUNT(*) AS c FROM worker_profiles")->fetch_assoc()['c'];
$pending_count  = mysqli_query($conn, "SELECT COUNT(*) AS c FROM worker_profiles WHERE status = 'Pending'")->fetch_assoc()['c'];
$request_count  = mysqli_query($conn, "SELECT COUNT(*) AS c FROM work_requests")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | BlueCollar-Hire</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
</head>

<body>

    <?php $base = "../"; $activePage = "dashboard"; include "../includes/navbar.php"; ?>

    <main id="main-content">
        <div class="dashboard">

            <div class="sidebar">
                <div class="profile-card">
                    <div class="avatar-circle" aria-hidden="true"><?= e($_SESSION['avatar']) ?></div>
                    <h2><?= e($_SESSION['name']) ?></h2>
                    <span class="role-tag"><i class="fa-solid fa-user-shield" aria-hidden="true"></i> Administrator</span>

                    <hr>

                    <nav class="menu" aria-label="Admin menu">
                        <a href="dashboard.php"><i class="fa-solid fa-gauge" aria-hidden="true"></i> Dashboard</a>
                        <a href="pending_workers.php"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i> Pending Workers</a>
                        <a href="workers.php"><i class="fa-solid fa-users-gear" aria-hidden="true"></i> All Workers</a>
                        <a href="users.php"><i class="fa-solid fa-users" aria-hidden="true"></i> Manage Users</a>
                        <a href="../logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Logout</a>
                    </nav>
                </div>
            </div>

            <div class="content">

                <div class="welcome">
                    <h1>Welcome, Admin <span aria-hidden="true">👋</span></h1>
                    <p>Approve worker profiles and manage all users on the platform from here.</p>
                </div>

                <div class="summary">

                    <div class="box">
                        <h3>Total Users</h3>
                        <p class="stat-number"><?= $total_users ?></p>
                    </div>

                    <div class="box">
                        <h3>Worker Profiles</h3>
                        <p class="stat-number"><?= $total_workers ?></p>
                    </div>

                    <div class="box">
                        <h3>Pending Approvals</h3>
                        <p class="stat-number warn"><?= $pending_count ?></p>
                    </div>

                    <div class="box">
                        <h3>Total Work Requests</h3>
                        <p class="stat-number"><?= $request_count ?></p>
                    </div>

                </div>

                <div class="actions">
                    <a class="action" href="pending_workers.php"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>Review Pending Workers</a>
                    <a class="action" href="workers.php"><i class="fa-solid fa-users-gear" aria-hidden="true"></i>View All Workers</a>
                    <a class="action" href="users.php"><i class="fa-solid fa-users" aria-hidden="true"></i>Manage Users</a>
                </div>

            </div>

        </div>
    </main>

    <?php $base = "../"; include "../includes/footer.php"; ?>
    <script src="../assets/js/main.js" defer></script>

</body>

</html>

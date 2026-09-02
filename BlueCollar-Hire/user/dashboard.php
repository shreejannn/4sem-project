<?php
require_once "../config/session.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] == "admin") {
    header("Location: ../admin/dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_stmt_get_result($stmt)->fetch_assoc();

$worker = null;

if ($user['role'] == "worker") {
$stmt = mysqli_prepare($conn, "
    SELECT
        worker_profiles.*,

        GROUP_CONCAT(
            DISTINCT categories.name
            ORDER BY categories.name
            SEPARATOR ', '
        ) AS category_name

    FROM worker_profiles

    LEFT JOIN worker_categories
        ON worker_profiles.id =
           worker_categories.worker_profile_id

    LEFT JOIN categories
        ON worker_categories.category_id =
           categories.id

    WHERE worker_profiles.user_id = ?

    GROUP BY worker_profiles.id
");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $wr = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($wr) > 0) {
        $worker = mysqli_fetch_assoc($wr);
    }
}

// Small stats
$pending_count = 0;
if ($worker) {
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM work_requests WHERE worker_profile_id = ? AND status = 'Pending'");
    mysqli_stmt_bind_param($stmt, "i", $worker['id']);
    mysqli_stmt_execute($stmt);
    $pending_count = mysqli_stmt_get_result($stmt)->fetch_assoc()['c'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | BlueCollar-Hire</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
<style>      
   .avatar-circle {
            
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f1f1;
            border: 3px solid #e5e7eb;
        }
</style>

</head>

<body>

    <?php $base = "../"; $activePage = "dashboard"; include "../includes/navbar.php"; ?>

    <main id="main-content">
        <div class="dashboard">

            <div class="sidebar">
                <div class="profile-card">

                <div class="avatar-circle">
    <?php if (
        !empty($user['avatar']) &&
        strpos($user['avatar'], 'uploads/profile/') === 0
    ): ?>

        <img
            src="../<?= e($user['avatar']) ?>"
            alt="<?= e($user['name']) ?>'s profile photo"
        >

    <?php elseif (!empty($user['avatar'])): ?>

        <span class="avatar-emoji">
            <?= e($user['avatar']) ?>
        </span>

    <?php else: ?>

        <i class="fa-solid fa-user"></i>

    <?php endif; ?>
</div>
                <h2><?= e($user['name']) ?></h2>
                    <span class="role-tag"><?= ucfirst($user['role']) ?></span>

                    <div class="contact-list">
                        <p><i class="fa-solid fa-envelope" aria-hidden="true"></i> <?= e($user['email']) ?></p>
                        <p><i class="fa-solid fa-phone" aria-hidden="true"></i> <?= e($user['phone']) ?></p>
                    </div>

                    <hr>

                    <nav class="menu" aria-label="Dashboard menu">

                        <?php if ($user['role'] == "client"): ?>
                            <a href="../browse.php"><i class="fa-solid fa-users" aria-hidden="true"></i> Browse Workers</a>
                            <a href="../my_requests.php"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> My Requests</a>
                            <a href="profile.php"><i class="fa-solid fa-user-pen" aria-hidden="true"></i> Edit Profile</a>
                        <?php endif; ?>

                        <?php if ($user['role'] == "worker"): ?>
                            <?php if ($worker == null): ?>
                                <a href="create_worker_profile.php"><i class="fa-solid fa-id-card" aria-hidden="true"></i> Create Worker Profile</a>
                            <?php else: ?>
                                <a href="my_profile.php"><i class="fa-solid fa-user" aria-hidden="true"></i> My Profile</a>
                                <a href="work_requests.php"><i class="fa-solid fa-briefcase" aria-hidden="true"></i> Work Requests</a>
                                <a href="edit_profile.php"><i class="fa-solid fa-pen" aria-hidden="true"></i> Edit Worker Profile</a>
                            <?php endif; ?>
                        <?php endif; ?>

                        <a href="../logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Logout</a>

                    </nav>

                </div>
            </div>

            <div class="content">

                <div class="welcome">

                    <h1>Welcome, <?= e($user['name']) ?> <span aria-hidden="true">👋</span></h1>

                    <?php if ($user['role'] == "client"): ?>

                        <p>Browse skilled workers, send work requests and manage all your hiring requests from one place.</p>

                        <div class="actions">
                            <a class="action" href="../browse.php"><i class="fa-solid fa-users" aria-hidden="true"></i>Browse Workers</a>
                            <a class="action" href="../my_requests.php"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>My Requests</a>
                            <a class="action" href="profile.php"><i class="fa-solid fa-user-pen" aria-hidden="true"></i>Edit Profile</a>
                        </div>

                        <div class="summary">
                            <div class="box">
                                <h3>Account</h3>
                                <p><strong>Role:</strong> Client</p>
                                <p><strong>Email:</strong> <?= e($user['email']) ?></p>
                                <p><strong>Phone:</strong> <?= e($user['phone']) ?></p>
                            </div>
                            <div class="box">
                                <h3>Quick Tip</h3>
                                <p>Browse workers by category and hire the worker that best matches your work.</p>
                            </div>
                        </div>

                    <?php elseif ($user['role'] == "worker"): ?>

                        <?php if ($worker == null): ?>

                            <p>Your worker profile has not been created yet. Create it before clients can hire you.</p>

                            <div class="actions">
                                <a class="action" href="create_worker_profile.php">
                                    <i class="fa-solid fa-id-card" aria-hidden="true"></i>Create Worker Profile
                                </a>
                            </div>

                        <?php else: ?>

                            <p>Manage your worker profile and receive work requests from clients.</p>

                            <div class="actions">
                                <a class="action" href="my_profile.php"><i class="fa-solid fa-user" aria-hidden="true"></i>My Profile</a>
                                <a class="action" href="work_requests.php"><i class="fa-solid fa-briefcase" aria-hidden="true"></i>Work Requests (<?= $pending_count ?> new)</a>
                                <a class="action" href="edit_profile.php"><i class="fa-solid fa-pen" aria-hidden="true"></i>Edit Profile</a>
                            </div>

                            <div class="summary">
                                <div class="box">
                                    <h3>Profile Information</h3>
                                    <p><strong>Category:</strong> <?= e($worker['category_name']) ?></p>
                                    <p><strong>Experience:</strong> <?= e($worker['experience']) ?> Years</p>
                                    <p><strong>Daily Rate:</strong> Rs. <?= e($worker['daily_rate']) ?></p>
                                </div>
                                <div class="box">
                                    <h3>Availability</h3>
                                    <p>
                                        <strong>Approval Status:</strong>
                                        <?php if ($worker['status'] == "Approved"): ?>
                                            <span class="badge approved">Approved</span>
                                        <?php elseif ($worker['status'] == "Rejected"): ?>
                                            <span class="badge rejected">Rejected</span>
                                        <?php else: ?>
                                            <span class="badge pending">Pending Approval</span>
                                        <?php endif; ?>
                                    </p>
                                    <p><strong>Work Status:</strong> <?= e($worker['availability']) ?></p>
                                    <p><strong>Address:</strong> <?= e($worker['address']) ?></p>
                                </div>
                                <div class="box">
                                    <h3>About You</h3>
                                    <p><?= nl2br(e($worker['bio'])) ?></p>
                                </div>
                            </div>

                        <?php endif; ?>

                    <?php endif; ?>

                </div>

            </div>

        </div>
    </main>

    <?php $base = "../"; include "../includes/footer.php"; ?>
    <script src="../assets/js/main.js" defer></script>

</body>

</html>

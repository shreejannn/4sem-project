<?php
require_once "../config/session.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "worker") {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Find this worker's profile
$stmt = mysqli_prepare($conn, "SELECT id FROM worker_profiles WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$profile = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$profile) {
    header("Location: create_worker_profile.php");
    exit();
}

$worker_profile_id = $profile['id'];

$stmt = mysqli_prepare($conn, "
    SELECT work_requests.*, users.name AS client_name, users.phone AS client_phone, users.avatar
    FROM work_requests
    JOIN users ON work_requests.client_id = users.id
    WHERE work_requests.worker_profile_id = ?
    ORDER BY work_requests.created_at DESC
");
mysqli_stmt_bind_param($stmt, "i", $worker_profile_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Requests | BlueCollar-Hire</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
</head>

<body>

    <?php $base = "../"; $activePage = "dashboard"; include "../includes/navbar.php"; ?>

    <main id="main-content">
        <div class="page-wrap" style="max-width: 800px;">

            <div class="page-head" style="text-align:left;">
                <h1>Work Requests</h1>
                <p>Review and respond to requests sent by clients.</p>
            </div>
            <p class="breadcrumb"><a href="dashboard.php">&larr; Back to Dashboard</a></p>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert success" role="status"><?= e($_SESSION['success']) ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (mysqli_num_rows($result) > 0): ?>

                <?php while ($req = mysqli_fetch_assoc($result)): ?>

                    <div class="request-card">
                        <div class="request-head">
                            <h3><span aria-hidden="true"><?= e($req['avatar']) ?></span> <?= e($req['client_name']) ?></h3>
                            <span class="badge <?= strtolower($req['status']) ?>"><?= e($req['status']) ?></span>
                        </div>
                        <p class="meta"><strong>Date needed:</strong> <?= e($req['work_date']) ?></p>
                        <p class="meta"><strong>Location:</strong> <?= e($req['location']) ?></p>
                        <?php if (!empty($req['message'])): ?>
                            <p class="meta"><strong>Message:</strong> <?= e($req['message']) ?></p>
                        <?php endif; ?>
                        <p class="meta"><strong>Client phone:</strong> <?= e($req['client_phone']) ?></p>

                        <?php if ($req['status'] == "Pending"): ?>
                            <div class="request-actions">
                                <a class="btn-accept" href="accept_request.php?id=<?= $req['id'] ?>">Accept</a>
                                <a class="btn-reject" href="reject_request.php?id=<?= $req['id'] ?>" onclick="return confirm('Reject this work request?');">Reject</a>
                            </div>
                        <?php endif; ?>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-inbox" aria-hidden="true"></i>
                    <h3>No work requests yet</h3>
                    <p>Requests from clients will show up here.</p>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <?php $base = "../"; include "../includes/footer.php"; ?>
    <script src="../assets/js/main.js" defer></script>

</body>

</html>

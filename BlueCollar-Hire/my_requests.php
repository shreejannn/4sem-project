<?php
require_once "config/session.php";

/* -------------------------------------------------
   CLIENT ACCESS ONLY
   ------------------------------------------------- */

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== "client") {
    header("Location: user/dashboard.php");
    exit();
}

$client_id = $_SESSION['user_id'];


/* -------------------------------------------------
   LOAD CLIENT'S WORK REQUESTS
   ------------------------------------------------- */

$stmt = mysqli_prepare($conn, "

    SELECT
        work_requests.*,

        worker.name AS worker_name,
        worker.avatar AS worker_avatar,

        client.phone AS client_phone,

        GROUP_CONCAT(
            DISTINCT categories.name
            ORDER BY categories.name
            SEPARATOR ', '
        ) AS category

    FROM work_requests

    JOIN worker_profiles
        ON work_requests.worker_profile_id =
           worker_profiles.id

    JOIN users AS worker
        ON worker_profiles.user_id =
           worker.id

    JOIN users AS client
        ON work_requests.client_id =
           client.id

    LEFT JOIN worker_categories
        ON worker_profiles.id =
           worker_categories.worker_profile_id

    LEFT JOIN categories
        ON worker_categories.category_id =
           categories.id

    WHERE work_requests.client_id = ?

    GROUP BY work_requests.id

    ORDER BY work_requests.created_at DESC

");

if (!$stmt) {
    die("Database query failed.");
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $client_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Requests | BlueCollar-Hire</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

</head>


<body>


<?php

$base = "";

include "includes/navbar.php";

?>


<main id="main-content">


    <div
        class="page-wrap"
        style="max-width: 800px;"
    >


        <!-- PAGE HEADER -->

        <div
            class="page-head"
            style="text-align: left;"
        >

            <h1>
                My Work Requests
            </h1>

            <p>
                Track the status of the work you've requested.
            </p>

        </div>


        <!-- BACK TO DASHBOARD -->

        <p class="breadcrumb">

            <a href="user/dashboard.php">
                &larr; Back to Dashboard
            </a>

        </p>


        <!-- SUCCESS MESSAGE -->

        <?php if (isset($_SESSION['success'])): ?>

            <div
                class="alert success"
                role="status"
            >

                <?= e($_SESSION['success']) ?>

            </div>

            <?php unset($_SESSION['success']); ?>

        <?php endif; ?>


        <!-- REQUESTS -->

        <?php if (mysqli_num_rows($result) > 0): ?>


            <?php while ($req = mysqli_fetch_assoc($result)): ?>


                <div class="request-card">


                    <!-- REQUEST HEADER -->

                    <div class="request-head">


                        <h3>


                            <!-- WORKER AVATAR -->

                            <span
                                class="request-worker-avatar"
                                aria-hidden="true"
                            >

                                <span class="avatar-circle">

                                    <?php
                                    $workerAvatar =
                                        !empty($req['worker_avatar'])
                                            ? $req['worker_avatar']
                                            : '👷';
                                    ?>

                                    <?php if (
                                        strpos(
                                            $workerAvatar,
                                            'uploads/profile/'
                                        ) === 0
                                    ): ?>

                                        <img
                                            src="<?= e($workerAvatar) ?>"
                                            alt="<?= e($req['worker_name']) ?>'s profile photo"
                                        >

                                    <?php else: ?>

                                        <span class="avatar-emoji">
                                            <?= e($workerAvatar) ?>
                                        </span>

                                    <?php endif; ?>

                                </span>

                            </span>


                            <!-- WORKER NAME -->

                            <?= e($req['worker_name']) ?>


                            <br>


                            <!-- CATEGORY -->

                            <small>
                                <?= e(
                                    $req['category'] ?? 'Worker'
                                ) ?>
                            </small>


                        </h3>


                        <!-- STATUS -->

                        <?php
                        $status = $req['status'] ?? 'Pending';
                        $statusClass = strtolower($status);
                        ?>

                        <span
                            class="badge <?= e($statusClass) ?>"
                        >
                            <?= e($status) ?>
                        </span>


                    </div>


                    <!-- DATE -->

                    <p class="meta">

                        <strong>
                            Date needed:
                        </strong>

                        <?= e($req['work_date']) ?>

                    </p>


                    <!-- LOCATION -->

                    <p class="meta">

                        <strong>
                            Work location:
                        </strong>

                        <?= e($req['location']) ?>

                    </p>


                    <!-- CLIENT PHONE -->

                    <p class="meta">

                        <strong>
                            Phone number:
                        </strong>

                        <?= e($req['client_phone']) ?>

                    </p>


                    <!-- MESSAGE -->

                    <?php if (!empty($req['message'])): ?>

                        <p class="meta">

                            <strong>
                                Message:
                            </strong>

                            <?= e($req['message']) ?>

                        </p>

                    <?php endif; ?>


                    <!-- REQUEST DATE -->

                    <small>

                        Sent on
                        <?= e($req['created_at']) ?>

                    </small>


                </div>


            <?php endwhile; ?>


        <?php else: ?>


            <!-- EMPTY STATE -->

            <div class="empty-state">


                <div
                    class="empty-icon"
                    aria-hidden="true"
                >
                    📋
                </div>


                <h3>
                    No requests yet
                </h3>


                <p>

                    <a href="browse.php">
                        Browse workers
                    </a>

                    to send your first request.

                </p>


            </div>


        <?php endif; ?>


    </div>


</main>


<?php

$base = "";

include "includes/footer.php";

?>


<script
    src="assets/js/main.js"
    defer
></script>


</body>

</html>
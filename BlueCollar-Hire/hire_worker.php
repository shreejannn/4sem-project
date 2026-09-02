<?php
require_once "config/session.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] != "client") {
    header("Location: browse.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: browse.php");
    exit();
}

$worker_profile_id = intval($_GET['id']);
$client_id = $_SESSION['user_id'];
$message = "";

// Load the worker profile being hired
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
mysqli_stmt_bind_param($stmt, "i", $worker_profile_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: browse.php");
    exit();
}

$worker = mysqli_fetch_assoc($result);

if (isset($_POST['send_request'])) {

    $work_date = $_POST['work_date'] ?? "";
    $note = trim($_POST['message'] ?? "");
    $location = trim($_POST['location'] ?? "");



    if (empty($work_date)) {
        $message = "Please select a date for the work.";
    } 
    elseif (empty($location)) {
    $message = "Please enter the work location.";
}

elseif (strtotime($work_date) < strtotime(date('Y-m-d'))) {
        $message = "Please select today or a future date.";
    } else {

        $insert = mysqli_prepare($conn, "
            INSERT INTO work_requests (client_id, worker_profile_id, work_date, location, message, status)
            VALUES (?, ?, ?, ?, ?, 'Pending')
        ");
        mysqli_stmt_bind_param($insert, "iisss", $client_id, $worker_profile_id, $work_date, $location, $note);

        if (mysqli_stmt_execute($insert)) {
            $_SESSION['success'] = "Work request sent to " . $worker['name'] . " successfully.";
            header("Location: my_requests.php");
            exit();
        } else {
            $message = "Something went wrong. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hire <?= e($worker['name']) ?> | BlueCollar-Hire</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">

  <style>
                        .cancel-link {
                            display: inline-block;
                            margin-top: 1rem;
                            color: #64748b;
                            text-decoration: none;
                        }

                        .cancel-link:hover {
                            color: #1e293b;
                        }
                    </style>

</head>

<body>

    <?php $base = ""; include "includes/navbar.php"; ?>

    <main id="main-content" class="form-page">
        <div class="form-container">
            <div class="form-card">
                <form method="POST" novalidate>

                    <h2>Hire <?= e($worker['name']) ?></h2>
                    <p class="form-subtitle">
                        <?= e($worker['category']) ?> &middot; Rs. <?= e($worker['daily_rate']) ?>/day
                    </p>

                    <?php if ($message != ""): ?>
                        <div class="alert error" role="alert"><?= e($message) ?></div>
                    <?php endif; ?>

                    <div class="field">
                        <label for="work_date">Date Needed</label>
                        <input type="date" id="work_date" name="work_date" min="<?= date('Y-m-d') ?>" value="<?= e($_POST['work_date'] ?? '') ?>" required>
                    </div>

                    <div class="field">
                        <label for="location">Work Location</label>
                        <textarea
                            id="location"
                            name="location"
                            placeholder="Enter the address: city, municipality, ward no, street"
                            required
                        ><?= e($_POST['location'] ?? '') ?></textarea>
                    </div>

                    <div class="field">
                        <label for="message">Message for the worker <span class="hint" style="display:inline;">(optional)</span></label>
                        <textarea id="message" name="message" placeholder="Describe the work you need done..."><?= e($_POST['message'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" name="send_request" class="btn primary block">Send Work Request</button>

                </form>

                  <div style="text-align: center;">
  <a
    href="worker.php?id=<?= $worker_profile_id ?>"
    class="cancel-link"
>
    Cancel
</a>
</div>
                  
            </div>
        </div>
    </main>

    <?php $base = ""; include "includes/footer.php"; ?>
    <script src="assets/js/main.js" defer></script>

</body>

</html>

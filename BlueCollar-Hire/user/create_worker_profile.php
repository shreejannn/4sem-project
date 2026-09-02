<?php
require_once "../config/session.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] != "worker") {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// Redirect away if profile already exists
$stmt = mysqli_prepare($conn, "SELECT id FROM worker_profiles WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
if (mysqli_stmt_get_result($stmt)->num_rows > 0) {
    $_SESSION['success'] = "You have already created your worker profile.";
    header("Location: dashboard.php");
    exit();
}

$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name ASC");

if (isset($_POST['create_profile'])) {

    $category_id  = intval($_POST['category']);
    $experience   = intval($_POST['experience']);
    $daily_rate   = floatval($_POST['daily_rate']);
    $address      = trim($_POST['address'] ?? "");
    $bio          = trim($_POST['bio'] ?? "");
    $availability = $_POST['availability'] ?? "Available";

    if (empty($category_id) || empty($address) || empty($bio)) {
        $message = "Please fill in all required fields.";
    } elseif ($experience < 0) {
        $message = "Experience cannot be negative.";
    } elseif ($daily_rate <= 0) {
        $message = "Daily rate must be greater than zero.";
    } else {

        $insert = mysqli_prepare($conn, "
            INSERT INTO worker_profiles (user_id, category_id, experience, daily_rate, address, bio, availability, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')
        ");
        mysqli_stmt_bind_param($insert, "iiidsss", $user_id, $category_id, $experience, $daily_rate, $address, $bio, $availability);

        if (mysqli_stmt_execute($insert)) {
            $_SESSION['success'] = "Worker profile created successfully. It is now waiting for admin approval.";
            header("Location: dashboard.php");
            exit();
        } else {
            $message = "Database error. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Worker Profile | BlueCollar-Hire</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
</head>

<body>

    <?php $base = "../"; $activePage = "dashboard"; include "../includes/navbar.php"; ?>

    <main id="main-content" class="form-page">
        <div class="form-container">
            <div class="form-card">
                <form method="POST" novalidate>

                    <h2>Create Worker Profile</h2>
                    <p class="form-subtitle">This will be reviewed by an admin before it goes live.</p>

                    <?php if ($message != ""): ?>
                        <div class="alert error" role="alert"><?= e($message) ?></div>
                    <?php endif; ?>

                    <div class="field">
                        <label for="category">Category</label>
                        <select name="category" id="category" required>
                            <option value="">Select Category</option>
                            <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                                <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="experience">Years of Experience</label>
                        <input type="number" id="experience" name="experience" min="0" placeholder="e.g. 3" required>
                    </div>

                    <div class="field">
                        <label for="daily_rate">Daily Rate (Rs.)</label>
                        <input type="number" id="daily_rate" step="0.01" name="daily_rate" min="1" placeholder="e.g. 1000" required>
                    </div>

                    <div class="field">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" placeholder="Your city / area" required>
                    </div>

                    <div class="field">
                        <label for="bio">About You</label>
                        <textarea id="bio" name="bio" placeholder="Describe your skills and experience..." required></textarea>
                    </div>

                    <div class="field">
                        <label for="availability">Availability</label>
                        <select name="availability" id="availability">
                            <option value="Available">Available</option>
                            <option value="Busy">Busy</option>
                        </select>
                    </div>

                    <button type="submit" name="create_profile" class="btn primary block">Create Profile</button>

                </form>
            </div>
        </div>
    </main>

    <?php $base = "../"; include "../includes/footer.php"; ?>
    <script src="../assets/js/main.js" defer></script>

</body>

</html>

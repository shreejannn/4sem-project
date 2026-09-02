<?php
require_once "config/session.php";

$message = "";

if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] == "admin" ? "admin/dashboard.php" : "user/dashboard.php"));
    exit();
}

if (isset($_POST['login'])) {

    $email = trim($_POST['email'] ?? "");
    $password = $_POST['password'] ?? "";

    if (empty($email) || empty($password)) {
        $message = "Please enter email and password.";
    } else {

        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 1) {

            $user = mysqli_fetch_assoc($result);

            if (password_verify($password, $user['password'])) {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name']    = $user['name'];
                $_SESSION['avatar']  = $user['avatar'];
                $_SESSION['role']    = $user['role'];

                if ($user['role'] == "admin") {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: user/dashboard.php");
                }
                exit();

            } else {
                $message = "Wrong password.";
            }
        } else {
            $message = "Account not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | BlueCollar-Hire</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
</head>

<body>

    <?php $base = ""; include "includes/navbar.php"; ?>

    <main id="main-content" class="form-page">
        <div class="form-container">
            <div class="form-card">
                <form method="POST" novalidate>

                    <h2>Welcome Back</h2>
                    <p class="form-subtitle">Sign in to access your account.</p>

                    <?php if (isset($_GET['registered'])): ?>
                        <div class="alert success" role="status">Registration successful. Please log in.</div>
                    <?php endif; ?>

                    <?php if ($message != ""): ?>
                        <div class="alert error" role="alert"><?= e($message) ?></div>
                    <?php endif; ?>

                    <div class="field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="you@example.com" autocomplete="email" required>
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                    </div>

                    <button type="submit" name="login" class="btn primary block">Sign In</button>

                    <p class="link">Don't have an account yet? <a href="register.php">Create Account</a></p>

                </form>
            </div>
        </div>
    </main>

    <?php $base = ""; include "includes/footer.php"; ?>
    <script src="assets/js/main.js" defer></script>

</body>

</html>

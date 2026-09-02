<?php
require_once "../config/session.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: ../login.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);
$message = "";

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$user = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$user) {
    header("Location: users.php");
    exit();
}

if (isset($_POST['update'])) {

    $name  = trim($_POST['name'] ?? "");
    $email = trim($_POST['email'] ?? "");
    $phone = trim($_POST['phone'] ?? "");
    $role  = $_POST['role'] ?? "client";

    if (empty($name) || empty($email) || empty($phone)) {
        $message = "Please fill in all fields.";
    } elseif (!in_array($role, ['client', 'worker', 'admin'])) {
        $message = "Invalid role.";
    } else {

        $update = mysqli_prepare($conn, "UPDATE users SET name = ?, email = ?, phone = ?, role = ? WHERE id = ?");
        mysqli_stmt_bind_param($update, "ssssi", $name, $email, $phone, $role, $id);

        if (mysqli_stmt_execute($update)) {
            $_SESSION['success'] = "User updated successfully.";
            header("Location: users.php");
            exit();
        } else {
            $message = "Database error. That email may already be in use.";
        }
    }

    $user['name']  = $name;
    $user['email'] = $email;
    $user['phone'] = $phone;
    $user['role']  = $role;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User | BlueCollar-Hire Admin</title>
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

                    <h2>Edit User</h2>
                    <p class="breadcrumb"><a href="users.php">&larr; Back to Users</a></p>

                    <?php if ($message != ""): ?>
                        <div class="alert error" role="alert"><?= e($message) ?></div>
                    <?php endif; ?>

                    <div class="field">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?= e($user['name']) ?>" required>
                    </div>

                    <div class="field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?= e($user['email']) ?>" required>
                    </div>

                    <div class="field">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" value="<?= e($user['phone']) ?>" required>
                    </div>

                    <div class="field">
                        <label for="role">Role</label>
                        <select name="role" id="role">
                            <option value="client" <?= $user['role'] == "client" ? "selected" : "" ?>>Client</option>
                            <option value="worker" <?= $user['role'] == "worker" ? "selected" : "" ?>>Worker</option>
                            <option value="admin" <?= $user['role'] == "admin" ? "selected" : "" ?>>Admin</option>
                        </select>
                    </div>

                    <button type="submit" name="update" class="btn primary block">Update User</button>

                </form>
            </div>
        </div>
    </main>

    <?php $base = "../"; include "../includes/footer.php"; ?>
    <script src="../assets/js/main.js" defer></script>

</body>

</html>

<?php
require_once "../config/session.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: ../login.php");
    exit();
}

$result = mysqli_query($conn, "SELECT * FROM users ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | BlueCollar-Hire Admin</title>
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
                <h1>Manage Users</h1>
                <p>View, edit, or remove accounts on the platform.</p>
            </div>
            <p class="breadcrumb"><a href="dashboard.php">&larr; Back to Dashboard</a></p>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert success" role="status"><?= e($_SESSION['success']) ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Role</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td><?= e($user['name']) ?></td>
                                    <td><?= e($user['email']) ?></td>
                                    <td><?= ucfirst($user['role']) ?></td>
                                    <td>
                                        <div class="row-actions">
                                            <a class="edit-btn" href="edit_user.php?id=<?= $user['id'] ?>">Edit</a>
                                            <?php if ($user['role'] != "admin"): ?>
                                                <a class="delete-btn" href="delete_user.php?id=<?= $user['id'] ?>" onclick="return confirm('Delete this user? This cannot be undone.')">Delete</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-user-slash" aria-hidden="true"></i>
                    <h3>No users found</h3>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <?php $base = "../"; include "../includes/footer.php"; ?>
    <script src="../assets/js/main.js" defer></script>

</body>

</html>

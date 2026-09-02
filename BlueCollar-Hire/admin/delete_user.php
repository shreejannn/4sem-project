<?php
require_once "../config/session.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: ../login.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);

// Prevent an admin from deleting themselves or another admin by mistake
$stmt = mysqli_prepare($conn, "SELECT role FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$target = mysqli_stmt_get_result($stmt)->fetch_assoc();

if ($target && $target['role'] != "admin") {
    $delete = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
    mysqli_stmt_bind_param($delete, "i", $id);
    mysqli_stmt_execute($delete);
    $_SESSION['success'] = "User deleted successfully.";
}

header("Location: users.php");
exit();

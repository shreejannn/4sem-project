<?php
require_once "../config/session.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: ../login.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conn, "UPDATE worker_profiles SET status = 'Approved' WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$_SESSION['success'] = "Worker profile approved.";
header("Location: pending_workers.php");
exit();

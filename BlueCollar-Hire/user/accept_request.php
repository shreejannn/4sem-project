<?php
require_once "../config/session.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "worker") {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$request_id = intval($_GET['id'] ?? 0);

// Make sure this request actually belongs to a profile owned by the logged-in worker
$stmt = mysqli_prepare($conn, "
    UPDATE work_requests
    SET status = 'Accepted'
    WHERE id = ? AND worker_profile_id IN (
        SELECT id FROM worker_profiles WHERE user_id = ?
    )
");
mysqli_stmt_bind_param($stmt, "ii", $request_id, $user_id);
mysqli_stmt_execute($stmt);

$_SESSION['success'] = "Work request accepted.";
header("Location: work_requests.php");
exit();

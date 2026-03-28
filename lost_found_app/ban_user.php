<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { echo "error_permission"; exit(); }

$target_user_id = $_POST['user_id'];
$action = $_POST['action'];

if ($action == 'unban') {
    $stmt = $conn->prepare("UPDATE users SET is_banned=0, ban_category=NULL, ban_details=NULL, ban_until=NULL WHERE id=?");
    $stmt->bind_param("i", $target_user_id);
    if($stmt->execute()) echo "success";
    exit();
}

if ($action == 'ban') {
    $category = $_POST['category'];
    $details = $_POST['details'];
    $duration = $_POST['duration'];

    $ban_until = null; // ถาวร
    if ($duration == '1h') $ban_until = date('Y-m-d H:i:s', strtotime('+1 hour'));
    elseif ($duration == '1d') $ban_until = date('Y-m-d H:i:s', strtotime('+1 day'));
    elseif ($duration == '3d') $ban_until = date('Y-m-d H:i:s', strtotime('+3 days'));
    elseif ($duration == '7d') $ban_until = date('Y-m-d H:i:s', strtotime('+7 days'));
    elseif ($duration == '1m') $ban_until = date('Y-m-d H:i:s', strtotime('+1 month'));
    elseif ($duration == '1y') $ban_until = date('Y-m-d H:i:s', strtotime('+1 year'));

    $stmt = $conn->prepare("UPDATE users SET is_banned=1, ban_category=?, ban_details=?, ban_until=? WHERE id=?");
    $stmt->bind_param("sssi", $category, $details, $ban_until, $target_user_id);
    if($stmt->execute()) echo "success";
    exit();
}
?>
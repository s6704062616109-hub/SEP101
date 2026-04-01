<?php
session_start();
require 'db.php';
// บังคับให้ระบบใช้เวลาประเทศไทย (แก้ปัญหาเวลาแบนเพี้ยน)
date_default_timezone_set('Asia/Bangkok'); 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { echo "error"; exit(); }

$target_id = $_POST['user_id'];
$action = $_POST['action'];

if ($action == 'ban') {
    $category = $_POST['category'];
    $details = $_POST['details'];
    $duration = $_POST['duration'];
    
    $ban_until = null;
    if ($duration !== 'permanent') {
        $time_add = "";
        if ($duration == '1h') $time_add = "+1 hour";
        if ($duration == '1d') $time_add = "+1 day";
        if ($duration == '3d') $time_add = "+3 days";
        if ($duration == '7d') $time_add = "+7 days";
        if ($duration == '1m') $time_add = "+1 month";
        if ($duration == '1y') $time_add = "+1 year";
        // คำนวณเวลาแบนตามเวลาไทย
        $ban_until = date('Y-m-d H:i:s', strtotime($time_add)); 
    }
    
    $stmt = $conn->prepare("UPDATE users SET is_banned=1, ban_category=?, ban_details=?, ban_until=? WHERE id=?");
    $stmt->bind_param("sssi", $category, $details, $ban_until, $target_id);
    if ($stmt->execute()) echo "success";

} elseif ($action == 'unban') {
    $stmt = $conn->prepare("UPDATE users SET is_banned=0, ban_category=NULL, ban_details=NULL, ban_until=NULL WHERE id=?");
    $stmt->bind_param("i", $target_id);
    if ($stmt->execute()) echo "success";
}
?>
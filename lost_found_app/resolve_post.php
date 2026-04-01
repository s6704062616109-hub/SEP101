<?php
session_start();
require 'db.php';

// ตรวจสอบว่าได้ล็อกอินหรือไม่
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    $post_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];

    // เช็กว่าโพสต์นี้เป็นของใคร
    $check_sql = "SELECT user_id FROM posts WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $post_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $post = $result->fetch_assoc();
        
        // ถ้าเป็น Admin หรือเป็นเจ้าของโพสต์ ให้อนุญาตอัปเดตได้
        if ($role === 'admin' || $post['user_id'] == $user_id) {
            $update_sql = "UPDATE posts SET status = 'resolved' WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $post_id);
            $update_stmt->execute();
            $update_stmt->close();
        }
    }
    $check_stmt->close();
}

// กลับไปหน้าเดิม
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
header("Location: " . $referer);
exit();
?>
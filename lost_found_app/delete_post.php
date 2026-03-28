<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) { header("Location: index.php"); exit(); }

$post_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($role == 'admin') {
    $sql = "DELETE FROM posts WHERE id = ?"; // Admin ลบได้เลยแค่รู้ ID โพสต์
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $post_id);
} else {
    $sql = "DELETE FROM posts WHERE id = ? AND user_id = ?"; // User ทั่วไป ลบได้แค่ของตัวเอง
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $post_id, $user_id);
}
$stmt->execute();
$stmt->close();

// เด้งกลับไปหน้าเดิม (ไม่ว่าจะกดลบจากหน้า index หรือ my_posts)
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>
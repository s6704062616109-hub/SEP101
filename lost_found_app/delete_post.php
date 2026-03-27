<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit();
}

$post_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// ลบเฉพาะโพสต์ที่เป็นของตัวเองเท่านั้น (กันคนอื่นมาแอบลบ)
$sql = "DELETE FROM posts WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $post_id, $user_id);
$stmt->execute();
$stmt->close();

// ลบเสร็จให้เด้งกลับไปหน้าโพสต์ของฉัน
header("Location: my_posts.php");
exit();
?>
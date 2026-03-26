<?php
session_start();
require 'db.php';

if (isset($_SESSION['user_id']) && isset($_POST['post_id']) && isset($_POST['comment_text'])) {
    $user_id = $_SESSION['user_id'];
    $post_id = $_POST['post_id'];
    $comment_text = trim($_POST['comment_text']);

    if ($comment_text !== "") {
        $sql = "INSERT INTO comments (post_id, user_id, comment_text) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $post_id, $user_id, $comment_text);
        if ($stmt->execute()) {
            echo "success"; // บอก JavaScript ว่าบันทึกสำเร็จ
        } else {
            echo "error";
        }
    }
}
?>
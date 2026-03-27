<?php
session_start();
require 'db.php';

if (isset($_SESSION['user_id']) && isset($_POST['post_id']) && isset($_POST['comment_text'])) {
    $user_id = $_SESSION['user_id'];
    $post_id = $_POST['post_id'];
    $comment_text = trim($_POST['comment_text']);

    // รับค่า parent_id (ถ้ามีการตอบกลับ)
    $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : NULL;

    if ($comment_text !== "") {
        $sql = "INSERT INTO comments (post_id, user_id, comment_text, parent_id) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisi", $post_id, $user_id, $comment_text, $parent_id);
        if ($stmt->execute()) { echo "success"; } else { echo "error"; }
    }
}
?>
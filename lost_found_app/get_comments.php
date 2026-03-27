<?php
require 'db.php';
if (isset($_GET['post_id'])) {
    $post_id = $_GET['post_id'];
    // เพิ่มการดึง c.id, c.parent_id และ u.id มาด้วย
    $sql = "SELECT c.id, c.parent_id, c.comment_text, c.created_at, u.id AS user_id, u.username, u.profile_picture 
            FROM comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.post_id = ? 
            ORDER BY c.created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $comments = [];
    while($row = $result->fetch_assoc()) { $comments[] = $row; }
    echo json_encode($comments);
}
?>
<?php
require 'db.php';

if (isset($_GET['post_id'])) {
    $post_id = $_GET['post_id'];
    
    // ดึงคอมเมนต์ พร้อมชื่อและรูปของคนพิมพ์
    $sql = "SELECT c.comment_text, c.created_at, u.username, u.profile_picture 
            FROM comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.post_id = ? 
            ORDER BY c.created_at ASC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $comments = [];
    while($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
    
    // ส่งข้อมูลกลับไปในรูปแบบ JSON (ภาษาที่ JavaScript เข้าใจง่าย)
    echo json_encode($comments);
}
?>
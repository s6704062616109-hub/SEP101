<?php
session_start();
require 'db.php';

if (isset($_SESSION['user_id']) && isset($_POST['post_id']) && isset($_POST['comment_text'])) {
    $actor_id = $_SESSION['user_id'];
    $post_id = $_POST['post_id'];
    $comment_text = trim($_POST['comment_text']);
    $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : NULL;

    if ($comment_text !== "") {
        // บันทึกคอมเมนต์
        $sql = "INSERT INTO comments (post_id, user_id, comment_text, parent_id) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisi", $post_id, $actor_id, $comment_text, $parent_id);
        
        if ($stmt->execute()) { 
            echo "success"; 
            
            // ================= ระบบสร้างการแจ้งเตือน =================
            if ($parent_id === NULL) {
                // กรณี: คอมเมนต์บนโพสต์ -> แจ้งเตือนเจ้าของโพสต์
                $p_stmt = $conn->query("SELECT user_id FROM posts WHERE id = $post_id");
                $post_owner = $p_stmt->fetch_assoc()['user_id'];
                
                // ไม่แจ้งเตือนตัวเอง
                if ($post_owner != $actor_id) {
                    $conn->query("INSERT INTO notifications (user_id, actor_id, post_id, type) VALUES ($post_owner, $actor_id, $post_id, 'comment')");
                }
            } else {
                // กรณี: ตอบกลับคอมเมนต์ -> แจ้งเตือนเจ้าของคอมเมนต์หลัก
                $c_stmt = $conn->query("SELECT user_id FROM comments WHERE id = $parent_id");
                $comment_owner = $c_stmt->fetch_assoc()['user_id'];
                
                if ($comment_owner != $actor_id) {
                    $conn->query("INSERT INTO notifications (user_id, actor_id, post_id, type) VALUES ($comment_owner, $actor_id, $post_id, 'reply')");
                }
            }
            // ========================================================
        } else { 
            echo "error"; 
        }
    }
}
?>
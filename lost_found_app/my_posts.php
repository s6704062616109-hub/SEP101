<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงเฉพาะโพสต์ของตัวเอง
$sql_posts = "
    SELECT p.id AS post_id, p.title, p.description, p.image_url, p.post_type, 
           p.item_category, p.status, p.created_at, 
           u.id AS post_user_id, u.username, u.profile_picture 
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.user_id = ? 
    ORDER BY p.created_at DESC
";
$stmt = $conn->prepare($sql_posts);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_posts = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>โพสต์ของฉัน - ระบบแจ้งของหาย</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h2 style="text-align: center;">📁 โพสต์ของฉัน</h2>
    <a href="index.php" style="display: block; text-align: center; margin-bottom: 20px; color: gray; text-decoration: none;">← กลับไปหน้าหลัก</a>

    <?php if ($result_posts->num_rows > 0): ?>
        <?php while($post = $result_posts->fetch_assoc()): ?>
            <div class="post-card">
                
                <div class="post-body">
                    <?php if ($post['status'] == 'resolved'): ?>
                        <span class="badge" style="background-color: #28a745;">✅ สำเร็จแล้ว</span>
                    <?php else: ?>
                        <span class="badge" style="background-color: #ffc107; color: #333;">⏳ ยังไม่สำเร็จ</span>
                    <?php endif; ?>

                    <?php if ($post['post_type'] == 'lost'): ?>
                        <span class="badge badge-lost">🔍 แจ้งของหาย</span>
                    <?php else: ?>
                        <span class="badge badge-found">💡 พบสิ่งของ</span>
                    <?php endif; ?>

                    <h3 class="post-title" style="margin-top: 10px;"><?php echo htmlspecialchars($post['title']); ?></h3>
                    <p class="post-desc"><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>
                    
                    <?php if (!empty($post['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($post['image_url']); ?>" class="post-image" alt="Post Image">
                    <?php endif; ?>
                </div>

                <div style="border-top: 1px solid #eee; padding-top: 15px; display: flex; gap: 10px;">
                    <a href="edit_post.php?id=<?php echo $post['post_id']; ?>" class="edit-btn">✏️ แก้ไขโพสต์</a>
                    <a href="delete_post.php?id=<?php echo $post['post_id']; ?>" class="delete-btn" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบโพสต์นี้? ข้อมูลจะหายไปอย่างถาวร');">🗑️ ลบโพสต์</a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align: center; color: gray;">คุณยังไม่ได้สร้างโพสต์ใดๆ</p>
    <?php endif; ?>
</div>

</body>
</html>
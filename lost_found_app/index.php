<?php
session_start();
require 'db.php';

// เช็กว่าเข้าสู่ระบบหรือยัง
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลผู้ใช้งานที่กำลังล็อกอิน (สำหรับเมนูด้านบน)
$sql_user = "SELECT username, role, profile_picture FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

$my_profile_image = !empty($user_data['profile_picture']) ? $user_data['profile_picture'] : 'https://via.placeholder.com/40?text=U';

// ================= ส่วนดึงข้อมูลโพสต์ทั้งหมดจากฐานข้อมูล =================
// ใช้ JOIN เพื่อดึงชื่อและรูปโปรไฟล์ของ "เจ้าของโพสต์" มาด้วย เรียงจากโพสต์ล่าสุดไปเก่าสุด
$sql_posts = "
    SELECT p.id AS post_id, p.title, p.description, p.image_url, p.post_type, p.created_at, 
           u.username, u.profile_picture 
    FROM posts p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
";
$result_posts = $conn->query($sql_posts);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>หน้าแรก - ระบบแจ้งของหาย</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 20px; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); max-width: 600px; margin: auto; }
        
        /* ส่วนหัว Header */
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
        .header-title { margin: 0; color: #333; font-size: 24px; }
        .user-menu { display: flex; align-items: center; gap: 15px; }
        .profile-link { display: flex; align-items: center; gap: 10px; text-decoration: none; color: #333; font-weight: bold; padding: 5px 10px; border-radius: 20px; }
        .nav-profile-img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #007bff; }
        
        /* ปุ่มสร้างโพสต์ */
        .create-post-btn { background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; display: block; text-align: center; margin-bottom: 20px; font-weight: bold; }
        
        /* ================= สไตล์สำหรับกล่องโพสต์ (Post Card) ================= */
        .post-card { background: #fff; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px; padding: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        
        /* 1. ส่วนบนซ้าย (รูปโปรไฟล์ + ชื่อคนโพสต์) */
        .post-header { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
        .post-profile-img { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 1px solid #ccc; }
        .post-author { font-weight: bold; color: #333; font-size: 16px; margin: 0; }
        .post-time { font-size: 12px; color: gray; margin: 0; }
        
        /* 2. ส่วนกลาง (รายละเอียดและรูปภาพ) */
        .post-body { margin-bottom: 15px; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; color: white; margin-bottom: 10px; }
        .badge-lost { background-color: #dc3545; } /* สีแดง ของหาย */
        .badge-found { background-color: #17a2b8; } /* สีฟ้า พบของ */
        
        .post-title { font-size: 18px; font-weight: bold; margin: 0 0 5px 0; }
        .post-desc { margin: 0 0 15px 0; line-height: 1.5; color: #444; }
        .post-image { width: 100%; max-height: 400px; object-fit: cover; border-radius: 8px; border: 1px solid #eee; }
        
        /* 3. ส่วนล่างซ้าย (ปุ่ม Comment) */
        .post-footer { border-top: 1px solid #eee; padding-top: 10px; }
        .comment-btn { display: inline-block; background-color: #f0f2f5; color: #444; padding: 8px 15px; border-radius: 20px; text-decoration: none; font-weight: bold; font-size: 14px; transition: 0.2s; }
        .comment-btn:hover { background-color: #e4e6e9; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2 class="header-title">ระบบแจ้งของหาย</h2>
        <div class="user-menu">
            <a href="profile.php" class="profile-link">
                <img src="<?php echo htmlspecialchars($my_profile_image); ?>" class="nav-profile-img" alt="Profile">
                <span><?php echo htmlspecialchars($user_data['username']); ?></span>
            </a>
        </div>
    </div>

    <a href="create_post.php" class="create-post-btn">➕ สร้างโพสต์แจ้งของหาย/พบของ</a>
    <hr style="border: 0; border-top: 1px solid #ddd; margin-bottom: 20px;">

    <?php if ($result_posts->num_rows > 0): ?>
        <?php while($post = $result_posts->fetch_assoc()): ?>
            
            <div class="post-card">
                <div class="post-header">
                    <?php 
                        $author_img = !empty($post['profile_picture']) ? $post['profile_picture'] : 'https://via.placeholder.com/45?text=U';
                    ?>
                    <img src="<?php echo htmlspecialchars($author_img); ?>" class="post-profile-img" alt="Author">
                    <div>
                        <p class="post-author"><?php echo htmlspecialchars($post['username']); ?></p>
                        <p class="post-time"><?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></p>
                    </div>
                </div>

                <div class="post-body">
                    <?php if ($post['post_type'] == 'lost'): ?>
                        <span class="badge badge-lost">🔍 แจ้งของหาย</span>
                    <?php else: ?>
                        <span class="badge badge-found">💡 พบสิ่งของ</span>
                    <?php endif; ?>

                    <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                    <p class="post-desc"><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>
                    
                    <?php if (!empty($post['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($post['image_url']); ?>" class="post-image" alt="Post Image">
                    <?php endif; ?>
                </div>

                <div class="post-footer">
                    <a href="post_detail.php?id=<?php echo $post['post_id']; ?>" class="comment-btn">💬 แสดงความคิดเห็น</a>
                </div>
            </div>

        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align: center; color: gray;">ยังไม่มีโพสต์ในขณะนี้ เริ่มสร้างโพสต์แรกเลย!</p>
    <?php endif; ?>
    </div>

</body>
</html>
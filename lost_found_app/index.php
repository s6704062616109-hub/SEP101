<?php
session_start();
require 'db.php'; 

// เช็กว่าเข้าสู่ระบบหรือยัง
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลผู้ใช้งาน (รวมถึงรูปโปรไฟล์) จากฐานข้อมูล
$sql = "SELECT username, role, profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// กำหนดรูปโปรไฟล์ (ถ้าไม่มีรูป ให้ใช้รูปพื้นฐาน)
$profile_image = !empty($user_data['profile_picture']) ? $user_data['profile_picture'] : 'https://via.placeholder.com/40?text=U';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>หน้าแรก - ระบบแจ้งของหาย</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 20px; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); max-width: 800px; margin: auto; }
        
        /* จัดการส่วนหัว (Header) ให้แยกซ้าย-ขวา */
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
        .header-title { margin: 0; color: #333; font-size: 24px; }
        
        /* จัดการกลุ่มเมนูด้านขวา (รูปโปรไฟล์ + ชื่อ) */
        .user-menu { display: flex; align-items: center; gap: 15px; }
        .profile-link { display: flex; align-items: center; gap: 10px; text-decoration: none; color: #333; font-weight: bold; padding: 5px 10px; border-radius: 20px; transition: 0.3s; }
        .profile-link:hover { background-color: #f0f0f0; }
        .nav-profile-img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #007bff; }
        
        .admin-box { background-color: #fff3cd; color: #856404; padding: 15px; border: 1px solid #ffeeba; border-radius: 4px; margin-bottom: 20px; }
        .admin-box a { color: #d39e00; font-weight: bold; text-decoration: none; margin-right: 15px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2 class="header-title">ระบบแจ้งของหาย</h2>
        
        <div class="user-menu">
            <a href="profile.php" class="profile-link">
                <img src="<?php echo htmlspecialchars($profile_image); ?>" class="nav-profile-img" alt="Profile">
                <span><?php echo htmlspecialchars($user_data['username']); ?></span>
            </a>
            </div>
    </div>
    <?php if ($user_data['role'] == 'admin'): ?>
        <div class="admin-box">
            <h3>🛠️ เครื่องมือผู้ดูแลระบบ (Admin Panel)</h3>
            <p>คุณมีสิทธิ์ในการจัดการข้อมูลทั้งหมดในระบบ</p>
            <a href="#">จัดการโพสต์ทั้งหมด</a>
            <a href="#">จัดการผู้ใช้งาน</a>
        </div>
    <?php else: ?>
        <div style="margin-bottom: 20px;">
            <a href="create_post.php" style="background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; display: inline-block;">➕ สร้างโพสต์แจ้งของหาย/พบของ</a>
        </div>
    <?php endif; ?>

    <hr>
    <h3>ฟีดรายการล่าสุด</h3>
    <p style="color: gray;">(เดี๋ยวเราจะดึงโพสต์จากฐานข้อมูลมาแสดงตรงนี้ พร้อมปุ่มคอมเมนต์ครับ)</p>
</div>

</body>
</html>
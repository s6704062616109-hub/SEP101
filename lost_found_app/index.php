<?php
session_start();

// เช็กว่าล็อกอินหรือยัง ถ้ายังให้เด้งไปหน้า login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>หน้าแรก - ระบบแจ้งของหาย</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 20px; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); max-width: 800px; margin: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        .logout-btn { color: white; background-color: #dc3545; padding: 8px 15px; text-decoration: none; border-radius: 4px; }
        .logout-btn:hover { background-color: #c82333; }
        
        /* กล่องเมนูสำหรับ Admin โดยเฉพาะ */
        .admin-box { background-color: #fff3cd; color: #856404; padding: 15px; border: 1px solid #ffeeba; border-radius: 4px; margin-bottom: 20px; }
        .admin-box a { color: #d39e00; font-weight: bold; text-decoration: none; margin-right: 15px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>ระบบแจ้งของหายและพบเจอสิ่งของ</h2>
        <a href="logout.php" class="logout-btn">ออกจากระบบ</a>
    </div>

    <p>สวัสดี, <strong><?php echo $_SESSION['username']; ?></strong> (สถานะ: <?php echo $_SESSION['role']; ?>)</p>

    <?php if ($_SESSION['role'] == 'admin'): ?>
        <div class="admin-box">
            <h3>🛠️ เครื่องมือผู้ดูแลระบบ (Admin Panel)</h3>
            [cite_start]<p>คุณมีสิทธิ์ในการจัดการข้อมูลทั้งหมดในระบบ [cite: 6]</p>
            <a href="#">จัดการโพสต์ทั้งหมด</a>
            <a href="#">จัดการผู้ใช้งาน (บล็อก/ปลดบล็อก)</a>
        </div>
    <?php else: ?>
        <div style="margin-bottom: 20px;">
            <a href="profile.php" style="background: #17a2b8; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;">👤 โปรไฟล์ของฉัน</a>
            <a href="create_post.php" style="background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;">➕ สร้างโพสต์ใหม่</a>
        </div>
    <?php endif; ?>
    <hr>
    <h3>ฟีดรายการล่าสุด</h3>
    <p style="color: gray;">(เดี๋ยวเราจะดึงโพสต์จากฐานข้อมูลมาแสดงตรงนี้ พร้อมปุ่มคอมเมนต์ครับ)</p>
</div>

</body>
</html>
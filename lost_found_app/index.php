<?php
// 1. เริ่ม Session เพื่อดึงข้อมูลคนที่ล็อกอินอยู่
session_start();

// 2. ตรวจสอบว่ามีบัตรผ่าน (Session) หรือไม่ ถ้าไม่มีให้เด้งกลับไปหน้า login
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
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); max-width: 600px; margin: auto; }
        .logout-btn { color: red; text-decoration: none; font-weight: bold; float: right; }
    </style>
</head>
<body>

<div class="container">
    <a href="logout.php" class="logout-btn">ออกจากระบบ</a>
    
    <h2>ยินดีต้อนรับคุณ, <?php echo $_SESSION['username']; ?>! 🎉</h2>
    
    <p>สถานะของคุณคือ: <strong><?php echo $_SESSION['role']; ?></strong></p>
    <hr>
    
    <h3>รายการแจ้งของหาย / พบของ (ฟีดจำลอง)</h3>
    <p style="color: gray;">(เดี๋ยวเราจะมาทำระบบโพสต์ข้อมูลลงตรงนี้ในครั้งหน้านะครับ)</p>
</div>

</body>
</html>
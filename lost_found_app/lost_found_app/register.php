<?php
// 1. เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูลที่เราทำไว้
require 'db.php';

$message = ""; // ตัวแปรสำหรับเก็บข้อความแจ้งเตือน

// 2. ตรวจสอบว่ามีการกดปุ่ม "สมัครสมาชิก" ส่งข้อมูลมาหรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 3. ตรวจสอบว่ากรอกข้อมูลครบไหม
    if (!empty($username) && !empty($password)) {
        
        // 4. เข้ารหัสผ่านเพื่อความปลอดภัย (สำคัญมาก! จะไม่เก็บรหัสผ่านตรงๆ ลงฐานข้อมูล)
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 5. เตรียมคำสั่ง SQL สำหรับเพิ่มข้อมูลลงในตาราง users
        $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $hashed_password);

        // 6. สั่งรันคำสั่งและตรวจสอบผลลัพธ์
        if ($stmt->execute()) {
            $message = "<span style='color: green;'>สมัครสมาชิกสำเร็จ! <a href='login.php'>เข้าสู่ระบบที่นี่</a></span>";
        } else {
            $message = "<span style='color: red;'>เกิดข้อผิดพลาด หรือชื่อผู้ใช้นี้มีคนใช้แล้ว</span>";
        }
        $stmt->close();
    } else {
        $message = "<span style='color: red;'>กรุณากรอกข้อมูลให้ครบถ้วน</span>";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สมัครสมาชิก - ระบบแจ้งของหาย</title>
    <style>
        /* ตกแต่งหน้าตาเว็บเบื้องต้นด้วย CSS */
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .register-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); text-align: center; width: 300px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #218838; }
    </style>
</head>
<body>

<div class="register-box">
    <h2>สมัครสมาชิก</h2>
    
    <?php if($message != "") echo "<p>$message</p>"; ?>

    <form method="POST" action="">
        <input type="text" name="username" placeholder="ตั้งชื่อผู้ใช้งาน (Username)" required>
        <input type="password" name="password" placeholder="ตั้งรหัสผ่าน (Password)" required>
        <button type="submit">ยืนยันการสมัคร</button>
    </form>
    
    <p style="font-size: 14px;">มีบัญชีอยู่แล้ว? <a href="login.php">เข้าสู่ระบบ</a></p>
</div>

</body>
</html>
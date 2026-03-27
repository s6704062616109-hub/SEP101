<?php
// 1. เริ่มต้นระบบ Session (สำคัญมาก ต้องอยู่บรรทัดบนสุด เพื่อใช้จดจำการล็อกอิน)
session_start();

// 2. เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล
require 'db.php';

$message = "";

// 3. ตรวจสอบว่ามีการกดปุ่ม "เข้าสู่ระบบ" ส่งข้อมูลมาหรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        
        // 4. ค้นหาชื่อผู้ใช้ในฐานข้อมูล
        $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        // 5. ถ้าเจอชื่อผู้ใช้ในระบบ
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc(); // ดึงข้อมูลของคนๆ นั้นออกมา
            
            // 6. ตรวจสอบรหัสผ่านที่กรอกมา ว่าตรงกับรหัสผ่านที่ถูกเข้ารหัสไว้ในฐานข้อมูลหรือไม่
            if (password_verify($password, $user['password'])) {
                
                // 7. ถ้ารหัสผ่านถูกต้อง ให้สร้าง Session (บัตรผ่าน)
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role']; // เก็บสิทธิ์ไว้ด้วยว่าเป็น user หรือ admin
                
                // 8. พาผู้ใช้งานไปยังหน้าหลัก (หน้าฟีด)
                header("Location: index.php");
                exit();
                
            } else {
                $message = "<span style='color: red;'>รหัสผ่านไม่ถูกต้อง</span>";
            }
        } else {
            $message = "<span style='color: red;'>ไม่พบชื่อผู้ใช้งานนี้ในระบบ</span>";
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
    <title>เข้าสู่ระบบ - ระบบแจ้งของหาย</title>
    <style>
        /* ตกแต่งหน้าตาให้เหมือนหน้าสมัครสมาชิก */
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); text-align: center; width: 300px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #0056b3; }
    </style>
</head>
<body>

<div class="login-box">
    <h2>เข้าสู่ระบบ</h2>
    
    <?php if($message != "") echo "<p>$message</p>"; ?>

    <form method="POST" action="">
        <input type="text" name="username" placeholder="ชื่อผู้ใช้งาน (Username)" required>
        <input type="password" name="password" placeholder="รหัสผ่าน (Password)" required>
        <button type="submit">เข้าสู่ระบบ</button>
    </form>
    
    <p style="font-size: 14px;">ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิกที่นี่</a></p>
</div>

</body>
</html>
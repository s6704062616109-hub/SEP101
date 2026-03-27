<?php
// 1. เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล
require 'db.php';

$message = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // ตรวจสอบว่ากรอกข้อมูลครบไหม
    if (!empty($username) && !empty($password)) {
        
        // ------------------ เริ่มส่วนตรวจสอบเงื่อนไข ------------------

        // เช็กที่ 1: ชื่อผู้ใช้ต้องเป็นภาษาอังกฤษและตัวเลขเท่านั้น (ห้ามเว้นวรรค ห้ามมีภาษาไทย)
        if (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
            $message = "<span style='color: red;'>ชื่อผู้ใช้งานต้องเป็นอักษรภาษาอังกฤษหรือตัวเลขเท่านั้นครับ</span>";
        } 
        // เช็กที่ 2: รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร
        else if (strlen($password) < 8) {
            $message = "<span style='color: red;'>รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษรครับ</span>";
        }
        // เช็กที่ 3: รหัสผ่านต้องมีทั้งอักษรภาษาอังกฤษ (a-z หรือ A-Z) และ ตัวเลข (0-9)
        else if (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $message = "<span style='color: red;'>รหัสผ่านต้องประกอบด้วยภาษาอังกฤษและตัวเลขผสมกันครับ</span>";
        } 
        else {
            // เช็กที่ 4: ตรวจสอบในฐานข้อมูลว่ามีชื่อผู้ใช้นี้ซ้ำหรือไม่
            $check_sql = "SELECT id FROM users WHERE username = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $username);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                // ถ้าค้นหาแล้วเจอข้อมูล แสดงว่าชื่อนี้มีคนใช้แล้ว
                $message = "<span style='color: red;'>ชื่อผู้ใช้งาน '$username' มีคนใช้แล้ว กรุณาเปลี่ยนชื่อใหม่ครับ</span>";
            } else {
                // ------------------ ถ้าผ่านทุกเงื่อนไข ให้บันทึกข้อมูลลงฐานข้อมูล ------------------
                
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $username, $hashed_password);

                if ($stmt->execute()) {
                    $message = "<span style='color: green;'>สมัครสมาชิกสำเร็จ! <a href='login.php'>เข้าสู่ระบบที่นี่</a></span>";
                } else {
                    $message = "<span style='color: red;'>เกิดข้อผิดพลาดในการสมัครสมาชิก กรุณาลองใหม่</span>";
                }
                $stmt->close();
            }
            $check_stmt->close();
        }
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
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .register-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); text-align: center; width: 350px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        button:hover { background-color: #218838; }
        .hints { font-size: 12px; color: gray; text-align: left; margin-bottom: 10px; line-height: 1.5; }
    </style>
</head>
<body>

<div class="register-box">
    <h2>สมัครสมาชิก</h2>
    
    <?php if($message != "") echo "<p>$message</p>"; ?>

    <form method="POST" action="">
        <input type="text" name="username" placeholder="ตั้งชื่อผู้ใช้งาน (Username)" required>
        <input type="password" name="password" placeholder="ตั้งรหัสผ่าน (Password)" required>
        
        <div class="hints">
            * ชื่อผู้ใช้: เฉพาะภาษาอังกฤษและตัวเลขเท่านั้น<br>
            * รหัสผ่าน: ยาว 8 ตัวอักษรขึ้นไป และต้องมีภาษาอังกฤษผสมตัวเลข
        </div>

        <button type="submit">ยืนยันการสมัคร</button>
    </form>
    
    <p style="font-size: 14px; margin-top: 20px;">มีบัญชีอยู่แล้ว? <a href="login.php">เข้าสู่ระบบ</a></p>
</div>

</body>
</html>
<?php
session_start();
require 'db.php';
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                
                // ================= ระบบเช็กสถานะแบน =================
                if ($user['is_banned'] == 1) {
                    // ตรวจสอบว่าหมดเวลาแบนหรือยัง
                    if ($user['ban_until'] !== null && strtotime($user['ban_until']) <= time()) {
                        // ปลดแบนอัตโนมัติ
                        $unban_stmt = $conn->prepare("UPDATE users SET is_banned=0, ban_category=NULL, ban_details=NULL, ban_until=NULL WHERE id=?");
                        $unban_stmt->bind_param("i", $user['id']);
                        $unban_stmt->execute();
                    } else {
                        // ยังโดนแบนอยู่ เตะออก!
                        $ban_time = ($user['ban_until'] !== null) ? "ถึงวันที่ " . date('d/m/Y H:i', strtotime($user['ban_until'])) : "ถาวร";
                        $message = "<span style='color: red;'>⚠️ บัญชีของคุณถูกระงับการใช้งาน<br>สาเหตุ: {$user['ban_category']}<br>ระยะเวลา: $ban_time</span>";
                        goto end_login; // กระโดดข้ามการล็อกอิน
                    }
                }
                // ===============================================

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                header("Location: index.php");
                exit();
                
                end_login:
            } else { $message = "<span style='color: red;'>รหัสผ่านไม่ถูกต้อง</span>"; }
        } else { $message = "<span style='color: red;'>ไม่พบชื่อผู้ใช้งานนี้ในระบบ</span>"; }
        $stmt->close();
    } else { $message = "<span style='color: red;'>กรุณากรอกข้อมูลให้ครบถ้วน</span>"; }
}
?>
<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8"><title>เข้าสู่ระบบ</title><link rel="stylesheet" href="style.css"></head>
<body style="display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0;">
<div class="container" style="text-align: center; width: 300px;">
    <h2>เข้าสู่ระบบ</h2>
    <?php if($message != "") echo "<p>$message</p>"; ?>
    <form method="POST" action="">
        <input type="text" name="username" placeholder="ชื่อผู้ใช้งาน (Username)" required>
        <input type="password" name="password" placeholder="รหัสผ่าน (Password)" required>
        <button type="submit" class="search-btn" style="width: 100%; margin-top: 10px;">เข้าสู่ระบบ</button>
    </form>
    <p style="font-size: 14px;">ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิกที่นี่</a></p>
</div>
</body></html>
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
                
                if ($user['is_banned'] == 1) {
                    if ($user['ban_until'] !== null && strtotime($user['ban_until']) <= time()) {
                        $unban_stmt = $conn->prepare("UPDATE users SET is_banned=0, ban_category=NULL, ban_details=NULL, ban_until=NULL WHERE id=?");
                        $unban_stmt->bind_param("i", $user['id']);
                        $unban_stmt->execute();
                    } else {
                        $ban_time = ($user['ban_until'] !== null) ? "ถึงวันที่ " . date('d/m/Y H:i', strtotime($user['ban_until'])) : "ถาวร";
                        $message = "<span style='color: red;'>⚠️ บัญชีของคุณถูกระงับการใช้งาน<br>สาเหตุ: {$user['ban_category']}<br>ระยะเวลา: $ban_time</span>";
                        goto end_login; 
                    }
                }

                $conn->query("DELETE FROM login_logs WHERE login_time < DATE_SUB(NOW(), INTERVAL 1 MONTH)");
                
                $log_stmt = $conn->prepare("INSERT INTO login_logs (user_id) VALUES (?)");
                $log_stmt->bind_param("i", $user['id']);
                $log_stmt->execute();

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
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .input-group { position: relative; width: 100%; margin-top: 10px; }
        .input-group input { width: 100%; padding: 10px 40px 10px 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; font-family: inherit; }
        .eye-btn { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; user-select: none; color: gray; font-size: 18px; }
        .eye-btn:hover { color: #333; }
        
        /* ================= ซ่อนปุ่มตาดั้งเดิมของ Browser ================= */
        input::-ms-reveal,
        input::-ms-clear { display: none; }
        input[type="password"]::-webkit-contacts-auto-fill-button, 
        input[type="password"]::-webkit-credentials-auto-fill-button { visibility: hidden; pointer-events: none; position: absolute; right: 0; }
        /* ========================================================== */
    </style>
</head>
<body style="display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background-color: #f4f4f9;">
<div class="container" style="text-align: center; width: 320px; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
    <h2 style="margin-top: 0; color: #333;">เข้าสู่ระบบ</h2>
    <?php if($message != "") echo "<p style='font-size:14px;'>$message</p>"; ?>
    
    <form method="POST" action="">
        <div class="input-group">
            <input type="text" name="username" placeholder="ชื่อผู้ใช้งาน (Username)" required>
        </div>
        
        <div class="input-group">
            <input type="password" id="loginPassword" name="password" placeholder="รหัสผ่าน (Password)" required>
            <span class="eye-btn" onclick="togglePassword('loginPassword', this)">👁️</span>
        </div>

        <button type="submit" class="search-btn" style="width: 100%; margin-top: 20px; padding: 12px; border-radius: 4px;">เข้าสู่ระบบ</button>
    </form>
    <p style="font-size: 14px; margin-top: 20px;">ยังไม่มีบัญชี? <a href="register.php" style="color: #0084ff; text-decoration: none; font-weight: bold;">สมัครสมาชิกที่นี่</a></p>
</div>

<script>
function togglePassword(inputId, icon) {
    let input = document.getElementById(inputId);
    if (input.type === "password") {
        input.type = "text";
        icon.innerText = "🙈"; 
    } else {
        input.type = "password";
        icon.innerText = "👁️"; 
    }
}
</script>
</body>
</html>
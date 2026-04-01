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
                
                // ตรวจสอบการแบน
                if ($user['is_banned'] == 1) {
                    if ($user['ban_until'] !== null && strtotime($user['ban_until']) <= time()) {
                        $unban_stmt = $conn->prepare("UPDATE users SET is_banned=0, ban_category=NULL, ban_details=NULL, ban_until=NULL WHERE id=?");
                        $unban_stmt->bind_param("i", $user['id']);
                        $unban_stmt->execute();
                    } else {
                        $ban_time = ($user['ban_until'] !== null) ? "Until " . date('d M Y, H:i', strtotime($user['ban_until'])) : "Permanent";
                        $message = "<span style='color: #ef4444;'>⚠️ Account Suspended<br>Reason: {$user['ban_category']}<br>Duration: $ban_time</span>";
                        goto end_login; 
                    }
                }

                // บันทึก Log การเข้าสู่ระบบ
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
            } else { $message = "<span style='color: #ef4444;'>Incorrect password.</span>"; }
        } else { $message = "<span style='color: #ef4444;'>Username not found.</span>"; }
        $stmt->close();
    } else { $message = "<span style='color: #ef4444;'>Please fill in all fields.</span>"; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Lost & Found</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        body { background-color: var(--bg-light, #0b0c10); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; overflow: hidden; }
        .input-group { position: relative; width: 100%; margin-top: 15px; }
        .input-group input { width: 100%; padding: 12px 40px 12px 15px; box-sizing: border-box; border-radius: 8px; font-family: inherit; background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.08); outline: none; }
        .input-group input:focus { border-color: #3b82f6; }
        .eye-btn { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; user-select: none; color: #94a3b8; font-size: 18px; }
        .eye-btn:hover { color: #e2e8f0; }
        .login-card { text-align: center; width: 350px; background: var(--card-bg, #1f202e); padding: 40px 30px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.08); z-index: 10; }
    </style>
</head>
<body>

<div class="watermark-bg">LOST & FOUND</div>

<div class="login-card">
    <h2 style="margin-top: 0; color: #e2e8f0; font-size: 28px;">Welcome Back</h2>
    <p style="color: #94a3b8; font-size: 14px; margin-bottom: 25px;">Sign in to continue</p>
    
    <?php if($message != "") echo "<p style='font-size:14px; background: rgba(239,68,68,0.1); padding: 10px; border-radius: 8px;'>$message</p>"; ?>
    
    <form method="POST" action="">
        <div class="input-group">
            <input type="text" name="username" placeholder="Username" required>
        </div>
        
        <div class="input-group">
            <input type="password" id="loginPassword" name="password" placeholder="Password" required>
            <span class="eye-btn" onclick="togglePassword('loginPassword', this)">👁️</span>
        </div>

        <button type="submit" style="width: 100%; margin-top: 25px; padding: 12px; border-radius: 8px; font-size: 16px; background: #3b82f6; color: white; border: none; cursor: pointer; font-weight: bold; transition: 0.2s;">Sign In</button>
    </form>
    <p style="font-size: 14px; margin-top: 25px; color: #94a3b8;">Don't have an account? <a href="register.php" style="color: #3b82f6; text-decoration: none; font-weight: bold;">Register here</a></p>
</div>

<script>
function togglePassword(inputId, icon) {
    let input = document.getElementById(inputId);
    if (input.type === "password") { input.type = "text"; icon.innerText = "🙈"; } 
    else { input.type = "password"; icon.innerText = "👁️"; }
}
</script>
</body>
</html>
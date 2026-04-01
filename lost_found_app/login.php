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
                        $ban_time = ($user['ban_until'] !== null) ? "Until " . date('d M Y, H:i', strtotime($user['ban_until'])) : "Permanent";
                        $message = "<span style='color: #ef4444;'>⚠️ Account Banned<br>Reason: {$user['ban_category']}<br>Duration: $ban_time</span>";
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
</head>
<body class="auth-body">

    <div class="auth-bg-text">LOST&FOUND</div>

    <div class="auth-container">
        <h2 style="margin-top: 0; color: white;">Sign In</h2>
        <?php if($message != "") echo "<p style='font-size:14px;'>$message</p>"; ?>
        
        <form method="POST" action="">
            <div class="auth-input-group">
                <input type="text" name="username" placeholder="Username" required>
            </div>
            
            <div class="auth-input-group">
                <input type="password" id="loginPassword" name="password" placeholder="Password" required>
                <span class="auth-eye-btn" onclick="togglePassword('loginPassword', this)">👁️</span>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 25px; padding: 12px; font-size:16px;">Sign In</button>
        </form>
        <p style="font-size: 14px; margin-top: 20px; color: var(--text-muted);">Don't have an account? <a href="register.php" style="color: var(--primary); text-decoration: none; font-weight: bold;">Sign Up here</a></p>
    </div>

<script>
function togglePassword(inputId, icon) {
    let input = document.getElementById(inputId);
    if (input.type === "password") {
        input.type = "text"; icon.innerText = "🙈"; 
    } else {
        input.type = "password"; icon.innerText = "👁️"; 
    }
}
</script>
</body>
</html>
<?php
session_start();
require 'db.php';
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $message = "<span style='color: #ef4444;'>⚠️ Passwords do not match.</span>";
    } 
    else {
        $check_sql = "SELECT id FROM users WHERE username = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $message = "<span style='color: #ef4444;'>⚠️ Username is already taken.</span>";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user'; 
            
            $insert_sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sss", $username, $hashed_password, $role);
            
            if ($insert_stmt->execute()) {
                $message = "<span style='color: #22c55e;'>✨ Registered successfully! <br><br><a href='login.php' style='background:#3b82f6; color:white; padding:10px 15px; border-radius:8px; text-decoration:none; display:inline-block; margin-top:10px;'>Sign In Now</a></span>";
            } else {
                $message = "<span style='color: #ef4444;'>❌ Error creating account.</span>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Lost & Found</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        body { background-color: var(--bg-light, #0b0c10); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; overflow: hidden; }
        .input-group { position: relative; width: 100%; margin-top: 15px; }
        .input-group input { width: 100%; padding: 12px 40px 12px 15px; box-sizing: border-box; border-radius: 8px; font-family: inherit; background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.08); outline: none; }
        .input-group input:focus { border-color: #3b82f6; }
        .eye-btn { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; user-select: none; color: #94a3b8; font-size: 18px; }
        .eye-btn:hover { color: #e2e8f0; }
        .register-card { text-align: center; width: 380px; background: var(--card-bg, #1f202e); padding: 40px 30px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.08); z-index: 10; }

        .rules-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.85); justify-content: center; align-items: center; }
        .rules-content { background: var(--card-bg, #1f202e); padding: 30px; border-radius: 12px; max-width: 500px; width: 90%; box-shadow: 0 10px 30px rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.08); color: #e2e8f0; }
        .rules-box { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 15px; border-radius: 8px; max-height: 250px; overflow-y: auto; text-align: left; font-size: 14px; line-height: 1.6; margin-bottom: 20px; }
        .rules-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 20px; }
    </style>
</head>
<body>

<div class="watermark-bg">LOST & FOUND</div>

<div class="register-card">
    <h2 style="margin-top: 0; color: #e2e8f0; font-size: 28px;">Create Account</h2>
    <p style="color: #94a3b8; font-size: 14px; margin-bottom: 25px;">Join our community</p>
    
    <?php if($message != "") echo "<div style='font-size:14px; background: rgba(255,255,255,0.05); padding: 15px; border-radius: 8px; margin-bottom: 15px;'>$message</div>"; else { ?>
    
    <form method="POST" action="" onsubmit="return validatePassword()">
        <div class="input-group">
            <input type="text" name="username" placeholder="Username" required>
        </div>
        
        <div class="input-group">
            <input type="password" id="regPassword" name="password" placeholder="Password" required>
            <span class="eye-btn" onclick="togglePassword('regPassword', this)">👁️</span>
        </div>

        <div class="input-group">
            <input type="password" id="regConfirmPassword" name="confirm_password" placeholder="Confirm Password" required>
            <span class="eye-btn" onclick="togglePassword('regConfirmPassword', this)">👁️</span>
        </div>

        <button type="submit" style="width: 100%; margin-top: 25px; padding: 12px; border-radius: 8px; font-size: 16px; background: #22c55e; color: white; border: none; cursor: pointer; font-weight: bold; transition: 0.2s;">Register</button>
    </form>
    <?php } ?>
    
    <p style="font-size: 14px; margin-top: 25px; color: #94a3b8;">Already have an account? <a href="login.php" style="color: #3b82f6; text-decoration: none; font-weight: bold;">Sign In</a></p>
</div>

<div id="rulesModal" class="rules-modal">
    <div class="rules-content">
        <h2 style="margin-top: 0; color: #ef4444; font-size: 22px;">⚖️ Terms & Conditions</h2>
        <div class="rules-box">
            <p style="margin-top: 0;">Please read and understand the following terms before using the system:</p>
            <ol style="padding-left: 20px; margin-bottom: 0;">
                <li style="margin-bottom: 8px;"><strong>Authentic Information:</strong> Do not post false, misleading, or impersonating information.</li>
                <li style="margin-bottom: 8px;"><strong>No Scams:</strong> Do not use this platform to deceive or defraud others.</li>
                <li style="margin-bottom: 8px;"><strong>Appropriate Behavior:</strong> No explicit, offensive, or privacy-violating content is allowed.</li>
                <li style="margin-bottom: 8px;"><strong>Responsibility:</strong> Your contact info is collected solely for returning items. Share it carefully.</li>
                <li><strong>Admin Rights:</strong> Admins reserve the right to delete posts or ban users without prior notice if rules are violated.</li>
            </ol>
        </div>
        <div class="rules-footer">
            <label style="cursor: pointer; display: flex; align-items: center; gap: 10px; font-weight: bold; font-size: 14px;">
                <input type="checkbox" id="agreeCheck" style="width: 18px; height: 18px; accent-color: #22c55e;">
                I agree to the terms
            </label>
            <button id="agreeBtn" disabled onclick="closeRulesModal()" style="background: #22c55e; color: white; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; font-weight: bold; opacity: 0.5;">Accept & Continue</button>
        </div>
    </div>
</div>

<script>
function togglePassword(inputId, icon) {
    let input = document.getElementById(inputId);
    if (input.type === "password") { input.type = "text"; icon.innerText = "🙈"; } 
    else { input.type = "password"; icon.innerText = "👁️"; }
}

function validatePassword() {
    let pass = document.getElementById('regPassword').value;
    let confirmPass = document.getElementById('regConfirmPassword').value;
    if (pass !== confirmPass) {
        alert("⚠️ Passwords do not match!");
        return false; 
    }
    return true; 
}

window.onload = function() {
    <?php if($message == "") { ?>
        document.getElementById('rulesModal').style.display = 'flex';
    <?php } ?>
};

document.getElementById('agreeCheck').addEventListener('change', function() {
    let btn = document.getElementById('agreeBtn');
    if (this.checked) { btn.disabled = false; btn.style.opacity = '1'; } 
    else { btn.disabled = true; btn.style.opacity = '0.5'; }
});

function closeRulesModal() {
    document.getElementById('rulesModal').style.display = 'none';
}
</script>
</body>
</html>
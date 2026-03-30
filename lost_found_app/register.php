<?php
session_start();
require 'db.php';
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $message = "<span style='color: #ef4444;'>⚠️ Passwords do not match. Please try again.</span>";
    } else {
        $check_sql = "SELECT id FROM users WHERE username = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $message = "<span style='color: #ef4444;'>⚠️ Username already taken.</span>";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user'; 
            
            $insert_sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sss", $username, $hashed_password, $role);
            
            if ($insert_stmt->execute()) {
                $message = "<span style='color: #22c55e;'>✨ Account created! <a href='login.php' style='color:var(--primary); font-weight:bold;'>Sign In now</a></span>";
            } else {
                $message = "<span style='color: #ef4444;'>❌ System error. Please try again.</span>";
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
    <title>Sign Up - Lost & Found</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body class="auth-body">

    <div class="auth-bg-text">LOST AND FOUND</div>

    <div class="auth-container">
        <h2 style="margin-top: 0; color: white;">📝 Sign Up</h2>
        <?php if($message != "") echo "<p style='font-size:14px;'>$message</p>"; ?>
        
        <form method="POST" action="" onsubmit="return validatePassword()">
            <div class="auth-input-group">
                <input type="text" name="username" placeholder="Choose a Username" required>
            </div>
            
            <div class="auth-input-group">
                <input type="password" id="regPassword" name="password" placeholder="Password" required>
                <span class="auth-eye-btn" onclick="togglePassword('regPassword', this)">👁️</span>
            </div>

            <div class="auth-input-group">
                <input type="password" id="regConfirmPassword" name="confirm_password" placeholder="Confirm Password" required>
                <span class="auth-eye-btn" onclick="togglePassword('regConfirmPassword', this)">👁️</span>
            </div>

            <button type="submit" class="btn btn-success" style="width: 100%; margin-top: 25px; padding: 12px; font-size:16px;">Create Account</button>
        </form>
        <p style="font-size: 14px; margin-top: 20px; color: var(--text-muted);">Already have an account? <a href="login.php" style="color: var(--primary); text-decoration: none; font-weight: bold;">Sign In</a></p>
    </div>

    <div id="rulesModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <h2 style="margin-top: 0; color: var(--danger);">⚖️ Terms and Rules</h2>
            <div class="rules-box">
                <p>Please read and understand the following rules before using the system:</p>
                <ol style="padding-left: 20px;">
                    <li><strong>Authentic Information:</strong> Do not post fake lost/found items or impersonate others.</li>
                    <li><strong>No Scams:</strong> Using this platform for fraud, scams, or demanding rewards from owners is strictly prohibited.</li>
                    <li><strong>Appropriate Behavior:</strong> Do not post inappropriate, explicit, or abusive content. Respect others' privacy.</li>
                    <li><strong>Responsibility:</strong> Your contact information may be displayed to help return items. Share personal info responsibly.</li>
                    <li><strong>Admin Rights:</strong> Admins reserve the right to delete posts, hide comments, or ban accounts without prior notice if rules are violated.</li>
                </ol>
            </div>
            <div class="rules-footer">
                <label style="cursor: pointer; display: flex; align-items: center; gap: 8px; font-weight: bold; color: white;">
                    <input type="checkbox" id="agreeCheck" style="width: 18px; height: 18px;">
                    I have read and agree to the rules
                </label>
                <button id="agreeBtn" class="agree-btn" disabled onclick="closeRulesModal()">Accept & Continue</button>
            </div>
        </div>
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
function validatePassword() {
    let pass = document.getElementById('regPassword').value;
    let confirmPass = document.getElementById('regConfirmPassword').value;
    if (pass !== confirmPass) { alert("⚠️ Passwords do not match. Please try again."); return false; }
    return true; 
}
window.onload = function() { document.getElementById('rulesModal').style.display = 'flex'; };
document.getElementById('agreeCheck').addEventListener('change', function() {
    document.getElementById('agreeBtn').disabled = !this.checked;
});
function closeRulesModal() { document.getElementById('rulesModal').style.display = 'none'; }
</script>
</body>
</html>
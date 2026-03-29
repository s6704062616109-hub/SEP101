<?php
session_start();
require 'db.php';
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $message = "<span style='color: red;'>⚠️ รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน กรุณาลองใหม่</span>";
    } 
    else {
        $check_sql = "SELECT id FROM users WHERE username = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $message = "<span style='color: red;'>⚠️ ชื่อผู้ใช้งานนี้ถูกใช้ไปแล้ว กรุณาใช้ชื่ออื่น</span>";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user'; 
            
            $insert_sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sss", $username, $hashed_password, $role);
            
            if ($insert_stmt->execute()) {
                $message = "<span style='color: green;'>✨ สมัครสมาชิกสำเร็จ! <a href='login.php' style='color:#0084ff; font-weight:bold;'>เข้าสู่ระบบเลย</a></span>";
            } else {
                $message = "<span style='color: red;'>❌ เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่</span>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สมัครสมาชิก</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .input-group { position: relative; width: 100%; margin-top: 15px; }
        .input-group input { width: 100%; padding: 10px 40px 10px 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; font-family: inherit; }
        .eye-btn { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; user-select: none; color: gray; font-size: 18px; }
        .eye-btn:hover { color: #333; }

        /* ================= ซ่อนปุ่มตาดั้งเดิมของ Browser ================= */
        input::-ms-reveal,
        input::-ms-clear { display: none; }
        input[type="password"]::-webkit-contacts-auto-fill-button, 
        input[type="password"]::-webkit-credentials-auto-fill-button { visibility: hidden; pointer-events: none; position: absolute; right: 0; }
        /* ========================================================== */

        .rules-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); justify-content: center; align-items: center; }
        .rules-content { background: white; padding: 25px; border-radius: 8px; max-width: 500px; width: 90%; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .rules-box { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; border-radius: 4px; max-height: 250px; overflow-y: auto; text-align: left; font-size: 14px; line-height: 1.6; margin-bottom: 20px; }
        .rules-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #eee; padding-top: 15px; }
        .agree-btn { background-color: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .agree-btn:disabled { background-color: #ccc; cursor: not-allowed; }
    </style>
</head>
<body style="display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background-color: #f4f4f9;">

<div class="container" style="text-align: center; width: 350px; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
    <h2 style="margin-top: 0; color: #333;">📝 สมัครสมาชิก</h2>
    <?php if($message != "") echo "<p style='font-size:14px;'>$message</p>"; ?>
    
    <form method="POST" action="" onsubmit="return validatePassword()">
        <div class="input-group">
            <input type="text" name="username" placeholder="ตั้งชื่อผู้ใช้งาน (Username)" required>
        </div>
        
        <div class="input-group">
            <input type="password" id="regPassword" name="password" placeholder="รหัสผ่าน (Password)" required>
            <span class="eye-btn" onclick="togglePassword('regPassword', this)">👁️</span>
        </div>

        <div class="input-group">
            <input type="password" id="regConfirmPassword" name="confirm_password" placeholder="ยืนยันรหัสผ่านอีกครั้ง" required>
            <span class="eye-btn" onclick="togglePassword('regConfirmPassword', this)">👁️</span>
        </div>

        <button type="submit" class="search-btn" style="background-color: #28a745; width: 100%; margin-top: 25px; padding: 12px; border-radius: 4px;">สมัครสมาชิก</button>
    </form>
    <p style="font-size: 14px; margin-top: 20px;">มีบัญชีอยู่แล้ว? <a href="login.php" style="color: #0084ff; text-decoration: none; font-weight: bold;">เข้าสู่ระบบ</a></p>
</div>

<div id="rulesModal" class="rules-modal">
    <div class="rules-content">
        <h2 style="margin-top: 0; color: #d32f2f;">⚖️ กฎและข้อตกลงการใช้งาน</h2>
        <div class="rules-box">
            <p>กรุณาอ่านและทำความเข้าใจข้อตกลงก่อนใช้งานระบบแจ้งของหาย:</p>
            <ol style="padding-left: 20px;">
                <li><strong>ข้อมูลต้องเป็นความจริง:</strong> ห้ามโพสต์แจ้งของหายหรือพบของที่เป็นข้อมูลเท็จ บิดเบือน หรือแอบอ้างเป็นบุคคลอื่น</li>
                <li><strong>ห้ามหลอกลวง:</strong> ห้ามใช้ระบบนี้เป็นเครื่องมือในการหลอกลวง ฉ้อโกง หรือเรียกรับผลประโยชน์จากผู้ที่ทำของหายโดยเด็ดขาด</li>
                <li><strong>พฤติกรรมที่เหมาะสม:</strong> ห้ามโพสต์ข้อความ รูปภาพ หรือคอมเมนต์ที่มีลักษณะอนาจาร หยาบคาย หรือละเมิดสิทธิส่วนบุคคลของผู้อื่น</li>
                <li><strong>ความรับผิดชอบ:</strong> ข้อมูลการติดต่อของคุณจะถูกจัดเก็บและอาจแสดงผลให้ผู้ใช้อื่นเห็น เพื่อจุดประสงค์ในการส่งคืนสิ่งของเท่านั้น ผู้ใช้ควรระมัดระวังในการให้ข้อมูลส่วนตัว</li>
                <li><strong>สิทธิ์ของผู้ดูแลระบบ (Admin):</strong> ทีมงานขอสงวนสิทธิ์ในการลบโพสต์, ซ่อนคอมเมนต์, หรือระงับบัญชีผู้ใช้งาน (แบน) ได้ทันทีหากตรวจพบการกระทำที่ผิดกฎ หรือเข้าข่ายผิดวัตถุประสงค์ของระบบโดยไม่ต้องแจ้งให้ทราบล่วงหน้า</li>
            </ol>
            <p><em>* หากพบเห็นผู้ใช้งานที่ทำผิดกฎ ท่านสามารถแจ้งทีมงานได้ทันที</em></p>
        </div>
        <div class="rules-footer">
            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px; font-weight: bold; color: #333;">
                <input type="checkbox" id="agreeCheck" style="width: 18px; height: 18px;">
                ฉันได้อ่านและยอมรับกฎกติกา
            </label>
            <button id="agreeBtn" class="agree-btn" disabled onclick="closeRulesModal()">ตกลงและดำเนินการต่อ</button>
        </div>
    </div>
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

function validatePassword() {
    let pass = document.getElementById('regPassword').value;
    let confirmPass = document.getElementById('regConfirmPassword').value;
    if (pass !== confirmPass) {
        alert("⚠️ รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง!");
        return false; 
    }
    return true; 
}

window.onload = function() {
    document.getElementById('rulesModal').style.display = 'flex';
};

document.getElementById('agreeCheck').addEventListener('change', function() {
    let btn = document.getElementById('agreeBtn');
    if (this.checked) {
        btn.disabled = false;
    } else {
        btn.disabled = true;
    }
});

function closeRulesModal() {
    document.getElementById('rulesModal').style.display = 'none';
}
</script>

</body>
</html>
<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// ส่วนบันทึกข้อมูล (เหมือนเดิม)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $contact_info = $_POST['contact_info'];
    $profile_picture_path = "";

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['profile_picture']['type'], $allowed_types)) {
            $new_filename = time() . "_" . basename($_FILES['profile_picture']['name']);
            $target_file = "uploads/profiles/" . $new_filename;
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                $profile_picture_path = $target_file;
            } else { $message = "<span style='color: red;'>เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ</span>"; }
        } else { $message = "<span style='color: red;'>อัปโหลดได้เฉพาะไฟล์ JPG, PNG และ GIF เท่านั้น</span>"; }
    }

    if ($profile_picture_path != "") {
        $stmt = $conn->prepare("UPDATE users SET contact_info = ?, profile_picture = ? WHERE id = ?");
        $stmt->bind_param("ssi", $contact_info, $profile_picture_path, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET contact_info = ? WHERE id = ?");
        $stmt->bind_param("si", $contact_info, $user_id);
    }
    if ($stmt->execute()) { $message = "<span style='color: green;'>บันทึกข้อมูลโปรไฟล์สำเร็จ!</span>"; }
    $stmt->close();
}

// ส่วนดึงข้อมูลมาแสดง (เพิ่มการดึง role มาด้วยเพื่อเช็กสิทธิ์)
$sql = "SELECT username, profile_picture, contact_info, role FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>โปรไฟล์ของฉัน</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); max-width: 500px; margin: auto; text-align: center; position: relative; }
        .profile-img { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #007bff; margin-bottom: 15px; }
        input[type="file"], textarea { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button[type="submit"] { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; width: 100%; margin-bottom: 20px; }
        .bottom-actions { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #eee; padding-top: 15px; }
    </style>
</head>
<body>

<div class="profile-container">
    <?php if ($user_data['role'] == 'admin'): ?>
        <button onclick="openAdminMenu()" style="position: absolute; right: 20px; top: 20px; background: none; border: none; font-size: 28px; cursor: pointer; color: #333;" title="เมนูผู้ดูแลระบบ">☰</button>
    <?php endif; ?>

    <h2>โปรไฟล์ของ <?php echo htmlspecialchars($user_data['username']); ?></h2>
    <?php if($message != "") echo "<p>$message</p>"; ?>

    <?php if (!empty($user_data['profile_picture'])): ?>
        <img src="<?php echo htmlspecialchars($user_data['profile_picture']); ?>" class="profile-img">
    <?php else: ?>
        <img src="https://via.placeholder.com/150?text=U" class="profile-img">
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
        <div style="text-align: left;"><label>เปลี่ยนรูปโปรไฟล์:</label><input type="file" name="profile_picture" accept="image/png, image/jpeg, image/gif"></div>
        <div style="text-align: left;"><label>ข้อมูลติดต่อ:</label><textarea name="contact_info" rows="4"><?php echo htmlspecialchars($user_data['contact_info'] ?? ''); ?></textarea></div>
        <button type="submit">บันทึกข้อมูล</button>
    </form>
    
    <div class="bottom-actions">
        <a href="index.php" style="text-decoration: none; color: gray;">← กลับไปหน้าแรก</a>
        <a href="logout.php" style="color: white; background-color: #dc3545; padding: 8px 15px; text-decoration: none; border-radius: 4px;">ออกจากระบบ</a>
    </div>
</div>

<?php if ($user_data['role'] == 'admin'): ?>
<div id="adminMenuModal" class="modal" style="z-index: 3000;">
    <div class="modal-content" style="max-width: 300px; text-align: center;">
        <span class="close-btn" onclick="closeAdminMenu()">&times;</span>
        <h3 style="margin-top: 0; color: #333;">🛠️ เมนูผู้ดูแลระบบ</h3>
        <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 20px;">
        
        <a href="search_users.php" class="search-btn" style="display: block; text-decoration: none; margin-bottom: 15px; padding: 12px; border-radius: 8px;">👥 ค้นหารายชื่อผู้ใช้</a>
        
        <a href="logs.php" class="search-btn" style="display: block; text-decoration: none; background-color: #17a2b8; padding: 12px; border-radius: 8px;">📜 ประวัติการเข้าใช้งาน (Log)</a>
    </div>
</div>

<script>
    function openAdminMenu() { document.getElementById('adminMenuModal').style.display = 'block'; }
    function closeAdminMenu() { document.getElementById('adminMenuModal').style.display = 'none'; }
    window.onclick = function(event) {
        let modal = document.getElementById('adminMenuModal');
        if (event.target == modal) closeAdminMenu();
    }
</script>
<?php endif; ?>

</body>
</html>
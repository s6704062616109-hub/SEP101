<?php
session_start();
require 'db.php';

// เช็กว่าเข้าสู่ระบบหรือยัง
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// ================= 1. ส่วนบันทึกข้อมูลเมื่อมีการกดปุ่ม "บันทึกข้อมูล" =================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $contact_info = $_POST['contact_info'];
    $profile_picture_path = "";

    // ตรวจสอบว่ามีการอัปโหลดไฟล์รูปภาพมาด้วยไหม
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_picture']['type'];
        
        // เช็กนามสกุลไฟล์ว่าใช่รูปภาพไหม
        if (in_array($file_type, $allowed_types)) {
            // ตั้งชื่อไฟล์ใหม่ไม่ให้ซ้ำกัน (ใช้เวลาปัจจุบันต่อท้าย)
            $new_filename = time() . "_" . basename($_FILES['profile_picture']['name']);
            $target_dir = "uploads/profiles/";
            $target_file = $target_dir . $new_filename;

            // ย้ายไฟล์จากไฟล์ชั่วคราว ไปไว้ในโฟลเดอร์ uploads/profiles/ ของเรา
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                $profile_picture_path = $target_file;
            } else {
                $message = "<span style='color: red;'>เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ</span>";
            }
        } else {
            $message = "<span style='color: red;'>อัปโหลดได้เฉพาะไฟล์ JPG, PNG และ GIF เท่านั้น</span>";
        }
    }

    // อัปเดตข้อมูลลงฐานข้อมูล
    if ($profile_picture_path != "") {
        // ถ้ามีการเปลี่ยนรูปด้วย อัปเดตทั้งคู่
        $sql = "UPDATE users SET contact_info = ?, profile_picture = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $contact_info, $profile_picture_path, $user_id);
    } else {
        // ถ้าไม่ได้เปลี่ยนรูป อัปเดตแค่ข้อมูลติดต่อ
        $sql = "UPDATE users SET contact_info = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $contact_info, $user_id);
    }

    if ($stmt->execute()) {
        $message = "<span style='color: green;'>บันทึกข้อมูลโปรไฟล์สำเร็จ!</span>";
    }
    $stmt->close();
}

// ================= 2. ส่วนดึงข้อมูลปัจจุบันมาแสดงผล =================
$sql = "SELECT username, profile_picture, contact_info FROM users WHERE id = ?";
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
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 20px; }
        .profile-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); max-width: 500px; margin: auto; text-align: center; }
        .profile-img { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #007bff; margin-bottom: 15px; }
        input[type="file"], textarea { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; width: 100%; margin-top: 10px;}
        button:hover { background-color: #0056b3; }
        
        /* จัดกลุ่มปุ่มด้านล่างให้อยู่ซ้าย-ขวา */
        .footer-links { display: flex; justify-content: space-between; align-items: center; margin-top: 25px; border-top: 1px solid #eee; padding-top: 15px; }
        .back-link { text-decoration: none; color: gray; font-size: 14px; }
        .back-link:hover { color: #333; }
        
        /* ตกแต่งปุ่มออกจากระบบให้อยู่ในหน้าโปรไฟล์ */
        .logout-link { text-decoration: none; color: #dc3545; font-size: 14px; font-weight: bold; }
        .logout-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="profile-container">
    <h2>โปรไฟล์ของ <?php echo $user_data['username']; ?></h2>
    
    <?php if($message != "") echo "<p>$message</p>"; ?>

    <?php if (!empty($user_data['profile_picture'])): ?>
        <img src="<?php echo $user_data['profile_picture']; ?>" class="profile-img" alt="Profile Picture">
    <?php else: ?>
        <img src="https://via.placeholder.com/150?text=No+Image" class="profile-img" alt="Default Profile">
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
        <div style="text-align: left;">
            <label style="font-weight: bold; font-size: 14px;">เปลี่ยนรูปโปรไฟล์:</label>
            <input type="file" name="profile_picture" accept="image/png, image/jpeg, image/gif">
        </div>
        
        <div style="text-align: left;">
            <label style="font-weight: bold; font-size: 14px;">ข้อมูลติดต่อ (เบอร์โทร, Line ID, หรือ Facebook):</label>
            <textarea name="contact_info" rows="3" placeholder="กรอกข้อมูลเพื่อให้ผู้ที่พบของสามารถติดต่อคุณได้..."><?php echo htmlspecialchars($user_data['contact_info'] ?? ''); ?></textarea>
        </div>

        <button type="submit">บันทึกข้อมูล</button>
    </form>
    
    <div class="footer-links">
        <a href="index.php" class="back-link">← กลับไปหน้าแรก</a>
        <a href="logout.php" class="logout-link">ออกจากระบบ 🚪</a>
    </div>
</div>

</body>
</html>
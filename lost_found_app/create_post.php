<?php
session_start();
require 'db.php';

// เช็กว่าเข้าสู่ระบบหรือยัง
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

// ================= ส่วนบันทึกข้อมูลเมื่อกดปุ่ม "โพสต์" =================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $post_type = $_POST['post_type']; // รับค่าว่าเป็น 'lost' (ของหาย) หรือ 'found' (พบของ)
    $user_id = $_SESSION['user_id'];
    
    $image_path = NULL; // ค่าเริ่มต้นถ้าไม่ได้อัปโหลดรูป

    // ตรวจสอบและจัดการอัปโหลดรูปภาพ (ถ้ามีการแนบรูปมา)
    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['post_image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            // ตั้งชื่อไฟล์ใหม่ด้วยเวลา เพื่อป้องกันไฟล์ชื่อซ้ำกัน
            $new_filename = time() . "_" . basename($_FILES['post_image']['name']);
            $target_dir = "uploads/posts/";
            
            // เช็กว่าสร้างโฟลเดอร์ไว้หรือยัง ถ้ายังให้ระบบสร้างให้เลย
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $target_file = $target_dir . $new_filename;

            if (move_uploaded_file($_FILES['post_image']['tmp_name'], $target_file)) {
                $image_path = $target_file; // เก็บที่อยู่ไฟล์ไว้บันทึกลงฐานข้อมูล
            } else {
                $message = "<span style='color: red;'>เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ</span>";
            }
        } else {
            $message = "<span style='color: red;'>อัปโหลดได้เฉพาะไฟล์ JPG, PNG และ GIF เท่านั้น</span>";
        }
    }

    // ถ้าไม่มี error เรื่องการอัปโหลด ให้บันทึกข้อมูลลงฐานข้อมูล
    if (empty($message) && !empty($title) && !empty($description)) {
        // เตรียมคำสั่ง SQL บันทึกลงตาราง posts
        $sql = "INSERT INTO posts (user_id, post_type, title, description, image_url) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issss", $user_id, $post_type, $title, $description, $image_path);

        if ($stmt->execute()) {
            $message = "<span style='color: green;'>✨ สร้างโพสต์สำเร็จแล้ว! <a href='index.php'>กลับไปหน้าฟีด</a></span>";
        } else {
            $message = "<span style='color: red;'>เกิดข้อผิดพลาดในการบันทึกข้อมูล กรุณาลองใหม่</span>";
        }
        $stmt->close();
    } else if (empty($message)) {
        $message = "<span style='color: red;'>กรุณากรอกหัวข้อและรายละเอียดให้ครบถ้วน</span>";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สร้างโพสต์ใหม่ - ระบบแจ้งของหาย</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 20px; }
        .post-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); max-width: 600px; margin: auto; }
        
        h2 { text-align: center; color: #333; margin-top: 0; }
        
        label { font-weight: bold; margin-top: 15px; display: block; color: #555; }
        input[type="text"], textarea, select, input[type="file"] { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-family: inherit; }
        
        button { background-color: #28a745; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; width: 100%; margin-top: 20px; font-weight: bold; }
        button:hover { background-color: #218838; }
        
        .back-link { display: block; text-align: center; margin-top: 15px; text-decoration: none; color: gray; }
        .back-link:hover { color: #333; }
        
        .message-box { text-align: center; margin-bottom: 15px; font-weight: bold; }
    </style>
</head>
<body>

<div class="post-container">
    <h2>📝 สร้างโพสต์ใหม่</h2>
    
    <div class="message-box">
        <?php if($message != "") echo $message; ?>
    </div>

    <form method="POST" action="" enctype="multipart/form-data">
        
        <label>หมวดหมู่:</label>
        <select name="post_type" required>
            <option value="lost">🔍 แจ้งของหาย (ตามหาสิ่งของ)</option>
            <option value="found">💡 แจ้งพบของ (ตามหาเจ้าของ)</option>
        </select>

        <label>หัวข้อ:</label>
        <input type="text" name="title" placeholder="เช่น กระเป๋าสตางค์สีดำหายที่โรงอาหาร..." required>
        
        <label>รายละเอียด:</label>
        <textarea name="description" rows="5" placeholder="ระบุรายละเอียดเพิ่มเติม เช่น ยี่ห้อ, จุดที่คาดว่าหาย/จุดที่พบ, เวลาที่เกิดเหตุ..." required></textarea>

        <label>แนบรูปภาพ (ถ้ามี):</label>
        <input type="file" name="post_image" accept="image/png, image/jpeg, image/gif">

        <button type="submit">บันทึกโพสต์</button>
    </form>
    
    <a href="index.php" class="back-link">← ยกเลิกและกลับไปหน้าแรก</a>
</div>

</body>
</html>
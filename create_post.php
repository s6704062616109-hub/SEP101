<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $post_type = $_POST['post_type']; 
    
    // 1. รับค่า Checkbox แบบ Array แล้วเชื่อมกันด้วยลูกน้ำ (,)
    $item_category_arr = isset($_POST['item_category']) ? $_POST['item_category'] : ['อื่นๆ'];
    $item_category = implode(", ", $item_category_arr); 

    $user_id = $_SESSION['user_id'];
    $image_path = NULL; 

    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['post_image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $new_filename = time() . "_" . basename($_FILES['post_image']['name']);
            $target_dir = "uploads/posts/";
            if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
            $target_file = $target_dir . $new_filename;

            if (move_uploaded_file($_FILES['post_image']['tmp_name'], $target_file)) {
                $image_path = $target_file; 
            } else {
                $message = "<span style='color: red;'>เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ</span>";
            }
        } else {
            $message = "<span style='color: red;'>อัปโหลดได้เฉพาะไฟล์ JPG, PNG และ GIF เท่านั้น</span>";
        }
    }

    if (empty($message) && !empty($title) && !empty($description)) {
        $sql = "INSERT INTO posts (user_id, post_type, item_category, title, description, image_url) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssss", $user_id, $post_type, $item_category, $title, $description, $image_path);

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
    <link rel="stylesheet" href="style.css">
    <style>
        .post-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); max-width: 600px; margin: auto; margin-top: 20px; }
        label { font-weight: bold; margin-top: 15px; display: block; color: #555; }
        input[type="text"], textarea, select, input[type="file"] { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-family: inherit; }
        
        /* สไตล์สำหรับกล่อง Checkbox ให้ดูเป็นระเบียบ */
        .checkbox-group { background: #f9f9f9; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin-top: 5px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .checkbox-group label { margin-top: 0; font-weight: normal; cursor: pointer; display: flex; align-items: center; gap: 5px; }
        
        .btn-submit { background-color: #28a745; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; width: 100%; margin-top: 20px; font-weight: bold; }
        .btn-submit:hover { background-color: #218838; }
        .back-link { display: block; text-align: center; margin-top: 15px; text-decoration: none; color: gray; }
    </style>
</head>
<body>

<div class="post-container">
    <h2 style="text-align: center; margin-top:0;">📝 สร้างโพสต์ใหม่</h2>
    <div style="text-align: center; margin-bottom: 15px; font-weight: bold;">
        <?php if($message != "") echo $message; ?>
    </div>

    <form method="POST" action="" enctype="multipart/form-data">
        <label>ประเภทการแจ้ง:</label>
        <select name="post_type" required>
            <option value="lost">🔍 แจ้งของหาย (ตามหาสิ่งของ)</option>
            <option value="found">💡 แจ้งพบของ (ตามหาเจ้าของ)</option>
        </select>

        <label>หมวดหมู่สิ่งของ (เลือกได้มากกว่า 1 หมวดหมู่):</label>
        <div class="checkbox-group">
            <label><input type="checkbox" name="item_category[]" value="อุปกรณ์การเรียน"> 📚 อุปกรณ์การเรียน</label>
            <label><input type="checkbox" name="item_category[]" value="อุปกรณ์อิเล็กทรอนิกส์"> 📱 อุปกรณ์อิเล็กทรอนิกส์</label>
            <label><input type="checkbox" name="item_category[]" value="ของใช้ส่วนตัว"> 🎒 ของใช้ส่วนตัว</label>
            <label><input type="checkbox" name="item_category[]" value="เอกสารสำคัญ / บัตร"> 💳 เอกสารสำคัญ / บัตร</label>
            <label><input type="checkbox" name="item_category[]" value="อาหาร / กล่องข้าว"> 🍱 อาหาร / กล่องข้าว</label>
            <label><input type="checkbox" name="item_category[]" value="ของมีค่า / ของสำคัญอื่นๆ"> 💎 ของมีค่า</label>
            <label><input type="checkbox" name="item_category[]" value="อื่นๆ" checked> ❓ อื่นๆ</label>
        </div>

        <label>หัวข้อ:</label>
        <input type="text" name="title" placeholder="เช่น กระเป๋าสตางค์สีดำหายที่โรงอาหาร..." required>
        
        <label>รายละเอียด:</label>
        <textarea name="description" rows="5" placeholder="ระบุรายละเอียดเพิ่มเติม..." required></textarea>

        <label>แนบรูปภาพ (ถ้ามี):</label>
        <input type="file" name="post_image" accept="image/png, image/jpeg, image/gif">

        <button type="submit" class="btn-submit">บันทึกโพสต์</button>
    </form>
    <a href="index.php" class="back-link">← ยกเลิกและกลับไปหน้าแรก</a>
</div>

</body>
</html>
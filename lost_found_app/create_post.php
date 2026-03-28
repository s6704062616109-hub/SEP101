<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $post_type = $_POST['post_type']; 
    $item_category_arr = isset($_POST['item_category']) ? $_POST['item_category'] : ['อื่นๆ'];
    $item_category = implode(", ", $item_category_arr); 
    $user_id = $_SESSION['user_id'];
    
    // ================= ระบบอัปโหลดรูปภาพ (จำกัด 10 รูป) =================
    $image_paths = []; 
    if (isset($_FILES['post_images']) && !empty($_FILES['post_images']['name'][0])) {
        $file_count = count($_FILES['post_images']['name']);
        
        // เช็กฝั่งเซิร์ฟเวอร์ ถ้าเกิน 10 รูปให้บล็อกทันที
        if ($file_count > 10) {
            $message = "<span style='color: red;'>❌ อัปโหลดรูปภาพได้สูงสุดไม่เกิน 10 รูปเท่านั้นครับ</span>";
        } else {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $target_dir = "uploads/posts/";
            if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
            
            for ($i = 0; $i < $file_count; $i++) { 
                if ($_FILES['post_images']['error'][$i] == 0) {
                    $file_type = $_FILES['post_images']['type'][$i];
                    if (in_array($file_type, $allowed_types)) {
                        $new_filename = time() . "_" . $i . "_" . basename($_FILES['post_images']['name'][$i]);
                        $target_file = $target_dir . $new_filename;
                        if (move_uploaded_file($_FILES['post_images']['tmp_name'][$i], $target_file)) {
                            $image_paths[] = $target_file; 
                        }
                    }
                }
            }
        }
    }
    
    // ถ้าไม่มี error เรื่องจำนวนรูป ให้เซฟลงฐานข้อมูล
    if (empty($message) && !empty($title) && !empty($description)) {
        $image_path_string = !empty($image_paths) ? implode(",", $image_paths) : NULL;
        $sql = "INSERT INTO posts (user_id, post_type, item_category, title, description, image_url) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssss", $user_id, $post_type, $item_category, $title, $description, $image_path_string);
        if ($stmt->execute()) { $message = "<span style='color: green;'>✨ สร้างโพสต์สำเร็จแล้ว! <a href='index.php'>กลับไปหน้าฟีด</a></span>"; } 
        else { $message = "<span style='color: red;'>เกิดข้อผิดพลาดในการบันทึกข้อมูล</span>"; }
        $stmt->close();
    } else if (empty($message)) { 
        $message = "<span style='color: red;'>กรุณากรอกข้อมูลให้ครบถ้วน</span>"; 
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
        .checkbox-group { background: #f9f9f9; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin-top: 5px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .btn-submit { background-color: #28a745; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; width: 100%; margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body>

<div class="post-container">
    <h2 style="text-align: center; margin-top:0;">📝 สร้างโพสต์ใหม่</h2>
    <div style="text-align: center; margin-bottom: 15px; font-weight: bold;"><?php echo $message; ?></div>

    <form id="postForm" method="POST" action="" enctype="multipart/form-data">
        <label>ประเภทการแจ้ง:</label>
        <select name="post_type" required>
            <option value="lost">🔍 แจ้งของหาย (ตามหาสิ่งของ)</option>
            <option value="found">💡 แจ้งพบของ (ตามหาเจ้าของ)</option>
        </select>

        <label>หมวดหมู่สิ่งของ (เลือกได้มากกว่า 1):</label>
        <div class="checkbox-group">
            <label><input type="checkbox" name="item_category[]" value="อุปกรณ์การเรียน"> 📚 อุปกรณ์การเรียน</label>
            <label><input type="checkbox" name="item_category[]" value="อุปกรณ์อิเล็กทรอนิกส์"> 📱 อิเล็กทรอนิกส์</label>
            <label><input type="checkbox" name="item_category[]" value="ของใช้ส่วนตัว"> 🎒 ของใช้ส่วนตัว</label>
            <label><input type="checkbox" name="item_category[]" value="เอกสารสำคัญ / บัตร"> 💳 เอกสาร/บัตร</label>
            <label><input type="checkbox" name="item_category[]" value="อาหาร / กล่องข้าว"> 🍱 อาหาร/กล่องข้าว</label>
            <label><input type="checkbox" name="item_category[]" value="ของมีค่า / ของสำคัญอื่นๆ"> 💎 ของมีค่า</label>
            <label><input type="checkbox" name="item_category[]" value="อื่นๆ" checked> ❓ อื่นๆ</label>
        </div>

        <label>หัวข้อ:</label>
        <input type="text" name="title" placeholder="หัวข้อโพสต์..." required>
        <label>รายละเอียด:</label>
        <textarea name="description" rows="5" required></textarea>

        <label>แนบรูปภาพ <span style="color:#dc3545;">(สูงสุดไม่เกิน 10 รูป)</span>:</label>
        <input type="file" id="postImages" name="post_images[]" accept="image/png, image/jpeg, image/gif" multiple>

        <button type="submit" class="btn-submit">บันทึกโพสต์</button>
    </form>
    <a href="index.php" style="display: block; text-align: center; margin-top: 15px; color: gray; text-decoration: none;">← กลับไปหน้าแรก</a>
</div>

<script>
// เช็กฝั่งหน้าเว็บตอนผู้ใช้กดอัปโหลด ว่าเลือกเกิน 10 ไฟล์หรือไม่
document.getElementById('postForm').addEventListener('submit', function(e) {
    var fileInput = document.getElementById('postImages');
    if(fileInput.files.length > 10) {
        e.preventDefault(); // เบรกไม่ให้บันทึก
        alert("⚠️ คุณเลือกรูปภาพเกิน 10 รูป กรุณาเลือกใหม่ครับ");
        fileInput.value = ''; // ล้างค่าที่เลือกไว้
    }
});
</script>

</body>
</html>
<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$post_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$message = "";

// ดึงข้อมูลเก่ามาแสดงในฟอร์ม
$sql = "SELECT * FROM posts WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $post_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("ไม่พบโพสต์ หรือคุณไม่มีสิทธิ์แก้ไขโพสต์นี้");
}
$post = $result->fetch_assoc();

// เปลี่ยน String ของหมวดหมู่ ให้กลายเป็น Array เพื่อเอาไปเช็ก Checkbox
$saved_categories = explode(", ", $post['item_category']);

// เมื่อมีการกด "บันทึกการแก้ไข"
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $post_type = $_POST['post_type'];
    $status = $_POST['status']; // รับค่าสถานะ
    
    $item_category_arr = isset($_POST['item_category']) ? $_POST['item_category'] : ['อื่นๆ'];
    $item_category = implode(", ", $item_category_arr); 

    // ใช้รูปเดิมเป็นค่าตั้งต้น
    $image_path = $post['image_url']; 

    // ถ้ามีการอัปโหลดรูปใหม่
    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['post_image']['type'], $allowed_types)) {
            $new_filename = time() . "_" . basename($_FILES['post_image']['name']);
            $target_file = "uploads/posts/" . $new_filename;
            if (move_uploaded_file($_FILES['post_image']['tmp_name'], $target_file)) {
                $image_path = $target_file; // เปลี่ยนไปใช้รูปใหม่
            }
        }
    }

    if (!empty($title) && !empty($description)) {
        // อัปเดตข้อมูลลงฐานข้อมูล (รวมถึงสถานะ)
        $update_sql = "UPDATE posts SET post_type=?, item_category=?, status=?, title=?, description=?, image_url=? WHERE id=? AND user_id=?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssssssii", $post_type, $item_category, $status, $title, $description, $image_path, $post_id, $user_id);
        
        if ($update_stmt->execute()) {
            header("Location: my_posts.php"); // เซฟเสร็จเด้งกลับไปหน้าโพสต์ของฉัน
            exit();
        } else {
            $message = "<span style='color: red;'>เกิดข้อผิดพลาดในการบันทึก</span>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขโพสต์</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .post-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); max-width: 600px; margin: auto; margin-top: 20px; }
        label { font-weight: bold; margin-top: 15px; display: block; color: #555; }
        input[type="text"], textarea, select, input[type="file"] { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .checkbox-group { background: #f9f9f9; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin-top: 5px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .btn-submit { background-color: #ffc107; color: #333; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; width: 100%; margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body>

<div class="post-container">
    <h2 style="text-align: center; margin-top:0;">✏️ แก้ไขโพสต์</h2>
    <div style="text-align: center; margin-bottom: 15px; font-weight: bold;"><?php echo $message; ?></div>

    <form method="POST" action="" enctype="multipart/form-data">
        
        <label style="color: #dc3545;">สถานะโพสต์:</label>
        <select name="status" style="background-color: #fff3cd;" required>
            <option value="active" <?php if($post['status'] == 'active') echo 'selected'; ?>>⏳ ยังไม่สำเร็จ</option>
            <option value="resolved" <?php if($post['status'] == 'resolved') echo 'selected'; ?>>✅ สำเร็จแล้ว (พบของ/คืนของแล้ว)</option>
        </select>

        <label>ประเภทการแจ้ง:</label>
        <select name="post_type" required>
            <option value="lost" <?php if($post['post_type'] == 'lost') echo 'selected'; ?>>🔍 แจ้งของหาย</option>
            <option value="found" <?php if($post['post_type'] == 'found') echo 'selected'; ?>>💡 แจ้งพบของ</option>
        </select>

        <label>หมวดหมู่สิ่งของ:</label>
        <div class="checkbox-group">
            <label><input type="checkbox" name="item_category[]" value="อุปกรณ์การเรียน" <?php if(in_array('อุปกรณ์การเรียน', $saved_categories)) echo 'checked'; ?>> 📚 อุปกรณ์การเรียน</label>
            <label><input type="checkbox" name="item_category[]" value="อุปกรณ์อิเล็กทรอนิกส์" <?php if(in_array('อุปกรณ์อิเล็กทรอนิกส์', $saved_categories)) echo 'checked'; ?>> 📱 อุปกรณ์อิเล็กทรอนิกส์</label>
            <label><input type="checkbox" name="item_category[]" value="ของใช้ส่วนตัว" <?php if(in_array('ของใช้ส่วนตัว', $saved_categories)) echo 'checked'; ?>> 🎒 ของใช้ส่วนตัว</label>
            <label><input type="checkbox" name="item_category[]" value="เอกสารสำคัญ / บัตร" <?php if(in_array('เอกสารสำคัญ / บัตร', $saved_categories)) echo 'checked'; ?>> 💳 เอกสารสำคัญ / บัตร</label>
            <label><input type="checkbox" name="item_category[]" value="อาหาร / กล่องข้าว" <?php if(in_array('อาหาร / กล่องข้าว', $saved_categories)) echo 'checked'; ?>> 🍱 อาหาร / กล่องข้าว</label>
            <label><input type="checkbox" name="item_category[]" value="ของมีค่า / ของสำคัญอื่นๆ" <?php if(in_array('ของมีค่า / ของสำคัญอื่นๆ', $saved_categories)) echo 'checked'; ?>> 💎 ของมีค่า</label>
            <label><input type="checkbox" name="item_category[]" value="อื่นๆ" <?php if(in_array('อื่นๆ', $saved_categories)) echo 'checked'; ?>> ❓ อื่นๆ</label>
        </div>

        <label>หัวข้อ:</label>
        <input type="text" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
        
        <label>รายละเอียด:</label>
        <textarea name="description" rows="5" required><?php echo htmlspecialchars($post['description']); ?></textarea>

        <label>เปลี่ยนรูปภาพ (หากไม่ต้องการเปลี่ยน ไม่ต้องเลือกไฟล์):</label>
        <?php if(!empty($post['image_url'])): ?>
            <img src="<?php echo $post['image_url']; ?>" style="max-height: 100px; display: block; margin-bottom: 10px; border-radius: 4px;">
        <?php endif; ?>
        <input type="file" name="post_image" accept="image/png, image/jpeg, image/gif">

        <button type="submit" class="btn-submit">บันทึกการแก้ไข</button>
    </form>
    <a href="my_posts.php" style="display: block; text-align: center; margin-top: 15px; text-decoration: none; color: gray;">← ยกเลิก</a>
</div>

</body>
</html>
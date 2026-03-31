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

// ข้อมูล User สำหรับ Header
$sql_user = "SELECT username, role, profile_picture FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();
$my_profile_image = !empty($user_data['profile_picture']) ? $user_data['profile_picture'] : 'https://via.placeholder.com/40?text=U';

// ดึงข้อมูลโพสต์เดิม
$sql = "SELECT * FROM posts WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $post_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Post not found or you do not have permission to edit.");
}
$post = $result->fetch_assoc();
$saved_categories = explode(", ", $post['item_category']);

// เมื่อกดบันทึก
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $post_type = $_POST['post_type'];
    $status = $_POST['status']; 
    
    $item_category_arr = isset($_POST['item_category']) ? $_POST['item_category'] : ['Others'];
    $item_category = implode(", ", $item_category_arr); 

    // อัปโหลดรูป (ถ้ามีการเลือกไฟล์ใหม่) เราจะแทนที่รูปเดิม
    $image_path_string = $post['image_url']; // ใช้รูปเดิมเป็นค่าตั้งต้น
    $image_paths = []; 
    
    if (isset($_FILES['post_images']) && !empty($_FILES['post_images']['name'][0])) {
        $file_count = count($_FILES['post_images']['name']);
        if ($file_count > 10) {
            $message = "<span style='color: #ef4444;'>❌ Maximum 10 images allowed.</span>";
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
            if(!empty($image_paths)) {
                $image_path_string = implode(",", $image_paths); // อัปเดต Path ใหม่
            }
        }
    }

    if (empty($message) && !empty($title) && !empty($description)) {
        $update_sql = "UPDATE posts SET post_type=?, item_category=?, status=?, title=?, description=?, image_url=? WHERE id=? AND user_id=?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssssssii", $post_type, $item_category, $status, $title, $description, $image_path_string, $post_id, $user_id);
        
        if ($update_stmt->execute()) {
            header("Location: my_posts.php");
            exit();
        } else {
            $message = "<span style='color: #ef4444;'>Error saving updates.</span>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post - Lost & Found</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="app-container">
    <header class="top-bar">
        <div class="header-logo-group">
            <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
            <a href="index.php" class="header-logo">Lost & Found</a>
        </div>
        <div class="header-right-group" style="margin-left:auto;">
            <a href="profile.php" class="header-user-menu">
                <img src="<?php echo htmlspecialchars($my_profile_image); ?>" class="header-profile-img">
                <span class="header-username"><?php echo htmlspecialchars($user_data['username']); ?></span>
            </a>
        </div>
    </header>

    <div class="overlay" id="mobileOverlay" onclick="toggleSidebar()"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-menu">
            <div class="menu-label">Discover</div>
            <a href="index.php" class="menu-item"><span class="icon">🏠</span> Home</a>
            <div class="menu-label" style="margin-top: 20px;">My Space</div>
            <a href="my_posts.php" class="menu-item active"><span class="icon">📁</span> My Posts</a>
            <?php if ($user_data['role'] == 'admin'): ?>
                <div class="menu-label" style="margin-top: 20px; color:#ef4444;">Admin Only</div>
                <a href="search_users.php" class="menu-item" style="color:#ef4444;"><span class="icon">⚙️</span> Backend (Admin)</a>
                <a href="logs.php" class="menu-item" style="color:#ef4444;"><span class="icon">📜</span> System Logs</a>
            <?php endif; ?>
        </div>
        <div class="sidebar-footer">
            <a href="profile.php" class="menu-item"><span class="icon">👤</span> Settings</a>
            <a href="logout.php" class="menu-item" style="color: #ef4444;"><span class="icon">🚪</span> Sign Out</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="content-wrapper" style="display:flex; justify-content:center;">
            <div class="post-card" style="width: 100%; max-width: 600px;">
                <h2 style="text-align: center; margin-top:0; color: var(--warning);">✏️ Edit Post</h2>
                <div style="text-align: center; margin-bottom: 15px; font-weight: bold;"><?php echo $message; ?></div>

                <form id="postForm" method="POST" action="" enctype="multipart/form-data">
                    
                    <label style="font-weight:bold; display:block; margin-bottom:8px; color: var(--warning);">Post Status:</label>
                    <select name="status" style="width: 100%; padding: 10px; border-radius: 8px; margin-bottom: 15px; border: 1px solid var(--warning);" required>
                        <option value="active" <?php if($post['status'] == 'active') echo 'selected'; ?>>⏳ Pending</option>
                        <option value="resolved" <?php if($post['status'] == 'resolved') echo 'selected'; ?>>✅ Resolved (Found/Returned)</option>
                    </select>

                    <label style="font-weight:bold; display:block; margin-bottom:8px;">Post Type:</label>
                    <select name="post_type" style="width: 100%; padding: 10px; border-radius: 8px; margin-bottom: 15px;" required>
                        <option value="lost" <?php if($post['post_type'] == 'lost') echo 'selected'; ?>>🔍 Lost Item</option>
                        <option value="found" <?php if($post['post_type'] == 'found') echo 'selected'; ?>>💡 Found Item</option>
                    </select>

                    <label style="font-weight:bold; display:block; margin-bottom:8px;">Category (Select multiple):</label>
                    <div class="checkbox-group" style="margin-bottom: 15px;">
                        <label style="cursor:pointer; display:flex; align-items:center; gap:5px;"><input type="checkbox" name="item_category[]" value="Study Tools" <?php if(in_array('Study Tools', $saved_categories)) echo 'checked'; ?>> 📚 Study Tools</label>
                        <label style="cursor:pointer; display:flex; align-items:center; gap:5px;"><input type="checkbox" name="item_category[]" value="Electronics" <?php if(in_array('Electronics', $saved_categories)) echo 'checked'; ?>> 📱 Electronics</label>
                        <label style="cursor:pointer; display:flex; align-items:center; gap:5px;"><input type="checkbox" name="item_category[]" value="Personal Items" <?php if(in_array('Personal Items', $saved_categories)) echo 'checked'; ?>> 🎒 Personal Items</label>
                        <label style="cursor:pointer; display:flex; align-items:center; gap:5px;"><input type="checkbox" name="item_category[]" value="Documents/Cards" <?php if(in_array('Documents/Cards', $saved_categories)) echo 'checked'; ?>> 💳 Documents/Cards</label>
                        <label style="cursor:pointer; display:flex; align-items:center; gap:5px;"><input type="checkbox" name="item_category[]" value="Food/Boxes" <?php if(in_array('Food/Boxes', $saved_categories)) echo 'checked'; ?>> 🍱 Food/Boxes</label>
                        <label style="cursor:pointer; display:flex; align-items:center; gap:5px;"><input type="checkbox" name="item_category[]" value="Valuables" <?php if(in_array('Valuables', $saved_categories)) echo 'checked'; ?>> 💎 Valuables</label>
                        <label style="cursor:pointer; display:flex; align-items:center; gap:5px;"><input type="checkbox" name="item_category[]" value="Others" <?php if(in_array('Others', $saved_categories)) echo 'checked'; ?>> ❓ Others</label>
                    </div>

                    <label style="font-weight:bold; display:block; margin-bottom:8px;">Title:</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" style="width: 100%; padding: 10px; border-radius: 8px; margin-bottom: 15px;" required>
                    
                    <label style="font-weight:bold; display:block; margin-bottom:8px;">Description:</label>
                    <textarea name="description" rows="5" style="width: 100%; padding: 10px; border-radius: 8px; margin-bottom: 15px;" required><?php echo htmlspecialchars($post['description']); ?></textarea>

                    <label style="font-weight:bold; display:block; margin-bottom:8px;">Change Images <span style="color:#ef4444;">(Leave empty to keep current images)</span>:</label>
                    <?php if(!empty($post['image_url'])): ?>
                        <p style="font-size:12px; color:var(--text-muted);">Current images attached. Uploading new images will replace them.</p>
                    <?php endif; ?>
                    <input type="file" id="postImages" name="post_images[]" accept="image/png, image/jpeg, image/gif" style="width: 100%; padding: 10px; border-radius: 8px; margin-bottom: 20px;" multiple>

                    <button type="submit" class="btn btn-warning" style="width: 100%; padding: 15px; color:#111;">Update Post</button>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobileOverlay');
    sidebar.classList.toggle('active');
    overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
}
document.getElementById('postForm').addEventListener('submit', function(e) {
    var fileInput = document.getElementById('postImages');
    if(fileInput.files.length > 10) {
        e.preventDefault();
        alert("⚠️ Maximum 10 images allowed. Please re-select.");
        fileInput.value = '';
    }
});
</script>
</body>
</html>
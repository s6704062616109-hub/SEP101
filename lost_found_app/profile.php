<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// ส่วนบันทึกข้อมูล
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ป้องกัน XSS และ Code Injection
    $contact_info = htmlspecialchars(strip_tags(trim($_POST['contact_info'])), ENT_QUOTES, 'UTF-8');
    
    $profile_picture_path = "";

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['profile_picture']['type'], $allowed_types)) {
            $new_filename = time() . "_" . basename($_FILES['profile_picture']['name']);
            $target_file = "uploads/profiles/" . $new_filename;
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                $profile_picture_path = $target_file;
            } else { $message = "<span style='color: #ef4444;'>Error uploading image.</span>"; }
        } else { $message = "<span style='color: #ef4444;'>Only JPG, PNG, and GIF allowed.</span>"; }
    }

    if ($profile_picture_path != "") {
        $stmt = $conn->prepare("UPDATE users SET contact_info = ?, profile_picture = ? WHERE id = ?");
        $stmt->bind_param("ssi", $contact_info, $profile_picture_path, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET contact_info = ? WHERE id = ?");
        $stmt->bind_param("si", $contact_info, $user_id);
    }
    if ($stmt->execute()) { $message = "<span style='color: #22c55e;'>Profile updated successfully!</span>"; }
    $stmt->close();
}

$sql = "SELECT username, profile_picture, contact_info, role, is_banned FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($user_data['is_banned'] == 1) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$my_profile_image = !empty($user_data['profile_picture']) ? $user_data['profile_picture'] : 'https://via.placeholder.com/40?text=U';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - Lost & Found App</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="app-container">
    
    <header class="top-bar">
        <div class="header-logo-group">
            <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
            <a href="index.php" class="header-logo">Lost & Found</a>
        </div>
        
        <form method="GET" action="index.php" class="header-search-form">
            <input type="text" name="q" class="search-input" placeholder="Search items, locations...">
            <button type="submit" class="btn-header btn-header-primary">Search</button>
        </form>
        
        <div class="header-right-group">
            <a href="create_post.php" class="header-create-btn" title="Create Post">➕</a>
            <a href="profile.php" class="header-user-menu">
                <img src="<?php echo htmlspecialchars($my_profile_image); ?>" class="header-profile-img" alt="Profile">
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
            <a href="my_posts.php" class="menu-item"><span class="icon">📁</span> My Posts</a>
            
            <?php if ($user_data['role'] == 'admin'): ?>
                <div class="menu-label" style="margin-top: 20px; color:#ef4444;">Admin Only</div>
                <a href="search_users.php" class="menu-item" style="color:#ef4444;"><span class="icon">⚙️</span> Backend (Admin)</a>
                <a href="logs.php" class="menu-item" style="color:#ef4444;"><span class="icon">📜</span> System Logs</a>
            <?php endif; ?>
        </div>
        <div class="sidebar-footer">
            <a href="profile.php" class="menu-item active"><span class="icon">👤</span> Settings</a>
            <a href="logout.php" class="menu-item" style="color: #ef4444;"><span class="icon">🚪</span> Sign Out</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="content-wrapper" style="display: flex; justify-content: center; padding-top: 40px;">
            
            <div class="post-card" style="width: 100%; max-width: 500px; text-align: center;">
                <h2 style="margin-top: 0;">Profile Settings</h2>
                <p style="color: var(--text-muted); margin-bottom: 20px;">@<?php echo htmlspecialchars($user_data['username']); ?></p>
                
                <?php if($message != "") echo "<p style='font-weight:bold; margin-bottom: 15px;'>$message</p>"; ?>

                <img src="<?php echo htmlspecialchars($my_profile_image); ?>" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary); margin-bottom: 20px;">

                <form method="POST" action="" enctype="multipart/form-data" style="text-align: left;">
                    <label style="font-weight: bold; color: var(--text-muted); display: block; margin-bottom: 8px;">Change Profile Picture:</label>
                    <input type="file" name="profile_picture" accept="image/png, image/jpeg, image/gif" style="width: 100%; padding: 10px; border-radius: 8px; margin-bottom: 20px;">

                    <label style="font-weight: bold; color: var(--text-muted); display: block; margin-bottom: 8px;">Contact Info:</label>
                    <textarea name="contact_info" rows="4" style="width: 100%; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-family: inherit; resize: vertical;" placeholder="Phone number, Line ID, Facebook..."><?php echo htmlspecialchars($user_data['contact_info'] ?? ''); ?></textarea>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 16px;">Save Changes</button>
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
</script>

</body>
</html>
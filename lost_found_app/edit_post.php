<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) { header("Location: index.php"); exit(); }

$post_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// ดึงโปรไฟล์
$sql_user = "SELECT username, role, profile_picture FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();
$my_profile_image = !empty($user_data['profile_picture']) ? $user_data['profile_picture'] : 'https://via.placeholder.com/40?text=U';
$message = "";

// ดึงข้อมูลโพสต์เก่ามาแสดง
$sql = "SELECT * FROM posts WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $post_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) { die("Post not found or unauthorized."); }
$post = $result->fetch_assoc();
$saved_categories = explode(", ", $post['item_category']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $post_type = $_POST['post_type'];
    $status = $_POST['status']; 
    $item_category_arr = isset($_POST['item_category']) ? $_POST['item_category'] : ['Others'];
    $item_category = implode(", ", $item_category_arr); 

    $image_path_string = $post['image_url']; 
    
    if (isset($_FILES['post_images']) && !empty($_FILES['post_images']['name'][0])) {
        $image_paths = []; 
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
            if(!empty($image_paths)) { $image_path_string = implode(",", $image_paths); }
        }
    }

    if (empty($message) && !empty($title) && !empty($description)) {
        $update_sql = "UPDATE posts SET post_type=?, item_category=?, status=?, title=?, description=?, image_url=? WHERE id=? AND user_id=?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssssssii", $post_type, $item_category, $status, $title, $description, $image_path_string, $post_id, $user_id);
        
        if ($update_stmt->execute()) { header("Location: my_posts.php"); exit(); } 
        else { $message = "<span style='color: #ef4444;'>Error updating post.</span>"; }
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
            <a href="my_posts.php" class="menu-item"><span class="icon">📁</span> My Posts</a>
            <a href="#" class="menu-item" onclick="openNotificationModal()"><span class="icon">🔔</span> Notifications</a>
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
                <h2 style="text-align: center; margin-top:0;">✏️ Edit Post</h2>
                <div style="text-align: center; margin-bottom: 15px; font-weight: bold;"><?php echo $message; ?></div>

                <form id="postForm" method="POST" action="" enctype="multipart/form-data">
                    <label style="font-weight:bold; display:block; margin-bottom:8px; color: var(--warning);">Post Status:</label>
                    <select name="status" style="width: 100%; padding: 10px; border-radius: 8px; margin-bottom: 15px; border-color: var(--warning);" required>
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

                    <label style="font-weight:bold; display:block; margin-bottom:8px;">Change Images <span style="color:var(--text-muted); font-weight:normal;">(Leave empty to keep existing images)</span>:</label>
                    <input type="file" id="postImages" name="post_images[]" accept="image/png, image/jpeg, image/gif" style="width: 100%; padding: 10px; border-radius: 8px; margin-bottom: 20px;" multiple>

                    <button type="submit" class="btn btn-warning" style="width: 100%; padding: 15px;">Save Changes</button>
                </form>
            </div>
        </div>
    </main>
</div>

<div id="notificationModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <span class="close-btn" onclick="closeNotificationModal()">&times;</span>
        <div class="notif-header">
            <h3 style="margin: 0; color: var(--text-main);">🔔 Notifications</h3>
            <div style="display: flex; gap: 10px;">
                <button onclick="openMutedUsersModal()" class="notif-manage-btn">🔕 Muted Users</button>
                <button class="notif-clear-btn" onclick="clearAllNotifications()">Clear All</button>
            </div>
        </div>
        <div id="notificationList" style="max-height: 400px; overflow-y: auto; padding-right: 5px;"></div>
    </div>
</div>

<div id="mutedUsersModal" class="modal" style="z-index: 2100;">
    <div class="modal-content" style="max-width: 450px;">
        <span class="close-btn" onclick="closeMutedUsersModal()">&times;</span>
        <h3 style="margin-top: 0; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; color: var(--text-main);">🔕 Muted Users</h3>
        <div id="mutedUsersList" style="max-height: 300px; overflow-y: auto;"></div>
    </div>
</div>

<script>
function toggleSidebar() { document.getElementById('sidebar').classList.toggle('active'); document.getElementById('mobileOverlay').style.display = document.getElementById('sidebar').classList.contains('active') ? 'block' : 'none'; }
document.getElementById('postForm').addEventListener('submit', function(e) { var fileInput = document.getElementById('postImages'); if(fileInput.files.length > 10) { e.preventDefault(); alert("⚠️ Maximum 10 images allowed."); fileInput.value = ''; } });

function openNotificationModal() { document.getElementById('notificationModal').style.display = 'flex'; loadNotifications(); }
function closeNotificationModal() { document.getElementById('notificationModal').style.display = 'none'; }
function openMutedUsersModal() { document.getElementById('mutedUsersModal').style.display = 'flex'; loadMutedUsers(); }
function closeMutedUsersModal() { document.getElementById('mutedUsersModal').style.display = 'none'; }
function loadNotifications() {
    let list = document.getElementById('notificationList'); list.innerHTML = '<p style="text-align:center; color: var(--text-muted); margin-top: 20px;">Loading...</p>';
    fetch('notification_api.php?action=fetch').then(r => r.json()).then(data => {
        list.innerHTML = ''; if(data.length === 0) { list.innerHTML = '<p style="text-align:center; color: var(--text-muted); margin-top: 20px;">No new notifications</p>'; return; }
        data.forEach(n => {
            let img = n.profile_picture ? n.profile_picture : 'https://via.placeholder.com/45?text=U';
            let actionText = n.type === 'comment' ? 'commented on your post.' : 'replied to your comment.';
            let timeStr = new Date(n.created_at).toLocaleString('en-GB', { day:'numeric', month:'short', hour:'2-digit', minute:'2-digit' });
            list.innerHTML += `<div class="notif-item" onclick="window.location.href='index.php?post_id=${n.post_id}'"><img src="${img}" class="notif-avatar"><div class="notif-content"><p class="notif-text"><strong style="color:var(--primary);">${n.username}</strong> ${actionText}</p><p class="notif-time">${timeStr}</p></div><button class="notif-menu-btn" onclick="event.stopPropagation(); toggleNotifMenu(${n.id}, event)">⋮</button><div id="notif-menu-${n.id}" class="notif-dropdown"><div class="notif-dropdown-item" onclick="event.stopPropagation(); muteUser(${n.actor_id}, '${n.username}')">🔕 Mute notifications</div><div class="notif-dropdown-item danger" onclick="event.stopPropagation(); deleteNotification(${n.id})">🗑️ Delete</div></div></div>`;
        });
    });
}
function toggleNotifMenu(id, event) { event.stopPropagation(); document.querySelectorAll('.notif-dropdown').forEach(d => d.classList.remove('show')); document.getElementById('notif-menu-' + id).classList.toggle('show'); }
function clearAllNotifications() { if(!confirm("Clear all notifications?")) return; let fd = new FormData(); fd.append('action', 'delete_all'); fetch('notification_api.php', { method: 'POST', body: fd }).then(r=>r.text()).then(res=>{ if(res==='success') loadNotifications(); }); }
function deleteNotification(id) { let fd = new FormData(); fd.append('action', 'delete_one'); fd.append('notif_id', id); fetch('notification_api.php', { method: 'POST', body: fd }).then(r=>r.text()).then(res=>{ if(res==='success') loadNotifications(); }); }
function muteUser(mutedId, username) { if(!confirm(`Mute notifications from ${username}?`)) return; let fd = new FormData(); fd.append('action', 'mute_user'); fd.append('muted_id', mutedId); fetch('notification_api.php', { method: 'POST', body: fd }).then(r=>r.text()).then(res=>{ if(res==='success') { alert(`Muted ${username}`); loadNotifications(); } }); }
function loadMutedUsers() {
    let list = document.getElementById('mutedUsersList'); list.innerHTML = '<p style="text-align:center; color: var(--text-muted);">Loading...</p>';
    let fd = new FormData(); fd.append('action', 'fetch_muted');
    fetch('notification_api.php', { method: 'POST', body: fd }).then(r => r.json()).then(data => {
        list.innerHTML = ''; if(data.length === 0) { list.innerHTML = '<p style="text-align:center; color: var(--text-muted); padding: 20px;">No muted users.</p>'; return; }
        data.forEach(u => {
            let img = u.profile_picture ? u.profile_picture : 'https://via.placeholder.com/35?text=U';
            list.innerHTML += `<div style="display:flex; justify-content:space-between; align-items:center; padding:10px; border-bottom:1px solid var(--border-color);"><div style="display:flex; align-items:center; gap:10px;"><img src="${img}" style="width:35px; height:35px; border-radius:50%; object-fit:cover;"><span style="color:var(--text-main); font-weight:bold;">${u.username}</span></div><button onclick="unmuteUser(${u.muted_user_id})" class="btn btn-success" style="padding: 5px 10px; font-size:12px;">Unmute</button></div>`;
        });
    });
}
function unmuteUser(id) { let fd = new FormData(); fd.append('action', 'unmute_user'); fd.append('muted_id', id); fetch('notification_api.php', { method: 'POST', body: fd }).then(r=>r.text()).then(res=>{ if(res==='success') { loadMutedUsers(); loadNotifications(); } }); }
window.onclick = function(event) { document.querySelectorAll('.notif-dropdown').forEach(d => d.classList.remove('show')); let nModal = document.getElementById('notificationModal'); let mModal = document.getElementById('mutedUsersModal'); if (event.target == nModal) closeNotificationModal(); if (event.target == mModal) closeMutedUsersModal(); }
</script>
</body>
</html>
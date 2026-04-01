<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$user_id = $_SESSION['user_id'];
$sql_user = "SELECT username, role, profile_picture FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

$my_profile_image = !empty($user_data['profile_picture']) ? $user_data['profile_picture'] : 'https://via.placeholder.com/40?text=U';
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $post_type = $_POST['post_type']; 
    $item_category_arr = isset($_POST['item_category']) ? $_POST['item_category'] : ['Others'];
    $item_category = implode(", ", $item_category_arr); 
    
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
        }
    }
    
    if (empty($message) && !empty($title) && !empty($description)) {
        $image_path_string = !empty($image_paths) ? implode(",", $image_paths) : NULL;
        $sql = "INSERT INTO posts (user_id, post_type, item_category, title, description, image_url) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssss", $user_id, $post_type, $item_category, $title, $description, $image_path_string);
        if ($stmt->execute()) { $message = "<span style='color: #22c55e;'>✨ Post created successfully! <a href='index.php' style='color:#3b82f6;'>Go to Feed</a></span>"; } 
        else { $message = "<span style='color: #ef4444;'>Error saving data.</span>"; }
        $stmt->close();
    } else if (empty($message)) { 
        $message = "<span style='color: #ef4444;'>Please fill in all required fields.</span>"; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post - Lost & Found</title>
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
                <h2 style="text-align: center; margin-top:0;">📝 Create New Post</h2>
                <div style="text-align: center; margin-bottom: 15px; font-weight: bold;"><?php echo $message; ?></div>

                <form id="postForm" method="POST" action="" enctype="multipart/form-data">
                    <label style="font-weight:bold; display:block; margin-bottom:8px;">Post Type:</label>
                    <select name="post_type" style="width: 100%; padding: 10px; border-radius: 8px; margin-bottom: 15px;" required>
                        <option value="lost">🔍 Lost Item (Looking for item)</option>
                        <option value="found">💡 Found Item (Looking for owner)</option>
                    </select>

                    <label style="font-weight:bold; display:block; margin-bottom:8px;">Category (Select multiple):</label>
                    <div class="checkbox-group" style="margin-bottom: 15px;">
                        <label style="cursor:pointer; display:flex; align-items:center; gap:5px;"><input type="checkbox" name="item_category[]" value="Study Tools"> 📚 Study Tools</label>
                        <label style="cursor:pointer; display:flex; align-items:center; gap:5px;"><input type="checkbox" name="item_category[]" value="Electronics"> 📱 Electronics</label>
                        <label style="cursor:pointer; display:flex; align-items:center; gap:5px;"><input type="checkbox" name="item_category[]" value="Personal Items"> 🎒 Personal Items</label>
                        <label style="cursor:pointer; display:flex; align-items:center; gap:5px;"><input type="checkbox" name="item_category[]" value="Documents/Cards"> 💳 Documents/Cards</label>
                        <label style="cursor:pointer; display:flex; align-items:center; gap:5px;"><input type="checkbox" name="item_category[]" value="Food/Boxes"> 🍱 Food/Boxes</label>
                        <label style="cursor:pointer; display:flex; align-items:center; gap:5px;"><input type="checkbox" name="item_category[]" value="Valuables"> 💎 Valuables</label>
                        <label style="cursor:pointer; display:flex; align-items:center; gap:5px;"><input type="checkbox" name="item_category[]" value="Others" checked> ❓ Others</label>
                    </div>

                    <label style="font-weight:bold; display:block; margin-bottom:8px;">Title:</label>
                    <input type="text" name="title" id="titleInput" maxlength="150" style="width: 100%; padding: 10px; border-radius: 8px;" placeholder="Post title..." required>
                    <span class="char-counter" id="titleCount" style="margin-bottom: 15px;">0 / 150</span>
                    
                    <label style="font-weight:bold; display:block; margin-bottom:8px;">Description:</label>
                    <textarea name="description" rows="5" style="width: 100%; padding: 10px; border-radius: 8px; margin-bottom: 15px;" placeholder="More details..." required></textarea>

                    <label style="font-weight:bold; display:block; margin-bottom:8px;">Upload Images <span style="color:#ef4444;">(Max 10)</span>:</label>
                    <input type="file" id="postImages" name="post_images[]" accept="image/png, image/jpeg, image/gif" style="width: 100%; padding: 10px; border-radius: 8px; margin-bottom: 20px;" multiple>

                    <button type="submit" class="btn btn-success" style="width: 100%; padding: 15px;">Save Post</button>
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
// ตัวนับอักษรหัวข้อโพสต์
const titleInput = document.getElementById('titleInput');
const titleCount = document.getElementById('titleCount');
titleInput.addEventListener('input', () => {
    let len = titleInput.value.length;
    titleCount.innerText = `${len} / 150`;
    titleCount.style.color = len >= 150 ? 'var(--danger)' : 'var(--text-muted)';
});

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
<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
        <div class="header-right-group" style="margin-left:auto;">
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
            <a href="#" class="menu-item" onclick="openNotificationModal()"><span class="icon">🔔</span> Notifications</a>
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

window.onclick = function(event) {
    document.querySelectorAll('.notif-dropdown').forEach(d => d.classList.remove('show'));
    let nModal = document.getElementById('notificationModal');
    let mModal = document.getElementById('mutedUsersModal');
    if (event.target == nModal) closeNotificationModal();
    if (event.target == mModal) closeMutedUsersModal();
}
</script>
</body>
</html>
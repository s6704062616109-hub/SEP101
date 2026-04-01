<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: index.php"); exit(); }

$user_id = $_SESSION['user_id'];
$sql_user = "SELECT username, profile_picture FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();
$my_profile_image = !empty($user_data['profile_picture']) ? $user_data['profile_picture'] : 'https://via.placeholder.com/40?text=U';

$search_q = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT l.login_time, u.username FROM login_logs l JOIN users u ON l.user_id = u.id WHERE 1=1 ";
$params = []; $types = "";
if ($search_q !== '') {
    $sql .= " AND (u.username LIKE ? OR DATE_FORMAT(l.login_time, '%d/%m/%Y') LIKE ? OR DATE_FORMAT(l.login_time, '%Y-%m-%d') LIKE ?) ";
    $q_param = "%" . $search_q . "%"; $params = [$q_param, $q_param, $q_param]; $types = "sss";
}
$sql .= " ORDER BY l.login_time DESC";
$stmt = $conn->prepare($sql);
if ($types !== '') { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result_logs = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - Admin</title>
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
            <div class="menu-label" style="margin-top: 20px; color:#ef4444;">Admin Only</div>
            <a href="search_users.php" class="menu-item" style="color:#ef4444;"><span class="icon">⚙️</span> Backend (Admin)</a>
            <a href="logs.php" class="menu-item active" style="color:#ef4444;"><span class="icon">📜</span> System Logs</a>
        </div>
        <div class="sidebar-footer">
            <a href="profile.php" class="menu-item"><span class="icon">👤</span> Settings</a>
            <a href="logout.php" class="menu-item" style="color: #ef4444;"><span class="icon">🚪</span> Sign Out</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="content-wrapper">
            <h1 class="page-heading">📜 System Login Logs</h1>
            <p style="color: var(--text-muted); font-size: 14px; margin-bottom:20px;">*Logs older than 1 month are auto-deleted.</p>
            
            <form method="GET" action="" style="display: flex; gap: 10px; margin-bottom: 20px;">
                <input type="text" name="q" placeholder="Search by username or date (e.g. 28/03/2026)..." value="<?php echo htmlspecialchars($search_q); ?>" style="flex-grow: 1; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); background: rgba(255,255,255,0.05); color:white; outline:none;">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>

            <?php if ($result_logs->num_rows > 0): ?>
                <table class="log-table">
                    <thead>
                        <tr>
                            <th style="width: 50%;">📅 Date/Time</th>
                            <th style="width: 50%;">👤 Username</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($log = $result_logs->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d M Y - H:i:s', strtotime($log['login_time'])); ?></td>
                                <td style="color: var(--primary); font-weight: bold;"><?php echo htmlspecialchars($log['username']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 30px; background: var(--card-bg); border-radius: 8px; border: 1px solid var(--border-color);">
                    <h3 style="color: var(--text-muted);">No login logs found</h3>
                </div>
            <?php endif; ?>
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
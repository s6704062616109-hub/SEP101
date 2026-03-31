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

$search_query = "";
$result_users = null;

if (isset($_GET['search_name'])) {
    $search_query = trim($_GET['search_name']);
    $q = "%" . $search_query . "%";
    $sql = "SELECT id, username, is_banned, role FROM users WHERE username LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $q);
    $stmt->execute();
    $result_users = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Users (Admin) - Lost & Found</title>
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
            <div class="menu-label" style="margin-top: 20px; color:#ef4444;">Admin Only</div>
            <a href="search_users.php" class="menu-item active" style="color:#ef4444;"><span class="icon">⚙️</span> Backend (Admin)</a>
            <a href="logs.php" class="menu-item" style="color:#ef4444;"><span class="icon">📜</span> System Logs</a>
        </div>
        <div class="sidebar-footer">
            <a href="profile.php" class="menu-item"><span class="icon">👤</span> Settings</a>
            <a href="logout.php" class="menu-item" style="color: #ef4444;"><span class="icon">🚪</span> Sign Out</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="content-wrapper">
            <h1 class="page-heading" style="margin-bottom: 20px;">👥 Search Users (Admin Panel)</h1>
            <form method="GET" action="" style="display: flex; gap: 10px; margin-bottom: 20px;">
                <input type="text" name="search_name" placeholder="Search by username..." value="<?php echo htmlspecialchars($search_query); ?>" style="flex-grow: 1; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); background: rgba(255,255,255,0.05); color:white; outline:none;">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>

            <?php if ($result_users !== null): ?>
                <?php if ($result_users->num_rows > 0): ?>
                    <div style="background: var(--card-bg); padding: 15px; border-radius: 12px; border: 1px solid var(--border-color);">
                        <?php while($user = $result_users->fetch_assoc()): ?>
                            <div class="user-list-item" onclick="openProfileModal(<?php echo $user['id']; ?>)">
                                <div>
                                    <strong style="color:var(--primary); font-size:16px;"><?php echo htmlspecialchars($user['username']); ?></strong>
                                    <?php if($user['role'] == 'admin') echo "<span style='color:#3b82f6; font-size:12px; margin-left:8px;'>(Admin)</span>"; ?>
                                    <?php if($user['is_banned'] == 1) echo "<span style='color:#ef4444; font-size:12px; margin-left:8px; font-weight:bold;'>⚠️ Banned</span>"; ?>
                                </div>
                                <span style="font-size: 13px; color: var(--text-muted);">Click to manage ⚙️</span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-muted);">No users found matching "<?php echo htmlspecialchars($search_query); ?>"</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<div id="profileModal" class="modal">
    <div class="modal-content" style="text-align: center; max-width: 350px;">
        <span class="close-btn" onclick="closeProfileModal()">&times;</span>
        <img id="pm-img" src="" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin: auto; border: 3px solid var(--primary);">
        <h2 id="pm-name" style="margin: 10px 0;"></h2>
        <p id="pm-ban-status" style="color: #ef4444; font-weight: bold; display: none; background: rgba(239,68,68,0.1); padding: 5px; border-radius: 4px;">⚠️ Banned</p>
        <p style="color: var(--text-muted); font-size: 14px; font-weight:bold;">Contact Info:</p>
        <p id="pm-contact"></p>

        <div id="admin-ban-controls" style="margin-top: 15px; border-top: 1px solid var(--border-color); padding-top: 15px;">
            <input type="hidden" id="targetUserId">
            <button id="btn-show-ban" onclick="openBanModal()" class="btn btn-danger" style="width: 100%; margin-bottom:5px;">🚫 Ban User</button>
            <button id="btn-do-unban" onclick="unbanUser()" class="btn btn-success" style="width: 100%; display: none;">✅ Unban User</button>
        </div>
    </div>
</div>

<div id="banModal" class="modal" style="z-index: 2500;">
    <div class="modal-content" style="max-width: 400px;">
        <span class="close-btn" onclick="closeBanModal()">&times;</span>
        <h3 style="margin-top: 0; color: #ef4444;">🚫 Ban User</h3>
        <label style="display:block; margin-top:10px; font-weight:bold;">Reason:</label>
        <select id="banCategory" style="width: 100%; padding: 10px; border-radius:8px; margin-bottom: 10px;">
            <option value="Inappropriate Behavior">Inappropriate Behavior</option>
            <option value="Fake Information">Fake Information</option>
            <option value="Scam/Fraud">Scam / Fraud</option>
            <option value="System Abuse">System Abuse</option>
            <option value="Others">Others</option>
        </select>
        <label style="display:block; font-weight:bold;">Details:</label>
        <textarea id="banDetails" rows="3" style="width: 100%; padding: 10px; border-radius:8px; margin-bottom: 10px;" placeholder="Enter details..."></textarea>
        <label style="display:block; font-weight:bold;">Duration:</label>
        <select id="banDuration" style="width: 100%; padding: 10px; border-radius:8px; margin-bottom: 20px;">
            <option value="1h">1 Hour</option>
            <option value="1d">1 Day</option>
            <option value="7d">7 Days</option>
            <option value="1m">1 Month</option>
            <option value="permanent">Permanent</option>
        </select>
        <button onclick="submitBan()" class="btn btn-danger" style="width: 100%;">Confirm Ban</button>
    </div>
</div>

<script>
function toggleSidebar() { document.getElementById('sidebar').classList.toggle('active'); document.getElementById('mobileOverlay').style.display = document.getElementById('sidebar').classList.contains('active') ? 'block' : 'none'; }
function openBanModal() { document.getElementById('banModal').style.display = 'flex'; }
function closeBanModal() { document.getElementById('banModal').style.display = 'none'; }
function closeProfileModal() { document.getElementById('profileModal').style.display = 'none'; }
window.onclick = function(e) { if(e.target == document.getElementById('profileModal')) closeProfileModal(); if(e.target == document.getElementById('banModal')) closeBanModal(); }

function openProfileModal(userId) {
    event.stopPropagation();
    fetch('get_profile.php?id=' + userId).then(r => r.json()).then(data => {
        document.getElementById('pm-img').src = data.profile_picture ? data.profile_picture : 'https://via.placeholder.com/100?text=U';
        document.getElementById('pm-name').innerText = data.username;
        document.getElementById('pm-contact').innerText = data.contact_info ? data.contact_info : 'No contact info provided...';
        let banStatus = document.getElementById('pm-ban-status');
        if(data.is_banned == 1) { banStatus.innerText = '⚠️ Banned' + (data.ban_until ? ' (Until '+data.ban_until+')' : ''); banStatus.style.display = 'block'; document.getElementById('btn-show-ban').style.display = 'none'; document.getElementById('btn-do-unban').style.display = 'inline-block'; } 
        else { banStatus.style.display = 'none'; document.getElementById('btn-show-ban').style.display = 'inline-block'; document.getElementById('btn-do-unban').style.display = 'none'; }
        document.getElementById('targetUserId').value = data.id;
        document.getElementById('profileModal').style.display = 'flex';
    });
}
function submitBan() {
    let formData = new FormData(); formData.append('user_id', document.getElementById('targetUserId').value); formData.append('action', 'ban'); formData.append('category', document.getElementById('banCategory').value); formData.append('details', document.getElementById('banDetails').value); formData.append('duration', document.getElementById('banDuration').value);
    fetch('ban_user.php', { method: 'POST', body: formData }).then(r => r.text()).then(res => { if(res === 'success') { alert('User banned successfully'); window.location.reload(); } });
}
function unbanUser() {
    if(!confirm('Are you sure you want to unban this user?')) return;
    let formData = new FormData(); formData.append('user_id', document.getElementById('targetUserId').value); formData.append('action', 'unban');
    fetch('ban_user.php', { method: 'POST', body: formData }).then(r => r.text()).then(res => { if(res === 'success') { alert('User unbanned successfully'); window.location.reload(); } });
}
</script>
</body>
</html>
<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$sql_user = "SELECT username, role, profile_picture, is_banned, ban_until FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

if ($user_data['is_banned'] == 1) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$my_profile_image = !empty($user_data['profile_picture']) ? $user_data['profile_picture'] : 'https://via.placeholder.com/40?text=U';

// ดึงเฉพาะโพสต์ของตัวเอง
$sql_posts = "
    SELECT p.id AS post_id, p.title, p.description, p.image_url, p.post_type, 
           p.item_category, p.status, p.created_at, 
           u.id AS post_user_id, u.username, u.profile_picture 
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
";
$stmt_posts = $conn->prepare($sql_posts);
$stmt_posts->bind_param("i", $user_id);
$stmt_posts->execute();
$result_posts = $stmt_posts->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Posts - Lost & Found</title>
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
        <div class="content-wrapper">
            <div class="page-title-bar">
                <h1 class="page-heading">📁 My Posts</h1>
            </div>

            <?php if ($result_posts->num_rows > 0): ?>
                <?php while($post = $result_posts->fetch_assoc()): ?>
                    <div class="post-card">
                        <div class="post-header">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <?php $author_img = !empty($post['profile_picture']) ? $post['profile_picture'] : 'https://via.placeholder.com/45?text=U'; ?>
                                <img src="<?php echo htmlspecialchars($author_img); ?>" class="post-profile-img">
                                <div>
                                    <p class="post-author"><?php echo htmlspecialchars($post['username']); ?></p>
                                    <p class="post-time"><?php echo date('d M Y, H:i', strtotime($post['created_at'])); ?></p>
                                </div>
                            </div>
                            <div style="display:flex; gap: 8px;">
                                <a href="edit_post.php?id=<?php echo $post['post_id']; ?>" class="btn btn-warning" style="padding: 5px 10px; font-size: 12px; height: fit-content;">✏️ Edit</a>
                                <a href="delete_post.php?id=<?php echo $post['post_id']; ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px; height: fit-content;" onclick="return confirm('Are you sure you want to delete this post?');">🗑️ Delete</a>
                            </div>
                        </div>

                        <div class="post-body">
                            <?php if ($post['status'] == 'resolved'): ?>
                                <span class="badge" style="background-color: #22c55e;">✅ Resolved</span>
                            <?php else: ?>
                                <span class="badge" style="background-color: #eab308; color: #111;">⏳ Pending</span>
                            <?php endif; ?>
                            <?php if ($post['post_type'] == 'lost'): ?>
                                <span class="badge" style="background-color: #ef4444;">🔍 Lost Item</span>
                            <?php else: ?>
                                <span class="badge" style="background-color: #3b82f6;">💡 Found Item</span>
                            <?php endif; ?>
                            <?php 
                                $categories = !empty($post['item_category']) ? explode(",", $post['item_category']) : ['Others'];
                                foreach($categories as $cat): 
                                    $cat = trim($cat);
                                    if(!empty($cat)):
                            ?>
                                <span class="badge" style="background-color: #64748b;">🏷️ <?php echo htmlspecialchars($cat); ?></span>
                            <?php endif; endforeach; ?>

                            <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                            <p class="post-desc"><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>
                            
                            <?php if (!empty($post['image_url'])): 
                                $images = explode(",", $post['image_url']); 
                                $images = array_map('trim', $images);
                                $images = array_filter($images);
                                $img_count = count($images);
                                $grid_class = "grid-" . ($img_count >= 4 ? 4 : $img_count);
                                $js_images_array = htmlspecialchars(json_encode(array_values($images)), ENT_QUOTES, 'UTF-8');
                            ?>
                                <div class="post-image-grid <?php echo $grid_class; ?>">
                                    <?php 
                                    foreach($images as $index => $img): 
                                        if($index < 4): 
                                            $overlay = '';
                                            if($index == 3 && $img_count > 4) {
                                                $more = $img_count - 4;
                                                $overlay = "<div class='more-images-overlay'>+$more</div>";
                                            }
                                    ?>
                                        <div class="img-wrapper" onclick="openLightbox(<?php echo $js_images_array; ?>, <?php echo $index; ?>)">
                                            <img src="<?php echo htmlspecialchars($img); ?>">
                                            <?php echo $overlay; ?>
                                        </div>
                                    <?php endif; endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 50px 0; color: #888;">
                    <h3 style="margin-bottom: 5px;">You haven't posted anything yet.</h3>
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

<div id="lightboxModal" class="lightbox-modal">
    <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
    <div class="lightbox-counter" id="lightboxCounter">1 / 1</div>
    <div class="lightbox-nav lightbox-prev" onclick="changeLightboxImage(event, -1)">&#10094;</div>
    <img class="lightbox-content" id="lightboxImg">
    <div class="lightbox-nav lightbox-next" onclick="changeLightboxImage(event, 1)">&#10095;</div>
</div>

<script>
function applySeeMore(selector) {
    document.querySelectorAll(selector).forEach(function(elem) {
        if (elem.nextElementSibling && elem.nextElementSibling.classList.contains('see-more-btn')) return;
        if (elem.scrollHeight > elem.clientHeight) {
            let btn = document.createElement('button');
            btn.className = 'see-more-btn';
            btn.innerText = 'See more...';
            elem.parentNode.insertBefore(btn, elem.nextSibling);
            btn.addEventListener('click', function() {
                elem.classList.toggle('expanded');
                btn.innerText = elem.classList.contains('expanded') ? 'See less' : 'See more...';
            });
        }
    });
}
document.addEventListener("DOMContentLoaded", function() {
    setTimeout(() => applySeeMore('.post-desc'), 200);
});

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobileOverlay');
    sidebar.classList.toggle('active');
    overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
}

function openNotificationModal() { document.getElementById('notificationModal').style.display = 'flex'; loadNotifications(); }
function closeNotificationModal() { document.getElementById('notificationModal').style.display = 'none'; }
function openMutedUsersModal() { document.getElementById('mutedUsersModal').style.display = 'flex'; loadMutedUsers(); }
function closeMutedUsersModal() { document.getElementById('mutedUsersModal').style.display = 'none'; }

function loadNotifications() {
    let list = document.getElementById('notificationList');
    list.innerHTML = '<p style="text-align:center; color: var(--text-muted); margin-top: 20px;">Loading...</p>';
    fetch('notification_api.php?action=fetch').then(r => r.json()).then(data => {
        list.innerHTML = '';
        if(data.length === 0) { list.innerHTML = '<p style="text-align:center; color: var(--text-muted); margin-top: 20px;">No new notifications</p>'; return; }

        data.forEach(n => {
            let img = n.profile_picture ? n.profile_picture : 'https://via.placeholder.com/45?text=U';
            let actionText = n.type === 'comment' ? 'commented on your post.' : 'replied to your comment.';
            let timeStr = new Date(n.created_at).toLocaleString('en-GB', { day:'numeric', month:'short', hour:'2-digit', minute:'2-digit' });

            list.innerHTML += `
                <div class="notif-item" onclick="window.location.href='index.php?post_id=${n.post_id}'">
                    <img src="${img}" class="notif-avatar">
                    <div class="notif-content">
                        <p class="notif-text"><strong style="color:var(--primary);">${n.username}</strong> ${actionText}</p>
                        <p class="notif-time">${timeStr}</p>
                    </div>
                    <button class="notif-menu-btn" onclick="event.stopPropagation(); toggleNotifMenu(${n.id}, event)">⋮</button>
                    <div id="notif-menu-${n.id}" class="notif-dropdown">
                        <div class="notif-dropdown-item" onclick="event.stopPropagation(); muteUser(${n.actor_id}, '${n.username}')">🔕 Mute notifications</div>
                        <div class="notif-dropdown-item danger" onclick="event.stopPropagation(); deleteNotification(${n.id})">🗑️ Delete</div>
                    </div>
                </div>
            `;
        });
    });
}

function toggleNotifMenu(id, event) { event.stopPropagation(); document.querySelectorAll('.notif-dropdown').forEach(d => d.classList.remove('show')); document.getElementById('notif-menu-' + id).classList.toggle('show'); }
function clearAllNotifications() { if(!confirm("Clear all notifications?")) return; let fd = new FormData(); fd.append('action', 'delete_all'); fetch('notification_api.php', { method: 'POST', body: fd }).then(r=>r.text()).then(res=>{ if(res==='success') loadNotifications(); }); }
function deleteNotification(id) { let fd = new FormData(); fd.append('action', 'delete_one'); fd.append('notif_id', id); fetch('notification_api.php', { method: 'POST', body: fd }).then(r=>r.text()).then(res=>{ if(res==='success') loadNotifications(); }); }
function muteUser(mutedId, username) { if(!confirm(`Mute notifications from ${username}?`)) return; let fd = new FormData(); fd.append('action', 'mute_user'); fd.append('muted_id', mutedId); fetch('notification_api.php', { method: 'POST', body: fd }).then(r=>r.text()).then(res=>{ if(res==='success') { alert(`Muted ${username}`); loadNotifications(); } }); }

function loadMutedUsers() {
    let list = document.getElementById('mutedUsersList');
    list.innerHTML = '<p style="text-align:center; color: var(--text-muted);">Loading...</p>';
    let fd = new FormData(); fd.append('action', 'fetch_muted');
    fetch('notification_api.php', { method: 'POST', body: fd }).then(r => r.json()).then(data => {
        list.innerHTML = '';
        if(data.length === 0) { list.innerHTML = '<p style="text-align:center; color: var(--text-muted); padding: 20px;">No muted users.</p>'; return; }
        data.forEach(u => {
            let img = u.profile_picture ? u.profile_picture : 'https://via.placeholder.com/35?text=U';
            list.innerHTML += `
                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px; border-bottom:1px solid var(--border-color);">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <img src="${img}" style="width:35px; height:35px; border-radius:50%; object-fit:cover;">
                        <span style="color:var(--text-main); font-weight:bold;">${u.username}</span>
                    </div>
                    <button onclick="unmuteUser(${u.muted_user_id})" class="btn btn-success" style="padding: 5px 10px; font-size:12px;">Unmute</button>
                </div>
            `;
        });
    });
}
function unmuteUser(id) { let fd = new FormData(); fd.append('action', 'unmute_user'); fd.append('muted_id', id); fetch('notification_api.php', { method: 'POST', body: fd }).then(r=>r.text()).then(res=>{ if(res==='success') { loadMutedUsers(); loadNotifications(); } }); }

let currentLightboxImages = []; let currentLightboxIndex = 0;
function openLightbox(imageArray, startIndex) { currentLightboxImages = imageArray; currentLightboxIndex = startIndex; updateLightboxImage(); document.getElementById('lightboxModal').style.display = 'flex'; }
function closeLightbox() { document.getElementById('lightboxModal').style.display = 'none'; currentLightboxImages = []; }
function changeLightboxImage(e, direction) { e.stopPropagation(); currentLightboxIndex += direction; if (currentLightboxIndex >= currentLightboxImages.length) currentLightboxIndex = 0; else if (currentLightboxIndex < 0) currentLightboxIndex = currentLightboxImages.length - 1; updateLightboxImage(); }
function updateLightboxImage() { if(currentLightboxImages.length > 0) { document.getElementById('lightboxImg').src = currentLightboxImages[currentLightboxIndex]; document.getElementById('lightboxCounter').innerText = (currentLightboxIndex + 1) + " / " + currentLightboxImages.length; let navs = document.querySelectorAll('.lightbox-nav'); if(currentLightboxImages.length <= 1) navs.forEach(nav => nav.style.display = 'none'); else navs.forEach(nav => nav.style.display = 'flex'); } }

window.onclick = function(event) {
    document.querySelectorAll('.notif-dropdown').forEach(d => d.classList.remove('show'));
    let lModal = document.getElementById('lightboxModal');
    let nModal = document.getElementById('notificationModal');
    let mModal = document.getElementById('mutedUsersModal');
    if (event.target == lModal) closeLightbox();
    if (event.target == nModal) closeNotificationModal();
    if (event.target == mModal) closeMutedUsersModal();
}
</script>
</body>
</html>
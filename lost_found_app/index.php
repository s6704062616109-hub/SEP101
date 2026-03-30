<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลผู้ใช้งาน
$sql_user = "SELECT username, role, profile_picture, is_banned, ban_until FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

if ($user_data['is_banned'] == 1) {
    if ($user_data['ban_until'] !== null && strtotime($user_data['ban_until']) <= time()) {
        $unban_stmt = $conn->prepare("UPDATE users SET is_banned=0, ban_category=NULL, ban_details=NULL, ban_until=NULL WHERE id=?");
        $unban_stmt->bind_param("i", $user_id);
        $unban_stmt->execute();
        $unban_stmt->close();
    } else {
        session_destroy();
        header("Location: login.php");
        exit();
    }
}

$my_profile_image = !empty($user_data['profile_picture']) ? $user_data['profile_picture'] : 'https://via.placeholder.com/40?text=U';

// ================= ระบบค้นหาและตัวกรอง =================
$search_text = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$filter_categories = isset($_GET['category']) ? $_GET['category'] : []; 

$sql_posts = "
    SELECT p.id AS post_id, p.title, p.description, p.image_url, p.post_type, 
           p.item_category, p.status, p.created_at, 
           u.id AS post_user_id, u.username, u.profile_picture 
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE 1=1
";

$params = [];
$types = "";

if ($search_text !== '') {
    $sql_posts .= " AND (p.title LIKE ? OR p.description LIKE ?)";
    $search_param = "%" . $search_text . "%";
    $params[] = $search_param; $params[] = $search_param;
    $types .= "ss";
}
if ($filter_type !== '') {
    $sql_posts .= " AND p.post_type = ?";
    $params[] = $filter_type; $types .= "s";
}
if ($filter_status !== '') {
    $sql_posts .= " AND p.status = ?";
    $params[] = $filter_status; $types .= "s";
}
if (!empty($filter_categories)) {
    $cat_conditions = [];
    foreach ($filter_categories as $cat) {
        $cat_conditions[] = "p.item_category LIKE ?";
        $params[] = "%" . $cat . "%";
        $types .= "s";
    }
    $sql_posts .= " AND (" . implode(" OR ", $cat_conditions) . ")";
}

$sql_posts .= " ORDER BY p.created_at DESC";

$stmt_posts = $conn->prepare($sql_posts);
if (!empty($params)) {
    $stmt_posts->bind_param($types, ...$params);
}
$stmt_posts->execute();
$result_posts = $stmt_posts->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Lost & Found App</title>
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
            <input type="text" name="q" class="search-input" placeholder="Search items, locations..." value="<?php echo htmlspecialchars($search_text); ?>">
            <button type="submit" class="btn-header btn-header-primary">Search</button>
            <button type="button" class="btn-header btn-header-secondary" onclick="openFilterModal()">⚙️ Filter</button>
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
            <a href="index.php" class="menu-item active"><span class="icon">🏠</span> Home</a>
            
            <div class="menu-label" style="margin-top: 20px;">My Space</div>
            <a href="my_posts.php" class="menu-item"><span class="icon">📁</span> My Posts</a>
            
            <?php if ($user_data['role'] == 'admin'): ?>
                <div class="menu-label" style="margin-top: 20px; color:#ff6b6b;">Admin Only</div>
                <a href="search_users.php" class="menu-item" style="color:#ff6b6b;"><span class="icon">⚙️</span> Backend (Admin)</a>
                <a href="logs.php" class="menu-item" style="color:#ff6b6b;"><span class="icon">📜</span> System Logs</a>
            <?php endif; ?>
        </div>
        
        <div class="sidebar-footer">
            <a href="profile.php" class="menu-item"><span class="icon">👤</span> Settings</a>
            <a href="logout.php" class="menu-item" style="color: #ff6b6b;"><span class="icon">🚪</span> Sign Out</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="content-wrapper">
            
            <div class="page-title-bar">
                <h1 class="page-heading">Discover Feed</h1>
            </div>

            <?php if ($search_text !== '' || $filter_type !== '' || $filter_status !== '' || !empty($filter_categories)): ?>
                <a href="index.php" style="display:inline-block; margin-bottom:15px; color:#dc3545; text-decoration:none; font-weight:bold; font-size:14px;">✖ Clear all filters</a>
            <?php endif; ?>

            <?php if ($result_posts->num_rows > 0): ?>
                <?php while($post = $result_posts->fetch_assoc()): ?>
                    <div class="post-card">
                        <div class="post-header">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <?php $author_img = !empty($post['profile_picture']) ? $post['profile_picture'] : 'https://via.placeholder.com/45?text=U'; ?>
                                <img src="<?php echo htmlspecialchars($author_img); ?>" class="post-profile-img" style="cursor:pointer;" onclick="openProfileModal(<?php echo $post['post_user_id']; ?>)">
                                <div>
                                    <p class="post-author" style="cursor:pointer;" onclick="openProfileModal(<?php echo $post['post_user_id']; ?>)"><?php echo htmlspecialchars($post['username']); ?></p>
                                    <p class="post-time"><?php echo date('d M Y, H:i', strtotime($post['created_at'])); ?></p>
                                </div>
                            </div>
                            
                            <?php if ($user_data['role'] == 'admin'): ?>
                                <a href="delete_post.php?id=<?php echo $post['post_id']; ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px; height: fit-content;" onclick="return confirm('As an Admin, are you sure you want to delete this post?');">🗑️ Delete</a>
                            <?php endif; ?>
                        </div>

                        <div class="post-body">
                            <?php if ($post['status'] == 'resolved'): ?>
                                <span class="badge" style="background-color: #28a745;">✅ Resolved</span>
                            <?php else: ?>
                                <span class="badge" style="background-color: #ffc107; color: #333;">⏳ Pending</span>
                            <?php endif; ?>

                            <?php if ($post['post_type'] == 'lost'): ?>
                                <span class="badge" style="background-color: #dc3545;">🔍 Lost Item</span>
                            <?php else: ?>
                                <span class="badge" style="background-color: #0084ff;">💡 Found Item</span>
                            <?php endif; ?>

                            <?php 
                                $categories = !empty($post['item_category']) ? explode(",", $post['item_category']) : ['อื่นๆ'];
                                foreach($categories as $cat): 
                                    $cat = trim($cat);
                                    if(!empty($cat)):
                            ?>
                                <span class="badge" style="background-color: #6c757d;">🏷️ <?php echo htmlspecialchars($cat); ?></span>
                            <?php 
                                    endif;
                                endforeach; 
                            ?>

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
                                            <img src="<?php echo htmlspecialchars($img); ?>" alt="Post Image">
                                            <?php echo $overlay; ?>
                                        </div>
                                    <?php 
                                        endif; 
                                    endforeach; 
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="post-footer">
                            <button onclick="openModal(<?php echo $post['post_id']; ?>)" class="comment-btn">💬 Comments</button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 50px 0; color: #888;">
                    <h3 style="margin-bottom: 5px;">No posts found 🕵️‍♂️</h3>
                    <p>Try searching with different keywords or clear the filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<div id="commentModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h3 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">Comments</h3>
        <div id="commentList" class="comment-list" style="margin-bottom: 15px; max-height: 300px; overflow-y:auto; scrollbar-width: thin;"></div>
        <div class="comment-input-area" style="display:flex; gap:10px;">
            <input type="hidden" id="modalPostId">
            <input type="text" id="commentText" placeholder="Write a comment..." onkeypress="handleEnter(event)" style="flex-grow:1; padding:10px; border-radius:8px; border:1px solid #ccc; font-family: inherit;">
            <button onclick="submitComment()" class="btn btn-primary">Send</button>
        </div>
    </div>
</div>

<div id="profileModal" class="modal">
    <div class="modal-content" style="text-align: center; max-width: 350px;">
        <span class="close-btn" onclick="closeProfileModal()">&times;</span>
        <img id="pm-img" src="" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin: auto; border: 3px solid var(--primary);">
        <h2 id="pm-name" style="margin: 10px 0;"></h2>
        <p id="pm-ban-status" style="color: #dc3545; font-weight: bold; display: none; background: #ffeeba; padding: 5px; border-radius: 4px;">⚠️ Banned</p>
        <p style="color: gray; font-size: 14px;">Contact Info:</p>
        <p id="pm-contact" style="background: #f4f6f9; padding: 15px; border-radius: 8px; font-size: 14px; text-align: left;"></p>

        <div id="admin-ban-controls" style="display: none; margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
            <input type="hidden" id="targetUserId">
            <button id="btn-show-ban" onclick="openBanModal()" class="btn btn-danger" style="width: 100%; margin-bottom:5px;">🚫 Ban User</button>
            <button id="btn-do-unban" onclick="unbanUser()" class="btn btn-success" style="width: 100%; background-color: #28a745; display: none;">✅ Unban User</button>
        </div>
    </div>
</div>

<div id="banModal" class="modal" style="z-index: 2500;">
    <div class="modal-content" style="max-width: 400px;">
        <span class="close-btn" onclick="closeBanModal()">&times;</span>
        <h3 style="margin-top: 0; color: #dc3545;">🚫 Ban User</h3>
        <label style="display:block; margin-top:10px; font-weight:bold;">Reason:</label>
        <select id="banCategory" style="width: 100%; padding: 10px; border-radius:8px; margin-bottom: 10px; border:1px solid #ccc; font-family:inherit;">
            <option value="Inappropriate Behavior">Inappropriate Behavior</option>
            <option value="Fake Information">Fake Information</option>
            <option value="Scam/Fraud">Scam / Fraud</option>
            <option value="System Abuse">System Abuse</option>
            <option value="Others">Others</option>
        </select>
        <label style="display:block; font-weight:bold;">Details:</label>
        <textarea id="banDetails" rows="3" style="width: 100%; padding: 10px; border-radius:8px; margin-bottom: 10px; border:1px solid #ccc; font-family: inherit;" placeholder="Enter details..."></textarea>
        <label style="display:block; font-weight:bold;">Duration:</label>
        <select id="banDuration" style="width: 100%; padding: 10px; border-radius:8px; margin-bottom: 20px; border:1px solid #ccc; font-family: inherit;">
            <option value="1h">1 Hour</option>
            <option value="1d">1 Day</option>
            <option value="7d">7 Days</option>
            <option value="1m">1 Month</option>
            <option value="permanent">Permanent</option>
        </select>
        <button onclick="submitBan()" class="btn btn-danger" style="width: 100%;">Confirm Ban</button>
    </div>
</div>

<div id="lightboxModal" class="lightbox-modal">
    <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
    <div class="lightbox-counter" id="lightboxCounter">1 / 1</div>
    <div class="lightbox-nav lightbox-prev" onclick="changeLightboxImage(event, -1)">&#10094;</div>
    <img class="lightbox-content" id="lightboxImg">
    <div class="lightbox-nav lightbox-next" onclick="changeLightboxImage(event, 1)">&#10095;</div>
</div>

<div id="filterSearchModal" class="modal">
    <div class="modal-content">
        <form method="GET" action="index.php">
            <span class="close-btn" onclick="closeFilterModal()">&times;</span>
            <h3 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">⚙️ Search Filters</h3>
            
            <label style="font-weight: bold; margin-top: 10px; display: block;">1. Post Type:</label>
            <select name="filter_type" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc; font-family: inherit;">
                <option value="">-- All Types --</option>
                <option value="lost" <?php if($filter_type=='lost') echo 'selected'; ?>>🔍 Lost Item</option>
                <option value="found" <?php if($filter_type=='found') echo 'selected'; ?>>💡 Found Item</option>
            </select>

            <label style="font-weight: bold; margin-top: 15px; display: block;">2. Status:</label>
            <select name="filter_status" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc; font-family: inherit;">
                <option value="">-- All Statuses --</option>
                <option value="active" <?php if($filter_status=='active') echo 'selected'; ?>>⏳ Pending</option>
                <option value="resolved" <?php if($filter_status=='resolved') echo 'selected'; ?>>✅ Resolved</option>
            </select>

            <label style="font-weight: bold; margin-top: 15px; display: block;">3. Category:</label>
            <div class="checkbox-group">
                <?php 
                    $all_cats = ['อุปกรณ์การเรียน', 'อุปกรณ์อิเล็กทรอนิกส์', 'ของใช้ส่วนตัว', 'เอกสารสำคัญ / บัตร', 'อาหาร / กล่องข้าว', 'ของมีค่า / ของสำคัญอื่นๆ', 'อื่นๆ'];
                    $cat_en = ['Study Tools', 'Electronics', 'Personal Items', 'Documents/Cards', 'Food/Boxes', 'Valuables', 'Others'];
                    foreach($all_cats as $index => $c):
                ?>
                    <label style="cursor: pointer; display:flex; align-items:center; gap:5px;"><input type="checkbox" name="category[]" value="<?php echo $c; ?>" <?php if(in_array($c, $filter_categories)) echo 'checked'; ?>> <?php echo $cat_en[$index]; ?></label>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 20px;">Apply Filters</button>
        </form>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobileOverlay');
    sidebar.classList.toggle('active');
    overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
}

setInterval(function() {
    fetch('check_ban_status.php').then(r => r.text()).then(status => {
        if (status.trim() === 'banned') window.location.href = 'login.php';
    }).catch(e => console.error(e));
}, 3000);

const currentRole = '<?php echo $user_data['role']; ?>'; 

let currentLightboxImages = [];
let currentLightboxIndex = 0;

function openLightbox(imageArray, startIndex) {
    currentLightboxImages = imageArray;
    currentLightboxIndex = startIndex;
    updateLightboxImage();
    document.getElementById('lightboxModal').style.display = 'flex';
}
function closeLightbox() { document.getElementById('lightboxModal').style.display = 'none'; currentLightboxImages = []; }
function changeLightboxImage(e, direction) {
    e.stopPropagation(); 
    currentLightboxIndex += direction;
    if (currentLightboxIndex >= currentLightboxImages.length) currentLightboxIndex = 0;
    else if (currentLightboxIndex < 0) currentLightboxIndex = currentLightboxImages.length - 1;
    updateLightboxImage();
}
function updateLightboxImage() {
    if(currentLightboxImages.length > 0) {
        document.getElementById('lightboxImg').src = currentLightboxImages[currentLightboxIndex];
        document.getElementById('lightboxCounter').innerText = (currentLightboxIndex + 1) + " / " + currentLightboxImages.length;
        let navs = document.querySelectorAll('.lightbox-nav');
        if(currentLightboxImages.length <= 1) navs.forEach(nav => nav.style.display = 'none');
        else navs.forEach(nav => nav.style.display = 'flex');
    }
}

function openFilterModal() { document.getElementById('filterSearchModal').style.display = 'flex'; }
function closeFilterModal() { document.getElementById('filterSearchModal').style.display = 'none'; }
function openBanModal() { document.getElementById('banModal').style.display = 'flex'; }
function closeBanModal() { document.getElementById('banModal').style.display = 'none'; }
function closeProfileModal() { document.getElementById('profileModal').style.display = 'none'; }

window.onclick = function(event) {
    let cModal = document.getElementById('commentModal');
    let pModal = document.getElementById('profileModal');
    let fModal = document.getElementById('filterSearchModal');
    let bModal = document.getElementById('banModal');
    let lModal = document.getElementById('lightboxModal');
    if (event.target == cModal) closeModal();
    if (event.target == pModal) closeProfileModal();
    if (event.target == fModal) closeFilterModal();
    if (event.target == bModal) closeBanModal();
    if (event.target == lModal) closeLightbox();
}

function openProfileModal(userId) {
    fetch('get_profile.php?id=' + userId).then(r => r.json()).then(data => {
        document.getElementById('pm-img').src = data.profile_picture ? data.profile_picture : 'https://via.placeholder.com/100?text=U';
        document.getElementById('pm-name').innerText = data.username;
        document.getElementById('pm-contact').innerText = data.contact_info ? data.contact_info : 'No contact info provided...';

        let banStatus = document.getElementById('pm-ban-status');
        if(data.is_banned == 1) {
            let until = data.ban_until ? ' (Until ' + data.ban_until + ')' : ' (Permanent)';
            banStatus.innerText = '⚠️ Banned' + until;
            banStatus.style.display = 'block';
        } else { banStatus.style.display = 'none'; }

        if(currentRole === 'admin') {
            document.getElementById('admin-ban-controls').style.display = 'block';
            document.getElementById('targetUserId').value = data.id;
            if(data.is_banned == 1) {
                document.getElementById('btn-show-ban').style.display = 'none';
                document.getElementById('btn-do-unban').style.display = 'inline-block';
            } else {
                document.getElementById('btn-show-ban').style.display = 'inline-block';
                document.getElementById('btn-do-unban').style.display = 'none';
            }
        }
        document.getElementById('profileModal').style.display = 'flex';
    });
}

function submitBan() {
    let uid = document.getElementById('targetUserId').value;
    let formData = new FormData();
    formData.append('user_id', uid); formData.append('action', 'ban');
    formData.append('category', document.getElementById('banCategory').value);
    formData.append('details', document.getElementById('banDetails').value);
    formData.append('duration', document.getElementById('banDuration').value);
    fetch('ban_user.php', { method: 'POST', body: formData }).then(r => r.text()).then(res => {
        if(res === 'success') { alert('User banned successfully'); window.location.reload(); }
    });
}

function unbanUser() {
    if(!confirm('Are you sure you want to unban this user?')) return;
    let uid = document.getElementById('targetUserId').value;
    let formData = new FormData();
    formData.append('user_id', uid); formData.append('action', 'unban');
    fetch('ban_user.php', { method: 'POST', body: formData }).then(r => r.text()).then(res => {
        if(res === 'success') { alert('User unbanned successfully'); window.location.reload(); }
    });
}

let replyingToId = null; 
function openModal(postId) {
    document.getElementById('commentModal').style.display = 'flex';
    document.getElementById('modalPostId').value = postId;
    loadComments(postId);
}
function closeModal() { document.getElementById('commentModal').style.display = 'none'; cancelReply(); }
function setReply(commentId, username) {
    replyingToId = commentId;
    let input = document.getElementById('commentText');
    input.placeholder = "Replying to " + username + "...";
    input.focus();
}
function cancelReply() { replyingToId = null; document.getElementById('commentText').value = ''; document.getElementById('commentText').placeholder = "Write a comment..."; }

function loadComments(postId) {
    let list = document.getElementById('commentList');
    list.innerHTML = '<p style="text-align:center; color:gray;">Loading...</p>';
    fetch('get_comments.php?post_id=' + postId).then(r => r.json()).then(data => {
        list.innerHTML = '';
        if(data.length === 0) { list.innerHTML = '<p style="text-align:center; color:gray;">No comments yet</p>'; return; }
        let mainComments = data.filter(c => c.parent_id === null);
        let replies = data.filter(c => c.parent_id !== null);
        mainComments.forEach(c => {
            let img = c.profile_picture ? c.profile_picture : 'https://via.placeholder.com/35?text=U';
            list.innerHTML += `
                <div style="margin-bottom: 5px; display:flex; gap:10px;">
                    <img src="${img}" style="width:35px; height:35px; border-radius:50%; cursor:pointer;" onclick="openProfileModal(${c.user_id})">
                    <div style="flex-grow: 1;">
                        <div style="background:#f0f2f5; padding:8px 12px; border-radius:12px; display:inline-block;">
                            <strong style="cursor:pointer; color:var(--primary); font-size:14px;" onclick="openProfileModal(${c.user_id})">${c.username}</strong>
                            <p style="margin:2px 0 0 0; font-size:14px;">${c.comment_text}</p>
                        </div>
                        <div style="margin-top: 3px; margin-left: 10px;">
                            <span style="font-size:12px; cursor:pointer; font-weight:bold; color:var(--text-muted);" onclick="setReply(${c.id}, '${c.username}')">Reply</span>
                        </div>
                    </div>
                </div>
                <div id="replies-${c.id}" style="margin-left: 45px; margin-bottom: 15px;"></div>
            `;
        });
        replies.forEach(r => {
            let replyBox = document.getElementById('replies-' + r.parent_id);
            if(replyBox) {
                let img = r.profile_picture ? r.profile_picture : 'https://via.placeholder.com/35?text=U';
                replyBox.innerHTML += `
                    <div style="margin-bottom: 5px; margin-top: 5px; display:flex; gap:8px;">
                        <img src="${img}" style="width:25px; height:25px; border-radius:50%; cursor:pointer;" onclick="openProfileModal(${r.user_id})">
                        <div>
                            <div style="background:#f0f2f5; padding:5px 10px; border-radius:12px; display:inline-block;">
                                <strong style="cursor:pointer; color:var(--primary); font-size:12px;" onclick="openProfileModal(${r.user_id})">${r.username}</strong>
                                <p style="margin:2px 0 0 0; font-size:13px;">${r.comment_text}</p>
                            </div>
                        </div>
                    </div>
                `;
            }
        });
        list.scrollTop = list.scrollHeight;
    });
}

function submitComment() {
    let postId = document.getElementById('modalPostId').value;
    let textInput = document.getElementById('commentText');
    let text = textInput.value.trim();
    if(text === '') return;
    let formData = new FormData();
    formData.append('post_id', postId);
    formData.append('comment_text', text);
    if(replyingToId !== null) formData.append('parent_id', replyingToId);
    fetch('add_comment.php', { method: 'POST', body: formData }).then(r => r.text()).then(result => {
        if(result === 'success') { cancelReply(); loadComments(postId); } 
        else { alert('Error submitting comment'); }
    });
}
function handleEnter(e) { if(e.key === 'Enter') submitComment(); }
</script>

</body>
</html>
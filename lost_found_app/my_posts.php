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
                                <span class="badge" style="background-color: #28a745;">✅ Resolved</span>
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
                        <div class="post-footer">
                            <button onclick="openModal(<?php echo $post['post_id']; ?>)" class="comment-btn">💬 Comments</button>
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

<div id="lightboxModal" class="lightbox-modal">
    <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
    <div class="lightbox-counter" id="lightboxCounter">1 / 1</div>
    <div class="lightbox-nav lightbox-prev" onclick="changeLightboxImage(event, -1)">&#10094;</div>
    <img class="lightbox-content" id="lightboxImg">
    <div class="lightbox-nav lightbox-next" onclick="changeLightboxImage(event, 1)">&#10095;</div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobileOverlay');
    sidebar.classList.toggle('active');
    overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
}
let currentLightboxImages = []; let currentLightboxIndex = 0;
function openLightbox(imageArray, startIndex) { currentLightboxImages = imageArray; currentLightboxIndex = startIndex; updateLightboxImage(); document.getElementById('lightboxModal').style.display = 'flex'; }
function closeLightbox() { document.getElementById('lightboxModal').style.display = 'none'; currentLightboxImages = []; }
function changeLightboxImage(e, direction) { e.stopPropagation(); currentLightboxIndex += direction; if (currentLightboxIndex >= currentLightboxImages.length) currentLightboxIndex = 0; else if (currentLightboxIndex < 0) currentLightboxIndex = currentLightboxImages.length - 1; updateLightboxImage(); }
function updateLightboxImage() { if(currentLightboxImages.length > 0) { document.getElementById('lightboxImg').src = currentLightboxImages[currentLightboxIndex]; document.getElementById('lightboxCounter').innerText = (currentLightboxIndex + 1) + " / " + currentLightboxImages.length; let navs = document.querySelectorAll('.lightbox-nav'); if(currentLightboxImages.length <= 1) navs.forEach(nav => nav.style.display = 'none'); else navs.forEach(nav => nav.style.display = 'flex'); } }
</script>
</body>
</html>
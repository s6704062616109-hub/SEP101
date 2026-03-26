<?php
session_start();
require 'db.php';

// เช็กว่าเข้าสู่ระบบหรือยัง
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลผู้ใช้งานที่กำลังล็อกอิน (สำหรับเมนูด้านบน)
$sql_user = "SELECT username, role, profile_picture FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

$my_profile_image = !empty($user_data['profile_picture']) ? $user_data['profile_picture'] : 'https://via.placeholder.com/40?text=U';

// ================= ส่วนดึงข้อมูลโพสต์ทั้งหมดจากฐานข้อมูล =================
// ใช้ JOIN เพื่อดึงชื่อและรูปโปรไฟล์ของ "เจ้าของโพสต์" มาด้วย เรียงจากโพสต์ล่าสุดไปเก่าสุด
$sql_posts = "
    SELECT p.id AS post_id, p.title, p.description, p.image_url, p.post_type, p.created_at, 
           u.username, u.profile_picture 
    FROM posts p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
";
$result_posts = $conn->query($sql_posts);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>หน้าแรก - ระบบแจ้งของหาย</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <div class="header">
        <h2 class="header-title">ระบบแจ้งของหาย</h2>
        <div class="user-menu">
            <a href="profile.php" class="profile-link">
                <img src="<?php echo htmlspecialchars($my_profile_image); ?>" class="nav-profile-img" alt="Profile">
                <span><?php echo htmlspecialchars($user_data['username']); ?></span>
            </a>
        </div>
    </div>

    <a href="create_post.php" class="create-post-btn">➕ สร้างโพสต์แจ้งของหาย/พบของ</a>
    <hr style="border: 0; border-top: 1px solid #ddd; margin-bottom: 20px;">

    <?php if ($result_posts->num_rows > 0): ?>
        <?php while($post = $result_posts->fetch_assoc()): ?>
            
            <div class="post-card">
                <div class="post-header">
                    <?php 
                        $author_img = !empty($post['profile_picture']) ? $post['profile_picture'] : 'https://via.placeholder.com/45?text=U';
                    ?>
                    <img src="<?php echo htmlspecialchars($author_img); ?>" class="post-profile-img" alt="Author">
                    <div>
                        <p class="post-author"><?php echo htmlspecialchars($post['username']); ?></p>
                        <p class="post-time"><?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></p>
                    </div>
                </div>

                <div class="post-body">
                    <?php if ($post['post_type'] == 'lost'): ?>
                        <span class="badge badge-lost">🔍 แจ้งของหาย</span>
                    <?php else: ?>
                        <span class="badge badge-found">💡 พบสิ่งของ</span>
                    <?php endif; ?>

                    <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                    <p class="post-desc"><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>
                    
                    <?php if (!empty($post['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($post['image_url']); ?>" class="post-image" alt="Post Image">
                    <?php endif; ?>
                </div>

                <div class="post-footer">
                    <button onclick="openModal(<?php echo $post['post_id']; ?>)" class="comment-btn" style="border: none; cursor: pointer; font-family: inherit;">💬 แสดงความคิดเห็น</button>
                </div>
            </div>

        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align: center; color: gray;">ยังไม่มีโพสต์ในขณะนี้ เริ่มสร้างโพสต์แรกเลย!</p>
    <?php endif; ?>
    </div>
     <div id="commentModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h3 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">ความคิดเห็น</h3>
        
        <div id="commentList" class="comment-list">
            </div>
        
        <div class="comment-input-area">
            <input type="hidden" id="modalPostId">
            <input type="text" id="commentText" placeholder="เขียนความคิดเห็น..." onkeypress="handleEnter(event)">
            <button onclick="submitComment()" class="send-btn">ส่ง</button>
        </div>
    </div>
</div>

<script>
// ================= JavaScript จัดการ Popup =================
function openModal(postId) {
    document.getElementById('commentModal').style.display = 'block';
    document.getElementById('modalPostId').value = postId;
    loadComments(postId); // เรียกฟังก์ชันดึงคอมเมนต์มาโชว์
}

function closeModal() {
    document.getElementById('commentModal').style.display = 'none';
    document.getElementById('commentText').value = ''; // เคลียร์ช่องแชท
}

// ปิด Popup เมื่อคลิกที่พื้นหลังสีดำ
window.onclick = function(event) {
    let modal = document.getElementById('commentModal');
    if (event.target == modal) closeModal();
}

function loadComments(postId) {
    let list = document.getElementById('commentList');
    list.innerHTML = '<p style="text-align:center; color:gray;">กำลังโหลด...</p>';

    fetch('get_comments.php?post_id=' + postId)
    .then(response => response.json())
    .then(data => {
        list.innerHTML = '';
        if(data.length === 0) {
            list.innerHTML = '<p style="text-align:center; color:gray;">ยังไม่มีความคิดเห็น เป็นคนแรกที่แสดงความคิดเห็นสิ!</p>';
            return;
        }
        
        data.forEach(comment => {
            let img = comment.profile_picture ? comment.profile_picture : 'https://via.placeholder.com/35?text=U';
            list.innerHTML += `
                <div class="comment-item">
                    <img src="${img}" class="comment-avatar">
                    <div class="comment-box">
                        <p class="comment-name">${comment.username}</p>
                        <p class="comment-text">${comment.comment_text}</p>
                    </div>
                </div>
            `;
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

    fetch('add_comment.php', { method: 'POST', body: formData })
    .then(response => response.text())
    .then(result => {
        if(result === 'success') {
            textInput.value = ''; 
            loadComments(postId); 
        } else {
            alert('เกิดข้อผิดพลาดในการส่งข้อความ');
        }
    });
}

function handleEnter(e) {
    if(e.key === 'Enter') submitComment();
}
</script>   
</body>
</html>
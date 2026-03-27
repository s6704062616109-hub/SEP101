<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$sql_user = "SELECT username, role, profile_picture FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

$my_profile_image = !empty($user_data['profile_picture']) ? $user_data['profile_picture'] : 'https://via.placeholder.com/40?text=U';

// เพิ่ม u.id AS post_user_id เพื่อใช้กดดูโปรไฟล์เจ้าของโพสต์
$sql_posts = "
    SELECT p.id AS post_id, p.title, p.description, p.image_url, p.post_type, 
           p.item_category, p.status, p.created_at, 
           u.id AS post_user_id, u.username, u.profile_picture 
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
                    <?php $author_img = !empty($post['profile_picture']) ? $post['profile_picture'] : 'https://via.placeholder.com/45?text=U'; ?>
                    <img src="<?php echo htmlspecialchars($author_img); ?>" class="post-profile-img" style="cursor:pointer;" onclick="openProfileModal(<?php echo $post['post_user_id']; ?>)" alt="Author">
                    <div>
                        <p class="post-author" style="cursor:pointer; color:#0084ff;" onclick="openProfileModal(<?php echo $post['post_user_id']; ?>)"><?php echo htmlspecialchars($post['username']); ?></p>
                        <p class="post-time"><?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></p>
                    </div>
                </div>

                <div class="post-body">
                    <?php if ($post['status'] == 'resolved'): ?>
                        <span class="badge" style="background-color: #28a745;">✅ สำเร็จแล้ว (พบของ/คืนของแล้ว)</span>
                    <?php else: ?>
                        <span class="badge" style="background-color: #ffc107; color: #333;">⏳ ยังไม่สำเร็จ</span>
                    <?php endif; ?>

                    <?php if ($post['post_type'] == 'lost'): ?>
                        <span class="badge badge-lost">🔍 แจ้งของหาย</span>
                    <?php else: ?>
                        <span class="badge badge-found">💡 พบสิ่งของ</span>
                    <?php endif; ?>

                    <span class="badge" style="background-color: #6c757d;">🏷️ <?php echo htmlspecialchars($post['item_category'] ?? 'อื่นๆ'); ?></span>

                    <h3 class="post-title" style="margin-top: 10px;"><?php echo htmlspecialchars($post['title']); ?></h3>
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
        <p style="text-align: center; color: gray;">ยังไม่มีโพสต์ในขณะนี้</p>
    <?php endif; ?>
</div>

<div id="commentModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h3 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">ความคิดเห็น</h3>
        
        <div id="commentList" class="comment-list"></div>
        
        <div class="comment-input-area">
            <input type="hidden" id="modalPostId">
            <input type="text" id="commentText" placeholder="เขียนความคิดเห็น..." onkeypress="handleEnter(event)">
            <button onclick="submitComment()" class="send-btn">ส่ง</button>
        </div>
    </div>
</div>

<div id="profileModal" class="modal" style="z-index: 2000;">
    <div class="modal-content" style="text-align: center; max-width: 300px;">
        <span class="close-btn" onclick="closeProfileModal()">&times;</span>
        <img id="pm-img" src="" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin: auto; border: 3px solid #0084ff;">
        <h2 id="pm-name" style="margin: 10px 0;"></h2>
        <p style="color: gray; font-size: 14px;">ข้อมูลติดต่อ:</p>
        <p id="pm-contact" style="background: #f4f4f9; padding: 10px; border-radius: 8px; font-size: 14px; text-align: left;"></p>
    </div>
</div>

<script>
let replyingToId = null; // ตัวแปรเก็บว่ากำลังตอบกลับคอมเมนต์ไหน

// ----------------- ระบบ Popup คอมเมนต์ & ตอบกลับ -----------------
function openModal(postId) {
    document.getElementById('commentModal').style.display = 'block';
    document.getElementById('modalPostId').value = postId;
    loadComments(postId);
}

function closeModal() {
    document.getElementById('commentModal').style.display = 'none';
    cancelReply();
}

function setReply(commentId, username) {
    replyingToId = commentId;
    let input = document.getElementById('commentText');
    input.placeholder = "ตอบกลับ " + username + "...";
    input.focus();
}

function cancelReply() {
    replyingToId = null;
    document.getElementById('commentText').value = '';
    document.getElementById('commentText').placeholder = "เขียนความคิดเห็น...";
}

function loadComments(postId) {
    let list = document.getElementById('commentList');
    list.innerHTML = '<p style="text-align:center; color:gray;">กำลังโหลด...</p>';

    fetch('get_comments.php?post_id=' + postId)
    .then(response => response.json())
    .then(data => {
        list.innerHTML = '';
        if(data.length === 0) {
            list.innerHTML = '<p style="text-align:center; color:gray;">ยังไม่มีความคิดเห็น</p>';
            return;
        }
        
        // แยกคอมเมนต์หลัก และ คอมเมนต์ย่อย
        let mainComments = data.filter(c => c.parent_id === null);
        let replies = data.filter(c => c.parent_id !== null);
        
        mainComments.forEach(comment => {
            let img = comment.profile_picture ? comment.profile_picture : 'https://via.placeholder.com/35?text=U';
            // วาดคอมเมนต์หลัก
            list.innerHTML += `
                <div class="comment-item" style="margin-bottom: 5px;">
                    <img src="${img}" class="comment-avatar" style="cursor:pointer;" onclick="openProfileModal(${comment.user_id})">
                    <div style="flex-grow: 1;">
                        <div class="comment-box" style="display:inline-block;">
                            <p class="comment-name" style="cursor:pointer; color:#0084ff;" onclick="openProfileModal(${comment.user_id})">${comment.username}</p>
                            <p class="comment-text">${comment.comment_text}</p>
                        </div>
                        <div style="margin-top: 3px; margin-left: 10px;">
                            <span style="font-size:12px; cursor:pointer; font-weight:bold; color:#65676b;" onclick="setReply(${comment.id}, '${comment.username}')">ตอบกลับ</span>
                        </div>
                    </div>
                </div>
                <div id="replies-${comment.id}" style="margin-left: 45px; margin-bottom: 15px;"></div>
            `;
        });

        // วาดคอมเมนต์ย่อย เอาไปใส่ใต้คอมเมนต์หลัก
        replies.forEach(reply => {
            let replyBox = document.getElementById('replies-' + reply.parent_id);
            if(replyBox) {
                let img = reply.profile_picture ? reply.profile_picture : 'https://via.placeholder.com/35?text=U';
                replyBox.innerHTML += `
                    <div class="comment-item" style="margin-bottom: 5px; margin-top: 5px;">
                        <img src="${img}" class="comment-avatar" style="width:25px; height:25px; cursor:pointer;" onclick="openProfileModal(${reply.user_id})">
                        <div>
                            <div class="comment-box" style="padding: 5px 10px; display:inline-block;">
                                <p class="comment-name" style="cursor:pointer; color:#0084ff; font-size:12px;" onclick="openProfileModal(${reply.user_id})">${reply.username}</p>
                                <p class="comment-text" style="font-size:13px;">${reply.comment_text}</p>
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
    if(replyingToId !== null) {
        formData.append('parent_id', replyingToId);
    }

    fetch('add_comment.php', { method: 'POST', body: formData })
    .then(response => response.text())
    .then(result => {
        if(result === 'success') {
            cancelReply(); // ล้างค่าและกลับไปโหมดคอมเมนต์ปกติ
            loadComments(postId); 
        } else {
            alert('เกิดข้อผิดพลาดในการส่งข้อความ');
        }
    });
}

function handleEnter(e) { if(e.key === 'Enter') submitComment(); }

// ----------------- ระบบ Popup โปรไฟล์ -----------------
function openProfileModal(userId) {
    fetch('get_profile.php?id=' + userId)
    .then(response => response.json())
    .then(data => {
        document.getElementById('pm-img').src = data.profile_picture ? data.profile_picture : 'https://via.placeholder.com/100?text=U';
        document.getElementById('pm-name').innerText = data.username;
        document.getElementById('pm-contact').innerText = data.contact_info ? data.contact_info : 'ไม่ได้ระบุข้อมูลติดต่อไว้...';
        document.getElementById('profileModal').style.display = 'block';
    });
}

function closeProfileModal() {
    document.getElementById('profileModal').style.display = 'none';
}

// ปิด Popup เมื่อคลิกที่พื้นหลังสีดำ
window.onclick = function(event) {
    let cModal = document.getElementById('commentModal');
    let pModal = document.getElementById('profileModal');
    if (event.target == cModal) closeModal();
    if (event.target == pModal) closeProfileModal();
}
</script>

</body>
</html>
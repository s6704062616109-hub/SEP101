<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลผู้ใช้งาน
$sql_user = "SELECT username, role, profile_picture FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

$my_profile_image = !empty($user_data['profile_picture']) ? $user_data['profile_picture'] : 'https://via.placeholder.com/40?text=U';

// ================= ระบบรับค่าจากช่องค้นหาและตัวกรอง =================
$search_text = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$filter_categories = isset($_GET['category']) ? $_GET['category'] : []; 

// สร้างคำสั่ง SQL แบบไดนามิก (ประกอบร่างตามเงื่อนไขที่ค้นหา)
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

// 1. ถ้ามีการพิมพ์ข้อความค้นหา (หาทั้งหัวข้อและรายละเอียด)
if ($search_text !== '') {
    $sql_posts .= " AND (p.title LIKE ? OR p.description LIKE ?)";
    $search_param = "%" . $search_text . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

// 2. ถ้ามีการเลือกประเภท (ของหาย/พบของ)
if ($filter_type !== '') {
    $sql_posts .= " AND p.post_type = ?";
    $params[] = $filter_type;
    $types .= "s";
}

// 3. ถ้ามีการเลือกสถานะ (สำเร็จ/ยังไม่สำเร็จ)
if ($filter_status !== '') {
    $sql_posts .= " AND p.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

// 4. ถ้ามีการติ๊กหมวดหมู่สิ่งของ
if (!empty($filter_categories)) {
    $cat_conditions = [];
    foreach ($filter_categories as $cat) {
        $cat_conditions[] = "p.item_category LIKE ?";
        $params[] = "%" . $cat . "%";
        $types .= "s";
    }
    // เอาหมวดหมู่มาเชื่อมกันด้วย OR แล้วครอบด้วยวงเล็บ
    $sql_posts .= " AND (" . implode(" OR ", $cat_conditions) . ")";
}

$sql_posts .= " ORDER BY p.created_at DESC";

// รันคำสั่ง SQL ที่ประกอบร่างเสร็จแล้ว
$stmt_posts = $conn->prepare($sql_posts);
if (!empty($params)) {
    $stmt_posts->bind_param($types, ...$params);
}
$stmt_posts->execute();
$result_posts = $stmt_posts->get_result();
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

    <form method="GET" action="index.php" class="search-form">
        <div class="search-container">
            <input type="text" name="q" class="search-input" placeholder="🔍 ค้นหาสิ่งของ, สถานที่, รายละเอียด..." value="<?php echo htmlspecialchars($search_text); ?>" style="flex-grow: 1; padding: 12px 15px; border: 1px solid #ccc; border-radius: 20px;">
            <button type="submit" class="search-btn">ค้นหา</button>
            <button type="button" class="filter-btn" onclick="openFilterModal()">⚙️ กรอง</button>
            <?php if ($user_data['role'] == 'admin'): ?>
                <a href="search_users.php" class="filter-btn" style="background-color: #dc3545; text-decoration: none;">👥 ค้นหาผู้ใช้ (Admin)</a>
            <?php endif; ?>
        </div>

        <div id="filterSearchModal" class="modal" style="z-index: 1500;">
            <div class="modal-content">
                <span class="close-btn" onclick="closeFilterModal()">&times;</span>
                <h3 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">⚙️ ตัวกรองการค้นหา</h3>
                
                <label style="font-weight: bold; margin-top: 10px; display: block;">1. ประเภทการแจ้ง:</label>
                <select name="filter_type" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                    <option value="">-- ทั้งหมด --</option>
                    <option value="lost" <?php if($filter_type=='lost') echo 'selected'; ?>>🔍 แจ้งของหาย</option>
                    <option value="found" <?php if($filter_type=='found') echo 'selected'; ?>>💡 แจ้งพบของ</option>
                </select>

                <label style="font-weight: bold; margin-top: 15px; display: block;">2. สถานะ:</label>
                <select name="filter_status" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                    <option value="">-- ทั้งหมด --</option>
                    <option value="active" <?php if($filter_status=='active') echo 'selected'; ?>>⏳ ยังไม่สำเร็จ</option>
                    <option value="resolved" <?php if($filter_status=='resolved') echo 'selected'; ?>>✅ สำเร็จแล้ว</option>
                </select>

                <label style="font-weight: bold; margin-top: 15px; display: block;">3. หมวดหมู่สิ่งของ:</label>
                <div class="checkbox-group" style="background: #f9f9f9; padding: 10px; border-radius: 4px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 14px;">
                    <?php 
                        $all_cats = ['อุปกรณ์การเรียน', 'อุปกรณ์อิเล็กทรอนิกส์', 'ของใช้ส่วนตัว', 'เอกสารสำคัญ / บัตร', 'อาหาร / กล่องข้าว', 'ของมีค่า / ของสำคัญอื่นๆ', 'อื่นๆ'];
                        foreach($all_cats as $c):
                    ?>
                        <label style="cursor: pointer;"><input type="checkbox" name="category[]" value="<?php echo $c; ?>" <?php if(in_array($c, $filter_categories)) echo 'checked'; ?>> <?php echo $c; ?></label>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="search-btn" style="width: 100%; justify-content: center; margin-top: 20px;">ยืนยันและค้นหา</button>
            </div>
        </div>
    </form>

    <?php if ($search_text !== '' || $filter_type !== '' || $filter_status !== '' || !empty($filter_categories)): ?>
        <a href="index.php" class="clear-search" style="display:block; text-align:center; color:#dc3545; text-decoration:none; margin-bottom:15px; font-weight:bold;">✖ ล้างการค้นหาทั้งหมด (แสดงโพสต์ทั้งหมด)</a>
    <?php endif; ?>

    <div class="action-buttons-container">
        <a href="create_post.php" class="action-btn create-btn">➕ สร้างโพสต์</a>
        <a href="my_posts.php" class="action-btn my-post-btn">📁 โพสต์ของฉัน</a>
    </div>
    <hr style="border: 0; border-top: 1px solid #ddd; margin-bottom: 20px;">

    <?php if ($result_posts->num_rows > 0): ?>
        <?php while($post = $result_posts->fetch_assoc()): ?>
            <div class="post-card">
                
                <div class="post-header" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <?php $author_img = !empty($post['profile_picture']) ? $post['profile_picture'] : 'https://via.placeholder.com/45?text=U'; ?>
                        <img src="<?php echo htmlspecialchars($author_img); ?>" class="post-profile-img" style="cursor:pointer;" onclick="openProfileModal(<?php echo $post['post_user_id']; ?>)">
                        <div>
                            <p class="post-author" style="cursor:pointer; color:#0084ff;" onclick="openProfileModal(<?php echo $post['post_user_id']; ?>)"><?php echo htmlspecialchars($post['username']); ?></p>
                            <p class="post-time"><?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></p>
                        </div>
                    </div>
                    
                    <?php if ($user_data['role'] == 'admin'): ?>
                        <a href="delete_post.php?id=<?php echo $post['post_id']; ?>" class="delete-btn" style="background-color: #dc3545; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; height: fit-content;" onclick="return confirm('ในฐานะ Admin คุณต้องการลบโพสต์นี้ใช่หรือไม่?');">🗑️ ลบ</a>
                    <?php endif; ?>
                </div>

                <div class="post-body">
                    <?php if ($post['status'] == 'resolved'): ?>
                        <span class="badge" style="background-color: #28a745;">✅ สำเร็จแล้ว</span>
                    <?php else: ?>
                        <span class="badge" style="background-color: #ffc107; color: #333;">⏳ ยังไม่สำเร็จ</span>
                    <?php endif; ?>

                    <?php if ($post['post_type'] == 'lost'): ?>
                        <span class="badge badge-lost">🔍 แจ้งของหาย</span>
                    <?php else: ?>
                        <span class="badge badge-found">💡 พบสิ่งของ</span>
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

                    <h3 class="post-title" style="margin-top: 10px;"><?php echo htmlspecialchars($post['title']); ?></h3>
                    <p class="post-desc"><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>
                    <?php if (!empty($post['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($post['image_url']); ?>" class="post-image" style="width: 100%; border-radius: 8px; margin-top: 10px;">
                    <?php endif; ?>
                </div>

                <div class="post-footer">
                    <button onclick="openModal(<?php echo $post['post_id']; ?>)" class="comment-btn" style="border: none; cursor: pointer; font-family: inherit;">💬 แสดงความคิดเห็น</button>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 40px 0; color: gray;">
            <h3 style="margin-bottom: 5px;">ไม่พบโพสต์ที่คุณค้นหา 🕵️‍♂️</h3>
            <p>ลองเปลี่ยนคำค้นหา หรือล้างตัวกรองดูนะครับ</p>
        </div>
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
    <div class="modal-content" style="text-align: center; max-width: 350px;">
        <span class="close-btn" onclick="closeProfileModal()">&times;</span>
        <img id="pm-img" src="" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin: auto; border: 3px solid #0084ff;">
        <h2 id="pm-name" style="margin: 10px 0;"></h2>
        <p id="pm-ban-status" style="color: #dc3545; font-weight: bold; display: none; background: #ffeeba; padding: 5px; border-radius: 4px;">⚠️ ถูกแบน</p>
        <p style="color: gray; font-size: 14px;">ข้อมูลติดต่อ:</p>
        <p id="pm-contact" style="background: #f4f4f9; padding: 10px; border-radius: 8px; font-size: 14px; text-align: left;"></p>

        <div id="admin-ban-controls" style="display: none; margin-top: 15px; border-top: 1px solid #ccc; padding-top: 15px;">
            <input type="hidden" id="targetUserId">
            <span id="targetUserName" style="display:none;"></span>
            <button id="btn-show-ban" onclick="openBanModal()" class="delete-btn" style="width: 100%;">🚫 ระงับการใช้งาน (แบน)</button>
            <button id="btn-do-unban" onclick="unbanUser()" class="search-btn" style="width: 100%; background-color: #28a745; display: none; border: none; padding: 10px; color: white; border-radius: 4px; cursor: pointer;">✅ ปลดแบนผู้ใช้นี้</button>
        </div>
    </div>
</div>

<div id="banModal" class="modal" style="z-index: 2500;">
    <div class="modal-content" style="max-width: 400px;">
        <span class="close-btn" onclick="closeBanModal()">&times;</span>
        <h3 style="margin-top: 0; color: #dc3545;">🚫 ระงับการใช้งาน</h3>
        
        <label>สาเหตุหลัก:</label>
        <select id="banCategory" style="width: 100%; padding: 8px; margin-bottom: 10px;">
            <option value="พฤติกรรมไม่เหมาะสม / ละเมิดกฎ">พฤติกรรมไม่เหมาะสม / ละเมิดกฎ</option>
            <option value="แจ้งข้อมูลเท็จ">แจ้งข้อมูลเท็จ</option>
            <option value="พยายามโกง / แอบอ้าง">พยายามโกง / แอบอ้าง</option>
            <option value="ใช้งานผิดวัตถุประสงค์ระบบ">ใช้งานผิดวัตถุประสงค์ระบบ</option>
            <option value="ละเมิดความปลอดภัยระบบ">ละเมิดความปลอดภัยระบบ</option>
            <option value="ไม่ปฏิบัติตามกติกา">ไม่ปฏิบัติตามกติกา</option>
            <option value="อื่นๆ">อื่นๆ</option>
        </select>

        <label>รายละเอียดเพิ่มเติม:</label>
        <textarea id="banDetails" rows="3" style="width: 100%; padding: 8px; margin-bottom: 10px;" placeholder="กรอกรายละเอียด..."></textarea>

        <label>ระยะเวลาแบน:</label>
        <select id="banDuration" style="width: 100%; padding: 8px; margin-bottom: 20px;">
            <option value="1h">1 ชั่วโมง</option>
            <option value="1d">1 วัน</option>
            <option value="3d">3 วัน</option>
            <option value="7d">7 วัน</option>
            <option value="1m">1 เดือน</option>
            <option value="1y">1 ปี</option>
            <option value="permanent">ถาวร</option>
        </select>

        <button onclick="submitBan()" class="delete-btn" style="width: 100%; border: none; padding: 10px; border-radius: 4px; color: white; cursor: pointer;">ยืนยันการแบน</button>
    </div>
</div>

<script>
// ================= JavaScript ทั้งหมดที่นี่ (คลีนแล้ว) =================
const currentRole = '<?php echo $user_data['role']; ?>'; 

// --- จัดการเปิด/ปิด Popup ต่างๆ ---
function openFilterModal() { document.getElementById('filterSearchModal').style.display = 'block'; }
function closeFilterModal() { document.getElementById('filterSearchModal').style.display = 'none'; }
function openBanModal() { document.getElementById('banModal').style.display = 'block'; }
function closeBanModal() { document.getElementById('banModal').style.display = 'none'; }
function closeProfileModal() { document.getElementById('profileModal').style.display = 'none'; }

// ปิดเมื่อคลิกพื้นหลังดำ
window.onclick = function(event) {
    let cModal = document.getElementById('commentModal');
    let pModal = document.getElementById('profileModal');
    let fModal = document.getElementById('filterSearchModal');
    let bModal = document.getElementById('banModal');
    if (event.target == cModal) closeModal();
    if (event.target == pModal) closeProfileModal();
    if (event.target == fModal) closeFilterModal();
    if (event.target == bModal) closeBanModal();
}

// --- ฟังก์ชัน Profile และ ระบบแบน ---
function openProfileModal(userId) {
    fetch('get_profile.php?id=' + userId).then(r => r.json()).then(data => {
        document.getElementById('pm-img').src = data.profile_picture ? data.profile_picture : 'https://via.placeholder.com/100?text=U';
        document.getElementById('pm-name').innerText = data.username;
        document.getElementById('pm-contact').innerText = data.contact_info ? data.contact_info : 'ไม่ได้ระบุข้อมูลติดต่อไว้...';

        let banStatus = document.getElementById('pm-ban-status');
        if(data.is_banned == 1) {
            let until = data.ban_until ? ' (ถึง ' + data.ban_until + ')' : ' (ถาวร)';
            banStatus.innerText = '⚠️ ผู้ใช้นี้ถูกแบน' + until;
            banStatus.style.display = 'block';
        } else {
            banStatus.style.display = 'none';
        }

        if(currentRole === 'admin') {
            document.getElementById('admin-ban-controls').style.display = 'block';
            document.getElementById('targetUserId').value = data.id;
            document.getElementById('targetUserName').innerText = data.username;

            if(data.is_banned == 1) {
                document.getElementById('btn-show-ban').style.display = 'none';
                document.getElementById('btn-do-unban').style.display = 'inline-block';
            } else {
                document.getElementById('btn-show-ban').style.display = 'inline-block';
                document.getElementById('btn-do-unban').style.display = 'none';
            }
        }
        document.getElementById('profileModal').style.display = 'block';
    });
}

function submitBan() {
    let uid = document.getElementById('targetUserId').value;
    let formData = new FormData();
    formData.append('user_id', uid);
    formData.append('action', 'ban');
    formData.append('category', document.getElementById('banCategory').value);
    formData.append('details', document.getElementById('banDetails').value);
    formData.append('duration', document.getElementById('banDuration').value);

    fetch('ban_user.php', { method: 'POST', body: formData }).then(r => r.text()).then(res => {
        if(res === 'success') { alert('แบนผู้ใช้สำเร็จ'); closeBanModal(); openProfileModal(uid); }
    });
}

function unbanUser() {
    if(!confirm('คุณแน่ใจหรือไม่ว่าต้องการปลดแบนผู้ใช้นี้?')) return;
    let uid = document.getElementById('targetUserId').value;
    let formData = new FormData();
    formData.append('user_id', uid);
    formData.append('action', 'unban');

    fetch('ban_user.php', { method: 'POST', body: formData }).then(r => r.text()).then(res => {
        if(res === 'success') { alert('ปลดแบนสำเร็จ'); openProfileModal(uid); }
    });
}

// --- ฟังก์ชันคอมเมนต์ ---
let replyingToId = null; 
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
        if(data.length === 0) { list.innerHTML = '<p style="text-align:center; color:gray;">ยังไม่มีความคิดเห็น</p>'; return; }
        let mainComments = data.filter(c => c.parent_id === null);
        let replies = data.filter(c => c.parent_id !== null);
        mainComments.forEach(comment => {
            let img = comment.profile_picture ? comment.profile_picture : 'https://via.placeholder.com/35?text=U';
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
    if(replyingToId !== null) formData.append('parent_id', replyingToId);
    fetch('add_comment.php', { method: 'POST', body: formData })
    .then(response => response.text())
    .then(result => {
        if(result === 'success') { cancelReply(); loadComments(postId); } 
        else { alert('เกิดข้อผิดพลาดในการส่งข้อความ'); }
    });
}
function handleEnter(e) { if(e.key === 'Enter') submitComment(); }
</script>

</body>
</html>
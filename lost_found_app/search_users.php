<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: index.php"); exit(); }

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
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ค้นหาผู้ใช้ (Admin)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h2>👥 ค้นหารายชื่อผู้ใช้ (Admin Panel)</h2>
    <form method="GET" action="" style="display: flex; gap: 10px; margin-bottom: 20px;">
        <input type="text" name="search_name" placeholder="พิมพ์ชื่อผู้ใช้ที่ต้องการค้นหา..." value="<?php echo htmlspecialchars($search_query); ?>" style="flex-grow: 1; padding: 10px;">
        <button type="submit" class="search-btn">ค้นหา</button>
    </form>
    <a href="index.php" style="display: block; margin-bottom: 20px; color: gray; text-decoration: none;">← กลับไปหน้าหลัก</a>

    <?php if ($result_users !== null): ?>
        <?php if ($result_users->num_rows > 0): ?>
            <ul style="list-style: none; padding: 0;">
                <?php while($user = $result_users->fetch_assoc()): ?>
                    <li style="background: #f9f9f9; padding: 15px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; cursor: pointer; transition: 0.2s;" onmouseover="this.style.background='#f1f1f1'" onmouseout="this.style.background='#f9f9f9'" onclick="openProfileModal(<?php echo $user['id']; ?>)">
                        <div>
                            <strong style="color:#0084ff; text-decoration:underline;"><?php echo htmlspecialchars($user['username']); ?></strong>
                            <?php if($user['role'] == 'admin') echo "<span style='color:blue; font-size:12px;'>(Admin)</span>"; ?>
                            <?php if($user['is_banned'] == 1) echo "<span style='color:red; font-size:12px; margin-left: 10px; font-weight:bold;'>⚠️ โดนแบน</span>"; ?>
                        </div>
                        <span style="font-size: 14px; color: gray;">คลิกเพื่อจัดการ ⚙️</span>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>ไม่พบผู้ใช้ที่ชื่อตรงกับ "<?php echo htmlspecialchars($search_query); ?>"</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div id="profileModal" class="modal" style="z-index: 2000;">
    <div class="modal-content" style="text-align: center; max-width: 350px;">
        <span class="close-btn" onclick="closeProfileModal()">&times;</span>
        <img id="pm-img" src="" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin: auto; border: 3px solid #0084ff;">
        <h2 id="pm-name" style="margin: 10px 0;"></h2>
        <p id="pm-ban-status" style="color: #dc3545; font-weight: bold; display: none; background: #ffeeba; padding: 5px; border-radius: 4px;">⚠️ ถูกแบน</p>
        <p style="color: gray; font-size: 14px;">ข้อมูลติดต่อ:</p>
        <p id="pm-contact" style="background: #f4f4f9; padding: 10px; border-radius: 8px; font-size: 14px; text-align: left;"></p>

        <div id="admin-ban-controls" style="margin-top: 15px; border-top: 1px solid #ccc; padding-top: 15px;">
            <input type="hidden" id="targetUserId">
            <span id="targetUserName" style="display:none;"></span>
            <button id="btn-show-ban" onclick="openBanModal()" class="delete-btn" style="width: 100%;">🚫 ระงับการใช้งาน (แบน)</button>
            <button id="btn-do-unban" onclick="unbanUser()" class="search-btn" style="width: 100%; background-color: #28a745; display: none;">✅ ปลดแบนผู้ใช้นี้</button>
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

        <button onclick="submitBan()" class="delete-btn" style="width: 100%;">ยืนยันการแบน</button>
    </div>
</div>

<script>
// จัดการเปิด/ปิด Popup
function openBanModal() { document.getElementById('banModal').style.display = 'block'; }
function closeBanModal() { document.getElementById('banModal').style.display = 'none'; }
function closeProfileModal() { document.getElementById('profileModal').style.display = 'none'; }

window.onclick = function(event) {
    let pModal = document.getElementById('profileModal');
    let bModal = document.getElementById('banModal');
    if (event.target == pModal) closeProfileModal();
    if (event.target == bModal) closeBanModal();
}

// เปิดโปรไฟล์
function openProfileModal(userId) {
    // ป้องกันการทำงานซ้ำซ้อนถ้าคลิกที่แถวแล้ว
    event.stopPropagation();
    
    fetch('get_profile.php?id=' + userId).then(r => r.json()).then(data => {
        document.getElementById('pm-img').src = data.profile_picture ? data.profile_picture : 'https://via.placeholder.com/100?text=U';
        document.getElementById('pm-name').innerText = data.username;
        document.getElementById('pm-contact').innerText = data.contact_info ? data.contact_info : 'ไม่ได้ระบุข้อมูลติดต่อไว้...';

        let banStatus = document.getElementById('pm-ban-status');
        if(data.is_banned == 1) {
            let until = data.ban_until ? ' (ถึง ' + data.ban_until + ')' : ' (ถาวร)';
            banStatus.innerText = '⚠️ ผู้ใช้นี้ถูกแบน' + until;
            banStatus.style.display = 'block';
            document.getElementById('btn-show-ban').style.display = 'none';
            document.getElementById('btn-do-unban').style.display = 'inline-block';
        } else {
            banStatus.style.display = 'none';
            document.getElementById('btn-show-ban').style.display = 'inline-block';
            document.getElementById('btn-do-unban').style.display = 'none';
        }

        document.getElementById('targetUserId').value = data.id;
        document.getElementById('targetUserName').innerText = data.username;
        document.getElementById('profileModal').style.display = 'block';
    });
}

// บันทึกการแบน
function submitBan() {
    let uid = document.getElementById('targetUserId').value;
    let formData = new FormData();
    formData.append('user_id', uid);
    formData.append('action', 'ban');
    formData.append('category', document.getElementById('banCategory').value);
    formData.append('details', document.getElementById('banDetails').value);
    formData.append('duration', document.getElementById('banDuration').value);

    fetch('ban_user.php', { method: 'POST', body: formData }).then(r => r.text()).then(res => {
        if(res === 'success') { 
            alert('แบนผู้ใช้สำเร็จ'); 
            window.location.reload(); // โหลดหน้าใหม่เพื่อให้สถานะอัปเดต
        }
    });
}

// ปลดแบน
function unbanUser() {
    if(!confirm('คุณแน่ใจหรือไม่ว่าต้องการปลดแบนผู้ใช้นี้?')) return;
    let uid = document.getElementById('targetUserId').value;
    let formData = new FormData();
    formData.append('user_id', uid);
    formData.append('action', 'unban');

    fetch('ban_user.php', { method: 'POST', body: formData }).then(r => r.text()).then(res => {
        if(res === 'success') { 
            alert('ปลดแบนสำเร็จ'); 
            window.location.reload(); // โหลดหน้าใหม่เพื่อให้สถานะอัปเดต
        }
    });
}
</script>
</body>
</html>
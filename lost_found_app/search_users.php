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
<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8"><title>ค้นหาผู้ใช้ (Admin)</title><link rel="stylesheet" href="style.css"></head>
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
                    <li style="background: #f9f9f9; padding: 15px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                            <?php if($user['role'] == 'admin') echo "<span style='color:blue; font-size:12px;'>(Admin)</span>"; ?>
                            <?php if($user['is_banned'] == 1) echo "<span style='color:red; font-size:12px; margin-left: 10px;'>⚠️ โดนแบน</span>"; ?>
                        </div>
                        <a href="index.php" class="edit-btn" style="text-decoration:none;">กลับไปที่ฟีดเพื่อคลิกดูโปรไฟล์</a>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>ไม่พบผู้ใช้ที่ชื่อตรงกับ "<?php echo htmlspecialchars($search_query); ?>"</p>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body></html>
<?php
session_start();
require 'db.php';

// ดักไว้ไม่ให้ User ธรรมดาแอบเข้าหน้านี้ได้
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: index.php"); 
    exit(); 
}

$search_q = isset($_GET['q']) ? trim($_GET['q']) : '';

// คำสั่งดึง Log และเอาไปเชื่อมกับตาราง users เพื่อเอาชื่อผู้ใช้มาแสดง
$sql = "SELECT l.login_time, u.username 
        FROM login_logs l
        JOIN users u ON l.user_id = u.id
        WHERE 1=1 ";

$params = [];
$types = "";

// ถ้าระบุคำค้นหา (ค้นได้ทั้งชื่อ user และช่วงเวลา/วันที่)
if ($search_q !== '') {
    $sql .= " AND (u.username LIKE ? OR DATE_FORMAT(l.login_time, '%d/%m/%Y') LIKE ? OR DATE_FORMAT(l.login_time, '%Y-%m-%d') LIKE ?) ";
    $q_param = "%" . $search_q . "%";
    $params = [$q_param, $q_param, $q_param];
    $types = "sss";
}

$sql .= " ORDER BY l.login_time DESC";

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result_logs = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ประวัติการเข้าสู่ระบบ (Logs) - Admin Panel</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .log-table { width: 100%; border-collapse: collapse; margin-top: 10px; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-radius: 8px; overflow: hidden; }
        .log-table th { background-color: #343a40; color: white; padding: 12px; text-align: left; }
        .log-table td { padding: 12px; border-bottom: 1px solid #eee; }
        .log-table tr:hover { background-color: #f1f1f1; }
    </style>
</head>
<body>

<div class="container" style="max-width: 800px;">
    <h2>📜 ประวัติการเข้าสู่ระบบ (Login Logs)</h2>
    <p style="color: gray; font-size: 14px;">*ระบบจะลบประวัติที่เก่าเกิน 1 เดือนออกโดยอัตโนมัติ</p>
    
    <form method="GET" action="" style="display: flex; gap: 10px; margin-bottom: 20px;">
        <input type="text" name="q" placeholder="🔍 ค้นหาด้วยชื่อผู้ใช้ หรือ วันที่ (เช่น 28/03/2026)..." value="<?php echo htmlspecialchars($search_q); ?>" style="flex-grow: 1; padding: 10px; border-radius: 20px; border: 1px solid #ccc; outline: none;">
        <button type="submit" class="search-btn">ค้นหา</button>
    </form>
    
    <a href="profile.php" style="display: inline-block; margin-bottom: 20px; color: #0084ff; text-decoration: none; font-weight: bold;">← กลับไปหน้าโปรไฟล์</a>

    <?php if ($result_logs->num_rows > 0): ?>
        <table class="log-table">
            <thead>
                <tr>
                    <th style="width: 50%;">📅 วัน/เดือน/ปี เวลา</th>
                    <th style="width: 50%;">👤 ชื่อผู้ใช้งาน</th>
                </tr>
            </thead>
            <tbody>
                <?php while($log = $result_logs->fetch_assoc()): ?>
                    <tr>
                        <td style="color: #555;"><?php echo date('d/m/Y - H:i:s', strtotime($log['login_time'])); ?></td>
                        <td style="color: #0084ff; font-weight: bold;"><?php echo htmlspecialchars($log['username']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="text-align: center; padding: 30px; background: white; border-radius: 8px; border: 1px dashed #ccc;">
            <h3 style="color: gray;">ไม่พบประวัติการเข้าสู่ระบบ</h3>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
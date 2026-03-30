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

$search_q = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT l.login_time, u.username FROM login_logs l JOIN users u ON l.user_id = u.id WHERE 1=1 ";
$params = []; $types = "";
if ($search_q !== '') {
    $sql .= " AND (u.username LIKE ? OR DATE_FORMAT(l.login_time, '%d/%m/%Y') LIKE ? OR DATE_FORMAT(l.login_time, '%Y-%m-%d') LIKE ?) ";
    $q_param = "%" . $search_q . "%"; $params = [$q_param, $q_param, $q_param]; $types = "sss";
}
$sql .= " ORDER BY l.login_time DESC";
$stmt = $conn->prepare($sql);
if ($types !== '') { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result_logs = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - Admin</title>
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
            <a href="search_users.php" class="menu-item" style="color:#ef4444;"><span class="icon">⚙️</span> Backend (Admin)</a>
            <a href="logs.php" class="menu-item active" style="color:#ef4444;"><span class="icon">📜</span> System Logs</a>
        </div>
        <div class="sidebar-footer">
            <a href="profile.php" class="menu-item"><span class="icon">👤</span> Settings</a>
            <a href="logout.php" class="menu-item" style="color: #ef4444;"><span class="icon">🚪</span> Sign Out</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="content-wrapper">
            <h1 class="page-heading">📜 System Login Logs</h1>
            <p style="color: var(--text-muted); font-size: 14px; margin-bottom:20px;">*Logs older than 1 month are auto-deleted.</p>
            
            <form method="GET" action="" style="display: flex; gap: 10px; margin-bottom: 20px;">
                <input type="text" name="q" placeholder="Search by username or date (e.g. 28/03/2026)..." value="<?php echo htmlspecialchars($search_q); ?>" style="flex-grow: 1; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); background: rgba(255,255,255,0.05); color:white; outline:none;">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>

            <?php if ($result_logs->num_rows > 0): ?>
                <table class="log-table">
                    <thead>
                        <tr>
                            <th style="width: 50%;">📅 Date/Time</th>
                            <th style="width: 50%;">👤 Username</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($log = $result_logs->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d M Y - H:i:s', strtotime($log['login_time'])); ?></td>
                                <td style="color: var(--primary); font-weight: bold;"><?php echo htmlspecialchars($log['username']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 30px; background: var(--card-bg); border-radius: 8px; border: 1px solid var(--border-color);">
                    <h3 style="color: var(--text-muted);">No login logs found</h3>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
function toggleSidebar() { document.getElementById('sidebar').classList.toggle('active'); document.getElementById('mobileOverlay').style.display = document.getElementById('sidebar').classList.contains('active') ? 'block' : 'none'; }
</script>
</body>
</html>
<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) { exit(); }

$user_id = $_SESSION['user_id'];
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

if ($action == 'fetch') {
    $sql = "SELECT n.*, u.username, u.profile_picture 
            FROM notifications n 
            JOIN users u ON n.actor_id = u.id 
            WHERE n.user_id = ? 
            AND n.actor_id NOT IN (SELECT muted_user_id FROM muted_users WHERE user_id = ?)
            ORDER BY n.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifs = [];
    while($row = $result->fetch_assoc()) { $notifs[] = $row; }
    echo json_encode($notifs);
}
elseif ($action == 'delete_one') {
    $notif_id = $_POST['notif_id'];
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notif_id, $user_id);
    if($stmt->execute()) echo "success";
}
elseif ($action == 'delete_all') {
    $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    if($stmt->execute()) echo "success";
}
elseif ($action == 'mute_user') {
    $muted_id = $_POST['muted_id'];
    $stmt = $conn->prepare("INSERT IGNORE INTO muted_users (user_id, muted_user_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $muted_id);
    if($stmt->execute()) {
        $conn->query("DELETE FROM notifications WHERE user_id = $user_id AND actor_id = $muted_id");
        echo "success";
    }
}
// ดึงรายชื่อคนที่ถูกปิดการแจ้งเตือน
elseif ($action == 'fetch_muted') {
    $sql = "SELECT m.muted_user_id, u.username, u.profile_picture 
            FROM muted_users m 
            JOIN users u ON m.muted_user_id = u.id 
            WHERE m.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $muted = [];
    while($row = $result->fetch_assoc()) { $muted[] = $row; }
    echo json_encode($muted);
}
// ปลดบล็อกการแจ้งเตือน (Unmute)
elseif ($action == 'unmute_user') {
    $muted_id = $_POST['muted_id'];
    $stmt = $conn->prepare("DELETE FROM muted_users WHERE user_id = ? AND muted_user_id = ?");
    $stmt->bind_param("ii", $user_id, $muted_id);
    if($stmt->execute()) echo "success";
}
?>
<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo "banned"; // ถ้าไม่มี Session ให้ถือว่าเด้งออก
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT is_banned, ban_until FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();
    if ($user['is_banned'] == 1) {
        if ($user['ban_until'] !== null && strtotime($user['ban_until']) <= time()) {
            // หมดเวลาแบนแล้ว ปลดแบนอัตโนมัติ
            $unban_stmt = $conn->prepare("UPDATE users SET is_banned=0, ban_category=NULL, ban_details=NULL, ban_until=NULL WHERE id=?");
            $unban_stmt->bind_param("i", $user_id);
            $unban_stmt->execute();
            echo "ok";
        } else {
            // ยังโดนแบนอยู่ ทำลาย Session ทิ้ง!
            session_destroy();
            echo "banned";
        }
    } else {
        echo "ok";
    }
} else {
    echo "banned";
}
?>
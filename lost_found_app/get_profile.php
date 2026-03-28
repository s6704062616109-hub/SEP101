<?php
require 'db.php';
if (isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT id, username, contact_info, profile_picture, is_banned, ban_until FROM users WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    echo json_encode($result);
}
?>
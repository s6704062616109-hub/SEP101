<?php
// 1. เริ่ม Session
session_start();

// 2. ทำลาย Session (ฉีกบัตรผ่านทิ้ง)
session_destroy();

// 3. เด้งกลับไปหน้าเข้าสู่ระบบ
header("Location: login.php");
exit();
?>
<?php
// ต้อง include ไฟล์ที่เก็บฟังก์ชัน logout()
include('auth_config.php'); 

// เรียกใช้ฟังก์ชัน logout เพื่อเคลียร์ Session และ redirect ไปหน้า Login
logout(); 
?>
<?php
/**
 * Dashboard.php
 * ไฟล์นี้ทำหน้าที่เป็นตัวกระจายเส้นทาง (Redirector) หลังจากการ Login สำเร็จ
 * โดยจะตรวจสอบบทบาทของผู้ใช้ (UserRole) ใน Session และส่งไปยังหน้าหลักที่เหมาะสม
 */
include('auth_config.php');

// 1. ตรวจสอบว่าผู้ใช้ได้เข้าสู่ระบบแล้วหรือไม่
if (!is_logged_in()) {
    // ถ้ายังไม่ได้เข้าสู่ระบบ ให้ส่งกลับไปหน้า Login
    header("Location: login.php");
    exit();
}

$user_role = $_SESSION['user_role'];

// 2. ตรวจสอบบทบาทและ Redirect ไปยังหน้าหลักของบทบาทนั้นๆ
switch ($user_role) {
    case 'Teacher':
        // อาจารย์: ส่งไปยังหน้า Dashboard สรุปผลงาน
        header("Location: teacher_dashboard.php");
        exit();
    case 'Officer':
        // เจ้าหน้าที่: ส่งไปยังหน้า Dashboard สรุปภาพรวมการบริหารจัดการ
        header("Location: officer_dashboard.php");
        exit();
    case 'Admin':
        // ผู้ดูแลระบบ: ส่งไปยังหน้า Dashboard การจัดการระบบ
        header("Location: admin_dashboard.php");
        exit();
    case 'Student':
        // นักศึกษา: อาจจะส่งไปยังหน้าค้นหา/ดูผลงานหลัก
        header("Location: search.php");
        exit();
    default:
        // บทบาทไม่ถูกต้องหรือไม่ทราบ ให้ Logout เพื่อความปลอดภัย
        logout();
        break;
}

// ในทางทฤษฎีโค้ดจะไม่มาถึงบรรทัดนี้ เพราะมีการ Exit() แล้ว
?>

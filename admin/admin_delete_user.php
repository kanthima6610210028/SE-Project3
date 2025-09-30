<?php
// admin_delete_user.php
// ไฟล์สำหรับประมวลผลการลบผู้ใช้งาน (ไม่มีหน้าเว็บแสดงผล)

require_once 'auth_config.php';
require_role(['Admin']); // จำกัดสิทธิ์เฉพาะ Admin

// 1. ตรวจสอบว่ามี ID ส่งมาหรือไม่
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    set_session_message('error', 'คำขอไม่ถูกต้อง: ไม่พบรหัสผู้ใช้');
    header('Location: admin_user_management.php');
    exit;
}

$user_id_to_delete = (int)$_GET['id'];
$current_user_id = $_SESSION['user_id'];

// 2. ป้องกันไม่ให้ Admin ลบบัญชีของตัวเอง
if ($user_id_to_delete === $current_user_id) {
    set_session_message('error', 'ไม่สามารถลบบัญชีของตัวเองได้');
    header('Location: admin_user_management.php');
    exit;
}

try {
    // 3. เตรียมคำสั่ง SQL เพื่อลบผู้ใช้
    // หมายเหตุ: เนื่องจากเราตั้งค่า Foreign Key เป็น ON DELETE CASCADE
    // เมื่อลบผู้ใช้, ผลงาน (publications) และข้อมูลอื่นๆ ที่เชื่อมโยงกับ User_Id นี้จะถูกลบไปด้วย
    $stmt = $pdo->prepare("DELETE FROM users WHERE User_Id = ?");
    $stmt->execute([$user_id_to_delete]);

    // 4. ตรวจสอบว่ามีการลบเกิดขึ้นจริงหรือไม่
    if ($stmt->rowCount() > 0) {
        set_session_message('success', "✅ ลบผู้ใช้รหัส #{$user_id_to_delete} สำเร็จแล้ว");
    } else {
        set_session_message('error', "ไม่พบผู้ใช้รหัส #{$user_id_to_delete} ในระบบ");
    }

} catch (PDOException $e) {
    // กรณีเกิดปัญหาในการเชื่อมต่อฐานข้อมูล
    set_session_message('error', 'เกิดข้อผิดพลาดในการลบข้อมูล: ' . $e->getMessage());
}

// 5. ส่งกลับไปที่หน้ารายการผู้ใช้
header('Location: admin_user_management.php');
exit;
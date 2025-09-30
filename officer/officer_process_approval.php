<?php
// officer_process_approval.php
// (ฉบับแก้ไข: ปรับค่า Action_type ให้ตรงกับ ENUM ในฐานข้อมูล)

require_once 'auth_config.php';
require_role(['Officer', 'Admin']);

// 1. ตรวจสอบว่าเป็น POST request และมีข้อมูลครบถ้วน
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['publication_id'], $_POST['action'])) {
    set_session_message('error', 'คำขอไม่ถูกต้อง');
    header('Location: officer_review.php');
    exit;
}

// 2. รับและตรวจสอบข้อมูล
$publication_id = (int)$_POST['publication_id'];
$action = $_POST['action']; // 'approve', 'reject', 'revision'
$comment = trim($_POST['comment'] ?? '');
$officer_id = $_SESSION['user_id'];

$allowed_actions = ['approve', 'reject', 'revision'];
if ($publication_id <= 0 || !in_array($action, $allowed_actions)) {
    set_session_message('error', 'ข้อมูลที่ส่งมาไม่ถูกต้อง');
    header('Location: officer_review.php');
    exit;
}

// 3. กำหนดค่าสถานะใหม่ตาม action
$new_pub_status = '';
$approval_status = '';
$log_action_type = '';

switch ($action) {
    case 'approve':
        $new_pub_status = 'Published';
        $approval_status = 'Approved';
        $log_action_type = 'approve';
        break;
    case 'reject':
        $new_pub_status = 'Rejected';
        $approval_status = 'Rejected';
        $log_action_type = 'reject';
        break;
    case 'revision':
        $new_pub_status = 'Draft'; 
        $approval_status = 'Rejected';
        // --== แก้ไขตรงนี้: เปลี่ยน 'revision' เป็น 'rollback' ให้ตรงกับ ENUM ==--
        $log_action_type = 'rollback'; 
        break;
}

// 4. อัปเดตฐานข้อมูล (ใช้ Transaction)
try {
    $pdo->beginTransaction();

    // 4.1 อัปเดตสถานะในตาราง publications
    $stmt_pub = $pdo->prepare("UPDATE publications SET PubStatus = ? WHERE PubID = ?");
    $stmt_pub->execute([$new_pub_status, $publication_id]);

    // 4.2 บันทึกประวัติการตัดสินใจลงในตาราง approval
    $stmt_approval = $pdo->prepare(
        "INSERT INTO approval (Publication_id, Approved_by, Status, Approved_at, Comment) VALUES (?, ?, ?, NOW(), ?)"
    );
    $stmt_approval->execute([$publication_id, $officer_id, $approval_status, $comment]);

    // 4.3 บันทึกการกระทำลงใน Audit Log
    $stmt_log = $pdo->prepare(
        "INSERT INTO auditlog (User_id, Publication_id, Action_type, Log_date) VALUES (?, ?, ?, NOW())"
    );
    $stmt_log->execute([$officer_id, $publication_id, $log_action_type]);

    $pdo->commit();
    set_session_message('success', "✅ ดำเนินการกับผลงานรหัส #{$publication_id} สำเร็จแล้ว");

} catch (PDOException $e) {
    $pdo->rollBack();
    set_session_message('error', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage());
}

// 5. ส่งผู้ใช้กลับไปที่หน้ารวม
header('Location: officer_review.php');
exit;
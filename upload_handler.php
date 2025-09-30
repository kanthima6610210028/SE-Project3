<?php
/**
 * upload_handler.php
 * ไฟล์จัดการการส่งข้อมูลและอัปโหลดไฟล์สำหรับเพิ่ม (Add) และแก้ไข (Edit) ผลงานตีพิมพ์
 */
include('auth_config.php');

// ตรวจสอบสิทธิ์: ต้องเป็น Teacher เท่านั้น
if (!check_user_role('Teacher')) {
    flash_message('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้.', 'login.php');
}

// กำหนดโฟลเดอร์สำหรับเก็บไฟล์อัปโหลด
// ** สำคัญ: โฟลเดอร์นี้ต้องมีอยู่จริงและตั้งค่าสิทธิ์ให้ PHP เขียนได้ (chmod 777)
$upload_dir = 'uploads/publications/';

// 1. ตรวจสอบการรับค่า POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash_message('error', 'ไม่พบข้อมูลการส่งฟอร์ม.', 'teacher_dashboard.php');
}

// กำหนดค่าเริ่มต้นสำหรับการตรวจสอบ
$is_edit = isset($_POST['pub_id']) && !empty($_POST['pub_id']);
$target_page = $is_edit ? 'edit_publication.php?pub_id=' . $_POST['pub_id'] : 'add_publication.php';

// ดึงข้อมูลพื้นฐานจากฟอร์ม
$pub_id = $is_edit ? $_POST['pub_id'] : null;
$user_id = $_SESSION['user_id'];
$pub_name = trim($_POST['pub_name'] ?? '');
$pub_detail = trim($_POST['pub_detail'] ?? '');
$pub_type = trim($_POST['pub_type'] ?? '');
$pub_date = trim($_POST['pub_date'] ?? ''); // YYYY-MM-DD

// ตรวจสอบข้อมูลที่จำเป็น
if (empty($pub_name) || empty($pub_detail) || empty($pub_type) || empty($pub_date)) {
    flash_message('error', 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน.', $target_page);
}

try {
    $pdo->beginTransaction();

    // 2. จัดการข้อมูลหลัก (publications)
    if ($is_edit) {
        // Mode: แก้ไข (UPDATE)
        $sql = "UPDATE publications SET 
                PubName = ?, 
                PubDetail = ?, 
                PubType = ?, 
                PubDate = ?,
                PubStatus = 'In Revision' 
                WHERE PubID = ? AND User_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$pub_name, $pub_detail, $pub_type, $pub_date, $pub_id, $user_id]);
        $message = 'แก้ไขผลงานตีพิมพ์เรียบร้อยแล้วและสถานะถูกตั้งค่าเป็น "รอตรวจสอบ"';

        // หลังจากแก้ไข ต้องลบไฟล์เก่าที่อาจถูกอัปโหลดทับ
        if (isset($_POST['delete_current_file']) && $_POST['delete_current_file'] == '1') {
            $delete_sql = "DELETE FROM publicationfile WHERE Publication_id = ?";
            $pdo->prepare($delete_sql)->execute([$pub_id]);
        }

    } else {
        // Mode: เพิ่มใหม่ (INSERT)
        $sql = "INSERT INTO publications (User_id, PubName, PubDetail, PubType, PubDate, PubStatus, Created_at) 
                VALUES (?, ?, ?, ?, ?, 'Pending', NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $pub_name, $pub_detail, $pub_type, $pub_date]);
        $pub_id = $pdo->lastInsertId();
        $message = 'ส่งผลงานตีพิมพ์เข้าระบบเรียบร้อยแล้ว รอการตรวจสอบจากเจ้าหน้าที่';
    }

    // 3. จัดการไฟล์ที่อัปโหลด (Upload Files)
    if (isset($_FILES['publication_file']) && $_FILES['publication_file']['error'] == UPLOAD_ERR_OK) {
        
        // ตรวจสอบและสร้างโฟลเดอร์ถ้ายังไม่มี
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file = $_FILES['publication_file'];
        $file_name = basename($file['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $unique_file_name = uniqid('pub_', true) . '.' . $file_ext;
        $target_file = $upload_dir . $unique_file_name;
        
        // อนุญาตเฉพาะไฟล์ PDF, DOCX, ZIP
        $allowed_ext = ['pdf', 'docx', 'doc', 'zip'];
        if (!in_array($file_ext, $allowed_ext)) {
            $pdo->rollBack();
            flash_message('error', 'ไม่อนุญาตไฟล์ประเภท .' . $file_ext . ' กรุณาใช้ PDF, DOCX, DOC หรือ ZIP เท่านั้น.', $target_page);
        }

        // ย้ายไฟล์
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            
            // 3.1 บันทึกข้อมูลไฟล์ลงในตาราง publicationfile
            // สำหรับการแก้ไข (Edit) เราจะลบไฟล์เก่าของผลงานนี้ออกก่อน
            if ($is_edit) {
                 // ลบข้อมูลไฟล์เก่าออกจาก DB (ถ้ามี)
                $delete_file_sql = "DELETE FROM publicationfile WHERE Publication_id = ?";
                $pdo->prepare($delete_file_sql)->execute([$pub_id]);
            }

            $file_sql = "INSERT INTO publicationfile (Publication_id, FilePath, FileName, Uploaded_at) 
                         VALUES (?, ?, ?, NOW())";
            $pdo->prepare($file_sql)->execute([$pub_id, $target_file, $file_name]);

        } else {
            $pdo->rollBack();
            flash_message('error', 'เกิดข้อผิดพลาดในการย้ายไฟล์ไปยังเซิร์ฟเวอร์.', $target_page);
        }
    }

    $pdo->commit();
    flash_message('success', $message, 'teacher_publications.php');

} catch (\PDOException $e) {
    $pdo->rollBack();
    // บันทึก Error ลง Log เพื่อตรวจสอบ
    error_log("Publication Handler PDO Error: " . $e->getMessage());
    flash_message('error', 'เกิดข้อผิดพลาดทางฐานข้อมูล: ' . $e->getMessage(), $target_page);
} catch (\Exception $e) {
    // บันทึก Error ลง Log เพื่อตรวจสอบ
    error_log("Publication Handler General Error: " . $e->getMessage());
    flash_message('error', 'เกิดข้อผิดพลาดที่ไม่คาดคิด: ' . $e->getMessage(), $target_page);
}
?>

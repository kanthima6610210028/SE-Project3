<?php
/**
 * download_file.php
 * จัดการการดาวน์โหลดไฟล์แนบของผลงานตีพิมพ์
 * ไฟล์นี้จะตรวจสอบสิทธิ์ (ว่าไฟล์นี้มาจากผลงานที่ได้รับการอนุมัติหรือไม่) ก่อนส่งไฟล์
 */
include('auth_config.php');

$file_id = $_GET['file_id'] ?? null;

if (!$file_id || !is_numeric($file_id)) {
    // ส่งกลับไปหน้าหลักหากไม่มี file_id
    header('Location: index.php');
    exit;
}

try {
    // 1. ดึงข้อมูลไฟล์จากตาราง publicationfile
    $sql = "
        SELECT 
            pf.File_path, 
            pf.File_name,
            p.PubStatus
        FROM publicationfile pf
        JOIN publications p ON pf.Publication_id = p.PubID
        WHERE pf.File_id = :file_id
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':file_id' => $file_id]);
    $file_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$file_data) {
        // ไม่พบไฟล์
        flash_message("error", "ไม่พบไฟล์ที่ต้องการดาวน์โหลด", "public_publications.php");
        exit;
    }

    $file_path_db = $file_data['File_path'];
    $file_name = $file_data['File_name'] ?? basename($file_path_db);
    $pub_status = $file_data['PubStatus'];

    // 2. ตรวจสอบสถานะการอนุมัติ (Security Check)
    // อนุญาตให้ดาวน์โหลดเฉพาะผลงานที่มีสถานะ 'Approved' เท่านั้น
    if ($pub_status !== 'Approved') {
        flash_message("error", "ไฟล์นี้มาจากผลงานที่ยังไม่ได้รับการอนุมัติ", "public_publications.php");
        exit;
    }

    // 3. ตรวจสอบว่าไฟล์มีอยู่จริงในระบบ (สมมติว่าไฟล์ถูกเก็บอยู่ในโฟลเดอร์ 'uploads/')
    // **สำคัญ: คุณต้องแน่ใจว่า path นี้ถูกต้องตามโครงสร้างการเก็บไฟล์ของคุณ**
    $full_file_path = 'uploads/' . $file_path_db;

    if (!file_exists($full_file_path)) {
        flash_message("error", "ไฟล์ไม่พร้อมสำหรับการดาวน์โหลด (File not found on server).", "public_publications.php");
        exit;
    }

    // 4. จัดการ Header สำหรับการดาวน์โหลด
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($full_file_path));

    // 5. อ่านและส่งไฟล์ไปยังเบราว์เซอร์
    readfile($full_file_path);
    exit;

} catch (\PDOException $e) {
    // หากเกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล
    flash_message("error", "เกิดข้อผิดพลาดของระบบฐานข้อมูล: " . $e->getMessage(), "public_publications.php");
    exit;
} catch (Exception $e) {
    // ข้อผิดพลาดอื่น ๆ
    flash_message("error", "เกิดข้อผิดพลาดที่ไม่คาดคิด: " . $e->getMessage(), "public_publications.php");
    exit;
}
?>

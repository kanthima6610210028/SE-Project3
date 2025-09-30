<?php
// กำหนดให้ PHP แสดงข้อผิดพลาดทั้งหมดสำหรับการพัฒนา
ini_set('display_errors', 1);
error_reporting(E_ALL);

// =================================================================
// 1. การจัดการ Session และ Authorization
// =================================================================

// เริ่มต้น Session หากยังไม่ได้เริ่มต้น
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// รวมไฟล์การเชื่อมต่อฐานข้อมูล เพื่อให้ตัวแปร $pdo พร้อมใช้งาน
include('db.php');

/**
 * ตรวจสอบว่าผู้ใช้ได้เข้าสู่ระบบแล้วหรือไม่
 * @return bool True หากมี 'user_id' และ 'user_role' ใน Session
 */
function is_logged_in(): bool
{
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * ตรวจสอบบทบาทของผู้ใช้ที่เข้าสู่ระบบ
 * @param string $required_role บทบาทที่ต้องการ (เช่น 'Teacher', 'Officer', 'Admin')
 * @return bool True หากผู้ใช้มีบทบาทตรงตามที่กำหนด
 */
function check_user_role(string $required_role): bool
{
    if (!is_logged_in()) {
        return false;
    }
    // ใช้ strtoupper เพื่อความยืดหยุ่นในการเปรียบเทียบ Role
    return strtoupper($_SESSION['user_role']) === strtoupper($required_role);
}

/**
 * ฟังก์ชันบังคับให้ผู้ใช้มีบทบาทตามที่กำหนด
 * หากไม่มีสิทธิ์จะถูกส่งกลับไปหน้า login
 * @param array $allowed_roles อาร์เรย์ของบทบาทที่อนุญาต
 */
function require_role(array $allowed_roles): void
{
    if (!is_logged_in() || !in_array($_SESSION['user_role'], $allowed_roles)) {
        flash_message('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้', 'login.php');
    }
}

/**
 * ฟังก์ชันสำหรับการ Logout
 * ทำลาย Session และส่งกลับไปหน้า Login
 */
function logout(): void
{
    // ลบตัวแปร session ทั้งหมด
    $_SESSION = array();

    // ลบคุกกี้ session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // ทำลาย session
    session_destroy();

    header("Location: login.php");
    exit();
}

/**
 * ฟังก์ชันสำหรับกำหนดหน้า Dashboard ที่เหมาะสมตามบทบาทของผู้ใช้
 * @param string $role บทบาทของผู้ใช้
 * @return string Path ของหน้า Dashboard
 */
function get_user_role_dashboard(string $role): string
{
    switch ($role) {
        case 'Teacher':
            return 'teacher_dashboard.php';
        case 'Officer':
            return 'officer_dashboard.php';
        case 'Admin':
            return 'admin_dashboard.php';
        case 'Student':
            return 'search.php'; // หรือหน้าที่เหมาะสมสำหรับนักศึกษา
        default:
            return 'login.php';
    }
}


// =================================================================
// 2. การจัดการข้อความแจ้งเตือน (Flash Messages)
// =================================================================

/**
 * ตั้งค่าข้อความแจ้งเตือนและ Redirect ไปยังหน้าอื่น
 * @param string $type ประเภทของข้อความ ('success', 'error', 'warning')
 * @param string $message ข้อความที่ต้องการแสดง
 * @param string $redirect_to URL ที่จะ redirect ไป
 */
function flash_message(string $type, string $message, string $redirect_to): void
{
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
    header("Location: " . $redirect_to);
    exit();
}

/**
 * ตั้งค่าข้อความแจ้งเตือนลงใน Session
 * @param string $type ประเภทของข้อความ ('success', 'error', 'warning')
 * @param string $message ข้อความที่ต้องการแสดง
 */
function set_session_message(string $type, string $message)
{
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * ดึงข้อความแจ้งเตือนจาก Session และลบออก
 * @return array|null ข้อมูลข้อความแจ้งเตือน หรือ null ถ้าไม่มี
 */
function get_session_message(): ?array
{
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// =================================================================
// 3. ฟังก์ชันสำหรับบันทึก Audit Log
// =================================================================

/**
 * บันทึกกิจกรรมของผู้ใช้ลงในตาราง auditlog
 * @param PDO $pdo อ็อบเจกต์ PDO ที่เชื่อมต่อฐานข้อมูล
 * @param int $user_id ID ของผู้ใช้
 * @param string $action ประเภทของกิจกรรม (เช่น 'login', 'approve_publication')
 * @param string $details รายละเอียดของกิจกรรม
 * @param int|null $publication_id ID ของผลงาน (ถ้ามี)
 */
function log_audit(PDO $pdo, int $user_id, string $action, string $details, int $publication_id = null): void
{
    try {
        $sql = "INSERT INTO auditlog (User_id, Action_type, Details, Publication_id, Log_date) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $action, $details, $publication_id]);
    } catch (\PDOException $e) {
        // ในกรณีที่บันทึก Log ไม่ได้ ควรจะบันทึก error ลงไฟล์ log
        error_log("Audit Log Error: " . $e->getMessage());
    }
}
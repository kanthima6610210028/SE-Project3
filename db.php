<?php
// ตั้งค่าการแสดงผลข้อผิดพลาด (ควรปิดในการผลิตจริง)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ตั้งค่าฐานข้อมูล MySQL
// *** โปรดแก้ไข 3 บรรทัดนี้ให้ตรงกับการตั้งค่า MySQL ของคุณ ***
$host = 'localhost'; // หรือ IP ฐานข้อมูล
$user = 'root';      // ชื่อผู้ใช้ฐานข้อมูล (เปลี่ยนเป็นของคุณ)
$pass = 'root';  // รหัสผ่านฐานข้อมูล (เปลี่ยนเป็นของคุณ)
$dbname = 'publication_system'; // ชื่อฐานข้อมูลที่อัปโหลด SQL ไปแล้ว
// **********************************************************

// Data Source Name (DSN) สำหรับ PDO
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // โยนข้อยกเว้นเมื่อมีข้อผิดพลาด
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // ดึงข้อมูลในรูปแบบ Array ที่มี Key เป็นชื่อคอลัมน์
    PDO::ATTR_EMULATE_PREPARES   => false,                // ใช้ Prepared Statements จริงๆ
];

try {
    // สร้างอ็อบเจกต์ PDO เพื่อเชื่อมต่อฐานข้อมูล
    $pdo = new PDO($dsn, $user, $pass, $options);
    // echo "Connection successful!"; // สามารถเปิดบรรทัดนี้เพื่อทดสอบการเชื่อมต่อครั้งแรก
} catch (PDOException $e) {
    // หากเกิดข้อผิดพลาดในการเชื่อมต่อ
    die("❌ ไม่สามารถเชื่อมต่อฐานข้อมูลได้: " . $e->getMessage());
}
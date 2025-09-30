<?php
/**
 * user_manual.php
 * หน้าคู่มือการใช้งานระบบสำหรับผู้ใช้งานทุกประเภท
 */
include('auth_config.php');

// Helper function สำหรับจัดรูปแบบหัวข้อ
function manual_header($title) {
    echo '<h2 class="text-2xl font-bold text-indigo-700 mb-3 border-b-2 border-indigo-300 pb-1 mt-6"><i class="fas fa-arrow-alt-circle-right mr-2"></i> ' . $title . '</h2>';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คู่มือการใช้งานระบบ PubTracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', 'Tahoma', sans-serif; }
        .content-box { 
            background-color: #ffffff; 
            padding: 1.5rem; 
            border-radius: 12px; 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border-left: 6px solid #4f46e5;
        }
    </style>
</head>
<body class="bg-gray-50">

<div class="min-h-screen flex flex-col">
    <header class="bg-white shadow-md sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex-shrink-0">
                    <a href="index.php" class="text-2xl font-bold text-indigo-700">
                        <i class="fas fa-graduation-cap mr-2"></i> PubTracker
                    </a>
                </div>
                
                <nav class="hidden md:flex space-x-6 items-center">
                    <a href="index.php" class="text-gray-900 hover:text-indigo-600 transition duration-150">หน้าแรก</a>
                    <a href="public_publications.php" class="text-gray-900 hover:text-indigo-600 transition duration-150">บทความการตีพิมพ์</a>
                    <a href="user_manual.php" class="text-indigo-600 font-semibold border-b-2 border-indigo-600 pb-1 transition duration-150">คู่มือการใช้งานระบบ</a>
                    <a href="login.php" class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700 transition duration-150">
                        <i class="fas fa-sign-in-alt mr-1"></i> เข้าสู่ระบบ
                    </a>
                </nav>
                <div class="md:hidden">
                    <button class="text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 rounded-lg p-2">
                        <i class="fas fa-bars fa-lg"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-4xl font-extrabold text-gray-900 mb-8 border-b-4 border-indigo-500 pb-2">
            <i class="fas fa-book-reader text-indigo-600 mr-2"></i> คู่มือการใช้งานระบบจัดเก็บผลงานวิจัย (PubTracker)
        </h1>

        <div class="content-box">
            <p class="text-gray-700 mb-6">ระบบ PubTracker ถูกออกแบบมาเพื่ออำนวยความสะดวกในการจัดเก็บ ตรวจสอบ และเผยแพร่ผลงานตีพิมพ์ของคณาจารย์ในมหาวิทยาลัย โดยแบ่งการใช้งานออกเป็น 3 ส่วนหลัก ดังนี้:</p>
            
            <?php manual_header('ส่วนที่ 1: ผู้เยี่ยมชมทั่วไป (Public Visitor)'); ?>
            <ul class="list-disc list-inside space-y-2 text-gray-700 ml-4">
                <li><span class="font-semibold">หน้าแรก (`index.php`):</span> ดูผลงานล่าสุด 5 รายการที่ได้รับการอนุมัติแล้ว</li>
                <li><span class="font-semibold">บทความการตีพิมพ์ (`public_publications.php`):</span> ค้นหาและกรองผลงานทั้งหมดในระบบ (โดยใช้ Keyword, ประเภท, และปีที่พิมพ์)</li>
                <li><span class="font-semibold">ดูรายละเอียด (`public_detail.php`):</span> ดูบทคัดย่อ ข้อมูลผู้แต่ง และดาวน์โหลดไฟล์แนบ (ถ้ามีและได้รับการอนุญาต)</li>
            </ul>

            <?php manual_header('ส่วนที่ 2: อาจารย์ (Teacher Portal)'); ?>
            <p class="text-gray-700 mt-4 mb-3">เมื่ออาจารย์เข้าสู่ระบบผ่าน `login.php` จะถูกนำไปที่หน้า `teacher_dashboard.php` ซึ่งมีฟังก์ชันหลักดังนี้:</p>
            <ul class="list-disc list-inside space-y-2 text-gray-700 ml-4">
                <li><span class="font-semibold">แดชบอร์ด:</span> แสดงภาพรวมสถานะผลงานทั้งหมด (รอตรวจสอบ, อนุมัติแล้ว, ถูกตีกลับ)</li>
                <li><span class="font-semibold">ส่งผลงานใหม่ (`add_publication.php`):</span> กรอกรายละเอียดผลงาน (PubName, PubDetail, PubType, PubDate) และอัปโหลดไฟล์ที่เกี่ยวข้อง</li>
                <li><span class="font-semibold">จัดการผลงาน (`manage_publications.php`):</span> ดู, แก้ไข, หรือลบผลงานของตนเองที่ยังรอการตรวจสอบ หรือถูกตีกลับเพื่อแก้ไข</li>
                <li><span class="font-semibold">การแจ้งเตือน:</span> รับการแจ้งเตือนเมื่อผลงานถูกตรวจสอบหรือได้รับอนุมัติ</li>
            </ul>
            <div class="p-3 bg-yellow-50 border border-yellow-300 rounded-lg text-sm text-yellow-800 mt-4">
                <i class="fas fa-exclamation-triangle mr-2"></i> หากผลงานมีสถานะ "ถูกตีกลับ" โปรดแก้ไขข้อมูลและส่งผลงานอีกครั้ง
            </div>

            <?php manual_header('ส่วนที่ 3: เจ้าหน้าที่ (Officer Portal)'); ?>
            <p class="text-gray-700 mt-4 mb-3">เจ้าหน้าที่ผู้ดูแลระบบมีหน้าที่หลักในการตรวจสอบและอนุมัติผลงานผ่าน `officer_dashboard.php`:</p>
            <ul class="list-disc list-inside space-y-2 text-gray-700 ml-4">
                <li><span class="font-semibold">แดชบอร์ด/ตรวจสอบผลงาน:</span> แสดงรายการผลงานทั้งหมดที่มีสถานะ **"รอตรวจสอบ"**</li>
                <li><span class="font-semibold">หน้าตรวจสอบ (`review_publication.php`):</span> เข้าไปดูรายละเอียดผลงานที่ส่งมาทั้งหมด พร้อมปุ่มดำเนินการ:
                    <ul class="list-circle list-inside ml-6 mt-1">
                        <li><i class="fas fa-check-circle text-green-600"></i> <span class="font-medium">อนุมัติ (Approved):</span> ผลงานจะถูกเผยแพร่ใน Public Portal</li>
                        <li><i class="fas fa-times-circle text-red-600"></i> <span class="font-medium">ตีกลับ (Rejected/Need Revision):</span> ส่งข้อเสนอแนะกลับไปยังอาจารย์ผู้ส่งผลงาน</li>
                    </ul>
                </li>
                <li><span class="font-semibold">รายงาน (`reports.php`):</span> สร้างรายงานสรุปผลงานตามประเภทหรือปี (หากสร้างไฟล์นี้)</li>
            </ul>

            <?php manual_header('การแก้ไขปัญหาเบื้องต้น'); ?>
            <div class="space-y-3 text-gray-700">
                <p><span class="font-semibold">เข้าสู่ระบบไม่ได้:</span> ตรวจสอบ Username (Email) และ Password หากลืมรหัสผ่าน โปรดติดต่อผู้ดูแลระบบ</p>
                <p><span class="font-semibold">ส่งผลงานไม่สำเร็จ:</span> ตรวจสอบว่าได้กรอกข้อมูลในช่องที่จำเป็นครบถ้วน และขนาดไฟล์ที่อัปโหลดไม่เกินกำหนด (ตั้งค่าใน PHP/Web server)</p>
            </div>

        </div>
    </main>

    <footer class="bg-gray-900 text-white py-6 mt-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm opacity-75">
            &copy; <?= date('Y') ?> PubTracker System. All rights reserved.
        </div>
    </footer>
</div>

</body>
</html>

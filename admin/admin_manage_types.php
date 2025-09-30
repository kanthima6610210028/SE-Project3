<?php
// admin_manage_types.php
// หน้าสำหรับแสดงประเภทผลงานทั้งหมดที่มีอยู่ในระบบ

require_once 'auth_config.php';
require_role(['Admin']);

// --== แก้ไข SQL Query ตรงนี้ ==--
// ดึง "ประเภท" ที่ไม่ซ้ำกันทั้งหมดจากตาราง publications 
$query = "SELECT DISTINCT PubType FROM publications WHERE PubType IS NOT NULL AND PubType != '' ORDER BY PubType ASC";
$stmt = $pdo->query($query);
$types = $stmt->fetchAll(PDO::FETCH_COLUMN);
// --== จบส่วนแก้ไข ==--

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการประเภทผลงาน - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', 'Tahoma', sans-serif; background-color: #f4f7f9; }
    </style>
</head>
<body class="min-h-screen">

    <header class="bg-gray-800 text-gray-100 shadow-lg sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center p-4">
            <a href="admin_dashboard.php" class="text-xl font-bold"><i class="fas fa-cogs mr-2"></i> ADMIN PANEL</a>
            <nav class="hidden md:flex space-x-4">
                <a href="admin_dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">หน้าแรก</a>
                <a href="admin_manage_publications.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">จัดการสิ่งพิมพ์</a>
                <a href="admin_user_management.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">จัดการผู้ใช้</a>
                <a href="admin_manage_types.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700 bg-gray-900">จัดการประเภท</a>
                <a href="admin_audit_log.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700 ">บันทึกตรวจสอบ</a>
                <a href="logout.php" class="px-3 py-2 rounded-md text-sm font-medium text-red-400 hover:bg-red-700">ออกจากระบบ</a>
            </nav>
        </div>
    </header>

    <main class="ml-64 py-10 px-8">
        
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-sitemap text-indigo-600 mr-2"></i> จัดการประเภทผลงาน
            </h1>
             </div>

        <p class="text-gray-600 mb-8">
            นี่คือรายการประเภทผลงานทั้งหมดที่มีการใช้งานอยู่ในระบบ ณ ปัจจุบัน
        </p>

        <div class="bg-white rounded-xl shadow-lg overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-4 font-semibold text-gray-600">ชื่อประเภทผลงาน (TYPENAME)</th>
                        <th class="p-4 font-semibold text-gray-600">การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($types)): ?>
                        <tr>
                            <td colspan="2" class="text-center p-6 text-gray-500">
                                <i class="fas fa-info-circle mr-2"></i> ยังไม่มีประเภทผลงานในระบบ
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($types as $type): ?>
                            <tr class="border-t hover:bg-gray-50">
                                <td class="p-4 font-medium text-gray-800"><?= htmlspecialchars($type) ?></td>
                                <td class="p-4 text-gray-400 italic">
                                   ไม่มีการดำเนินการ
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>
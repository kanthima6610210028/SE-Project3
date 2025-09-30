<?php
/**
 * officer_dashboard.php
 * หน้าแดชบอร์ดสำหรับเจ้าหน้าที่ (Officer)
 * (ฉบับแก้ไขตาม Schema และ Layout ที่ถูกต้อง)
 */
include('auth_config.php');
require_role(['Officer', 'Admin']);

$summary_data = [
    'pending_count' => 0,
    'total_publications' => 0,
    'published_last_30_days' => 0,
    'draft_count' => 0
];
$pending_publications = [];
$error_message = '';

try {
    // 1. ดึงข้อมูลสรุป
    $stmt_summary = $pdo->query("
        SELECT 
            SUM(CASE WHEN PubStatus = 'Pending' THEN 1 ELSE 0 END) as pending_count,
            COUNT(PubID) as total_publications,
            SUM(CASE 
                WHEN PubStatus = 'Published' AND PubDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                THEN 1 ELSE 0 
            END) as published_last_30_days,
            SUM(CASE WHEN PubStatus = 'Draft' THEN 1 ELSE 0 END) as draft_count
        FROM publications
    ");
    $summary_data = $stmt_summary->fetch(PDO::FETCH_ASSOC);

    // 2. ดึงรายการผลงานที่ 'รอตรวจสอบ' ล่าสุด (Top 10)
    $stmt_pending = $pdo->query("
        SELECT p.PubID, p.PubName, p.PubType, p.Created_at, u.UserName
        FROM publications p
        JOIN users u ON p.User_id = u.User_Id
        WHERE p.PubStatus = 'Pending'
        ORDER BY p.Created_at ASC
        LIMIT 10
    ");
    $pending_publications = $stmt_pending->fetchAll(PDO::FETCH_ASSOC);

} catch (\PDOException $e) {
    $error_message = "❌ เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ดเจ้าหน้าที่</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', 'Tahoma', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">

    <header class="bg-gray-800 text-gray-100 shadow-lg sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center p-4">
            
            <a href="officer_dashboard.php" class="text-xl font-bold">
                <i class="fas fa-tools mr-2"></i> Officer Portal
            </a>

            <nav class="hidden md:flex space-x-4 items-center">
                <a href="officer_dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700 bg-gray-900">หน้าหลัก</a>
                <a href="officer_review.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">ตรวจสอบผลงาน</a>
                <a href="officer_all_publications.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">ผลงานทั้งหมด</a>
                <a href="officer_reports.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">รายงาน</a>
                <a href="profile.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">ข้อมูลส่วนตัว</a>
                <a href="logout.php" class="px-3 py-2 rounded-md text-sm font-medium text-red-400 hover:bg-red-700">ออกจากระบบ</a>
            </nav>
        </div>
    </header>

    <main class="container mx-auto p-6 md:p-10">
        <h1 class="text-3xl font-extrabold text-gray-900 mb-8">
            <i class="fas fa-tachometer-alt text-indigo-600 mr-2"></i> Officer Dashboard
        </h1>

        <?php if (!empty($error_message)): ?>
            <div class="p-4 rounded-lg mb-6 bg-red-100 text-red-700" role="alert">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-red-500">
                <p class="text-sm font-medium text-red-600">ผลงานที่รอตรวจสอบ</p>
                <p class="text-4xl font-extrabold text-red-900 mt-1"><?= $summary_data['pending_count'] ?? 0 ?></p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-blue-500">
                <p class="text-sm font-medium text-blue-600">ผลงานทั้งหมดในระบบ</p>
                <p class="text-4xl font-extrabold text-blue-900 mt-1"><?= $summary_data['total_publications'] ?? 0 ?></p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-yellow-500">
                <p class="text-sm font-medium text-yellow-600">สถานะแบบร่าง</p>
                <p class="text-4xl font-extrabold text-yellow-900 mt-1"><?= $summary_data['draft_count'] ?? 0 ?></p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-green-500">
                <p class="text-sm font-medium text-green-600">เผยแพร่ 30 วันล่าสุด</p>
                <p class="text-4xl font-extrabold text-green-900 mt-1"><?= $summary_data['published_last_30_days'] ?? 0 ?></p>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-xl shadow-2xl">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">รายการผลงานที่รอตรวจสอบ (ล่าสุด)</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อเรื่อง</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ผู้ส่ง</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ประเภท</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันที่ส่ง</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">ดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($pending_publications)): ?>
                            <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">ไม่มีผลงานที่รอการตรวจสอบ ณ ขณะนี้!</td></tr>
                        <?php else: foreach ($pending_publications as $pub): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold"><?= htmlspecialchars($pub['PubName']) ?></td>
                                <td class="px-6 py-4"><?= htmlspecialchars($pub['UserName']) ?></td>
                                <td class="px-6 py-4"><?= htmlspecialchars($pub['PubType']) ?></td>
                                <td class="px-6 py-4"><?= date('d/m/Y', strtotime($pub['Created_at'])) ?></td>
                                <td class="px-6 py-4 text-center">
                                    <a href="officer_review_detail.php?id=<?= $pub['PubID'] ?>" class="text-white bg-red-500 hover:bg-red-600 px-3 py-1 rounded-full text-xs font-bold">ตรวจสอบ</a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
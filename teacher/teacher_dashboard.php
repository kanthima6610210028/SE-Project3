<?php
/**
 * teacher_dashboard.php
 * หน้าหลักสำหรับอาจารย์ (Teacher) ดีไซน์ใหม่ตามข้อกำหนด
 * (ฉบับแก้ไข: เปลี่ยนเป็น Header Layout)
 */
include('auth_config.php');
require_role(['Teacher']);

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'อาจารย์';
$error_message = '';

$stats = [
    'total' => 0, 'published' => 0, 'pending' => 0,
    'draft' => 0, 'rejected' => 0,
];
$recent_publications = [];

try {
    // ดึงข้อมูลสถิติ
    $stmt_stats = $pdo->prepare("
        SELECT COUNT(PubID) AS total,
               SUM(CASE WHEN PubStatus = 'Published' THEN 1 ELSE 0 END) AS published,
               SUM(CASE WHEN PubStatus = 'Pending' THEN 1 ELSE 0 END) AS pending,
               SUM(CASE WHEN PubStatus = 'Draft' THEN 1 ELSE 0 END) AS draft,
               SUM(CASE WHEN PubStatus = 'Rejected' THEN 1 ELSE 0 END) AS rejected
        FROM publications WHERE User_id = ?
    ");
    $stmt_stats->execute([$user_id]);
    $result = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $stats = array_map(fn($val) => $val ?? 0, $result);
    }

    // ดึงข้อมูล 5 ผลงานล่าสุด
    $stmt_recent = $pdo->prepare("
        SELECT PubID, PubName, PubStatus, Created_at
        FROM publications WHERE User_id = ? ORDER BY Created_at DESC LIMIT 5
    ");
    $stmt_recent->execute([$user_id]);
    $recent_publications = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);

} catch (\PDOException $e) {
    $error_message = "❌ เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
}

// Helper function สำหรับแสดงสถานะเป็น Badge
if (!function_exists('get_status_badge')) {
    function get_status_badge($status) {
        switch ($status) {
            case 'Pending': return '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">รอตรวจสอบ</span>';
            case 'Published': return '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">เผยแพร่แล้ว</span>';
            case 'Draft': return '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">แบบร่าง</span>';
            case 'Rejected': return '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">ถูกปฏิเสธ</span>';
            default: return '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100">ไม่ทราบ</span>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= htmlspecialchars($user_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style> body { font-family: 'Inter', 'Tahoma', sans-serif; } </style>
</head>
<body class="bg-gray-100">

    <header class="bg-gray-800 text-gray-100 shadow-lg sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center p-4">
            <a href="teacher_dashboard.php" class="text-xl font-bold">
                <i class="fas fa-graduation-cap mr-2"></i> Teacher Portal
            </a>
            <nav class="hidden md:flex space-x-4 items-center">
                <a href="teacher_dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700 bg-gray-900">หน้าหลัก</a>
                <a href="teacher_publications.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">ผลงานของฉัน</a>
                <a href="add_publication.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">ส่งผลงานใหม่</a>
                <a href="profile.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">ข้อมูลส่วนตัว</a>
                <a href="teacher_reports.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">รายงานผลงาน</a>
                <a href="logout.php" class="px-3 py-2 rounded-md text-sm font-medium text-red-400 hover:bg-red-700">ออกจากระบบ</a>
            </nav>
        </div>
    </header>

    <main class="container mx-auto p-6 md:p-10">
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-extrabold text-gray-900">👋 ยินดีต้อนรับ, <?= htmlspecialchars($user_name) ?>!</h1>
        </header>

        <?php if ($error_message): ?>
            <div class="p-4 rounded-lg mb-6 bg-red-100 text-red-700" role="alert"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <section class="mb-10">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">สรุปภาพรวม</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-lg"><p class="text-sm font-medium text-gray-500">ผลงานทั้งหมด</p><p class="text-3xl font-bold text-gray-900"><?= $stats['total'] ?></p></div>
                <div class="bg-white p-6 rounded-xl shadow-lg"><p class="text-sm font-medium text-gray-500">รอตรวจสอบ</p><p class="text-3xl font-bold text-gray-900"><?= $stats['pending'] ?></p></div>
                <div class="bg-white p-6 rounded-xl shadow-lg"><p class="text-sm font-medium text-gray-500">เผยแพร่แล้ว</p><p class="text-3xl font-bold text-gray-900"><?= $stats['published'] ?></p></div>
                <div class="bg-white p-6 rounded-xl shadow-lg"><p class="text-sm font-medium text-gray-500">ถูกปฏิเสธ</p><p class="text-3xl font-bold text-gray-900"><?= $stats['rejected'] ?></p></div>
            </div>
        </section>

        <section class="mb-10">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">การดำเนินการด่วน</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <a href="add_publication.php" class="block bg-green-500 p-6 rounded-xl shadow-lg text-white hover:bg-green-600">
                    <h3 class="text-xl font-bold">ส่งผลงานใหม่</h3>
                    <p>อัปโหลดและกรอกรายละเอียดผลงานของคุณ</p>
                </a>
                <a href="teacher_publications.php" class="block bg-blue-500 p-6 rounded-xl shadow-lg text-white hover:bg-blue-600">
                    <h3 class="text-xl font-bold">ผลงานของฉัน</h3>
                    <p>ดูและจัดการผลงานทั้งหมดที่ส่งไปแล้ว</p>
                </a>
                <a href="profile.php" class="block bg-gray-700 p-6 rounded-xl shadow-lg text-white hover:bg-gray-800">
                    <h3 class="text-xl font-bold">แก้ไขข้อมูลส่วนตัว</h3>
                    <p>จัดการชื่อ, ตำแหน่ง, และรหัสผ่าน</p>
                </a>
            </div>
        </section>

        <section>
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800">ผลงานล่าสุด</h2>
                <a href="teacher_publications.php" class="text-indigo-600 hover:underline font-semibold">ดูทั้งหมด →</a>
            </div>
            <div class="bg-white rounded-xl shadow-lg overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="p-4 font-semibold text-gray-600">ชื่องานวิจัย</th>
                            <th class="p-4 font-semibold text-gray-600">วันที่ส่ง</th>
                            <th class="p-4 font-semibold text-gray-600">สถานะ</th>
                            <th class="p-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php if (empty($recent_publications)): ?>
                            <tr><td colspan="4" class="p-6 text-center text-gray-500">ยังไม่มีผลงานที่ส่งในระบบ</td></tr>
                        <?php else: foreach ($recent_publications as $pub): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-4 font-medium text-gray-800"><?= htmlspecialchars($pub['PubName']) ?></td>
                                <td class="p-4 text-gray-600"><?= date('d/m/Y', strtotime($pub['Created_at'])) ?></td>
                                <td class="p-4"><?= get_status_badge($pub['PubStatus']) ?></td>
                                <td class="p-4 text-right">
                                    <a href="teacher_view_publication.php?id=<?= $pub['PubID'] ?>" class="text-indigo-600 hover:text-indigo-800 font-medium">ดูรายละเอียด</a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

</body>
</html>
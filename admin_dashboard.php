<?php
/**
 * admin_dashboard.php
 * หน้าแดชบอร์ดสำหรับผู้ดูแลระบบ (Admin) แสดงภาพรวมและสถิติหลักของระบบ
 * (ฉบับแก้ไข SQL และ Layout)
 */
require_once 'auth_config.php';
require_role(['Admin']);

$user_name = $_SESSION['user_name'] ?? 'ผู้ดูแลระบบ';
$stats = []; // Initialize stats array

try {
    // --== แก้ไข: Query ที่ 1: เปลี่ยน 'Approved' เป็น 'Published' ==--
    $pub_sql = "
        SELECT
            COUNT(*) AS total_publications,
            SUM(CASE WHEN PubStatus = 'Published' THEN 1 ELSE 0 END) AS published_count,
            SUM(CASE WHEN PubStatus = 'Pending' THEN 1 ELSE 0 END) AS pending_count
        FROM publications
    ";
    $pub_stats_stmt = $pdo->query($pub_sql);
    $pub_stats = $pub_stats_stmt->fetch(PDO::FETCH_ASSOC);

    // --== Query ที่ 2 (เหมือนเดิม) ==--
    $user_sql = "
        SELECT
            COUNT(*) AS total_users,
            SUM(CASE WHEN UserRole = 'Teacher' THEN 1 ELSE 0 END) AS teacher_count,
            SUM(CASE WHEN UserRole = 'Officer' THEN 1 ELSE 0 END) AS officer_count,
            SUM(CASE WHEN UserRole = 'Admin' THEN 1 ELSE 0 END) AS admin_count
        FROM users
    ";
    $user_stats_stmt = $pdo->query($user_sql);
    $user_stats = $user_stats_stmt->fetch(PDO::FETCH_ASSOC);

    // รวมผลลัพธ์
    $stats = array_merge($pub_stats, $user_stats);

} catch (\PDOException $e) {
    die("เกิดข้อผิดพลาดในการดึงสถิติ: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ดผู้ดูแลระบบ - PubTracker</title>
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
            <nav class="hidden md:flex space-x-4 items-center">
                <a href="admin_dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700 bg-gray-900">หน้าแรก</a>
                <a href="admin_manage_publications.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">จัดการสิ่งพิมพ์</a>
                <a href="admin_user_management.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">จัดการผู้ใช้</a>
                <a href="admin_manage_types.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">จัดการประเภท</a>
                <a href="admin_audit_log.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">บันทึกตรวจสอบ</a>
                <a href="logout.php" class="px-3 py-2 rounded-md text-sm font-medium text-red-400 hover:bg-red-700">ออกจากระบบ</a>
            </nav>
        </div>
    </header>

    <main class="container mx-auto p-6 md:p-10"> 
        
        <?php 
        $message = get_session_message();
        if ($message): 
            $alert_class = $message['type'] === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700';
        ?>
            <div class="mb-6 p-4 rounded-lg shadow-md border <?= $alert_class; ?>" role="alert">
                <i class="fas fa-info-circle mr-2"></i> <?= htmlspecialchars($message['message']); ?>
            </div>
        <?php endif; ?>

        <h1 class="text-3xl font-bold text-gray-800 mb-6">
            <i class="fas fa-chart-line text-indigo-600 mr-2"></i> แดชบอร์ดผู้ดูแลและระบบ
        </h1>
        <p class="text-gray-600 mb-8">ภาพรวมสถานะปัจจุบันและการจัดการระบบทั้งหมด</p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <p class="text-sm font-medium text-gray-500">ผลงานทั้งหมดในระบบ</p>
                <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['total_publications'] ?? 0); ?></p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <p class="text-sm font-medium text-gray-500">รอการตรวจสอบ</p>
                <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['pending_count'] ?? 0); ?></p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <p class="text-sm font-medium text-gray-500">เผยแพร่แล้ว</p>
                <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['published_count'] ?? 0); ?></p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <p class="text-sm font-medium text-gray-500">จำนวนผู้ใช้งานทั้งหมด</p>
                <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['total_users'] ?? 0); ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-lg">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">การกระจายบทบาทผู้ใช้งาน</h2>
                <div class="space-y-4">
                    <div class="flex justify-between items-center"><span class="font-medium">อาจารย์ (Teacher)</span><span class="font-bold"><?= number_format($stats['teacher_count'] ?? 0); ?></span></div>
                    <div class="flex justify-between items-center"><span class="font-medium">เจ้าหน้าที่ (Officer)</span><span class="font-bold"><?= number_format($stats['officer_count'] ?? 0); ?></span></div>
                    <div class="flex justify-between items-center"><span class="font-medium">ผู้ดูแลระบบ (Admin)</span><span class="font-bold"><?= number_format($stats['admin_count'] ?? 0); ?></span></div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">ทางลัดการจัดการ</h2>
                <div class="space-y-3">
                    <a href="admin_user_management.php" class="block w-full text-center py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700">จัดการผู้ใช้งาน</a>
                    <a href="admin_manage_types.php" class="block w-full text-center py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700">จัดการประเภทผลงาน</a>
                    <a href="admin_audit_log.php" class="block w-full text-center py-3 bg-gray-600 text-white font-medium rounded-lg hover:bg-gray-700">ดูบันทึกการตรวจสอบ</a>
                </div>
            </div>
        </div>
    </main>

</body>
</html>
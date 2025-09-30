<?php
/**
 * officer_reports.php
 * หน้าแสดงรายงานและสถิติภาพรวมของระบบสำหรับเจ้าหน้าที่
 * (ฉบับแก้ไข: เปลี่ยนเป็น Header Layout)
 */
include('auth_config.php');
require_role(['Officer', 'Admin']);

$error_message = '';
$stats = [
    'total_publications' => 0, 'status_counts' => [], 'type_counts' => [],
    'yearly_counts' => [], 'pending_count' => 0, 'published_count' => 0,
];

try {
    // A. ดึงข้อมูลสรุป
    $stmt_summary = $pdo->query("
        SELECT COUNT(*) as total_publications,
               SUM(CASE WHEN PubStatus = 'Pending' THEN 1 ELSE 0 END) as pending_count,
               SUM(CASE WHEN PubStatus = 'Published' THEN 1 ELSE 0 END) as published_count
        FROM publications
    ");
    $summary_results = $stmt_summary->fetch(PDO::FETCH_ASSOC);
    if ($summary_results) {
        $stats = array_merge($stats, $summary_results);
    }

    // B. นับจำนวนตามสถานะ
    $stmt_status = $pdo->query("SELECT PubStatus, COUNT(*) as count FROM publications GROUP BY PubStatus");
    while ($row = $stmt_status->fetch(PDO::FETCH_ASSOC)) {
        $stats['status_counts'][$row['PubStatus']] = $row['count'];
    }

    // C. นับจำนวนตามประเภท
    $stmt_type = $pdo->query("SELECT PubType, COUNT(*) as count FROM publications WHERE PubType IS NOT NULL AND PubType != '' GROUP BY PubType ORDER BY count DESC");
    while ($row = $stmt_type->fetch(PDO::FETCH_ASSOC)) {
        $stats['type_counts'][$row['PubType']] = $row['count'];
    }

    // D. นับจำนวนตามปีที่พิมพ์
    $stmt_yearly = $pdo->query("SELECT YEAR(PubDate) as year, COUNT(*) as count FROM publications WHERE PubDate IS NOT NULL GROUP BY year ORDER BY year DESC");
    while ($row = $stmt_yearly->fetch(PDO::FETCH_ASSOC)) {
        if ($row['year'] > 0) $stats['yearly_counts'][$row['year']] = $row['count'];
    }

} catch (\PDOException $e) {
    $error_message = "❌ เกิดข้อผิดพลาด: " . $e->getMessage();
}

function get_status_color_class($status) {
    switch ($status) {
        case 'Pending': return 'bg-yellow-500';
        case 'Published': return 'bg-green-500';
        case 'Draft': return 'bg-gray-500';
        case 'Rejected': return 'bg-red-500';
        default: return 'bg-indigo-500';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานภาพรวมระบบ - เจ้าหน้าที่</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style> body { font-family: 'Inter', 'Tahoma', sans-serif; } </style>
</head>
<body class="bg-gray-100">

    <header class="bg-gray-800 text-gray-100 shadow-lg sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center p-4">
            <a href="officer_dashboard.php" class="text-xl font-bold"><i class="fas fa-tools mr-2"></i> Officer Portal</a>
            <nav class="hidden md:flex space-x-4 items-center">
                <a href="officer_dashboard.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">หน้าหลัก</a>
                <a href="officer_review.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">ตรวจสอบผลงาน</a>
                <a href="officer_all_publications.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">ผลงานทั้งหมด</a>
                <a href="officer_reports.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700 bg-gray-900">รายงาน</a>
                <a href="profile.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">ข้อมูลส่วนตัว</a>
                <a href="logout.php" class="px-3 py-2 text-sm rounded-md text-red-400 hover:bg-red-700">ออกจากระบบ</a>
            </nav>
        </div>
    </header>

    <main class="container mx-auto p-6 md:p-10">
        <h1 class="text-3xl font-extrabold text-gray-900 mb-8"><i class="fas fa-chart-line text-indigo-600 mr-2"></i> รายงานภาพรวมและสถิติระบบ</h1>
        
        <?php if ($error_message): ?>
            <div class="p-4 rounded-lg mb-6 bg-red-100 text-red-700"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <p class="text-sm font-medium text-gray-500">ผลงานทั้งหมด</p>
                <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['total_publications']) ?></p>
            </div>
            <?php
            $published_count = $stats['published_count'] ?? 0;
            $published_percent = $stats['total_publications'] > 0 ? round(($published_count / $stats['total_publications']) * 100) : 0;
            ?>
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <p class="text-sm font-medium text-gray-500">เผยแพร่แล้ว</p>
                <p class="text-3xl font-bold text-gray-900"><?= number_format($published_count) ?></p>
                <p class="text-xs text-gray-500 mt-1"><?= $published_percent ?>% ของทั้งหมด</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <p class="text-sm font-medium text-gray-500">รอตรวจสอบ (ด่วน)</p>
                <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['pending_count']) ?></p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h2 class="text-xl font-bold text-gray-800 mb-4">สถิติตามสถานะ</h2>
                <div class="space-y-3">
                    <?php foreach ($stats['status_counts'] as $status => $count): 
                        $percent = $stats['total_publications'] > 0 ? round(($count / $stats['total_publications']) * 100) : 0;
                        $color_class = get_status_color_class($status);
                    ?>
                        <div class="flex justify-between items-center text-sm">
                            <span class="font-medium"><?= htmlspecialchars($status) ?></span>
                            <div class="w-2/4 bg-gray-200 rounded-full h-2.5">
                                <div class="h-2.5 rounded-full <?= $color_class ?>" style="width: <?= $percent ?>%"></div>
                            </div>
                            <span class="font-bold"><?= number_format($count) ?> (<?= $percent ?>%)</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h2 class="text-xl font-bold text-gray-800 mb-4">สถิติตามประเภท</h2>
                <div class="space-y-3">
                    <?php foreach ($stats['type_counts'] as $type => $count): 
                        $percent = $stats['total_publications'] > 0 ? round(($count / $stats['total_publications']) * 100) : 0;
                    ?>
                        <div class="flex justify-between items-center text-sm">
                            <span class="font-medium"><?= htmlspecialchars($type) ?></span>
                            <div class="w-2/4 bg-gray-200 rounded-full h-2.5">
                                <div class="h-2.5 rounded-full bg-purple-500" style="width: <?= $percent ?>%"></div>
                            </div>
                            <span class="font-bold"><?= number_format($count) ?> (<?= $percent ?>%)</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-lg">
                <h2 class="text-xl font-bold text-gray-800 mb-4">ผลงานจำแนกตามปีที่ตีพิมพ์</h2>
                <div class="space-y-4">
                    <?php if (!empty($stats['yearly_counts'])):
                        $max_count = max($stats['yearly_counts']);
                        foreach ($stats['yearly_counts'] as $year => $count): 
                            $bar_width = $max_count > 0 ? round(($count / $max_count) * 100) : 0;
                        ?>
                            <div class="flex items-center text-sm">
                                <span class="w-16 font-bold"><?= htmlspecialchars($year) ?></span>
                                <div class="flex-1 bg-gray-200 rounded h-6 ml-3">
                                    <div class="h-full rounded bg-indigo-600 flex items-center justify-end pr-2" style="width: <?= max(5, $bar_width) ?>%">
                                        <span class="text-white text-xs font-semibold"><?= number_format($count) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; 
                    endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
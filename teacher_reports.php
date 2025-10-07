<?php
/**
 * teacher_reports.php
 * หน้าแสดงรายงานสรุปผลงานตีพิมพ์ของอาจารย์ (Teacher)
 * (ฉบับแก้ไข: เปลี่ยนเป็น Header Layout)
 */
include('auth_config.php');
require_role(['Teacher']);

$user_id = $_SESSION['user_id'];
$report_data = [];
$report_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$available_years = [];
$error_message = '';

// =================================================================
// 1. Logic ดึงปีที่มีผลงานทั้งหมด
// =================================================================
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT YEAR(PubDate) as publication_year
        FROM publications WHERE User_id = ? ORDER BY publication_year DESC
    ");
    $stmt->execute([$user_id]);
    $available_years = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($available_years) && !in_array($report_year, $available_years)) {
        $report_year = $available_years[0]; 
    }
} catch (\PDOException $e) {
    $error_message = "❌ เกิดข้อผิดพลาดในการดึงข้อมูลปี: " . $e->getMessage();
}

// =================================================================
// 2. Logic ดึงข้อมูลรายงาน
// =================================================================
try {
    $stmt_report = $pdo->prepare("
        SELECT 
            PubType,
            SUM(CASE WHEN PubStatus = 'Published' THEN 1 ELSE 0 END) as PublishedCount,
            SUM(CASE WHEN PubStatus = 'Pending' THEN 1 ELSE 0 END) as PendingCount,
            SUM(CASE WHEN PubStatus = 'Draft' THEN 1 ELSE 0 END) as DraftCount,
            SUM(CASE WHEN PubStatus = 'Rejected' THEN 1 ELSE 0 END) as RejectedCount,
            COUNT(PubID) as TotalCount
        FROM publications
        WHERE User_id = ? AND YEAR(PubDate) = ?
        GROUP BY PubType ORDER BY TotalCount DESC
    ");
    $stmt_report->execute([$user_id, $report_year]);
    $report_data = $stmt_report->fetchAll(PDO::FETCH_ASSOC);

} catch (\PDOException $e) {
    $error_message = "❌ เกิดข้อผิดพลาดในการดึงข้อมูลรายงาน: " . $e->getMessage();
}

// คำนวณยอดรวม
$grand_total = array_sum(array_column($report_data, 'TotalCount'));
$grand_published = array_sum(array_column($report_data, 'PublishedCount'));
$grand_pending = array_sum(array_column($report_data, 'PendingCount'));
$grand_draft = array_sum(array_column($report_data, 'DraftCount'));
$grand_rejected = array_sum(array_column($report_data, 'RejectedCount'));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานผลงานตีพิมพ์ - อาจารย์</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', 'Tahoma', sans-serif; }
        @media print {
            .print-hidden { display: none !important; }
            main { padding: 0 !important; margin: 0 !important; }
            .report-card { box-shadow: none !important; border: 1px solid #ccc !important; }
        }
    </style>
</head>
<body class="bg-gray-100">

    <!-- Header (Navbar) -->
    <header class="bg-gray-800 text-gray-100 shadow-lg sticky top-0 z-50 print-hidden">
        <div class="container mx-auto flex justify-between items-center p-4">
            <a href="teacher_dashboard.php" class="text-xl font-bold"><i class="fas fa-graduation-cap mr-2"></i> Teacher Portal</a>
            <nav class="hidden md:flex space-x-4 items-center">
                <a href="teacher_dashboard.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">หน้าหลัก</a>
                <a href="teacher_publications.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">ผลงานของฉัน</a>
                <a href="add_publication.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">ส่งผลงานใหม่</a>
                <a href="profile.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">ข้อมูลส่วนตัว</a>
                <a href="teacher_reports.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700 bg-gray-900">รายงานผลงาน</a>
                <a href="logout.php" class="px-3 py-2 text-sm rounded-md text-red-400 hover:bg-red-700">ออกจากระบบ</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto p-6 md:p-10">
        <div class="flex justify-between items-center mb-8 print-hidden">
            <h1 class="text-3xl font-extrabold text-gray-900"><i class="fas fa-chart-bar text-green-600 mr-2"></i> รายงานสรุปผลงานตีพิมพ์</h1>
            <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 shadow-md">
                <i class="fas fa-print mr-1"></i> พิมพ์ / ดาวน์โหลด
            </button>
        </div>
        
        <?php if ($error_message): ?>
            <div class="p-4 rounded-lg mb-6 bg-red-100 text-red-700 print-hidden" role="alert"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-xl shadow-2xl border-t-4 border-green-500 report-card">
            <div class="flex justify-between items-center mb-6 print-hidden">
                <h2 class="text-2xl font-bold text-gray-800">ข้อมูลประจำปี: <span class="text-green-600"><?= $report_year ?></span></h2>
                <form method="GET" action="teacher_reports.php">
                    <label for="year_select" class="text-gray-700 mr-2">เลือกปี:</label>
                    <select name="year" id="year_select" onchange="this.form.submit()" class="px-3 py-1 border border-gray-300 rounded-lg">
                        <?php 
                        $all_years = array_unique(array_merge($available_years, [date('Y')]));
                        rsort($all_years);
                        foreach ($all_years as $year): 
                        ?>
                            <option value="<?= $year ?>" <?= $year == $report_year ? 'selected' : '' ?>>ปี <?= $year ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <?php if (empty($report_data) && !$error_message): ?>
                <div class="p-8 text-center text-gray-500 italic"><i class="fas fa-box-open mr-2"></i> ไม่พบข้อมูลผลงานตีพิมพ์ในปี <?= $report_year ?></div>
            <?php elseif (!empty($report_data)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 border">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left">ประเภทผลงาน</th>
                                <th class="px-6 py-3 text-center">เผยแพร่แล้ว</th>
                                <th class="px-6 py-3 text-center">รอตรวจสอบ</th>
                                <th class="px-6 py-3 text-center">แบบร่าง</th>
                                <th class="px-6 py-3 text-center">ถูกปฏิเสธ</th>
                                <th class="px-6 py-3 text-center bg-gray-200">รวม</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <td class="px-6 py-4 font-bold"><?= htmlspecialchars($row['PubType']) ?></td>
                                    <td class="px-6 py-4 text-center"><?= $row['PublishedCount'] ?></td>
                                    <td class="px-6 py-4 text-center"><?= $row['PendingCount'] ?></td>
                                    <td class="px-6 py-4 text-center"><?= $row['DraftCount'] ?></td>
                                    <td class="px-6 py-4 text-center"><?= $row['RejectedCount'] ?></td>
                                    <td class="px-6 py-4 text-center font-extrabold bg-gray-100"><?= $row['TotalCount'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gray-200 font-extrabold border-t-2 border-gray-400">
                            <tr>
                                <td class="px-6 py-4 text-left">รวมทั้งหมด</td>
                                <td class="px-6 py-4 text-center"><?= $grand_published ?></td>
                                <td class="px-6 py-4 text-center"><?= $grand_pending ?></td>
                                <td class="px-6 py-4 text-center"><?= $grand_draft ?></td>
                                <td class="px-6 py-4 text-center"><?= $grand_rejected ?></td>
                                <td class="px-6 py-4 text-center bg-gray-300"><?= $grand_total ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>
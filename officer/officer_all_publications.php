<?php
/**
 * officer_all_publications.php
 * หน้าแสดงรายการผลงานทั้งหมดในระบบ (ทุกสถานะ) สำหรับเจ้าหน้าที่
 * (ฉบับแก้ไข: เปลี่ยนเป็น Header Layout)
 */
include('auth_config.php');
require_role(['Officer', 'Admin']);

$message = get_session_message();
$publications = [];
$error_message = '';

// ค่าตัวกรองเริ่มต้น
$filter_status = $_GET['status'] ?? '';
$filter_type = $_GET['type'] ?? '';
$filter_year = $_GET['year'] ?? '';
$filter_author = trim($_GET['author'] ?? '');

// Logic ดึงข้อมูลผลงานทั้งหมด
try {
    $sql = "
        SELECT p.PubID, p.PubName, p.PubType, p.PubDate, p.PubStatus, p.Created_at, u.UserName
        FROM publications p JOIN users u ON p.User_id = u.User_Id WHERE 1=1 
    ";
    $params = [];

    if (!empty($filter_status)) { $sql .= " AND p.PubStatus = ?"; $params[] = $filter_status; }
    if (!empty($filter_type)) { $sql .= " AND p.PubType = ?"; $params[] = $filter_type; }
    if (!empty($filter_year)) { $sql .= " AND YEAR(p.PubDate) = ?"; $params[] = $filter_year; }
    if ($filter_author) { $sql .= " AND u.UserName LIKE ?"; $params[] = "%" . $filter_author . "%"; }
    $sql .= " ORDER BY p.Created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $publications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ดึงรายการปีและประเภททั้งหมดที่มีผลงาน
    $stmt_years = $pdo->query("SELECT DISTINCT YEAR(PubDate) as year FROM publications WHERE PubDate IS NOT NULL ORDER BY year DESC");
    $available_years = $stmt_years->fetchAll(PDO::FETCH_COLUMN);
    $stmt_types = $pdo->query("SELECT DISTINCT PubType FROM publications WHERE PubType IS NOT NULL AND PubType != '' ORDER BY PubType ASC");
    $publication_types = $stmt_types->fetchAll(PDO::FETCH_COLUMN);

} catch (\PDOException $e) {
    $error_message = "❌ เกิดข้อผิดพลาด: " . $e->getMessage();
}

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
    <title>รายการผลงานทั้งหมด - เจ้าหน้าที่</title>
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
                <a href="officer_all_publications.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700 bg-gray-900">ผลงานทั้งหมด</a>
                <a href="officer_reports.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">รายงาน</a>
                <a href="profile.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">ข้อมูลส่วนตัว</a>
                <a href="logout.php" class="px-3 py-2 text-sm rounded-md text-red-400 hover:bg-red-700">ออกจากระบบ</a>
            </nav>
        </div>
    </header>

    <main class="container mx-auto p-6 md:p-10">
        <h1 class="text-3xl font-extrabold text-gray-900 mb-8"><i class="fas fa-list-alt text-indigo-600 mr-2"></i> รายการผลงานทั้งหมด</h1>
        
        <?php if ($message): ?>
            <div class="p-4 rounded-lg mb-6 <?= $message['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>"><?= htmlspecialchars($message['message']) ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="p-4 rounded-lg mb-6 bg-red-100 text-red-700"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-xl shadow-md mb-6">
            <form method="GET" action="officer_all_publications.php" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">สถานะ</label>
                    <select id="status" name="status" class="w-full border-gray-300 rounded-lg p-2">
                        <option value="" <?= empty($filter_status) ? 'selected' : '' ?>>-- ทั้งหมด --</option>
                        <option value="Draft" <?= $filter_status === 'Draft' ? 'selected' : '' ?>>แบบร่าง</option>
                        <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>รอตรวจสอบ</option>
                        <option value="Published" <?= $filter_status === 'Published' ? 'selected' : '' ?>>เผยแพร่แล้ว</option>
                        <option value="Rejected" <?= $filter_status === 'Rejected' ? 'selected' : '' ?>>ถูกปฏิเสธ</option>
                    </select>
                </div>
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">ประเภท</label>
                    <select id="type" name="type" class="w-full border-gray-300 rounded-lg p-2">
                        <option value="" <?= empty($filter_type) ? 'selected' : '' ?>>-- ทั้งหมด --</option>
                        <?php foreach ($publication_types as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>" <?= $filter_type === $type ? 'selected' : '' ?>><?= htmlspecialchars($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="year" class="block text-sm font-medium text-gray-700 mb-1">ปีที่พิมพ์</label>
                    <select id="year" name="year" class="w-full border-gray-300 rounded-lg p-2">
                        <option value="" <?= empty($filter_year) ? 'selected' : '' ?>>-- ทั้งหมด --</option>
                        <?php foreach ($available_years as $year): ?>
                            <option value="<?= $year ?>" <?= $filter_year == $year ? 'selected' : '' ?>><?= $year ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="author" class="block text-sm font-medium text-gray-700 mb-1">ค้นหาผู้แต่ง</label>
                    <input type="text" id="author" name="author" value="<?= htmlspecialchars($filter_author) ?>" placeholder="ชื่อผู้แต่ง" class="w-full border-gray-300 rounded-lg p-2">
                </div>
                <div class="flex space-x-2">
                    <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700">กรอง</button>
                    <a href="officer_all_publications.php" class="w-full px-4 py-2 bg-gray-300 text-gray-800 font-semibold rounded-lg hover:bg-gray-400 flex items-center justify-center">ล้าง</a>
                </div>
            </form>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-2xl">
            <h2 class="text-xl font-bold text-gray-800 mb-4">ผลงานทั้งหมด (<?= count($publications) ?> รายการ)</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">ชื่อเรื่อง</th>
                            <th class="px-4 py-3 text-left">ผู้แต่ง</th>
                            <th class="px-4 py-3 text-left">ประเภท</th>
                            <th class="px-4 py-3 text-center">ปี</th>
                            <th class="px-4 py-3 text-left">สถานะ</th>
                            <th class="px-4 py-3 text-center">ดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($publications)): ?>
                            <tr><td colspan="7" class="p-8 text-center text-gray-500">ไม่พบผลงานที่ตรงตามเงื่อนไข</td></tr>
                        <?php else: foreach ($publications as $pub): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-4"><?= $pub['PubID'] ?></td>
                                <td class="px-4 py-4 truncate max-w-xs"><?= htmlspecialchars($pub['PubName']) ?></td>
                                <td class="px-4 py-4"><?= htmlspecialchars($pub['UserName']) ?></td>
                                <td class="px-4 py-4"><?= htmlspecialchars($pub['PubType']) ?></td>
                                <td class="px-4 py-4 text-center"><?= date('Y', strtotime($pub['PubDate'])) ?></td>
                                <td class="px-4 py-4"><?= get_status_badge($pub['PubStatus']) ?></td>
                                <td class="px-4 py-4 text-center">
                                    <a href="officer_review_detail.php?id=<?= $pub['PubID'] ?>" class="text-indigo-600 hover:text-indigo-900">ดู/ตรวจสอบ</a>
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
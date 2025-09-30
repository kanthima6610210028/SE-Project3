<?php
/**
 * public_publications.php
 * หน้าแสดงรายการและค้นหาผลงานตีพิมพ์ที่ได้รับการอนุมัติแล้วสำหรับบุคคลทั่วไป
 * (ฉบับแก้ไขตาม Schema ที่ถูกต้อง)
 */
include('auth_config.php');

$search_keyword = trim($_GET['keyword'] ?? '');
$filter_type = $_GET['type'] ?? '';
$filter_year = $_GET['year'] ?? '';
$publications = [];
$error_message = '';

// =================================================================
// 1. Logic ดึงข้อมูลและ Filter (แก้ไข Query ทั้งหมด)
// =================================================================
try {
    // --== แก้ไขชื่อคอลัมน์, สถานะ, และ Syntax Error ==--
    $sql = "
        SELECT 
            p.PubID,
            p.PubName,
            p.PubDetail,
            p.PubType,
            p.PubStatus,
            p.PubDate,
            u.UserName
        FROM publications p
        JOIN users u ON p.User_id = u.User_Id
        WHERE p.PubStatus = 'Published'
    ";
    
    $params = [];
    
    if (!empty($search_keyword)) {
        $sql .= " AND (p.PubName LIKE ? OR p.PubDetail LIKE ? OR u.UserName LIKE ?)";
        $keyword_param = '%' . $search_keyword . '%';
        array_push($params, $keyword_param, $keyword_param, $keyword_param);
    }

    if (!empty($filter_type)) {
        $sql .= " AND p.PubType = ?";
        $params[] = $filter_type;
    }

    if (!empty($filter_year)) {
        $sql .= " AND YEAR(p.PubDate) = ?";
        $params[] = $filter_year;
    }

    $sql .= " ORDER BY p.PubDate DESC, p.PubName ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $publications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ดึงรายการตัวเลือกสำหรับ Filter (จากผลงานที่ Published แล้วเท่านั้น)
    $types_stmt = $pdo->query("SELECT DISTINCT PubType FROM publications WHERE PubStatus = 'Published' AND PubType IS NOT NULL AND PubType != '' ORDER BY PubType");
    $available_types = $types_stmt->fetchAll(PDO::FETCH_COLUMN);

    $years_stmt = $pdo->query("SELECT DISTINCT YEAR(PubDate) as year FROM publications WHERE PubStatus = 'Published' ORDER BY year DESC");
    $available_years = $years_stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (\PDOException $e) {
    $error_message = "❌ เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
}

// Helper function
function format_pub_type($type) {
    switch ($type) {
        case 'Journal': return '<i class="fas fa-book-open text-blue-500 mr-1"></i> วารสาร';
        case 'Conference': return '<i class="fas fa-users text-green-500 mr-1"></i> การประชุม';
        default: return '<i class="fas fa-file-alt text-gray-500 mr-1"></i> อื่นๆ';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บทความการตีพิมพ์ทั้งหมด</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', 'Tahoma', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">

<div class="min-h-screen flex flex-col">
    <header class="bg-white shadow-md sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="index.php" class="text-2xl font-bold text-indigo-700"><i class="fas fa-graduation-cap mr-2"></i> PubTracker</a>
                <nav class="hidden md:flex space-x-6 items-center">
                    <a href="index.php" class="text-gray-900 hover:text-indigo-600">หน้าแรก</a>
                    <a href="public_publications.php" class="text-indigo-600 font-semibold border-b-2 border-indigo-600 pb-1">บทความการตีพิมพ์</a>
                    <a href="user_manual.php" class="text-gray-900 hover:text-indigo-600">คู่มือการใช้งาน</a>
                    <a href="login.php" class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700">
                        <i class="fas fa-sign-in-alt mr-1"></i> เข้าสู่ระบบ
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-3xl font-extrabold text-gray-900 mb-8 border-b pb-2"><i class="fas fa-search-plus text-indigo-600 mr-2"></i> ค้นหาบทความการตีพิมพ์</h1>
        
        <?php if ($error_message): ?>
            <div class="p-4 rounded-lg mb-6 bg-red-100 border border-red-400 text-red-700" role="alert"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <form method="GET" action="public_publications.php" class="bg-white p-6 rounded-xl shadow-lg mb-8 border-t-4 border-indigo-500">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label for="keyword" class="block text-sm font-medium text-gray-700 mb-1">คำสำคัญ (ชื่อเรื่อง, ผู้แต่ง, บทคัดย่อ)</label>
                    <input type="text" name="keyword" id="keyword" value="<?= htmlspecialchars($search_keyword) ?>" placeholder="ค้นหา..." class="w-full p-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">ประเภทผลงาน</label>
                    <select name="type" id="type" class="w-full p-2.5 border border-gray-300 rounded-lg">
                        <option value="">-- ทั้งหมด --</option>
                        <?php foreach ($available_types as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>" <?= $filter_type === $type ? 'selected' : '' ?>><?= htmlspecialchars($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="year" class="block text-sm font-medium text-gray-700 mb-1">ปีที่ตีพิมพ์</label>
                    <select name="year" id="year" class="w-full p-2.5 border border-gray-300 rounded-lg">
                        <option value="">-- ทั้งหมด --</option>
                        <?php foreach ($available_years as $year): ?>
                            <option value="<?= htmlspecialchars($year) ?>" <?= $filter_year == $year ? 'selected' : '' ?>><?= htmlspecialchars($year) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="flex justify-end mt-4 space-x-3">
                <a href="public_publications.php" class="px-4 py-2 border rounded-lg hover:bg-gray-100">ล้างตัวกรอง</a>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700">กรองผลงาน</button>
            </div>
        </form>

        <h2 class="text-2xl font-bold text-gray-800 mb-6">ผลการค้นหา: <?= number_format(count($publications)) ?> รายการ</h2>
        
        <?php if (empty($publications)): ?>
            <div class="p-8 text-center text-gray-500 italic border border-dashed rounded-xl bg-white">ไม่พบผลงานที่ตรงกับเงื่อนไขการค้นหาของคุณ</div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($publications as $pub): ?>
                    <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition border-l-8 border-blue-500">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-xl font-extrabold text-gray-900"><?= htmlspecialchars($pub['PubName']) ?></h3>
                            <span class="text-sm font-semibold px-3 py-1 rounded-full bg-indigo-100 text-indigo-700">ปี: <?= date('Y', strtotime($pub['PubDate'])) ?></span>
                        </div>
                        <div class="text-sm text-gray-600 space-y-1 mb-4">
                            <p class="font-medium"><i class="fas fa-user-tie text-gray-400 mr-1"></i> ผู้แต่ง: <span class="text-gray-800"><?= htmlspecialchars($pub['UserName']) ?></span></p>
                            <p class="font-medium"><?= format_pub_type($pub['PubType']) ?></p>
                        </div>
                        <p class="text-gray-700 text-sm line-clamp-3 mb-4">
                            **บทคัดย่อ:** <?= htmlspecialchars(substr($pub['PubDetail'], 0, 200)) ?><?= strlen($pub['PubDetail']) > 200 ? '...' : '' ?>
                        </p>
                        <a href="public_detail.php?id=<?= $pub['PubID'] ?>" class="inline-block px-4 py-2 bg-blue-500 text-white font-semibold rounded-lg hover:bg-blue-600 text-sm">
                            <i class="fas fa-file-alt mr-1"></i> ดูรายละเอียดฉบับเต็ม
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer class="bg-gray-900 text-white py-6 mt-8">
        <div class="max-w-7xl mx-auto px-4 text-center text-sm opacity-75">&copy; <?= date('Y') ?> PubTracker System. All rights reserved.</div>
    </footer>
</div>

</body>
</html>
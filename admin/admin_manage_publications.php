<?php
// admin_manage_publications.php
// (ฉบับแก้ไขกลับ: ทำงานกับคอลัมน์ PubType โดยตรง)

require_once 'auth_config.php';
require_role(['Admin']);

// --- ดึงข้อมูลสำหรับ Dropdown ---
try {
    // ดึง "ประเภท" ที่มีอยู่ทั้งหมดจากตาราง publications โดยตรง
    $types_stmt = $pdo->query("SELECT DISTINCT PubType FROM publications WHERE PubType IS NOT NULL AND PubType != '' ORDER BY PubType ASC");
    $publication_types = $types_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $publication_types = [];
}

// --- การกรองข้อมูล ---
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? ''; // Filter ด้วยข้อความ PubType
$author_filter = trim($_GET['author'] ?? '');

// --- สร้าง Query หลัก (ไม่ต้อง JOIN pubtypes) ---
$main_query = "
    SELECT p.PubID, p.PubName, p.PubType, p.PubStatus, p.Created_at, u.UserName 
    FROM publications p
    JOIN users u ON p.User_id = u.User_Id
    WHERE 1=1
";

$params = [];
if (!empty($status_filter)) {
    $main_query .= " AND p.PubStatus = ?";
    $params[] = $status_filter;
}
if (!empty($type_filter)) {
    $main_query .= " AND p.PubType = ?";
    $params[] = $type_filter;
}
if (!empty($author_filter)) {
    $main_query .= " AND u.UserName LIKE ?";
    $params[] = '%' . $author_filter . '%';
}
$main_query .= " ORDER BY p.Created_at DESC";

try {
    $stmt_pubs = $pdo->prepare($main_query);
    $stmt_pubs->execute($params);
    $publications = $stmt_pubs->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $publications = [];
    $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสิ่งพิมพ์ทั้งหมด - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style> body { font-family: 'Inter', 'Tahoma', sans-serif; } </style>
</head>
<body class="bg-gray-100 min-h-screen">

    <header class="bg-gray-800 text-gray-100 shadow-lg sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center p-4">
            <a href="admin_dashboard.php" class="text-xl font-bold"><i class="fas fa-cogs mr-2"></i> ADMIN PANEL</a>
            <nav class="hidden md:flex space-x-4 items-center">
                <a href="admin_dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">หน้าแรก</a>
                <a href="admin_manage_publications.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700 bg-gray-900">จัดการสิ่งพิมพ์</a>
                <a href="admin_user_management.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">จัดการผู้ใช้</a>
                <a href="admin_manage_types.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">จัดการประเภท</a>
                <a href="admin_audit_log.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">บันทึกตรวจสอบ</a>
                <a href="logout.php" class="px-3 py-2 rounded-md text-sm font-medium text-red-400 hover:bg-red-700">ออกจากระบบ</a>
            </nav>
        </div>
    </header>

    <main class="container mx-auto p-6 md:p-10">
        <h1 class="text-3xl font-bold text-gray-800 mb-6"><i class="fas fa-book text-indigo-600 mr-2"></i> จัดการสิ่งพิมพ์ทั้งหมด</h1>

        <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
            <form action="" method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">สถานะ</label>
                    <select id="status" name="status" class="w-full p-2 border border-gray-300 rounded-lg">
                        <option value="">-- ทุกสถานะ --</option>
                        <option value="Draft" <?= ($status_filter == 'Draft') ? 'selected' : '' ?>>แบบร่าง</option>
                        <option value="Pending" <?= ($status_filter == 'Pending') ? 'selected' : '' ?>>รอตรวจสอบ</option>
                        <option value="Published" <?= ($status_filter == 'Published') ? 'selected' : '' ?>>เผยแพร่แล้ว</option>
                        <option value="Rejected" <?= ($status_filter == 'Rejected') ? 'selected' : '' ?>>ถูกปฏิเสธ</option>
                    </select>
                </div>
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">ประเภท</label>
                    <select id="type" name="type" class="w-full p-2 border border-gray-300 rounded-lg">
                        <option value="">-- ทุกประเภท --</option>
                        <?php foreach ($publication_types as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>" <?= ($type_filter == $type) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 <div>
                    <label for="author" class="block text-sm font-medium text-gray-700 mb-1">ค้นหาผู้ส่ง</label>
                    <input type="text" name="author" id="author" value="<?= htmlspecialchars($author_filter)?>" placeholder="ชื่อผู้ส่ง..." class="w-full p-2 border border-gray-300 rounded-lg">
                </div>
                <div class="flex space-x-2">
                     <button type="submit" class="w-full bg-indigo-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-indigo-700">กรอง</button>
                     <a href="admin_manage_publications.php" class="w-full text-center bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-lg hover:bg-gray-300">ล้าง</a>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-lg overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-4">ชื่องานวิจัย</th>
                        <th class="p-4">ผู้ส่ง</th>
                        <th class="p-4">ประเภท</th>
                        <th class="p-4">สถานะ</th>
                        <th class="p-4">วันที่สร้าง</th>
                        <th class="p-4">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($publications)): ?>
                        <tr><td colspan="6" class="text-center p-6 text-gray-500">ไม่พบผลงาน</td></tr>
                    <?php else: foreach ($publications as $pub): ?>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-4"><?= htmlspecialchars($pub['PubName']) ?></td>
                            <td class="p-4"><?= htmlspecialchars($pub['UserName']) ?></td>
                            <td class="p-4"><?= htmlspecialchars($pub['PubType'] ?? 'N/A') ?></td>
                            <td class="p-4">
                                <span class="px-3 py-1 text-sm rounded-full 
                                    <?php 
                                        if ($pub['PubStatus'] == 'Published') echo 'bg-green-100 text-green-800';
                                        elseif ($pub['PubStatus'] == 'Pending') echo 'bg-yellow-100 text-yellow-800';
                                        elseif ($pub['PubStatus'] == 'Rejected') echo 'bg-red-100 text-red-800';
                                        else echo 'bg-gray-100 text-gray-800';
                                    ?>">
                                    <?= htmlspecialchars($pub['PubStatus']) ?>
                                </span>
                            </td>
                            <td class="p-4"><?= date('d/m/Y', strtotime($pub['Created_at'])) ?></td>
                            <td class="p-4"><a href="admin_view_publication.php?id=<?= $pub['PubID'] ?>" class="text-indigo-600">ดูรายละเอียด</a></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>
<?php
// admin_audit_log.php (โค้ด PHP ส่วนบนเหมือนเดิม)
require_once 'auth_config.php';
require_role(['Admin']);

$search_user = trim($_GET['search_user'] ?? '');

$sql = "
    SELECT 
        al.Log_id, al.User_id, u.UserName, al.Action_type,
        al.Log_date, al.Publication_id, al.Record_id
    FROM auditlog al
    LEFT JOIN users u ON al.User_id = u.User_Id
";
$params = [];
if (!empty($search_user)) {
    $sql .= " WHERE u.UserName LIKE ?";
    $params[] = "%" . $search_user . "%";
}
$sql .= " ORDER BY al.Log_date DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $logs = [];
    $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บันทึกการตรวจสอบ - Admin</title>
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
                <a href="admin_manage_types.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">จัดการประเภท</a>
                <a href="admin_audit_log.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700 bg-gray-900">บันทึกตรวจสอบ</a>
                <a href="logout.php" class="px-3 py-2 rounded-md text-sm font-medium text-red-400 hover:bg-red-700">ออกจากระบบ</a>
            </nav>
        </div>
    </header>

    <main class="container mx-auto p-6 md:p-10">
        
        <h1 class="text-3xl font-bold text-gray-800 mb-6">
            <i class="fas fa-history text-indigo-600 mr-2"></i> บันทึกการตรวจสอบ (Audit Log)
        </h1>

        <p class="text-gray-600 mb-8">ประวัติการดำเนินการทั้งหมดที่เกิดขึ้นในระบบ</p>
        <div class="bg-white p-4 rounded-xl shadow-md mb-8">
             <form action="admin_audit_log.php" method="GET" class="flex items-center space-x-4">
                <div class="flex-grow relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400"></i></div>
                    <input type="text" name="search_user" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg" placeholder="ค้นหาด้วยชื่อผู้ใช้..." value="<?= htmlspecialchars($search_user) ?>">
                </div>
                <button type="submit" class="px-5 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700">ค้นหา</button>
                <a href="admin_audit_log.php" class="px-5 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300">ล้าง</a>
            </form>
        </div>
        <div class="bg-white rounded-xl shadow-lg overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-4 font-semibold text-gray-600">Log ID</th>
                        <th class="p-4 font-semibold text-gray-600">ผู้ดำเนินการ</th>
                        <th class="p-4 font-semibold text-gray-600">การกระทำ</th>
                        <th class="p-4 font-semibold text-gray-600">ID ที่เกี่ยวข้อง</th>
                        <th class="p-4 font-semibold text-gray-600">วัน-เวลา</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="5" class="text-center p-6 text-gray-500">ไม่พบข้อมูล</td></tr>
                    <?php else: foreach ($logs as $log): ?>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-4"><?= htmlspecialchars($log['Log_id']) ?></td>
                            <td class="p-4 font-medium"><?= htmlspecialchars($log['UserName'] ?? 'N/A') ?></td>
                            <td class="p-4"><span class="px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-800"><?= htmlspecialchars($log['Action_type']) ?></span></td>
                            <td class="p-4"><?php if($log['Publication_id']): ?>Pub ID: <?= htmlspecialchars($log['Publication_id']) ?><?php endif; ?></td>
                            <td class="p-4"><?= date('d/m/Y H:i:s', strtotime($log['Log_date'])) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>
<?php
include('auth_config.php');

// ตรวจสอบสิทธิ์: ต้องเป็นเจ้าหน้าที่ (Officer)
if (!check_user_role('Officer')) {
    header("Location: login.php");
    exit();
}

// 1. กำหนดค่าเริ่มต้นและการดึงข้อมูลผู้ใช้งาน
$user_id = $_SESSION['user_id'];
$officer_name = 'เจ้าหน้าที่';
$publications = [];
$error_message = '';
$search_keyword = $_GET['keyword'] ?? '';
$filter_status = $_GET['status'] ?? 'Pending'; // Default filter: รอตรวจสอบ

try {
    // ดึงชื่อเจ้าหน้าที่
    $stmt_user = $pdo->prepare("SELECT UserName FROM users WHERE User_Id = ?");
    $stmt_user->execute([$user_id]);
    $user_data = $stmt_user->fetch();
    if ($user_data) {
        $officer_name = htmlspecialchars($user_data['UserName']);
    }

    // 2. เตรียม Query หลักสำหรับการดึงผลงานทั้งหมดที่ส่งเข้ามา
    $sql = "
        SELECT 
            p.PubID, p.PubName, p.PubType, p.PubYear, p.PubStatus, p.CreatedAt,
            u.UserName AS AuthorName
        FROM publications p
        JOIN users u ON p.AuthorID = u.User_Id
        WHERE 1=1 
    ";
    $params = [];
    
    // 2.1. การกรองตามสถานะ (Status Filter)
    if ($filter_status && $filter_status !== 'All') {
        $sql .= " AND p.PubStatus = ?";
        $params[] = $filter_status;
    }

    // 2.2. การค้นหาด้วย Keyword (ชื่อเรื่อง/ผู้แต่ง)
    if ($search_keyword) {
        $sql .= " AND (p.PubName LIKE ? OR u.UserName LIKE ?)";
        $search_term = "%" . $search_keyword . "%";
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    // 2.3. การเรียงลำดับ: ให้รายการ 'Pending' (รอตรวจสอบ) ขึ้นมาก่อนเสมอ
    $sql .= " ORDER BY 
                CASE 
                    WHEN p.PubStatus = 'Pending' THEN 1 
                    ELSE 2 
                END, 
                p.CreatedAt DESC";

    // 3. ดำเนินการ Query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $publications = $stmt->fetchAll();

} catch (\PDOException $e) {
    $error_message = "❌ ข้อผิดพลาดในการดึงข้อมูลผลงาน: " . htmlspecialchars($e->getMessage());
}

// ฟังก์ชันสำหรับแปลงสถานะภาษาอังกฤษเป็นภาษาไทย
function get_status_thai(string $status): string {
    return [
        'Pending' => 'รอตรวจสอบ',
        'Approved' => 'อนุมัติแล้ว',
        'Rejected' => 'ถูกปฏิเสธ',
        'In Revision' => 'ให้แก้ไข',
        'Draft' => 'ฉบับร่าง'
    ][$status] ?? $status;
}

// ฟังก์ชันสำหรับกำหนดสีของสถานะ
function get_status_color(string $status): string {
    return [
        'Pending' => 'bg-yellow-100 text-yellow-800 border-yellow-500',
        'Approved' => 'bg-green-100 text-green-800 border-green-500',
        'Rejected' => 'bg-red-100 text-red-800 border-red-500',
        'In Revision' => 'bg-blue-100 text-blue-800 border-blue-500',
        'Draft' => 'bg-gray-100 text-gray-800 border-gray-500'
    ][$status] ?? 'bg-gray-100 text-gray-800 border-gray-500';
}

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจสอบผลงาน | เจ้าหน้าที่</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', 'Tahoma', sans-serif; background-color: #f0f4f8; }
        .sidebar { transition: width 0.3s; }
        /* สไตล์สำหรับ Search Input (ตามภาพร่าง Page 9) */
        .search-input { width: 100%; }
        @media (min-width: 768px) {
            .search-input { width: 300px; }
        }
    </style>
</head>
<body class="flex">

    <!-- Sidebar / Officer Navigation -->
    <nav class="sidebar w-64 bg-gray-800 text-white min-h-screen p-4 shadow-2xl flex flex-col">
        <div class="text-2xl font-bold mb-8 text-yellow-300 border-b border-gray-700 pb-4">
            <i class="fas fa-user-shield mr-2"></i> Officer Menu
        </div>
        <div class="mb-4 text-sm text-gray-300">
             สวัสดี, <span class="font-semibold"><?= $officer_name ?></span>
        </div>
        <ul class="flex-grow space-y-2">
            <li><a href="officer_dashboard.php" class="flex items-center p-3 rounded-lg hover:bg-gray-700 transition duration-150"><i class="fas fa-chart-line w-6"></i><span class="ml-3">Dashboard</span></a></li>
            <li><a href="manage_publications.php" class="flex items-center p-3 rounded-lg bg-gray-700 text-yellow-300 font-semibold"><i class="fas fa-clipboard-list w-6"></i><span class="ml-3">ตรวจสอบผลงาน (<?= count(array_filter($publications, fn($p) => $p['PubStatus'] === 'Pending')) ?>)</span></a></li>
            <li><a href="manage_teachers.php" class="flex items-center p-3 rounded-lg hover:bg-gray-700 transition duration-150"><i class="fas fa-users w-6"></i><span class="ml-3">จัดการอาจารย์</span></a></li>
            <li><a href="reports.php" class="flex items-center p-3 rounded-lg hover:bg-gray-700 transition duration-150"><i class="fas fa-file-alt w-6"></i><span class="ml-3">รายงาน</span></a></li>
        </ul>
        <a href="logout.php" class="mt-4 flex items-center justify-center p-3 rounded-lg bg-red-600 hover:bg-red-700 font-semibold transition duration-150"><i class="fas fa-sign-out-alt mr-2"></i> ออกจากระบบ</a>
    </nav>

    <!-- Main Content -->
    <div class="flex-grow p-4 md:p-8">
        <header class="mb-8 border-b pb-4 flex justify-between items-center">
            <h1 class="text-3xl font-extrabold text-gray-900">
                <i class="fas fa-clipboard-list text-yellow-600 mr-2"></i> รายการผลงานรอตรวจสอบ
            </h1>
        </header>

        <!-- Notification Message -->
        <?php if ($error_message): ?>
            <div class="p-4 rounded-lg mb-6 bg-red-100 border border-red-400 text-red-700" role="alert">
                <?= $error_message ?>
            </div>
        <?php endif; ?>

        <!-- Search and Filter Bar (Based on Page 9 UI) -->
        <div class="bg-white p-6 rounded-xl shadow-lg mb-6">
            <form method="GET" action="manage_publications.php" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                
                <!-- Keyword Search -->
                <div class="md:col-span-2">
                    <label for="keyword" class="block text-sm font-medium text-gray-700">🔍 ค้นหา (ชื่อเรื่อง/ผู้แต่ง)</label>
                    <input type="text" id="keyword" name="keyword" value="<?= htmlspecialchars($search_keyword) ?>"
                           class="search-input mt-1 block px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Status Filter -->
                <div class="md:col-span-1">
                    <label for="status" class="block text-sm font-medium text-gray-700">สถานะ</label>
                    <select id="status" name="status"
                            class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 bg-white">
                        <option value="All" <?= $filter_status === 'All' ? 'selected' : '' ?>>ทั้งหมด</option>
                        <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>รอตรวจสอบ (Pending)</option>
                        <option value="Approved" <?= $filter_status === 'Approved' ? 'selected' : '' ?>>อนุมัติแล้ว (Approved)</option>
                        <option value="Rejected" <?= $filter_status === 'Rejected' ? 'selected' : '' ?>>ถูกปฏิเสธ (Rejected)</option>
                        <option value="In Revision" <?= $filter_status === 'In Revision' ? 'selected' : '' ?>>ให้แก้ไข (In Revision)</option>
                    </select>
                </div>
                
                <!-- Action Buttons -->
                <div class="md:col-span-2 flex space-x-2">
                    <button type="submit"
                            class="flex-grow md:flex-none justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-blue-600 hover:bg-blue-700 transition duration-150">
                        <i class="fas fa-search mr-1"></i> ค้นหา
                    </button>
                    <a href="manage_publications.php"
                            class="flex-grow md:flex-none justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 transition duration-150 text-center">
                        <i class="fas fa-undo mr-1"></i> ล้าง
                    </a>
                </div>
            </form>
        </div>

        <!-- Publication List Table -->
        <div class="bg-white p-6 rounded-xl shadow-lg overflow-x-auto">
            <?php if (empty($publications)): ?>
                <div class="text-center p-10 text-gray-500">
                    <i class="fas fa-info-circle text-4xl mb-3"></i>
                    <p class="text-lg">ไม่พบผลงานที่ตรงตามเงื่อนไขการค้นหา/การกรอง</p>
                </div>
            <?php else: ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อเรื่อง / ปีพิมพ์</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ผู้แต่ง</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ประเภท</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่ส่ง</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($publications as $pub): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-blue-600 hover:text-blue-800 transition duration-150">
                                        <?= htmlspecialchars($pub['PubName']) ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        ปี: <?= htmlspecialchars($pub['PubYear']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <?= htmlspecialchars($pub['AuthorName']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <?= htmlspecialchars($pub['PubType']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full border 
                                        <?= get_status_color($pub['PubStatus']) ?>">
                                        <?= get_status_thai($pub['PubStatus']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    <?= date('Y-m-d', strtotime($pub['CreatedAt'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <a href="view_approval.php?pub_id=<?= $pub['PubID'] ?>" 
                                       class="text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-1 rounded-lg transition duration-150 shadow-md">
                                        <i class="fas fa-eye mr-1"></i> ตรวจสอบ
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>

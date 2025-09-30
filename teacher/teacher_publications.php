<?php
/**
 * teacher_publications.php
 * (ฉบับแก้ไข: เปลี่ยนเป็น Header Layout)
 */
include('auth_config.php');
require_role(['Teacher']);

$user_id = $_SESSION['user_id'];
$publications = [];
$error_message = '';
$filter_status = $_GET['status'] ?? '';

// =================================================================
// Logic การจัดการการลบ (เหมือนเดิม)
// =================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_pub_id'])) {
    $pub_id_to_delete = intval($_POST['delete_pub_id']);
    try {
        $pdo->beginTransaction();
        $stmt_check = $pdo->prepare("SELECT PubStatus FROM publications WHERE PubID = ? AND User_id = ?");
        $stmt_check->execute([$pub_id_to_delete, $user_id]);
        $status = $stmt_check->fetchColumn();
        
        if ($status && $status !== 'Published') {
            $stmt_del_files = $pdo->prepare("DELETE FROM publicationfile WHERE Publication_id = ?");
            $stmt_del_files->execute([$pub_id_to_delete]);
            $stmt_del = $pdo->prepare("DELETE FROM publications WHERE PubID = ? AND User_id = ?");
            $stmt_del->execute([$pub_id_to_delete, $user_id]);
            $pdo->commit();
            set_session_message('success', "✅ ลบผลงานตีพิมพ์สำเร็จแล้ว");
        } else {
            set_session_message('error', $status === 'Published' ? "❌ ไม่สามารถลบผลงานที่เผยแพร่แล้วได้" : "❌ ไม่พบผลงานที่ต้องการลบ");
        }
    } catch (\PDOException $e) {
        $pdo->rollBack();
        set_session_message('error', "❌ เกิดข้อผิดพลาดในการลบ: " . $e->getMessage());
    }
    header("Location: teacher_publications.php?status=" . urlencode($filter_status));
    exit();
}

// =================================================================
// Logic การดึงข้อมูลผลงาน (เหมือนเดิม)
// =================================================================
try {
    $sql = "SELECT p.PubID, p.PubName, p.PubType, p.PubStatus, p.PubDate, p.Created_at FROM publications p WHERE p.User_id = ?";
    $params = [$user_id];
    if (!empty($filter_status)) {
        $sql .= " AND p.PubStatus = ?";
        $params[] = $filter_status;
    }
    $sql .= " ORDER BY p.Created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $publications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $error_message = "❌ เกิดข้อผิดพลาด: " . $e->getMessage();
}

$status_display = [
    'Draft' => ['text-gray-700', 'bg-gray-100', 'แบบร่าง', 'edit'],
    'Pending' => ['text-yellow-700', 'bg-yellow-100', 'รอตรวจสอบ', 'clock'],
    'Published' => ['text-green-700', 'bg-green-100', 'เผยแพร่แล้ว', 'check-circle'],
    'Rejected' => ['text-red-700', 'bg-red-100', 'ถูกปฏิเสธ', 'times-circle'],
];

$flash_message = get_session_message();
$success_message = '';
$session_error_message = '';
if ($flash_message) {
    if ($flash_message['type'] === 'success') {
        $success_message = $flash_message['message'];
    } else {
        $session_error_message = $flash_message['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผลงานของฉัน - Teacher Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style> body { font-family: 'Inter', 'Tahoma', sans-serif; } </style>
</head>
<body class="bg-gray-100">

    <header class="bg-gray-800 text-gray-100 shadow-lg sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center p-4">
            <a href="teacher_dashboard.php" class="text-xl font-bold"><i class="fas fa-graduation-cap mr-2"></i> Teacher Portal</a>
            <nav class="hidden md:flex space-x-4 items-center">
                <a href="teacher_dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">หน้าหลัก</a>
                <a href="teacher_publications.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700 bg-gray-900">ผลงานของฉัน</a>
                <a href="add_publication.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">ส่งผลงานใหม่</a>
                <a href="profile.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">ข้อมูลส่วนตัว</a>
                <a href="teacher_reports.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">รายงานผลงาน</a>
                <a href="logout.php" class="px-3 py-2 rounded-md text-sm font-medium text-red-400 hover:bg-red-700">ออกจากระบบ</a>
            </nav>
        </div>
    </header>

    <main class="container mx-auto p-6 md:p-10">
        <h1 class="text-3xl font-extrabold text-gray-900 mb-8">
            <i class="fas fa-list-alt text-blue-600 mr-2"></i> ผลงานตีพิมพ์ทั้งหมดของฉัน
        </h1>

        <?php if ($success_message): ?>
            <div class="p-4 rounded-lg mb-6 bg-green-100 text-green-700"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <?php if ($error_message || $session_error_message): ?>
            <div class="p-4 rounded-lg mb-6 bg-red-100 text-red-700"><?= htmlspecialchars($error_message . $session_error_message) ?></div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-xl shadow-2xl border-t-4 border-blue-500">
            <div class="flex justify-between items-center mb-6">
                <div class="flex flex-wrap gap-2">
                    <?php 
                    $statuses = ['', 'Draft', 'Pending', 'Published', 'Rejected'];
                    foreach ($statuses as $status):
                        $active_class = $filter_status === $status ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-blue-100';
                        $display_name = empty($status) ? 'ทั้งหมด' : ($status_display[$status][2] ?? $status);
                        $icon = empty($status) ? 'fas fa-grip-lines' : 'fas fa-' . ($status_display[$status][3] ?? 'question');
                    ?>
                        <a href="teacher_publications.php?status=<?= urlencode($status) ?>" class="px-3 py-1 text-sm rounded-full font-semibold flex items-center <?= $active_class ?>">
                           <i class="<?= $icon ?> mr-1 text-xs"></i> <?= $display_name ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <a href="add_publication.php" class="px-4 py-2 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700">
                    <i class="fas fa-plus-circle mr-1"></i> ส่งผลงานใหม่
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อเรื่อง / ประเภท</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันที่ส่ง</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($publications)): ?>
                            <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">ยังไม่มีผลงานในระบบ</td></tr>
                        <?php else: foreach ($publications as $pub): 
                            [$text_color, $bg_color, $status_th, $icon] = $status_display[$pub['PubStatus']] ?? ['text-gray-600', 'bg-gray-100', 'ไม่ทราบ', 'question'];
                            $can_modify = $pub['PubStatus'] !== 'Published';
                        ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">#<?= $pub['PubID'] ?></td>
                                <td class="px-6 py-4">
                                    <span class="font-bold"><?= htmlspecialchars($pub['PubName']) ?></span>
                                    <span class="block text-xs text-gray-500 mt-1">ประเภท: <?= htmlspecialchars($pub['PubType']) ?> (ปี: <?= date('Y', strtotime($pub['PubDate'])) ?>)</span>
                                </td>
                                <td class="px-6 py-4"><?= date('d/m/Y', strtotime($pub['Created_at'])) ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full <?= $bg_color ?> <?= $text_color ?>">
                                        <i class="fas fa-<?= $icon ?> mr-1"></i> <?= $status_th ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center space-x-2">
                                    <a href="teacher_view_publication.php?id=<?= $pub['PubID'] ?>" class="text-blue-600 p-1" title="ดูรายละเอียด"><i class="fas fa-eye"></i></a>
                                    <?php if ($can_modify): ?>
                                        <a href="edit_publication.php?id=<?= $pub['PubID'] ?>" class="text-indigo-600 p-1" title="แก้ไข"><i class="fas fa-edit"></i></a>
                                        <button onclick="showDeleteModal(<?= $pub['PubID'] ?>, '<?= htmlspecialchars(addslashes($pub['PubName'])) ?>')" class="text-red-600 p-1" title="ลบ"><i class="fas fa-trash-alt"></i></button>
                                    <?php else: ?>
                                        <span class="text-gray-400 p-1" title="ไม่สามารถดำเนินการได้"><i class="fas fa-lock"></i></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm">
        <h3 class="text-xl font-bold text-red-700 mb-4"><i class="fas fa-exclamation-triangle mr-2"></i> ยืนยันการลบ</h3>
        <p id="deleteMessage" class="text-gray-600 mb-4"></p>
        <form method="POST" id="deleteForm">
            <input type="hidden" name="delete_pub_id" id="pubIdToDelete">
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeModal('deleteModal')" class="px-4 py-2 border rounded-lg">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 text-white bg-red-600 rounded-lg">ยืนยันการลบ</button>
            </div>
        </form>
    </div>
</div>

<script>
    function showDeleteModal(pubId, pubName) {
        document.getElementById('pubIdToDelete').value = pubId;
        document.getElementById('deleteMessage').innerHTML = `คุณแน่ใจหรือไม่ว่าต้องการลบผลงาน: "<strong>${pubName}</strong>"?`;
        document.getElementById('deleteModal').classList.remove('hidden');
        document.getElementById('deleteModal').classList.add('flex');
    }
    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
        document.getElementById(modalId).classList.remove('flex');
    }
</script>

</body>
</html>
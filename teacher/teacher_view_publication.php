<?php
/**
 * teacher_view_publication.php
 * หน้าสำหรับอาจารย์ดูรายละเอียดผลงานของตนเอง
 * (ฉบับแก้ไข: เปลี่ยนเป็น Header Layout)
 */
include('auth_config.php');
require_role(['Teacher']);

$user_id = $_SESSION['user_id'];

// 1. ตรวจสอบและรับค่า ID จาก URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    set_session_message('error', 'รหัสผลงานไม่ถูกต้อง');
    header('Location: teacher_publications.php');
    exit;
}
$publication_id = (int)$_GET['id'];

// 2. ดึงข้อมูลหลักของ Publication และตรวจสอบความเป็นเจ้าของ
try {
    $query_main = "
        SELECT p.*, u.UserName
        FROM publications p
        JOIN users u ON p.User_id = u.User_Id
        WHERE p.PubID = ? AND p.User_id = ?
    ";
    $stmt_main = $pdo->prepare($query_main);
    $stmt_main->execute([$publication_id, $user_id]);
    $publication = $stmt_main->fetch(PDO::FETCH_ASSOC);

    if (!$publication) {
        set_session_message('error', 'ไม่พบผลงานที่คุณต้องการ หรือคุณไม่ใช่เจ้าของผลงาน');
        header('Location: teacher_publications.php');
        exit;
    }

    // 3. ดึงข้อมูลที่เกี่ยวข้อง
    $stmt_authors = $pdo->prepare("SELECT * FROM authors a JOIN author_publication ap ON a.Author_id = ap.Author_id WHERE ap.Publication_id = ?");
    $stmt_authors->execute([$publication_id]);
    $authors = $stmt_authors->fetchAll(PDO::FETCH_ASSOC);

    $stmt_files = $pdo->prepare("SELECT * FROM publicationfile WHERE Publication_id = ?");
    $stmt_files->execute([$publication_id]);
    $files = $stmt_files->fetchAll(PDO::FETCH_ASSOC);

    $stmt_approvals = $pdo->prepare("SELECT a.*, u.UserName as ApproverName FROM approval a JOIN users u ON a.Approved_by = u.User_Id WHERE a.Publication_id = ? ORDER BY a.Approved_at DESC");
    $stmt_approvals->execute([$publication_id]);
    $approvals = $stmt_approvals->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage());
}

// Helper function
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
    <title>รายละเอียดผลงาน #<?= $publication_id ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style> body { font-family: 'Inter', 'Tahoma', sans-serif; } </style>
</head>
<body class="bg-gray-100">

    <!-- Header (Navbar) -->
    <header class="bg-gray-800 text-gray-100 shadow-lg sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center p-4">
            <a href="teacher_dashboard.php" class="text-xl font-bold"><i class="fas fa-graduation-cap mr-2"></i> Teacher Portal</a>
            <nav class="hidden md:flex space-x-4 items-center">
                <a href="teacher_dashboard.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">หน้าหลัก</a>
                <a href="teacher_publications.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700 bg-gray-900">ผลงานของฉัน</a>
                <a href="add_publication.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">ส่งผลงานใหม่</a>
                <a href="profile.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">ข้อมูลส่วนตัว</a>
                <a href="teacher_reports.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">รายงานผลงาน</a>
                <a href="logout.php" class="px-3 py-2 text-sm rounded-md text-red-400 hover:bg-red-700">ออกจากระบบ</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto p-6 md:p-10">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">รายละเอียดผลงาน</h1>
            <a href="teacher_publications.php" class="bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-lg hover:bg-gray-300">
                <i class="fas fa-arrow-left mr-2"></i> กลับไปหน้ารายการ
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-8">
                <!-- Main Info Card -->
                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4 border-b pb-3"><?= htmlspecialchars($publication['PubName']) ?></h2>
                     <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><dt class="font-medium text-gray-500">สถานะปัจจุบัน:</dt> <dd><?php echo get_status_badge($publication['PubStatus']); ?></dd></div>
                        <div><dt class="font-medium text-gray-500">ผู้ส่งผลงาน:</dt> <dd><?= htmlspecialchars($publication['UserName']) ?></dd></div>
                        <div><dt class="font-medium text-gray-500">ประเภทผลงาน:</dt> <dd><?= htmlspecialchars($publication['PubType']) ?></dd></div>
                        <div><dt class="font-medium text-gray-500">วันที่ตีพิมพ์:</dt> <dd><?= date('d F Y', strtotime($publication['PubDate'])) ?></dd></div>
                    </dl>
                    <div class="mt-6 border-t pt-4">
                        <h3 class="font-semibold text-gray-700 mb-2">รายละเอียด (Abstract)</h3>
                        <p class="text-gray-600 whitespace-pre-wrap"><?= htmlspecialchars($publication['PubDetail']) ?></p>
                    </div>
                </div>

                 <!-- Authors & Files -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                     <div class="bg-white p-6 rounded-xl shadow-lg">
                         <h2 class="text-xl font-semibold text-gray-800 mb-4">คณะผู้จัดทำ</h2>
                         <ul class="space-y-2">
                             <?php if (empty($authors)): ?>
                                <li class="text-gray-500">ไม่มีข้อมูล</li>
                             <?php else: foreach ($authors as $author): ?>
                                <li class="text-sm"><?= htmlspecialchars($author['Name']) ?></li>
                             <?php endforeach; endif; ?>
                         </ul>
                    </div>
                     <div class="bg-white p-6 rounded-xl shadow-lg">
                         <h2 class="text-xl font-semibold text-gray-800 mb-4">ไฟล์แนบ</h2>
                         <ul class="space-y-2">
                             <?php if (empty($files)): ?>
                                <li class="text-gray-500">ไม่มีไฟล์แนบ</li>
                            <?php else: foreach ($files as $file): ?>
                                <li>
                                    <a href="<?= htmlspecialchars($file['File_path']) ?>" download class="text-indigo-600 hover:underline">
                                        <i class="fas fa-download mr-2"></i> <?= basename(htmlspecialchars($file['File_path'])) ?>
                                    </a>
                                </li>
                            <?php endforeach; endif; ?>
                         </ul>
                    </div>
                </div>
            </div>

            <!-- Right Column: Actions & History -->
            <div class="lg:col-span-1 space-y-8">
                <!-- Action Card -->
                <?php $can_modify = in_array($publication['PubStatus'], ['Draft', 'Pending', 'Rejected']); ?>
                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">การดำเนินการ</h2>
                    <?php if ($can_modify): ?>
                        <div class="space-y-3">
                            <a href="edit_publication.php?id=<?= $publication['PubID'] ?>" class="block w-full text-center py-2 px-4 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700">
                                <i class="fas fa-edit mr-2"></i> แก้ไขข้อมูลผลงาน
                            </a>
                            <form action="teacher_publications.php" method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบผลงานนี้?');">
                                <input type="hidden" name="delete_pub_id" value="<?= $publication['PubID'] ?>">
                                <button type="submit" class="block w-full text-center py-2 px-4 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700">
                                    <i class="fas fa-trash-alt mr-2"></i> ลบผลงานนี้
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 italic">ผลงานนี้ถูกเผยแพร่แล้ว จึงไม่สามารถแก้ไขหรือลบได้</p>
                    <?php endif; ?>
                </div>

                <!-- Approval History Card -->
                <div class="bg-white p-6 rounded-xl shadow-lg">
                     <h2 class="text-xl font-semibold text-gray-800 mb-4">ประวัติการดำเนินการ</h2>
                     <ul class="space-y-4">
                        <?php if (empty($approvals)): ?>
                            <li class="text-gray-500">ยังไม่มีประวัติ</li>
                        <?php else: foreach ($approvals as $approval): ?>
                            <li class="border-b pb-3 last:border-b-0">
                                <p class="font-semibold"><?= htmlspecialchars($approval['Status']) ?> โดย <?= htmlspecialchars($approval['ApproverName']) ?></p>
                                <p class="text-xs text-gray-500"><?= date('d/m/Y H:i', strtotime($approval['Approved_at'])) ?></p>
                                <?php if (!empty($approval['Comment'])): ?>
                                    <p class="text-sm text-gray-600 bg-gray-50 p-2 mt-2 rounded"><strong>ความเห็น:</strong> <?= htmlspecialchars($approval['Comment']) ?></p>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; endif; ?>
                     </ul>
                </div>
            </div>
        </div>
    </main>

</body>
</html>
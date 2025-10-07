<?php
// admin_view_publication.php
// (ฉบับแก้ไข: แสดงรายละเอียดอย่างเดียว)

require_once 'auth_config.php';
require_role(['Admin']);

// 1. ตรวจสอบและรับค่า ID จาก URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    set_session_message('error', 'รหัสผลงานไม่ถูกต้อง');
    header('Location: admin_manage_publications.php');
    exit;
}
$publication_id = (int)$_GET['id'];

// 2. ดึงข้อมูลหลักของ Publication และข้อมูลผู้ส่ง
$query_main = "
    SELECT p.*, u.UserName, u.UserEmail
    FROM publications p
    JOIN users u ON p.User_id = u.User_Id
    WHERE p.PubID = ?
";
$stmt_main = $pdo->prepare($query_main);
$stmt_main->execute([$publication_id]);
$publication = $stmt_main->fetch(PDO::FETCH_ASSOC);

if (!$publication) {
    set_session_message('error', 'ไม่พบผลงานที่คุณต้องการ');
    header('Location: admin_manage_publications.php');
    exit;
}

// 3. ดึงรายชื่อผู้แต่งทั้งหมด (Authors)
$stmt_authors = $pdo->prepare("SELECT a.* FROM authors a JOIN author_publication ap ON a.Author_id = ap.Author_id WHERE ap.Publication_id = ?");
$stmt_authors->execute([$publication_id]);
$authors = $stmt_authors->fetchAll(PDO::FETCH_ASSOC);

// 4. ดึงไฟล์แนบทั้งหมด
$stmt_files = $pdo->prepare("SELECT * FROM publicationfile WHERE Publication_id = ?");
$stmt_files->execute([$publication_id]);
$files = $stmt_files->fetchAll(PDO::FETCH_ASSOC);

// 5. ดึงประวัติการอนุมัติ
$stmt_approvals = $pdo->prepare("SELECT a.*, u.UserName as ApproverName FROM approval a JOIN users u ON a.Approved_by = u.User_Id WHERE a.Publication_id = ? ORDER BY a.Approved_at DESC");
$stmt_approvals->execute([$publication_id]);
$approvals = $stmt_approvals->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดผลงาน - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', 'Tahoma', sans-serif; background-color: #f4f7f9; }
        .detail-grid { display: grid; grid-template-columns: 150px 1fr; gap: 0.5rem 1rem; }
    </style>
</head>
<body class="min-h-screen">

    <?php include('admin_sidebar.php'); ?>

    <main class="ml-64 py-10 px-8">

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                <i class="fas fa-file-alt text-indigo-600 mr-3"></i> รายละเอียดผลงาน
            </h1>
            <a href="admin_manage_publications.php" class="bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-lg hover:bg-gray-300 transition">
                <i class="fas fa-arrow-left mr-2"></i> กลับไปหน้ารายการ
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-8">
                
                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4 border-b pb-3"><?= htmlspecialchars($publication['PubName']) ?></h2>
                    <dl class="detail-grid">
                        <dt class="font-medium text-gray-500">สถานะปัจจุบัน:</dt>
                        <dd>
                            <?php 
                            $status_class = 'bg-gray-100 text-gray-800'; // Default
                            if ($publication['PubStatus'] == 'Published') $status_class = 'bg-green-100 text-green-800';
                            elseif ($publication['PubStatus'] == 'Pending') $status_class = 'bg-yellow-100 text-yellow-800';
                            elseif ($publication['PubStatus'] == 'Rejected') $status_class = 'bg-red-100 text-red-800';
                            ?>
                            <span class="px-3 py-1 text-sm font-semibold rounded-full <?= $status_class ?>">
                                <?= htmlspecialchars($publication['PubStatus']) ?>
                            </span>
                        </dd>
                        
                        <dt class="font-medium text-gray-500">ผู้ส่งผลงาน:</dt>
                        <dd><?= htmlspecialchars($publication['UserName']) ?> (<?= htmlspecialchars($publication['UserEmail']) ?>)</dd>
                        
                        <dt class="font-medium text-gray-500">ประเภทผลงาน:</dt>
                        <dd><?= htmlspecialchars($publication['type_name'] ?? 'N/A') ?></dd>
                        
                        <dt class="font-medium text-gray-500">วันที่ตีพิมพ์:</dt>
                        <dd><?= date('d F Y', strtotime($publication['PubDate'])) ?></dd>
                        
                        <dt class="font-medium text-gray-500">วันที่ส่งเข้าระบบ:</dt>
                        <dd><?= date('d F Y, H:i:s', strtotime($publication['Created_at'])) ?></dd>
                    </dl>
                    <div class="mt-6 border-t pt-4">
                        <h3 class="font-semibold text-gray-700 mb-2">รายละเอียด (Abstract)</h3>
                        <p class="text-gray-600 whitespace-pre-wrap"><?= htmlspecialchars($publication['PubDetail']) ?></p>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-lg">
                     <h2 class="text-xl font-semibold text-gray-800 mb-4"><i class="fas fa-users mr-2"></i> คณะผู้จัดทำ</h2>
                     <ul class="space-y-3">
                        <?php if (empty($authors)): ?>
                            <li class="text-gray-500">ไม่มีข้อมูลคณะผู้จัดทำ</li>
                        <?php else: ?>
                            <?php foreach ($authors as $author): ?>
                                <li class="p-3 bg-gray-50 rounded-lg">
                                    <p class="font-semibold text-gray-900"><?= htmlspecialchars($author['Name']) ?></p>
                                    <p class="text-sm text-gray-600"><?= htmlspecialchars($author['Affiliation']) ?></p>
                                    <p class="text-sm text-gray-500"><?= htmlspecialchars($author['Email']) ?></p>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                     </ul>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-lg">
                     <h2 class="text-xl font-semibold text-gray-800 mb-4"><i class="fas fa-paperclip mr-2"></i> ไฟล์แนบ</h2>
                     <ul class="space-y-2">
                        <?php if (empty($files)): ?>
                            <li class="text-gray-500">ไม่มีไฟล์แนบ</li>
                        <?php else: ?>
                            <?php foreach ($files as $file): ?>
                                <li>
                                    <a href="<?= htmlspecialchars($file['File_path']) ?>" download class="text-indigo-600 hover:underline">
                                        <i class="fas fa-download mr-2"></i> <?= basename(htmlspecialchars($file['File_path'])) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                     </ul>
                </div>

            </div>

            <div class="lg:col-span-1 space-y-8">
                
                <div class="bg-white p-6 rounded-xl shadow-lg">
                     <h2 class="text-xl font-semibold text-gray-800 mb-4"><i class="fas fa-history mr-2"></i> ประวัติการอนุมัติ</h2>
                     <ul class="space-y-4">
                        <?php if (empty($approvals)): ?>
                            <li class="text-gray-500">ยังไม่มีประวัติการอนุมัติ</li>
                        <?php else: ?>
                            <?php foreach ($approvals as $approval): ?>
                                <li class="border-b pb-3">
                                    <div class="flex justify-between items-center">
                                        <span class="font-semibold text-gray-800">
                                            <?= htmlspecialchars($approval['Status']) ?>
                                        </span>
                                        <span class="text-xs text-gray-500"><?= date('d/m/Y H:i', strtotime($approval['Approved_at'])) ?></span>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1">โดย: <?= htmlspecialchars($approval['ApproverName']) ?></p>
                                    <?php if (!empty($approval['Comment'])): ?>
                                    <p class="text-sm text-gray-500 bg-gray-50 p-2 mt-2 rounded-md">
                                        <strong>ความเห็น:</strong> <?= htmlspecialchars($approval['Comment']) ?>
                                    </p>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                     </ul>
                </div>

            </div>
        </div>

    </main>

</body>
</html>
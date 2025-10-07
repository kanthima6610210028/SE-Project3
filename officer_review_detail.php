<?php
// officer_review_detail.php
// (ฉบับแก้ไข: เปลี่ยนเป็น Header Layout)

require_once 'auth_config.php';
require_role(['Officer', 'Admin']);

// 1. ตรวจสอบและรับค่า ID จาก URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    set_session_message('error', 'รหัสผลงานไม่ถูกต้อง');
    header('Location: officer_all_publications.php');
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
    header('Location: officer_all_publications.php');
    exit;
}

// 3. ดึงข้อมูลที่เกี่ยวข้อง
$stmt_authors = $pdo->prepare("SELECT a.* FROM authors a JOIN author_publication ap ON a.Author_id = ap.Author_id WHERE ap.Publication_id = ?");
$stmt_authors->execute([$publication_id]);
$authors = $stmt_authors->fetchAll(PDO::FETCH_ASSOC);

$stmt_files = $pdo->prepare("SELECT * FROM publicationfile WHERE Publication_id = ?");
$stmt_files->execute([$publication_id]);
$files = $stmt_files->fetchAll(PDO::FETCH_ASSOC);

$stmt_approvals = $pdo->prepare("SELECT a.*, u.UserName as ApproverName FROM approval a JOIN users u ON a.Approved_by = u.User_Id WHERE a.Publication_id = ? ORDER BY a.Approved_at DESC");
$stmt_approvals->execute([$publication_id]);
$approvals = $stmt_approvals->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดผลงาน #<?= $publication_id ?> - Officer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style> body { font-family: 'Inter', 'Tahoma', sans-serif; } .detail-grid { display: grid; grid-template-columns: 150px 1fr; } </style>
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- Header (Navbar) -->
    <header class="bg-gray-800 text-gray-100 shadow-lg sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center p-4">
            <a href="officer_dashboard.php" class="text-xl font-bold"><i class="fas fa-tools mr-2"></i> Officer Portal</a>
            <nav class="hidden md:flex space-x-4 items-center">
                <a href="officer_dashboard.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">หน้าหลัก</a>
                <a href="officer_review.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">ตรวจสอบผลงาน</a>
                <a href="officer_all_publications.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700 bg-gray-900">ผลงานทั้งหมด</a>
                <a href="officer_reports.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">รายงาน</a>
                <a href="logout.php" class="px-3 py-2 text-sm rounded-md text-red-400 hover:bg-red-700">ออกจากระบบ</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto p-6 md:p-10">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800"><i class="fas fa-file-alt text-indigo-600 mr-3"></i> รายละเอียดผลงาน</h1>
            <a href="officer_all_publications.php" class="bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-lg hover:bg-gray-300">
                <i class="fas fa-arrow-left mr-2"></i> กลับไปหน้ารวม
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Details -->
            <div class="lg:col-span-2 space-y-8">
                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4 border-b pb-3"><?= htmlspecialchars($publication['PubName']) ?></h2>
                    <dl class="detail-grid">
                        <dt class="font-medium text-gray-500">สถานะ:</dt>
                        <dd>
                            <span class="px-3 py-1 text-sm font-semibold rounded-full 
                                <?php if ($publication['PubStatus'] == 'Published') echo 'bg-green-100 text-green-800'; elseif ($publication['PubStatus'] == 'Pending') echo 'bg-yellow-100 text-yellow-800'; else echo 'bg-red-100 text-red-800'; ?>">
                                <?= htmlspecialchars($publication['PubStatus']) ?>
                            </span>
                        </dd>
                        <dt class="font-medium text-gray-500">ผู้ส่ง:</dt>
                        <dd><?= htmlspecialchars($publication['UserName']) ?></dd>
                        <dt class="font-medium text-gray-500">ประเภท:</dt>
                        <dd><?= htmlspecialchars($publication['PubType']) ?></dd>
                        <dt class="font-medium text-gray-500">วันที่ตีพิมพ์:</dt>
                        <dd><?= date('d F Y', strtotime($publication['PubDate'])) ?></dd>
                        <dt class="font-medium text-gray-500">วันที่ส่ง:</dt>
                        <dd><?= date('d F Y, H:i', strtotime($publication['Created_at'])) ?></dd>
                    </dl>
                    <div class="mt-6 border-t pt-4">
                        <h3 class="font-semibold text-gray-700">บทคัดย่อ</h3>
                        <p class="text-gray-600 whitespace-pre-wrap mt-2"><?= htmlspecialchars($publication['PubDetail']) ?></p>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">คณะผู้จัดทำ</h2>
                    <ul class="space-y-3">
                        <?php if (empty($authors)): ?>
                            <li class="text-gray-500">ไม่มีข้อมูล</li>
                        <?php else: foreach ($authors as $author): ?>
                            <li class="p-3 bg-gray-50 rounded-lg">
                                <p class="font-semibold"><?= htmlspecialchars($author['Name']) ?></p>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($author['Affiliation']) ?></p>
                            </li>
                        <?php endforeach; endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Right Column: Actions & History -->
            <div class="lg:col-span-1 space-y-8">
                <?php if ($publication['PubStatus'] == 'Pending'): ?>
                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">การดำเนินการ</h2>
                    <form action="officer_process_approval.php" method="POST">
                        <input type="hidden" name="publication_id" value="<?= $publication['PubID'] ?>">
                        <div>
                            <label for="comment" class="block text-sm font-medium text-gray-700 mb-1">ความเห็นเพิ่มเติม</label>
                            <textarea name="comment" id="comment" rows="4" class="w-full p-2 border rounded-lg"></textarea>
                        </div>
                        <div class="mt-4 space-y-2">
                            <button type="submit" name="action" value="approve" class="w-full bg-green-600 text-white font-semibold py-2 rounded-lg hover:bg-green-700">เผยแพร่</button>
                            <button type="submit" name="action" value="reject" class="w-full bg-red-600 text-white font-semibold py-2 rounded-lg hover:bg-red-700">ปฏิเสธ</button>
                            <button type="submit" name="action" value="revision" class="w-full bg-yellow-500 text-white font-semibold py-2 rounded-lg hover:bg-yellow-600">ส่งกลับให้แก้ไข</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

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
                                    <p class="text-sm bg-gray-50 p-2 mt-2 rounded"><strong>ความเห็น:</strong> <?= htmlspecialchars($approval['Comment']) ?></p>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
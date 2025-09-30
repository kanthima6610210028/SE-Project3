<?php
/**
 * public_detail.php
 * หน้าแสดงรายละเอียดผลงานตีพิมพ์ฉบับเต็มสำหรับบุคคลทั่วไป
 */
include('auth_config.php');

// 1. ตรวจสอบและรับค่า ID จาก URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    // ถ้า ID ไม่ถูกต้องหรือไม่ใช่ตัวเลข ให้แสดงว่าไม่พบข้อมูล
    $publication = null;
} else {
    $publication_id = (int)$_GET['id'];

    try {
        // 2. ดึงข้อมูลหลักของ Publication และข้อมูลผู้ส่ง
        // **สำคัญ:** ต้องมีเงื่อนไข `AND p.PubStatus = 'Published'` เพื่อความปลอดภัย
        $query_main = "
            SELECT p.*, u.UserName
            FROM publications p
            JOIN users u ON p.User_id = u.User_Id
            WHERE p.PubID = ? AND p.PubStatus = 'Published'
        ";
        $stmt_main = $pdo->prepare($query_main);
        $stmt_main->execute([$publication_id]);
        $publication = $stmt_main->fetch(PDO::FETCH_ASSOC);

        // ถ้าพบข้อมูลผลงาน ให้ดึงข้อมูลอื่นๆ ที่เกี่ยวข้อง
        if ($publication) {
            // 3. ดึงรายชื่อผู้แต่งทั้งหมด (Authors)
            $stmt_authors = $pdo->prepare("SELECT * FROM authors a JOIN author_publication ap ON a.Author_id = ap.Author_id WHERE ap.Publication_id = ?");
            $stmt_authors->execute([$publication_id]);
            $authors = $stmt_authors->fetchAll(PDO::FETCH_ASSOC);

            // 4. ดึงไฟล์แนบทั้งหมด
            $stmt_files = $pdo->prepare("SELECT * FROM publicationfile WHERE Publication_id = ?");
            $stmt_files->execute([$publication_id]);
            $files = $stmt_files->fetchAll(PDO::FETCH_ASSOC);
        }

    } catch (PDOException $e) {
        $publication = null; // กรณีเกิด Error ให้ไม่แสดงข้อมูล
        $error_message = "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $publication ? htmlspecialchars($publication['PubName']) : 'ไม่พบผลงาน' ?> - PubTracker</title>
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
                    <a href="public_publications.php" class="text-gray-900 hover:text-indigo-600">บทความการตีพิมพ์</a>
                    <a href="user_manual.php" class="text-gray-900 hover:text-indigo-600">คู่มือการใช้งาน</a>
                    <a href="login.php" class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700">
                        <i class="fas fa-sign-in-alt mr-1"></i> เข้าสู่ระบบ
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <main class="flex-grow max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full">
        
        <div class="mb-6">
            <a href="public_publications.php" class="text-indigo-600 hover:underline font-semibold">
                <i class="fas fa-arrow-left mr-2"></i> กลับไปหน้ารายการ
            </a>
        </div>
        
        <?php if ($publication): ?>
            <div class="bg-white p-6 md:p-8 rounded-xl shadow-lg space-y-8">
                <div>
                    <p class="text-indigo-600 font-semibold"><?= htmlspecialchars($publication['PubType']) ?></p>
                    <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 mt-2"><?= htmlspecialchars($publication['PubName']) ?></h1>
                    <div class="text-md text-gray-500 mt-4 border-b pb-4">
                        <span>โดย: <strong class="text-gray-700"><?= htmlspecialchars($publication['UserName']) ?></strong></span>
                        <span class="mx-2">|</span>
                        <span>เผยแพร่เมื่อ: <strong class="text-gray-700"><?= date('d F Y', strtotime($publication['PubDate'])) ?></strong></span>
                    </div>
                </div>

                <div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-3">บทคัดย่อ (Abstract)</h2>
                    <p class="text-gray-700 leading-relaxed whitespace-pre-wrap"><?= htmlspecialchars($publication['PubDetail']) ?></p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-3">คณะผู้จัดทำ</h2>
                        <ul class="space-y-3">
                            <?php if (empty($authors)): ?>
                                <li class="text-gray-500">ไม่มีข้อมูล</li>
                            <?php else: foreach ($authors as $author): ?>
                                <li class="p-3 bg-gray-50 rounded-lg border">
                                    <p class="font-semibold text-gray-900"><?= htmlspecialchars($author['Name']) ?></p>
                                    <p class="text-sm text-gray-600"><?= htmlspecialchars($author['Affiliation']) ?></p>
                                </li>
                            <?php endforeach; endif; ?>
                        </ul>
                    </div>

                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-3">ไฟล์แนบ</h2>
                        <ul class="space-y-3">
                            <?php if (empty($files)): ?>
                                <li class="text-gray-500">ไม่มีไฟล์แนบ</li>
                            <?php else: foreach ($files as $file): ?>
                                <li class="p-3 bg-blue-50 rounded-lg border border-blue-200">
                                    <a href="<?= htmlspecialchars($file['File_path']) ?>" download class="flex items-center text-blue-700 hover:text-blue-900 font-semibold">
                                        <i class="fas fa-download mr-3 fa-lg"></i>
                                        <span><?= basename(htmlspecialchars($file['File_path'])) ?></span>
                                    </a>
                                </li>
                            <?php endforeach; endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center bg-white p-12 rounded-xl shadow-lg">
                <i class="fas fa-exclamation-triangle text-5xl text-red-500 mb-4"></i>
                <h1 class="text-3xl font-bold text-gray-800">ไม่พบข้อมูล</h1>
                <p class="text-gray-600 mt-2">
                    <?= isset($error_message) ? htmlspecialchars($error_message) : 'ผลงานที่คุณค้นหาอาจไม่มีอยู่จริง หรือยังไม่ได้รับการเผยแพร่' ?>
                </p>
            </div>
        <?php endif; ?>
    </main>

    <footer class="bg-gray-900 text-white py-6 mt-8">
        <div class="max-w-7xl mx-auto px-4 text-center text-sm opacity-75">&copy; <?= date('Y') ?> PubTracker System. All rights reserved.</div>
    </footer>
</div>

</body>
</html>
<?php
/**
 * index.php
 * หน้าแรกสำหรับผู้เยี่ยมชมทั่วไป (Public Landing Page)
 * (ฉบับแก้ไขตาม Schema ที่ถูกต้อง)
 */
include('auth_config.php');

// ตรวจสอบสถานะการเข้าสู่ระบบและ Redirect ไปยัง Dashboard ที่เหมาะสม
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'Teacher') {
        header("Location: teacher_dashboard.php");
        exit();
    } elseif ($_SESSION['user_role'] === 'Officer') {
        header("Location: officer_dashboard.php");
        exit();
    } elseif ($_SESSION['user_role'] === 'Admin') {
        header("Location: admin_dashboard.php");
        exit();
    }
}

$recent_publications = [];
$error_message = '';

// =================================================================
// Logic ดึงข้อมูลผลงานที่ได้รับการอนุมัติแล้วล่าสุด (แก้ไข Query ให้ถูกต้อง)
// =================================================================
try {
    // --== แก้ไขชื่อคอลัมน์และตารางทั้งหมดใน Query นี้ ==--
    $sql = "
        SELECT 
            p.PubID,
            p.PubName,
            p.PubType,
            p.PubDate,
            p.PubDetail,
            u.UserName
        FROM publications p
        JOIN users u ON p.User_id = u.User_Id
        WHERE p.PubStatus = 'Published'
        ORDER BY p.PubDate DESC, p.Created_at DESC
        LIMIT 5
    ";
    $stmt = $pdo->query($sql);
    $recent_publications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $error_message = "❌ เกิดข้อผิดพลาดในการดึงผลงานล่าสุด: " . $e->getMessage();
}

// Helper function สำหรับจัดรูปแบบประเภทผลงาน
function format_pub_type($type) {
    switch ($type) {
        case 'Journal': return '<i class="fas fa-book-open text-blue-500 mr-1"></i> วารสาร';
        case 'Conference': return '<i class="fas fa-users text-green-500 mr-1"></i> การประชุม';
        case 'Book Chapter': return '<i class="fas fa-book text-purple-500 mr-1"></i> บทในหนังสือ';
        case 'Patent': return '<i class="fas fa-lightbulb text-yellow-500 mr-1"></i> สิทธิบัตร';
        default: return '<i class="fas fa-file-alt text-gray-500 mr-1"></i> อื่นๆ';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดเก็บผลงานวิจัยและตีพิมพ์</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', 'Tahoma', sans-serif; }
        .hero { background-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    </style>
</head>
<body class="bg-gray-50">

<div class="min-h-screen flex flex-col">
    <header class="bg-white shadow-md sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex-shrink-0">
                    <a href="index.php" class="text-2xl font-bold text-indigo-700">
                        <i class="fas fa-graduation-cap mr-2"></i> PubTracker
                    </a>
                </div>
                
                <nav class="hidden md:flex space-x-6 items-center">
                    <a href="index.php" class="text-gray-900 font-semibold hover:text-indigo-600">หน้าแรก</a>
                    <a href="public_publications.php" class="text-gray-900 hover:text-indigo-600">บทความการตีพิมพ์</a>
                    <a href="user_manual.php" class="text-gray-900 hover:text-indigo-600">คู่มือการใช้งาน</a>
                    <a href="login.php" class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700">
                        <i class="fas fa-sign-in-alt mr-1"></i> เข้าสู่ระบบ / ส่งบทความ
                    </a>
                </nav>

                <div class="md:hidden">
                    <button class="text-gray-500 hover:text-gray-700 p-2">
                        <i class="fas fa-bars fa-lg"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-grow">
        <div class="hero text-white py-16 md:py-24 shadow-inner">
            <div class="max-w-7xl mx-auto px-4 text-center">
                <h1 class="text-4xl md:text-5xl font-extrabold mb-4">ยินดีต้อนรับสู่ระบบจัดเก็บผลงานวิจัย</h1>
                <p class="text-xl md:text-2xl font-light mb-8">แหล่งรวมผลงานตีพิมพ์ระดับชาติและนานาชาติของคณาจารย์ในมหาวิทยาลัย</p>
                <div class="mt-8 flex justify-center">
                    <a href="public_publications.php" class="px-8 py-3 bg-white text-indigo-600 font-bold text-lg rounded-full shadow-xl hover:bg-gray-100 transition transform hover:scale-105">
                        <i class="fas fa-search mr-2"></i> ค้นหาผลงานทั้งหมด
                    </a>
                </div>
            </div>
        </div>
        
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
            <h2 class="text-3xl font-bold text-gray-900 mb-8 border-b-4 border-indigo-500 inline-block pb-1">
                <i class="fas fa-newspaper mr-2 text-indigo-600"></i> ผลงานตีพิมพ์ล่าสุด 
            </h2>
            
            <?php if ($error_message): ?>
                <div class="p-4 rounded-lg bg-red-100 border border-red-400 text-red-700" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php elseif (empty($recent_publications)): ?>
                <div class="p-8 text-center text-gray-500 italic text-lg border border-dashed rounded-lg bg-white shadow-sm">
                    ขณะนี้ยังไม่มีผลงานที่ได้รับการเผยแพร่ในระบบ
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($recent_publications as $pub): ?>
                        <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition border-l-4 border-indigo-500 flex flex-col">
                            <h3 class="text-xl font-bold text-gray-800 mb-2 truncate" title="<?= htmlspecialchars($pub['PubName']) ?>">
                                <?= htmlspecialchars($pub['PubName']) ?>
                            </h3>
                            <div class="text-sm text-gray-600 mb-4 space-y-1">
                                <p><?= format_pub_type($pub['PubType']) ?></p>
                                <p><i class="fas fa-user-tie text-gray-400 mr-1"></i> ผู้แต่ง: <span class="font-medium"><?= htmlspecialchars($pub['UserName']) ?></span></p>
                                <p><i class="fas fa-calendar-alt text-gray-400 mr-1"></i> ปีที่พิมพ์: <span class="font-medium"><?= date('Y', strtotime($pub['PubDate'])) ?></span></p>
                            </div>
                            <p class="text-gray-700 text-sm line-clamp-3 mb-4 flex-grow">
                                **บทคัดย่อ:** <?= htmlspecialchars(substr($pub['PubDetail'], 0, 150)) ?><?= strlen($pub['PubDetail']) > 150 ? '...' : '' ?>
                            </p>
                            <a href="public_detail.php?id=<?= $pub['PubID'] ?>" 
                               class="mt-auto px-4 py-2 bg-indigo-100 text-indigo-700 font-semibold rounded-lg text-center hover:bg-indigo-200 text-sm">
                                <i class="fas fa-eye mr-1"></i> ดูรายละเอียด
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="bg-gray-800 text-white py-12">
            <div class="max-w-7xl mx-auto px-4 text-center">
                <h2 class="text-2xl font-bold mb-4">คณาจารย์ต้องการส่งผลงานตีพิมพ์ใช่หรือไม่?</h2>
                <p class="mb-6 opacity-80">เข้าสู่ระบบเพื่อจัดการผลงานทั้งหมดของคุณ รวมถึงการส่งผลงานใหม่และการแก้ไข</p>
                <a href="login.php" class="px-6 py-3 bg-green-500 text-white font-bold rounded-lg shadow-xl hover:bg-green-600 transition transform hover:scale-105">
                    <i class="fas fa-paper-plane mr-2"></i> เข้าสู่ระบบเพื่อส่งผลงาน
                </a>
            </div>
        </section>
    </main>

    <footer class="bg-gray-900 text-white py-6">
        <div class="max-w-7xl mx-auto px-4 text-center text-sm opacity-75">
            &copy; <?= date('Y') ?> PubTracker System. All rights reserved.
        </div>
    </footer>
</div>

</body>
</html>
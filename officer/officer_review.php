<?php
/**
 * officer_review.php
 * หน้าสำหรับเจ้าหน้าที่ตรวจสอบรายละเอียดผลงาน
 * (ฉบับแก้ไข: เปลี่ยนเป็น Header Layout)
 */
include('auth_config.php');
require_role(['Officer', 'Admin']);

$message = get_session_message();
$pub_id = isset($_GET['pub_id']) ? intval($_GET['pub_id']) : null;
$publication = null;
$all_pending = [];
$show_detail = false;
$error_message_list = '';

// POST Logic for approval/rejection (No changes needed here)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... PHP code for handling form submission remains the same ...
}

// Fetch all pending publications
try {
    $stmt_all_pending = $pdo->query("
        SELECT p.PubID, p.PubName, p.PubType, p.Created_at, u.UserName 
        FROM publications p JOIN users u ON p.User_id = u.User_Id 
        WHERE p.PubStatus = 'Pending' ORDER BY p.Created_at ASC
    ");
    $all_pending = $stmt_all_pending->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $error_message_list = "❌ เกิดข้อผิดพลาด: " . $e->getMessage();
}

// Fetch details of a single publication if an ID is provided
if ($pub_id) {
    try {
        $stmt_detail = $pdo->prepare("
            SELECT p.*, u.UserName 
            FROM publications p JOIN users u ON p.User_id = u.User_Id 
            WHERE p.PubID = ?
        ");
        $stmt_detail->execute([$pub_id]);
        $publication = $stmt_detail->fetch(PDO::FETCH_ASSOC);

        if ($publication) {
            $show_detail = true;
        } else {
            set_session_message('warning', "⚠️ ไม่พบข้อมูลผลงานรหัส #{$pub_id}");
            header("Location: officer_review.php");
            exit();
        }
    } catch (\PDOException $e) {
        $error_message_detail = "❌ เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

// Helper function
if (!function_exists('get_status_badge')) {
    function get_status_badge($status) {
        switch ($status) {
            case 'Pending': return '<span class="px-3 py-1 text-sm rounded-full bg-yellow-100 text-yellow-800">รอตรวจสอบ</span>';
            case 'Published': return '<span class="px-3 py-1 text-sm rounded-full bg-green-100 text-green-800">เผยแพร่แล้ว</span>';
            case 'Draft': return '<span class="px-3 py-1 text-sm rounded-full bg-gray-100 text-gray-800">แบบร่าง</span>';
            case 'Rejected': return '<span class="px-3 py-1 text-sm rounded-full bg-red-100 text-red-800">ถูกปฏิเสธ</span>';
            default: return '<span class="px-3 py-1 text-sm rounded-full bg-gray-100">ไม่ทราบ</span>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจสอบผลงาน - เจ้าหน้าที่</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style> body { font-family: 'Inter', 'Tahoma', sans-serif; } </style>
</head>
<body class="bg-gray-100">

    <header class="bg-gray-800 text-gray-100 shadow-lg sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center p-4">
            <a href="officer_dashboard.php" class="text-xl font-bold"><i class="fas fa-tools mr-2"></i> Officer Portal</a>
            <nav class="hidden md:flex space-x-4 items-center">
                <a href="officer_dashboard.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">หน้าหลัก</a>
                <a href="officer_review.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700 bg-gray-900">ตรวจสอบผลงาน</a>
                <a href="officer_all_publications.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">ผลงานทั้งหมด</a>
                <a href="officer_reports.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">รายงาน</a>
                <a href="profile.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">ข้อมูลส่วนตัว</a>
                <a href="logout.php" class="px-3 py-2 text-sm rounded-md text-red-400 hover:bg-red-700">ออกจากระบบ</a>
            </nav>
        </div>
    </header>

<main class="container mx-auto p-6 md:p-10">
    <h1 class="text-3xl font-extrabold text-gray-900 mb-8"><i class="fas fa-clipboard-check text-indigo-600 mr-2"></i> ตรวจสอบผลงาน</h1>
    
    <?php if ($message): ?>
        <div class="p-4 rounded-lg mb-6 <?= $message['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
            <?= htmlspecialchars($message['message']) ?>
        </div>
    <?php endif; ?>
    <?php if ($error_message_list): ?>
        <div class="p-4 rounded-lg mb-6 bg-red-100 text-red-700"><?= htmlspecialchars($error_message_list) ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1 bg-white p-4 rounded-xl shadow-lg border-t-4 border-red-500">
            <h2 class="text-xl font-bold text-gray-800 mb-4 pb-2 border-b"><i class="fas fa-list-ul mr-2 text-red-500"></i> ที่ต้องตรวจสอบ (<?= count($all_pending) ?>)</h2>
            <?php if (empty($all_pending)): ?>
                <div class="p-4 text-center text-gray-500 italic">ไม่มีผลงานที่ต้องตรวจสอบแล้ว</div>
            <?php else: ?>
                <div class="space-y-3 max-h-[70vh] overflow-y-auto pr-2">
                    <?php foreach ($all_pending as $pub): ?>
                        <a href="officer_review.php?pub_id=<?= $pub['PubID'] ?>" class="block p-4 rounded-lg <?= $pub['PubID'] == $pub_id ? 'bg-indigo-100' : 'bg-gray-50 hover:bg-gray-100' ?>">
                            <p class="text-sm font-bold text-gray-900 truncate">#<?= $pub['PubID'] ?> - <?= htmlspecialchars($pub['PubName']) ?></p>
                            <p class="text-xs text-gray-600 mt-1"><?= htmlspecialchars($pub['UserName']) ?> | <?= htmlspecialchars($pub['PubType']) ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="lg:col-span-2">
            <?php if ($show_detail && $publication): ?>
                <div class="bg-white p-6 rounded-xl shadow-lg border-t-4 border-indigo-500">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4 flex justify-between">
                        รายละเอียดผลงาน #<?= $publication['PubID'] ?>
                        <?= get_status_badge($publication['PubStatus']) ?>
                    </h2>
                    <div class="border-b pb-4 mb-4 space-y-3">
                        <p class="text-xl font-bold text-indigo-700"><?= htmlspecialchars($publication['PubName']) ?></p>
                        <p><span class="font-semibold">ผู้ส่ง:</span> <?= htmlspecialchars($publication['UserName']) ?></p>
                        <p><span class="font-semibold">รายละเอียด:</span><br><?= nl2br(htmlspecialchars($publication['PubDetail'])) ?></p>
                    </div>
                    <form method="POST" action="officer_process_approval.php">
                        <input type="hidden" name="publication_id" value="<?= $publication['PubID'] ?>">
                        <label for="comment" class="block font-semibold text-gray-700">ข้อเสนอแนะ:</label>
                        <textarea id="comment" name="comment" rows="4" class="mt-1 w-full border rounded-lg p-2" placeholder="ใส่เหตุผลหากต้องการปฏิเสธ หรือสั่งแก้ไข..."></textarea>
                        <div class="mt-6 pt-4 border-t grid grid-cols-1 md:grid-cols-3 gap-3">
                            <button type="submit" name="action" value="approve" class="p-2 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700">อนุมัติ</button>
                            <button type="submit" name="action" value="revision" class="p-2 bg-yellow-600 text-white font-bold rounded-lg hover:bg-yellow-700">สั่งแก้ไข</button>
                            <button type="submit" name="action" value="reject" class="p-2 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700">ปฏิเสธ</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="p-12 text-center text-gray-500 border border-dashed rounded-xl bg-white shadow-md">
                    <i class="fas fa-hand-pointer mr-2"></i> กรุณาเลือกผลงานจากรายการด้านซ้ายเพื่อทำการตรวจสอบ
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

</body>
</html>
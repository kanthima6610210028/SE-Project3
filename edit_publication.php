<?php
/**
 * edit_publication.php
 * หน้าฟอร์มสำหรับอาจารย์ (Teacher) ในการแก้ไขผลงานตีพิมพ์
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

// =================================================================
// Logic 2: จัดการการอัปเดตข้อมูล (POST Request)
// =================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ... (ส่วน PHP สำหรับรับและบันทึกข้อมูลยังคงเหมือนเดิมทุกประการ) ...
}

// =================================================================
// Logic 1: ดึงข้อมูลเดิมมาแสดง (GET Request)
// =================================================================
try {
    $stmt_pub = $pdo->prepare("SELECT * FROM publications WHERE PubID = ? AND User_id = ?");
    $stmt_pub->execute([$publication_id, $user_id]);
    $publication = $stmt_pub->fetch(PDO::FETCH_ASSOC);

    if (!$publication) {
        set_session_message('error', 'ไม่พบผลงานที่ต้องการแก้ไข หรือคุณไม่ใช่เจ้าของ');
        header('Location: teacher_publications.php');
        exit;
    }
    if ($publication['PubStatus'] === 'Published') {
        set_session_message('error', 'ไม่สามารถแก้ไขผลงานที่เผยแพร่แล้วได้');
        header('Location: teacher_view_publication.php?id=' . $publication_id);
        exit;
    }
    $stmt_authors = $pdo->prepare("SELECT a.* FROM authors a JOIN author_publication ap ON a.Author_id = ap.Author_id WHERE ap.Publication_id = ?");
    $stmt_authors->execute([$publication_id]);
    $current_authors = $stmt_authors->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage());
}

$messageData = get_session_message();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขผลงาน #<?= $publication_id ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style> body { font-family: 'Inter', 'Tahoma', sans-serif; } </style>
</head>
<body class="bg-gray-100">

    <header class="bg-gray-800 text-gray-100 shadow-lg sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center p-4">
            <a href="teacher_dashboard.php" class="text-xl font-bold"><i class="fas fa-graduation-cap mr-2"></i> Teacher Portal</a>
            <nav class="hidden md:flex space-x-4 items-center">
                <a href="teacher_dashboard.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">หน้าหลัก</a>
                <a href="teacher_publications.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">ผลงานของฉัน</a>
                <a href="add_publication.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">ส่งผลงานใหม่</a>
                <a href="profile.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">ข้อมูลส่วนตัว</a>
                <a href="teacher_reports.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">รายงานผลงาน</a>
                <a href="logout.php" class="px-3 py-2 text-sm rounded-md text-red-400 hover:bg-red-700">ออกจากระบบ</a>
            </nav>
        </div>
    </header>

    <main class="container mx-auto p-6 md:p-10">
        <h1 class="text-3xl font-extrabold text-gray-900 mb-8">
            <i class="fas fa-edit text-indigo-600 mr-2"></i> แก้ไขผลงานตีพิมพ์
        </h1>

        <?php if ($messageData): ?>
            <div class="p-4 rounded-lg mb-6 <?= $messageData['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                <?= htmlspecialchars($messageData['message']) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 md:p-8 rounded-xl shadow-2xl border-t-4 border-indigo-500">
            <form method="POST" action="edit_publication.php?id=<?= $publication_id ?>" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="publication_id" value="<?= $publication_id ?>">
                
                <div>
                    <label for="pub_name" class="block text-sm font-medium text-gray-700">ชื่อเรื่อง <span class="text-red-500">*</span></label>
                    <input type="text" name="pub_name" id="pub_name" required value="<?= htmlspecialchars($publication['PubName']) ?>" class="mt-1 block w-full p-2 border border-gray-300 rounded-lg">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="pub_type" class="block text-sm font-medium text-gray-700">ประเภทผลงาน</label>
                        <select name="pub_type" id="pub_type" class="mt-1 block w-full p-2 border border-gray-300 rounded-lg bg-white">
                            <?php $types = ['Journal', 'Conference', 'Textbook', 'Other']; ?>
                            <?php foreach($types as $type): ?>
                                <option value="<?= $type ?>" <?= $publication['PubType'] == $type ? 'selected' : '' ?>><?= $type ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="pub_year" class="block text-sm font-medium text-gray-700">ปีที่ตีพิมพ์</label>
                        <input type="number" name="pub_year" id="pub_year" value="<?= date('Y', strtotime($publication['PubDate'])) ?>" class="mt-1 block w-full p-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                <div>
                    <label for="pub_detail" class="block text-sm font-medium text-gray-700">บทคัดย่อ <span class="text-red-500">*</span></label>
                    <textarea name="pub_detail" id="pub_detail" rows="6" required class="mt-1 block w-full p-2 border border-gray-300 rounded-lg"><?= htmlspecialchars($publication['PubDetail']) ?></textarea>
                </div>

                <div class="pt-6 border-t mt-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">คณะผู้จัดทำ (ไม่บังคับ)</h2>
                    <div id="authors-container" class="space-y-4"></div>
                    <button type="button" id="add-author-btn" class="mt-4 px-4 py-2 bg-blue-100 text-blue-700 text-sm font-semibold rounded-lg hover:bg-blue-200">
                        <i class="fas fa-plus mr-2"></i> เพิ่มผู้จัดทำ
                    </button>
                </div>

                <div class="pt-6 border-t mt-6 flex justify-end items-center space-x-4">
                    <a href="teacher_view_publication.php?id=<?= $publication_id ?>" class="px-6 py-3 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300">ยกเลิก</a>
                    <button type="submit" class="px-8 py-3 rounded-lg shadow-md text-xl font-bold text-white bg-indigo-600 hover:bg-indigo-700">
                        <i class="fas fa-save mr-2"></i> บันทึกการเปลี่ยนแปลง
                    </button>
                </div>
            </form>
        </div>
    </main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('authors-container');
    const addButton = document.getElementById('add-author-btn');
    const existingAuthors = <?= json_encode($current_authors) ?>;
    
    function addAuthorRow(author = { Name: '', Affiliation: '', Email: '' }) {
        const authorRow = document.createElement('div');
        authorRow.className = 'p-4 border rounded-lg bg-gray-50 grid grid-cols-1 md:grid-cols-3 gap-4 relative';
        authorRow.innerHTML = `
            <div>
                <label class="block text-xs font-medium text-gray-600">ชื่อ-นามสกุล</label>
                <input type="text" name="authors[name][]" class="mt-1 w-full p-2 border rounded-md" value="${escapeHTML(author.Name)}">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600">สังกัด</label>
                <input type="text" name="authors[affiliation][]" class="mt-1 w-full p-2 border rounded-md" value="${escapeHTML(author.Affiliation)}">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600">อีเมล</label>
                <input type="email" name="authors[email][]" class="mt-1 w-full p-2 border rounded-md" value="${escapeHTML(author.Email)}">
            </div>
            <button type="button" class="remove-author-btn absolute -top-2 -right-2 bg-red-500 text-white rounded-full h-6 w-6 flex items-center justify-center text-xs">&times;</button>
        `;
        container.appendChild(authorRow);
        authorRow.querySelector('.remove-author-btn').addEventListener('click', () => authorRow.remove());
    }
    
    function escapeHTML(str) {
        return str ? str.toString().replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;') : '';
    }

    addButton.addEventListener('click', () => addAuthorRow());

    if (existingAuthors.length > 0) {
        existingAuthors.forEach(author => addAuthorRow(author));
    } else {
        addAuthorRow();
    }
});
</script>

</body>
</html>
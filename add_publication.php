<?php
/**
 * add_publication.php
 * หน้าฟอร์มสำหรับอาจารย์ (Teacher) ในการเพิ่มผลงานตีพิมพ์ใหม่
 * (ฉบับแก้ไข: เปลี่ยนเป็น Header Layout)
 */
include('auth_config.php');
require_role(['Teacher']);

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'N/A';

// =================================================================
// Logic การจัดการฟอร์ม (POST Submission)
// =================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pub_name = trim($_POST['pub_name'] ?? '');
    $pub_detail = trim($_POST['pub_detail'] ?? '');
    $pub_type = trim($_POST['pub_type'] ?? '');
    $pub_year = intval($_POST['pub_year'] ?? date('Y'));
    $pub_date = $pub_year . "-01-01";
    $authors = $_POST['authors'] ?? [];

    if (empty($pub_name) || empty($pub_detail)) {
        set_session_message("กรุณากรอกชื่อเรื่องและเนื้อหาบทความให้ครบถ้วน", 'error');
        header("Location: add_publication.php");
        exit();
    }
    
    try {
        $pdo->beginTransaction();

        $stmt_pub = $pdo->prepare("INSERT INTO publications (User_id, PubName, PubDetail, PubType, PubDate, PubStatus, Created_at) VALUES (?, ?, ?, ?, ?, 'Pending', NOW())");
        $stmt_pub->execute([$user_id, $pub_name, $pub_detail, $pub_type, $pub_date]);
        $new_pub_id = $pdo->lastInsertId();

        if (!empty($authors['name'])) {
            foreach ($authors['name'] as $key => $author_name) {
                $author_name = trim($author_name);
                if (empty($author_name)) continue;
                $author_affiliation = trim($authors['affiliation'][$key] ?? '');
                $author_email = trim($authors['email'][$key] ?? '');
                $author_id = null;

                $stmt_find_author = $pdo->prepare("SELECT Author_id FROM authors WHERE Name = ? AND Affiliation = ?");
                $stmt_find_author->execute([$author_name, $author_affiliation]);
                $existing_author = $stmt_find_author->fetch();

                if ($existing_author) {
                    $author_id = $existing_author['Author_id'];
                } else {
                    $stmt_add_author = $pdo->prepare("INSERT INTO authors (Name, Affiliation, Email) VALUES (?, ?, ?)");
                    $stmt_add_author->execute([$author_name, $author_affiliation, $author_email]);
                    $author_id = $pdo->lastInsertId();
                }

                $stmt_link = $pdo->prepare("INSERT INTO author_publication (Author_id, Publication_id) VALUES (?, ?)");
                $stmt_link->execute([$author_id, $new_pub_id]);
            }
        }

        if (isset($_FILES['pub_file']) && $_FILES['pub_file']['error'] === UPLOAD_ERR_OK) {
            // ... โค้ดส่วนอัปโหลดไฟล์ ...
        }
        
        $pdo->commit();
        set_session_message("✅ ส่งผลงานตีพิมพ์ '{$pub_name}' สำเร็จแล้ว!", 'success');
        
        header("Location: teacher_publications.php");
        exit();

    } catch (\Exception $e) {
        $pdo->rollBack();
        set_session_message("❌ เกิดข้อผิดพลาด: " . $e->getMessage(), 'error');
        header("Location: add_publication.php");
        exit();
    }
}

$messageData = get_session_message();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ส่งผลงานใหม่ - Teacher Portal</title>
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
                <a href="teacher_publications.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">ผลงานของฉัน</a>
                <a href="add_publication.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700 bg-gray-900">ส่งผลงานใหม่</a>
                <a href="profile.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">ข้อมูลส่วนตัว</a>
                <a href="teacher_reports.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">รายงานผลงาน</a>
                <a href="logout.php" class="px-3 py-2 rounded-md text-sm font-medium text-red-400 hover:bg-red-700">ออกจากระบบ</a>
            </nav>
        </div>
    </header>

    <main class="container mx-auto p-6 md:p-10">
        <h1 class="text-3xl font-extrabold text-gray-900 mb-8">
            <i class="fas fa-file-upload text-green-600 mr-2"></i> ส่งผลงานตีพิมพ์ใหม่
        </h1>

        <?php if ($messageData): ?>
            <div class="p-4 rounded-lg mb-6 <?= $messageData['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                <?= htmlspecialchars($messageData['message']) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 md:p-8 rounded-xl shadow-2xl border-t-4 border-green-500">
            <form method="POST" action="add_publication.php" enctype="multipart/form-data" class="space-y-6">
                <div class="space-y-6">
                    <div>
                        <label for="pub_name" class="block text-sm font-medium text-gray-700">ชื่อเรื่อง <span class="text-red-500">*</span></label>
                        <input type="text" name="pub_name" id="pub_name" required placeholder="ชื่อเรื่องของคุณ" class="mt-1 block w-full p-2 border border-gray-300 rounded-lg">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="pub_type" class="block text-sm font-medium text-gray-700">ประเภทผลงาน</label>
                            <select name="pub_type" id="pub_type" class="mt-1 block w-full p-2 border border-gray-300 rounded-lg bg-white">
                                <option>Journal</option>
                                <option>Conference</option>
                                <option>Textbook</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div>
                            <label for="pub_year" class="block text-sm font-medium text-gray-700">ปีที่ตีพิมพ์</label>
                            <input type="number" name="pub_year" id="pub_year" value="<?= date('Y') ?>" min="1950" max="<?= date('Y') + 1 ?>" class="mt-1 block w-full p-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>
                    <div>
                        <label for="pub_detail" class="block text-sm font-medium text-gray-700">บทคัดย่อ <span class="text-red-500">*</span></label>
                        <textarea name="pub_detail" id="pub_detail" rows="6" required class="mt-1 block w-full p-2 border border-gray-300 rounded-lg"></textarea>
                    </div>
                </div>

                <div class="pt-6 border-t mt-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">คณะผู้จัดทำ</h2>
                    <div id="authors-container" class="space-y-4"></div>
                    <button type="button" id="add-author-btn" class="mt-4 px-4 py-2 bg-blue-100 text-blue-700 text-sm font-semibold rounded-lg hover:bg-blue-200">
                        <i class="fas fa-plus mr-2"></i> เพิ่มผู้จัดทำ
                    </button>
                </div>

                <div class="pt-6 border-t mt-6">
                    <label for="pub_file" class="block text-sm font-medium text-gray-700">แนบไฟล์ (ถ้ามี)</label>
                    <input type="file" name="pub_file" id="pub_file" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                </div>

                <div class="pt-6 border-t mt-6">
                    <button type="submit" class="w-full flex justify-center py-3 px-4 rounded-lg shadow-md text-xl font-bold text-white bg-green-600 hover:bg-green-700">
                        <i class="fas fa-paper-plane mr-2"></i> ส่งบทความ
                    </button>
                </div>
            </form>
        </div>
    </main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('authors-container');
    const addButton = document.getElementById('add-author-btn');
    let authorCount = 0;

    function addAuthorRow() {
        authorCount++;
        const authorRow = document.createElement('div');
        authorRow.className = 'p-4 border rounded-lg bg-gray-50 grid grid-cols-1 md:grid-cols-3 gap-4 relative';
        
        authorRow.innerHTML = `
            <div>
                <label class="block text-xs font-medium text-gray-600">ชื่อ-นามสกุล</label>
                <input type="text" name="authors[name][]" required class="mt-1 w-full p-2 border rounded-md">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600">สังกัด</label>
                <input type="text" name="authors[affiliation][]" class="mt-1 w-full p-2 border rounded-md">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600">อีเมล</label>
                <input type="email" name="authors[email][]" class="mt-1 w-full p-2 border rounded-md">
            </div>
            ${authorCount > 1 ? `<button type="button" class="remove-author-btn absolute -top-2 -right-2 bg-red-500 text-white rounded-full h-6 w-6 flex items-center justify-center text-xs"><i class="fas fa-times"></i></button>` : ''}
        `;
        container.appendChild(authorRow);
        
        const removeButton = authorRow.querySelector('.remove-author-btn');
        if (removeButton) {
            removeButton.addEventListener('click', () => authorRow.remove());
        }
    }
    addButton.addEventListener('click', addAuthorRow);
    addAuthorRow();
});
</script>

</body>
</html>
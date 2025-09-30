<?php
/**
 * View_Approval.php
 * หน้าสำหรับเจ้าหน้าที่ (Officer) ในการตรวจสอบรายละเอียดผลงานตีพิมพ์ 
 * และดำเนินการอนุมัติ (Approve), ปฏิเสธ (Reject), หรือขอแก้ไข (Request Revision)
 */
include('auth_config.php');

// ตรวจสอบสิทธิ์การเข้าถึง: ต้องเป็น Officer เท่านั้น
if (!check_user_role('Officer')) {
    header("Location: login.php");
    exit();
}

// =================================================================
// 1. Logic การจัดการสถานะ (Update Status Logic)
// =================================================================

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['pub_id'])) {
    $pub_id = $_POST['pub_id'];
    $action = $_POST['action'];
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : null;
    $new_status = null;
    $log_message = '';

    // กำหนดสถานะใหม่ตาม Action
    switch ($action) {
        case 'approve':
            $new_status = 'Approved';
            $log_message = 'อนุมัติผลงานตีพิมพ์';
            break;
        case 'reject':
            $new_status = 'Rejected';
            $log_message = 'ปฏิเสธผลงานตีพิมพ์';
            break;
        case 'revision':
            $new_status = 'In Revision';
            $log_message = 'ขอให้อาจารย์แก้ไขผลงาน';
            break;
        default:
            header("Location: manage_publications.php");
            exit();
    }

    try {
        $pdo->beginTransaction();

        // อัปเดตสถานะในตาราง publications
        $stmt_update = $pdo->prepare("UPDATE publications SET PubStatus = ?, OfficerComment = ?, PubApprovalDate = NOW() WHERE PubID = ? AND PubStatus != 'Approved'");
        $stmt_update->execute([$new_status, $comment, $pub_id]);

        // **************** LOGGING *******************
        // ในระบบจริง จะต้องมีการเพิ่มบันทึกลงในตาราง Audit/History
        $officer_id = $_SESSION['user_id'];
        // ตัวอย่างการบันทึกประวัติ (สมมติว่ามีตาราง history)
        /*
        $stmt_log = $pdo->prepare("INSERT INTO publication_history (PubID, UserID, Action, Detail) VALUES (?, ?, ?, ?)");
        $stmt_log->execute([$pub_id, $officer_id, $log_message, $comment]);
        */
        // ********************************************

        $pdo->commit();
        $_SESSION['success_message'] = "✅ ดำเนินการ '$log_message' สำเร็จแล้ว";
        header("Location: manage_publications.php"); // Redirect กลับไปหน้าจัดการ
        exit();

    } catch (\PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "❌ เกิดข้อผิดพลาดในการดำเนินการ: " . $e->getMessage();
        header("Location: manage_publications.php"); 
        exit();
    }
}


// =================================================================
// 2. Logic การดึงข้อมูลผลงาน (Fetch Publication Data)
// =================================================================

$pub_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$publication = null;
$author = null;

if ($pub_id === 0 || !$pdo) {
    header("Location: manage_publications.php");
    exit();
}

try {
    // ดึงข้อมูลผลงานตีพิมพ์
    $stmt = $pdo->prepare("
        SELECT 
            p.*, 
            u.UserName, u.UserRole, u.UserEmail, 
            p.AuthorID
        FROM publications p
        JOIN users u ON p.AuthorID = u.User_Id
        WHERE p.PubID = ?
    ");
    $stmt->execute([$pub_id]);
    $publication = $stmt->fetch();

    if (!$publication) {
        $_SESSION['error_message'] = "ไม่พบผลงานตีพิมพ์หมายเลข #{$pub_id} นี้ในระบบ";
        header("Location: manage_publications.php");
        exit();
    }
    
    // ดึงข้อมูลไฟล์แนบ (สมมติว่ามีตาราง publication_files)
    $stmt_file = $pdo->prepare("SELECT FileName, FilePath FROM publication_files WHERE PubID = ? LIMIT 1");
    $stmt_file->execute([$pub_id]);
    $file_data = $stmt_file->fetch();

} catch (\PDOException $e) {
    // Log error
    $_SESSION['error_message'] = "❌ Error: ไม่สามารถดึงข้อมูลผลงานได้: " . $e->getMessage();
    header("Location: manage_publications.php");
    exit();
}

// กำหนดสีและข้อความสถานะ
$status_class = [
    'Pending' => ['text-yellow-600', 'bg-yellow-100', 'รอตรวจสอบ'],
    'Approved' => ['text-green-600', 'bg-green-100', 'อนุมัติแล้ว'],
    'Rejected' => ['text-red-600', 'bg-red-100', 'ถูกปฏิเสธ'],
    'In Revision' => ['text-blue-600', 'bg-blue-100', 'รอการแก้ไข'],
];
$current_status = $publication['PubStatus'];
[$text_color, $bg_color, $status_th] = $status_class[$current_status] ?? ['text-gray-600', 'bg-gray-100', 'ไม่ทราบสถานะ'];

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจสอบผลงาน #<?= $pub_id ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', 'Tahoma', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<!-- Header (Simplified Navigation Bar) -->
<header class="bg-white shadow-md p-4 flex justify-between items-center sticky top-0 z-10">
    <div class="flex items-center space-x-4">
        <span class="text-xl font-bold text-gray-800"><i class="fas fa-book-reader text-blue-600 mr-2"></i>ระบบตีพิมพ์</span>
        <a href="officer_dashboard.php" class="text-gray-600 hover:text-blue-600">หน้าหลัก</a>
        <a href="manage_publications.php" class="text-blue-600 font-bold">ตรวจสอบผลงาน</a>
    </div>
    <a href="logout.php" class="text-red-600 hover:text-red-800"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
</header>

<main class="container mx-auto p-4 md:p-8">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">
            <i class="fas fa-search-check text-blue-600"></i> ตรวจสอบผลงานตีพิมพ์
        </h1>
        <span class="px-4 py-2 rounded-full font-semibold <?= $bg_color ?> <?= $text_color ?> shadow-md">
            สถานะ: <?= $status_th ?>
        </span>
    </div>

    <!-- Publication Detail Card -->
    <div class="bg-white p-6 rounded-xl shadow-2xl border-l-4 border-blue-500 mb-6">
        <h2 class="text-2xl font-extrabold text-gray-900 mb-4"><?= htmlspecialchars($publication['PubName']) ?></h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8 text-gray-700">
            
            <!-- Author Info -->
            <div class="border-b pb-4">
                <p class="text-sm font-semibold text-blue-600 mb-1">ข้อมูลผู้ส่ง/ผู้แต่ง</p>
                <p><i class="fas fa-user-tie mr-2 text-blue-400"></i> **ชื่อผู้แต่ง:** <?= htmlspecialchars($publication['UserName']) ?></p>
                <p><i class="fas fa-envelope mr-2 text-blue-400"></i> **E-mail:** <?= htmlspecialchars($publication['UserEmail']) ?></p>
            </div>

            <!-- Publication Metadata -->
            <div class="border-b pb-4">
                <p class="text-sm font-semibold text-blue-600 mb-1">ข้อมูลการตีพิมพ์</p>
                <p><i class="fas fa-calendar-alt mr-2 text-blue-400"></i> **ปีที่ตีพิมพ์:** <?= htmlspecialchars($publication['PubYear'] ?? 'N/A') ?></p>
                <p><i class="fas fa-tag mr-2 text-blue-400"></i> **ประเภท:** <?= htmlspecialchars($publication['PubType'] ?? 'N/A') ?></p>
                <p><i class="fas fa-calendar-check mr-2 text-blue-400"></i> **วันที่ส่ง:** <?= date('d/m/Y', strtotime($publication['PubSubmissionDate'])) ?></p>
            </div>
            
            <!-- Abstract / Content -->
            <div class="md:col-span-2 mt-4">
                <p class="text-sm font-semibold text-gray-800 mb-2 border-b">เนื้อหา/บทคัดย่อ</p>
                <div class="bg-gray-50 p-4 rounded-lg border h-40 overflow-y-auto">
                    <?= nl2br(htmlspecialchars($publication['PubAbstract'])) ?>
                </div>
            </div>

        </div>
    </div>
    
    <!-- File and Action Panel -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- File Attachment -->
        <div class="lg:col-span-1 bg-white p-6 rounded-xl shadow-lg border-l-4 border-purple-500">
            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-file-pdf mr-2 text-purple-600"></i> ไฟล์แนบ
            </h3>
            <?php if ($file_data): ?>
                <div class="flex justify-between items-center bg-purple-50 p-3 rounded-lg border border-purple-200">
                    <span class="truncate text-sm font-medium text-gray-700"><?= htmlspecialchars($file_data['FileName']) ?></span>
                    <a href="download_file.php?file=<?= urlencode($file_data['FilePath']) ?>" 
                       class="text-white bg-purple-600 hover:bg-purple-700 px-3 py-1 rounded-lg text-sm transition duration-150"
                       download>
                        <i class="fas fa-download mr-1"></i> ดาวน์โหลด
                    </a>
                </div>
                <p class="text-xs mt-2 text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i> กรุณาตรวจสอบเนื้อหาในไฟล์แนบก่อนดำเนินการอนุมัติ
                </p>
            <?php else: ?>
                <p class="text-red-500 italic">⚠️ ไม่พบไฟล์แนบสำหรับผลงานนี้</p>
            <?php endif; ?>
        </div>
        
        <!-- Action Buttons -->
        <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-lg">
            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-cogs mr-2 text-orange-600"></i> ดำเนินการตรวจสอบ
            </h3>

            <?php if ($current_status === 'Approved'): ?>
                <div class="text-center py-6 bg-green-50 rounded-lg border border-green-200">
                    <p class="text-2xl font-bold text-green-700">ผลงานนี้ได้รับการอนุมัติเรียบร้อยแล้ว</p>
                    <p class="text-sm text-gray-600 mt-2">วันที่อนุมัติ: <?= date('d/m/Y H:i', strtotime($publication['PubApprovalDate'] ?? 'N/A')) ?></p>
                </div>
            <?php else: ?>
                <div class="flex flex-col space-y-4">
                    <!-- Approve Button -->
                    <button onclick="showConfirmModal('approve', <?= $pub_id ?>)"
                            class="w-full py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg transition duration-150 shadow-md">
                        <i class="fas fa-check-circle mr-2"></i> อนุมัติ (Approve)
                    </button>
                    
                    <!-- Request Revision Button (แจ้งแก้ไข) -->
                    <button onclick="showConfirmModal('revision', <?= $pub_id ?>)"
                            class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition duration-150 shadow-md">
                        <i class="fas fa-pencil-alt mr-2"></i> แจ้งแก้ไข (Request Revision)
                    </button>

                    <!-- Reject Button -->
                    <button onclick="showConfirmModal('reject', <?= $pub_id ?>)"
                            class="w-full py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg transition duration-150 shadow-md">
                        <i class="fas fa-times-circle mr-2"></i> ปฏิเสธ (Reject)
                    </button>
                    
                    <a href="manage_publications.php" class="text-center mt-4 text-sm text-gray-500 hover:text-gray-700">
                        <i class="fas fa-arrow-left mr-1"></i> กลับหน้ารายการ
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

</main>

<!-- Action Confirmation Modal -->
<div id="actionModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md transform transition-all">
        <h3 id="modalTitle" class="text-xl font-bold text-gray-800 mb-4 border-b pb-2"></h3>
        <p id="modalMessage" class="text-gray-600 mb-4"></p>
        
        <!-- Form for submission -->
        <form method="POST" id="actionForm">
            <input type="hidden" name="pub_id" id="modalPubId">
            <input type="hidden" name="action" id="modalActionType">
            
            <div id="commentField" class="hidden mb-4">
                <label for="comment" class="block text-sm font-medium text-gray-700">
                    ข้อคิดเห็น/เหตุผลประกอบการดำเนินการ:
                </label>
                <textarea id="comment" name="comment" rows="3" class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                <p class="text-xs text-gray-500 mt-1">
                    (จำเป็นต้องกรอกเมื่อ 'ปฏิเสธ' หรือ 'แจ้งแก้ไข')
                </p>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeModal()" 
                        class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-100 transition duration-150">
                    ยกเลิก
                </button>
                <button type="submit" id="confirmButton"
                        class="px-4 py-2 text-white font-semibold rounded-lg transition duration-150">
                    ยืนยัน
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('actionModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const modalPubId = document.getElementById('modalPubId');
    const modalActionType = document.getElementById('modalActionType');
    const confirmButton = document.getElementById('confirmButton');
    const commentField = document.getElementById('commentField');
    const commentTextarea = document.getElementById('comment');

    function showConfirmModal(action, pubId) {
        let title, message, btnClass;

        modalPubId.value = pubId;
        modalActionType.value = action;
        commentTextarea.required = false;
        commentField.classList.add('hidden');
        commentTextarea.value = '';

        switch (action) {
            case 'approve':
                title = "ยืนยันการอนุมัติผลงาน";
                message = `คุณกำลังจะอนุมัติผลงานหมายเลข #${pubId} ผลงานนี้จะถูกเผยแพร่สู่สาธารณะทันทีที่คุณยืนยัน`;
                btnClass = "bg-green-600 hover:bg-green-700";
                break;
            case 'reject':
                title = "ยืนยันการปฏิเสธผลงาน";
                message = `คุณกำลังจะปฏิเสธผลงานหมายเลข #${pubId} กรุณาระบุเหตุผลในการปฏิเสธเพื่อแจ้งให้อาจารย์ทราบ`;
                btnClass = "bg-red-600 hover:bg-red-700";
                commentField.classList.remove('hidden');
                commentTextarea.required = true;
                break;
            case 'revision':
                title = "ยืนยันการขอแก้ไขผลงาน";
                message = `คุณกำลังแจ้งให้อาจารย์แก้ไขผลงานหมายเลข #${pubId} ผลงานจะกลับไปอยู่ในสถานะ 'รอการแก้ไข' กรุณาแจ้งรายละเอียดที่ต้องแก้ไข`;
                btnClass = "bg-blue-600 hover:bg-blue-700";
                commentField.classList.remove('hidden');
                commentTextarea.required = true;
                break;
            default:
                return;
        }

        modalTitle.textContent = title;
        modalMessage.textContent = message;
        confirmButton.className = `px-4 py-2 text-white font-semibold rounded-lg transition duration-150 ${btnClass}`;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // ปิด Modal เมื่อคลิกนอกพื้นที่ Modal
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    // ปิด Modal เมื่อกดปุ่ม ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
    
    // Validate form on submit
    document.getElementById('actionForm').addEventListener('submit', function(event) {
        if (commentTextarea.required && commentTextarea.value.trim() === '') {
            alert('กรุณากรอกเหตุผลประกอบการดำเนินการ');
            event.preventDefault(); // หยุดการ Submit
        }
    });

</script>

</body>
</html>

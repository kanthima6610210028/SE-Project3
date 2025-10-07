<?php
/**
 * officer_users.php
 * หน้าจัดการผู้ใช้งาน (เพิ่ม/แก้ไข/ลบ อาจารย์) สำหรับเจ้าหน้าที่
 */
include('auth_config.php');

// ตรวจสอบสิทธิ์การเข้าถึง: ต้องเป็น Officer เท่านั้น
if (!check_user_role('Officer')) {
    header("Location: login.php");
    exit();
}

$message = get_session_message();
$users = [];
$error_message = '';

// =================================================================
// Logic จัดการฟอร์ม (Add/Edit User)
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? null;
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'Teacher'; // ค่าเริ่มต้นเป็น Teacher
    $password = $_POST['password'] ?? '';
    $new_password = $_POST['new_password'] ?? ''; // สำหรับการแก้ไข

    try {
        if ($action === 'add' && $full_name && $email) {
            // --- ADD USER ---
            if (!$password) {
                set_session_message('❌ กรุณากำหนดรหัสผ่านสำหรับผู้ใช้ใหม่', 'error');
                header("Location: officer_users.php");
                exit();
            }
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO users (FullName, Email, PasswordHash, UserRole) VALUES (?, ?, ?, ?)");
            $stmt->execute([$full_name, $email, $password_hash, $role]);
            set_session_message("✅ เพิ่มผู้ใช้งาน '{$full_name}' สำเร็จ", 'success');

        } elseif ($action === 'edit' && $user_id && $full_name && $email) {
            // --- EDIT USER ---
            $sql_parts = ["FullName = ?", "Email = ?", "UserRole = ?"];
            $params = [$full_name, $email, $role];

            // ตรวจสอบและอัปเดตรหัสผ่านใหม่ถ้ามีการกรอก
            if ($new_password) {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $sql_parts[] = "PasswordHash = ?";
                $params[] = $password_hash;
            }

            $sql = "UPDATE users SET " . implode(', ', $sql_parts) . " WHERE UserID = ?";
            $params[] = $user_id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            set_session_message("✅ แก้ไขข้อมูลผู้ใช้งาน '{$full_name}' สำเร็จ", 'success');
            
        } elseif ($action === 'delete' && $user_id) {
            // --- DELETE USER ---
            $stmt = $pdo->prepare("DELETE FROM users WHERE UserID = ?");
            $stmt->execute([$user_id]);
            set_session_message("✅ ลบผู้ใช้งาน UserID: {$user_id} สำเร็จ", 'success');
        }

    } catch (\PDOException $e) {
        // ตรวจสอบ Error: Duplicate entry for 'Email'
        if ($e->getCode() == '23000' && strpos($e->getMessage(), 'Duplicate entry') !== false) {
             set_session_message("❌ Email นี้มีผู้ใช้งานในระบบแล้ว กรุณาใช้อีเมลอื่น", 'error');
        } else {
             set_session_message("❌ เกิดข้อผิดพลาดในการจัดการผู้ใช้: " . $e->getMessage(), 'error');
        }
    }
    header("Location: officer_users.php");
    exit();
}

// =================================================================
// Logic ดึงข้อมูลผู้ใช้งานทั้งหมด
// =================================================================
try {
    // ดึงผู้ใช้ทั้งหมด โดยเรียงตามบทบาทและชื่อ
    $stmt = $pdo->query("SELECT UserID, FullName, Email, UserRole, LastLogin FROM users ORDER BY UserRole DESC, FullName ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $error_message = "❌ เกิดข้อผิดพลาดในการดึงรายการผู้ใช้งาน: " . $e->getMessage();
}

// Helper function สำหรับแสดงบทบาทเป็น Badge
function get_role_badge($role) {
    if ($role === 'Officer') {
        return '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">เจ้าหน้าที่</span>';
    } else {
        return '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">อาจารย์</span>';
    }
}

// Helper function สำหรับจัดรูปแบบวันที่
function format_thai_datetime($date_str) {
    if (!$date_str || $date_str === '0000-00-00 00:00:00') return "-";
    $timestamp = strtotime($date_str);
    return date('d/m/Y H:i', $timestamp);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้งาน - เจ้าหน้าที่</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', 'Tahoma', sans-serif; }
        .sidebar { min-height: 100vh; }
        .modal { background-color: rgba(0, 0, 0, 0.5); }
    </style>
</head>
<body class="bg-gray-100">

<div class="flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-gray-800 text-white p-6 sidebar shadow-xl hidden md:block">
        <div class="text-2xl font-bold mb-8 text-indigo-400">
            <i class="fas fa-tools"></i> Officer Portal
        </div>
        <nav class="space-y-3">
            <a href="officer_dashboard.php" class="flex items-center p-3 rounded-lg hover:bg-gray-700 transition duration-150">
                <i class="fas fa-tachometer-alt mr-3"></i> หน้าหลัก (Dashboard)
            </a>
            <a href="officer_review.php" class="flex items-center p-3 rounded-lg hover:bg-gray-700 transition duration-150">
                <i class="fas fa-clipboard-check mr-3"></i> ตรวจสอบผลงาน
            </a>
            <a href="officer_all_publications.php" class="flex items-center p-3 rounded-lg hover:bg-gray-700 transition duration-150">
                <i class="fas fa-list-alt mr-3"></i> รายการผลงานทั้งหมด
            </a>
            <a href="officer_reports.php" class="flex items-center p-3 rounded-lg hover:bg-gray-700 transition duration-150">
                <i class="fas fa-chart-line mr-3"></i> รายงานภาพรวมระบบ
            </a>
            <a href="officer_users.php" class="flex items-center p-3 rounded-lg bg-gray-700 font-bold text-white transition duration-150">
                <i class="fas fa-users mr-3"></i> จัดการผู้ใช้งาน
            </a>
        </nav>
        <div class="mt-8 pt-4 border-t border-gray-700">
            <a href="logout.php" class="flex items-center p-3 rounded-lg text-red-400 hover:bg-gray-700 transition duration-150">
                <i class="fas fa-sign-out-alt mr-3"></i> ออกจากระบบ
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-6 md:p-10">
        <h1 class="text-3xl font-extrabold text-gray-900 mb-8">
            <i class="fas fa-users text-indigo-600 mr-2"></i> จัดการผู้ใช้งานระบบ
        </h1>
        
        <?php if ($message): ?>
            <div class="p-4 rounded-lg mb-6 <?= $message['type'] === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : ($message['type'] === 'error' ? 'bg-red-100 border border-red-400 text-red-700' : 'bg-yellow-100 border border-yellow-400 text-yellow-700') ?>" role="alert">
                <?= $message['message'] ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="p-4 rounded-lg mb-6 bg-red-100 border border-red-400 text-red-700" role="alert">
                <?= $error_message ?>
            </div>
        <?php endif; ?>

        <!-- Top Actions -->
        <div class="flex justify-end mb-6">
            <button onclick="openAddModal()" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition duration-150 shadow-md">
                <i class="fas fa-plus mr-2"></i> เพิ่มผู้ใช้งานใหม่
            </button>
        </div>

        <!-- User Table -->
        <div class="bg-white p-6 rounded-xl shadow-2xl border-t-4 border-indigo-600">
            <h2 class="text-xl font-bold text-gray-800 mb-4 pb-2 border-b">
                <i class="fas fa-table mr-2 text-indigo-600"></i> รายการผู้ใช้งาน (<?= count($users) ?> คน)
            </h2>
            
            <?php if (empty($users)): ?>
                <div class="p-8 text-center text-gray-500 italic text-lg border border-dashed rounded-lg">
                    ไม่พบผู้ใช้งานในระบบ
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-[5%]">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-[30%]">ชื่อ-นามสกุล</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-[30%]">อีเมล</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-[15%]">บทบาท</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-[15%]">เข้าสู่ระบบล่าสุด</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-[10%]">ดำเนินการ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50 transition duration-100" data-user='<?= json_encode($user) ?>'>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= $user['UserID'] ?></td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($user['FullName']) ?></td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($user['Email']) ?></td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-center">
                                    <?= get_role_badge($user['UserRole']) ?>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    <?= format_thai_datetime($user['LastLogin']) ?>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-center text-sm font-medium space-x-2">
                                    <button onclick="openEditModal(this)" class="text-indigo-600 hover:text-indigo-900 transition duration-150 text-sm">
                                        <i class="fas fa-edit mr-1"></i> แก้ไข
                                    </button>
                                    <?php if ($user['UserRole'] !== 'Officer'): // ป้องกันการลบเจ้าหน้าที่หลัก ?>
                                        <button onclick="confirmDelete(<?= $user['UserID'] ?>, '<?= htmlspecialchars($user['FullName'], ENT_QUOTES) ?>')" 
                                                class="text-red-600 hover:text-red-900 transition duration-150 text-sm">
                                            <i class="fas fa-trash-alt mr-1"></i> ลบ
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Add/Edit Modal -->
<div id="userModal" class="modal fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 relative">
        <h3 id="modalTitle" class="text-2xl font-bold mb-4 border-b pb-2 text-indigo-700">เพิ่มผู้ใช้งานใหม่</h3>
        <button onclick="closeModal()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-800">
            <i class="fas fa-times fa-lg"></i>
        </button>
        
        <form method="POST" action="officer_users.php">
            <input type="hidden" name="action" id="actionInput" value="add">
            <input type="hidden" name="user_id" id="userIdInput">

            <div class="space-y-4">
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700">ชื่อ-นามสกุล</label>
                    <input type="text" id="full_name" name="full_name" required
                           class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">อีเมล (ใช้สำหรับ Login)</label>
                    <input type="email" id="email" name="email" required
                           class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div id="roleField" class="hidden">
                    <label for="role" class="block text-sm font-medium text-gray-700">บทบาท</label>
                    <select id="role" name="role" required
                            class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="Teacher">อาจารย์ (Teacher)</option>
                        <option value="Officer">เจ้าหน้าที่ (Officer)</option>
                    </select>
                </div>

                <!-- Password Fields for Add/Edit -->
                <div id="addPasswordField">
                    <label for="password" class="block text-sm font-medium text-gray-700">รหัสผ่าน (สำหรับผู้ใช้ใหม่)</label>
                    <input type="password" id="password" name="password" 
                           class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div id="editPasswordField" class="hidden">
                    <label for="new_password" class="block text-sm font-medium text-gray-700">รหัสผ่านใหม่ (กรอกเพื่อเปลี่ยน)</label>
                    <input type="password" id="new_password" name="new_password" 
                           class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-150">ยกเลิก</button>
                <button type="submit" id="submitButton" class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition duration-150">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form (Hidden) -->
<form method="POST" action="officer_users.php" id="deleteForm" class="hidden">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="user_id" id="deleteUserId">
</form>

<script>
    const userModal = document.getElementById('userModal');
    const modalTitle = document.getElementById('modalTitle');
    const actionInput = document.getElementById('actionInput');
    const userIdInput = document.getElementById('userIdInput');
    const fullNameInput = document.getElementById('full_name');
    const emailInput = document.getElementById('email');
    const roleInput = document.getElementById('role');
    const roleField = document.getElementById('roleField');
    const passwordField = document.getElementById('addPasswordField');
    const passwordInput = document.getElementById('password');
    const editPasswordField = document.getElementById('editPasswordField');
    const newPasswordInput = document.getElementById('new_password');

    function openModal() {
        userModal.classList.remove('hidden');
    }

    function closeModal() {
        userModal.classList.add('hidden');
        // Reset form to default (Add mode)
        document.querySelector('form').reset();
        openAddModal(false); // เรียกใช้ Add mode เพื่อรีเซ็ตโครงสร้าง Modal
    }

    function openAddModal(shouldOpen = true) {
        modalTitle.textContent = 'เพิ่มผู้ใช้งานใหม่';
        actionInput.value = 'add';
        userIdInput.value = '';
        
        // Show/Hide fields specific to Add
        passwordField.classList.remove('hidden');
        passwordInput.setAttribute('required', 'required');
        editPasswordField.classList.add('hidden');
        newPasswordInput.removeAttribute('required');
        
        // Always show Role for adding new users
        roleField.classList.remove('hidden');

        if (shouldOpen) {
            openModal();
        }
    }

    function openEditModal(buttonElement) {
        const row = buttonElement.closest('tr');
        const userData = JSON.parse(row.dataset.user);
        
        modalTitle.textContent = 'แก้ไขข้อมูลผู้ใช้งาน: ' + userData.FullName;
        actionInput.value = 'edit';
        userIdInput.value = userData.UserID;
        
        // Populate fields
        fullNameInput.value = userData.FullName;
        emailInput.value = userData.Email;
        roleInput.value = userData.UserRole;

        // Show/Hide fields specific to Edit
        passwordField.classList.add('hidden');
        passwordInput.removeAttribute('required');
        editPasswordField.classList.remove('hidden');
        newPasswordInput.value = ''; // Clear new password field

        // Prevent changing role if user is an Officer (Self-protection)
        if (userData.UserRole === 'Officer') {
            roleField.classList.add('hidden');
        } else {
            roleField.classList.remove('hidden');
        }
        
        openModal();
    }

    function confirmDelete(userId, fullName) {
        if (window.confirm(`⚠️ คุณแน่ใจหรือไม่ว่าต้องการลบผู้ใช้งาน "${fullName}" (ID: ${userId})? การดำเนินการนี้ไม่สามารถย้อนกลับได้`)) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteForm').submit();
        }
    }
</script>

</body>
</html>

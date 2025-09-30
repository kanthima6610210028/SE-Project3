<?php
// admin_edit_user.php
// หน้าสำหรับ Admin แก้ไขข้อมูลผู้ใช้งาน

require_once 'auth_config.php';
require_role(['Admin']);

$user_id_to_edit = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = null;

if ($user_id_to_edit <= 0) {
    set_session_message('error', 'รหัสผู้ใช้ไม่ถูกต้อง');
    header('Location: admin_user_management.php');
    exit;
}

// =================================================================
// Logic 1: จัดการการอัปเดตข้อมูล (POST Request)
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)$_POST['user_id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'];

    // Validation
    if (empty($username) || empty($email) || empty($role)) {
        set_session_message('error', 'กรุณากรอกข้อมูลให้ครบถ้วน');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_session_message('error', 'รูปแบบอีเมลไม่ถูกต้อง');
    } else {
        try {
            // ตรวจสอบอีเมลซ้ำ
            $stmt_check = $pdo->prepare("SELECT User_Id FROM users WHERE UserEmail = ? AND User_Id != ?");
            $stmt_check->execute([$email, $user_id]);
            if ($stmt_check->fetch()) {
                set_session_message('error', 'อีเมลนี้มีผู้ใช้งานอื่นในระบบแล้ว');
            } else {
                // สร้าง Query สำหรับอัปเดต
                if (!empty($password)) {
                    // ถ้ามีการกรอกรหัสผ่านใหม่ (TODO: ควร Hash รหัสผ่านในระบบจริง)
                    $sql = "UPDATE users SET UserName = ?, UserEmail = ?, UserRole = ?, UserPass = ? WHERE User_Id = ?";
                    $params = [$username, $email, $role, $password, $user_id];
                } else {
                    // ถ้าไม่ต้องการเปลี่ยนรหัสผ่าน
                    $sql = "UPDATE users SET UserName = ?, UserEmail = ?, UserRole = ? WHERE User_Id = ?";
                    $params = [$username, $email, $role, $user_id];
                }

                $stmt_update = $pdo->prepare($sql);
                $stmt_update->execute($params);

                set_session_message('success', "✅ อัปเดตข้อมูลผู้ใช้ '{$username}' สำเร็จแล้ว");
                header('Location: admin_user_management.php');
                exit;
            }
        } catch (PDOException $e) {
            set_session_message('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
    // Redirect กลับไปหน้าเดิมกรณี error
    header('Location: admin_edit_user.php?id=' . $user_id_to_edit);
    exit;
}

// =================================================================
// Logic 2: ดึงข้อมูลผู้ใช้เดิมมาแสดง (GET Request)
// =================================================================
try {
    $stmt = $pdo->prepare("SELECT User_Id, UserName, UserEmail, UserRole FROM users WHERE User_Id = ?");
    $stmt->execute([$user_id_to_edit]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        set_session_message('error', 'ไม่พบผู้ใช้ที่ต้องการแก้ไข');
        header('Location: admin_user_management.php');
        exit;
    }
} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูลผู้ใช้: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลผู้ใช้ - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', 'Tahoma', sans-serif; background-color: #f4f7f9; }
    </style>
</head>
<body class="min-h-screen">
<div class="flex">
    <?php include('admin_sidebar.php'); ?>

    <main class="flex-1 ml-64 p-6 md:p-10">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-user-edit text-indigo-600 mr-2"></i> แก้ไขข้อมูลผู้ใช้
            </h1>
            <a href="admin_user_management.php" class="bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-lg hover:bg-gray-300">
                <i class="fas fa-arrow-left mr-2"></i> กลับไปหน้ารายการ
            </a>
        </div>

        <?php 
        $message = get_session_message();
        if ($message): 
            $alert_class = $message['type'] === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700';
        ?>
            <div class="mb-6 p-4 rounded-lg <?= $alert_class; ?>" role="alert">
                <?= htmlspecialchars($message['message']); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-xl shadow-lg max-w-2xl mx-auto">
            <form action="admin_edit_user.php?id=<?= $user['User_Id'] ?>" method="POST">
                <input type="hidden" name="user_id" value="<?= $user['User_Id'] ?>">
                <div class="space-y-6">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">ชื่อ-นามสกุล</label>
                        <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['UserName']) ?>" required
                               class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">อีเมล (สำหรับเข้าระบบ)</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['UserEmail']) ?>" required
                               class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700">บทบาท (Role)</label>
                        <select id="role" name="role" required
                                class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                            <?php 
                            $roles = ['Admin', 'Officer', 'Teacher', 'Student'];
                            foreach ($roles as $role): 
                            ?>
                                <option value="<?= $role ?>" <?= ($user['UserRole'] === $role) ? 'selected' : '' ?>>
                                    <?= $role ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="border-t pt-6">
                        <label for="password" class="block text-sm font-medium text-gray-700">ตั้งรหัสผ่านใหม่</label>
                        <input type="password" id="password" name="password"
                               class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <p class="mt-1 text-xs text-gray-500">เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน</p>
                    </div>
                </div>
                <div class="mt-8 text-right">
                    <button type="submit" class="bg-indigo-600 text-white font-semibold py-2 px-6 rounded-lg hover:bg-indigo-700 transition duration-150">
                        <i class="fas fa-save mr-2"></i> บันทึกการเปลี่ยนแปลง
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>
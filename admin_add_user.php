<?php
// admin_add_user.php
// หน้าสำหรับ Admin เพิ่มผู้ใช้งานใหม่

require_once 'auth_config.php';
require_role(['Admin']);

// =================================================================
// Logic การจัดการฟอร์ม (POST Request)
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; // รับรหัสผ่านเป็น plain text
    $role = $_POST['role'];

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($role)) {
        set_session_message('error', 'กรุณากรอกข้อมูลให้ครบทุกช่อง');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_session_message('error', 'รูปแบบอีเมลไม่ถูกต้อง');
    } else {
        try {
            // 1. ตรวจสอบว่าอีเมลนี้ถูกใช้ไปแล้วหรือยัง
            $stmt_check = $pdo->prepare("SELECT User_Id FROM users WHERE UserEmail = ?");
            $stmt_check->execute([$email]);
            
            if ($stmt_check->fetch()) {
                set_session_message('error', 'อีเมลนี้มีผู้ใช้งานในระบบแล้ว');
            } else {
                // 2. เพิ่มผู้ใช้ใหม่ลงในฐานข้อมูล (เก็บรหัสผ่านเป็น Plain Text ตามคำขอ)
                $stmt_insert = $pdo->prepare(
                    "INSERT INTO users (UserName, UserEmail, UserPass, UserRole) VALUES (?, ?, ?, ?)"
                );
                // ส่งตัวแปร $password เข้าไปโดยตรง ไม่ผ่านการ hash
                $stmt_insert->execute([$username, $email, $password, $role]);
                $new_user_id = $pdo->lastInsertId();

                set_session_message('success', "✅ เพิ่มผู้ใช้ '{$username}' สำเร็จแล้ว");
                header('Location: admin_user_management.php');
                exit;
            }
        } catch (PDOException $e) {
            set_session_message('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
    header('Location: admin_add_user.php');
    exit;
}

$message = get_session_message();

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มผู้ใช้ใหม่ - Admin</title>
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
                <i class="fas fa-user-plus text-indigo-600 mr-2"></i> เพิ่มผู้ใช้ใหม่
            </h1>
            <a href="admin_user_management.php" class="bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-lg hover:bg-gray-300">
                <i class="fas fa-arrow-left mr-2"></i> กลับไปหน้ารายการ
            </a>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?= $message['type'] === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>" role="alert">
                <?= htmlspecialchars($message['message']); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-xl shadow-lg max-w-2xl mx-auto">
            <form action="admin_add_user.php" method="POST">
                <div class="space-y-6">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">ชื่อ-นามสกุล</label>
                        <input type="text" id="username" name="username" required
                               class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">อีเมล (สำหรับเข้าระบบ)</label>
                        <input type="email" id="email" name="email" required
                               class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                     <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">รหัสผ่าน</label>
                        <input type="password" id="password" name="password" required
                               class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700">บทบาท (Role)</label>
                        <select id="role" name="role" required
                                class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="" disabled selected>-- เลือกบทบาท --</option>
                            <option value="Admin">Admin</option>
                            <option value="Officer">Officer</option>
                            <option value="Teacher">Teacher</option>
                        </select>
                    </div>
                </div>
                <div class="mt-8 text-right">
                    <button type="submit" class="bg-indigo-600 text-white font-semibold py-2 px-6 rounded-lg hover:bg-indigo-700 transition duration-150">
                        <i class="fas fa-plus-circle mr-2"></i> เพิ่มผู้ใช้งาน
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>
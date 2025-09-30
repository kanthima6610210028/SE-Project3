<?php
// profile.php
// (ฉบับแก้ไข: เปลี่ยนเป็น Header Layout)

require_once 'auth_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// =================================================================
// Logic 1: จัดการการอัปเดตข้อมูลส่วนตัว (Update Profile)
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);

    if (empty($new_username) || empty($new_email)) {
        set_session_message('error', 'กรุณากรอกชื่อและอีเมลให้ครบถ้วน');
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        set_session_message('error', 'รูปแบบอีเมลไม่ถูกต้อง');
    } else {
        try {
            $stmt_check = $pdo->prepare("SELECT User_Id FROM users WHERE UserEmail = ? AND User_Id != ?");
            $stmt_check->execute([$new_email, $user_id]);
            
            if ($stmt_check->fetch()) {
                set_session_message('error', 'อีเมลนี้มีผู้ใช้งานอื่นในระบบแล้ว');
            } else {
                $stmt_update = $pdo->prepare("UPDATE users SET UserName = ?, UserEmail = ? WHERE User_Id = ?");
                $stmt_update->execute([$new_username, $new_email, $user_id]);
                
                $_SESSION['user_name'] = $new_username;
                set_session_message('success', '✅ อัปเดตข้อมูลส่วนตัวสำเร็จ');
            }
        } catch (PDOException $e) {
            set_session_message('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
    header('Location: profile.php');
    exit;
}

// =================================================================
// Logic 2: จัดการการเปลี่ยนรหัสผ่าน (Change Password)
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        set_session_message('error', 'กรุณากรอกข้อมูลรหัสผ่านให้ครบทุกช่อง');
    } elseif ($new_password !== $confirm_password) {
        set_session_message('error', 'รหัสผ่านใหม่และการยืนยันไม่ตรงกัน');
    } else {
        try {
            $stmt_pass = $pdo->prepare("SELECT UserPass FROM users WHERE User_Id = ?");
            $stmt_pass->execute([$user_id]);
            $user = $stmt_pass->fetch(PDO::FETCH_ASSOC);

            if ($user && $user['UserPass'] === $current_password) {
                $stmt_update_pass = $pdo->prepare("UPDATE users SET UserPass = ? WHERE User_Id = ?");
                $stmt_update_pass->execute([$new_password, $user_id]);
                set_session_message('success', '✅ เปลี่ยนรหัสผ่านสำเร็จแล้ว');
            } else {
                set_session_message('error', 'รหัสผ่านปัจจุบันไม่ถูกต้อง');
            }
        } catch (PDOException $e) {
            set_session_message('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
    header('Location: profile.php');
    exit;
}

// ดึงข้อมูลผู้ใช้ปัจจุบัน
try {
    $stmt_user = $pdo->prepare("SELECT UserName, UserEmail, Created_at FROM users WHERE User_Id = ?");
    $stmt_user->execute([$user_id]);
    $current_user = $stmt_user->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("ไม่สามารถดึงข้อมูลผู้ใช้ได้: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลส่วนตัว - <?= htmlspecialchars($current_user['UserName']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style> body { font-family: 'Inter', 'Tahoma', sans-serif; } </style>
</head>
<body class="bg-gray-100">

    <header class="bg-gray-800 text-gray-100 shadow-lg sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center p-4">
            <?php if ($user_role === 'Admin'): ?>
                <a href="admin_dashboard.php" class="text-xl font-bold"><i class="fas fa-cogs mr-2"></i> ADMIN PANEL</a>
                <nav class="hidden md:flex space-x-4 items-center">
                    <a href="admin_dashboard.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">หน้าแรก</a>
                    <a href="admin_manage_publications.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">จัดการสิ่งพิมพ์</a>
                    <a href="admin_user_management.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">จัดการผู้ใช้</a>
                    <a href="admin_manage_types.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">จัดการประเภท</a>
                    <a href="admin_audit_log.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">บันทึกตรวจสอบ</a>
                    <a href="logout.php" class="px-3 py-2 text-sm rounded-md text-red-400 hover:bg-red-700">ออกจากระบบ</a>
                </nav>
            <?php elseif ($user_role === 'Officer'): ?>
                
                <a href="officer_dashboard.php" class="text-xl font-bold"><i class="fas fa-tools mr-2"></i> Officer Portal</a>
                <nav class="hidden md:flex space-x-4 items-center">
                    <a href="officer_dashboard.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">หน้าหลัก</a>
                    <a href="officer_review.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">ตรวจสอบผลงาน</a>
                    <a href="officer_all_publications.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">ผลงานทั้งหมด</a>
                    <a href="officer_reports.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700 ">รายงาน</a>
                    <a href="profile.php" class="px-3 py-2 text-sm rounded-md bg-gray-700 bg-gray-900">ข้อมูลส่วนตัว</a>
                    <a href="logout.php" class="px-3 py-2 text-sm rounded-md text-red-400 hover:bg-red-700">ออกจากระบบ</a>
                </nav>
            
            <?php elseif ($user_role === 'Teacher'): ?>
                <a href="teacher_dashboard.php" class="text-xl font-bold"><i class="fas fa-graduation-cap mr-2"></i> Teacher Portal</a>
                <nav class="hidden md:flex space-x-4 items-center">
                    <a href="teacher_dashboard.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">หน้าหลัก</a>
                    <a href="teacher_publications.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">ผลงานของฉัน</a>
                    <a href="add_publication.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">ส่งผลงานใหม่</a>
                    <a href="profile.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700 bg-gray-900">ข้อมูลส่วนตัว</a>
                    <a href="teacher_reports.php" class="px-3 py-2 text-sm rounded-md hover:bg-gray-700">รายงานผลงาน</a>
                    <a href="logout.php" class="px-3 py-2 text-sm rounded-md text-red-400 hover:bg-red-700">ออกจากระบบ</a>
                </nav>
            <?php endif; ?>
        </div>
    </header>

    <main class="container mx-auto p-6 md:p-10">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">
            <i class="fas fa-user-edit text-indigo-600 mr-2"></i> ข้อมูลส่วนตัว
        </h1>

        <?php 
        $message = get_session_message();
        if ($message): 
            $alert_class = $message['type'] === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700';
        ?>
            <div class="mb-6 p-4 rounded-lg shadow-md border <?= $alert_class; ?>" role="alert">
                <?= htmlspecialchars($message['message']); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-3">แก้ไขข้อมูลทั่วไป</h2>
                <form action="profile.php" method="POST">
                    <div class="space-y-4">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700">ชื่อ-นามสกุล</label>
                            <input type="text" id="username" name="username" value="<?= htmlspecialchars($current_user['UserName']) ?>" class="mt-1 w-full p-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">อีเมล (สำหรับเข้าระบบ)</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($current_user['UserEmail']) ?>" class="mt-1 w-full p-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">วันที่สมัคร</label>
                            <p class="text-gray-500 mt-1"><?= date('d F Y', strtotime($current_user['Created_at'])) ?></p>
                        </div>
                    </div>
                    <div class="mt-6 text-right">
                        <button type="submit" name="update_profile" class="bg-indigo-600 text-white font-semibold py-2 px-6 rounded-lg hover:bg-indigo-700">
                            <i class="fas fa-save mr-2"></i> บันทึกข้อมูล
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-3">เปลี่ยนรหัสผ่าน</h2>
                <form action="profile.php" method="POST">
                    <div class="space-y-4">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700">รหัสผ่านปัจจุบัน</label>
                            <input type="password" id="current_password" name="current_password" required class="mt-1 w-full p-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700">รหัสผ่านใหม่</label>
                            <input type="password" id="new_password" name="new_password" required class="mt-1 w-full p-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700">ยืนยันรหัสผ่านใหม่</label>
                            <input type="password" id="confirm_password" name="confirm_password" required class="mt-1 w-full p-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>
                     <div class="mt-6 text-right">
                        <button type="submit" name="change_password" class="bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg hover:bg-gray-800">
                            <i class="fas fa-key mr-2"></i> เปลี่ยนรหัสผ่าน
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
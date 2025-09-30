<?php
/**
 * login.php
 * หน้าเข้าสู่ระบบ (Login)
 * (ฉบับแก้ไข: รองรับทั้ง Hashed และ Plain Text Password)
 */
include('auth_config.php');
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error_message = 'กรุณากรอกอีเมลและรหัสผ่านให้ครบถ้วน';
    } else {
        try {
            // --== ส่วนที่แก้ไข ==--
            // 1. ดึงข้อมูลผู้ใช้ทั้งหมดโดยใช้ Email เพียงอย่างเดียว
            $sql = "SELECT User_Id, UserName, UserPass, UserRole FROM users WHERE UserEmail = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $login_success = false;
            if ($user) {
                // 2. ตรวจสอบรหัสผ่าน
                // ขั้นตอนที่ 1: ลองตรวจสอบแบบ Hashed ก่อน
                if (password_verify($password, $user['UserPass'])) {
                    $login_success = true;
                } 
                // ขั้นตอนที่ 2: ถ้าแบบ Hashed ไม่ผ่าน, ลองตรวจสอบแบบ Plain Text (สำหรับผู้ใช้เก่า)
                elseif ($user['UserPass'] === $password) {
                    $login_success = true;
                }
            }

            if ($login_success) {
                // 3. ตั้งค่า Session เมื่อล็อกอินสำเร็จ
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user['User_Id'];
                $_SESSION['user_name'] = $user['UserName']; // เพิ่มการตั้งค่าชื่อผู้ใช้
                $_SESSION['user_role'] = $user['UserRole'];

                // 4. Redirect ไปยัง Dashboard ที่ถูกต้อง
                $redirect_page = get_user_role_dashboard($user['UserRole']);
                header("Location: " . $redirect_page);
                exit();
            } else {
                $error_message = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
            }
            // --== จบส่วนแก้ไข ==--

        } catch (\PDOException $e) {
            $error_message = 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล';
            error_log("Login Error: " . $e->getMessage());
        }
    }
}

// ถ้าผู้ใช้ล็อกอินอยู่แล้ว ให้ Redirect
if (is_logged_in()) {
    header("Location: " . get_user_role_dashboard($_SESSION['user_role']));
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - PubTracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', 'Tahoma', sans-serif; background-color: #f7f9fb; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">

    <?php 
    $message = get_session_message();
    if ($message): 
        $alert_class = $message['type'] === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700';
    ?>
        <div class="fixed top-5 left-1/2 transform -translate-x-1/2 w-full max-w-lg p-3 rounded-lg shadow-lg border <?= $alert_class; ?>" role="alert">
            <i class="fas fa-info-circle mr-2"></i> <?= htmlspecialchars($message['message']); ?>
        </div>
    <?php endif; ?>

<div class="w-full max-w-md p-8 space-y-6 bg-white rounded-xl shadow-2xl border">
    <div class="text-center">
        <a href="index.php" class="text-3xl font-extrabold text-indigo-700">
            <i class="fas fa-graduation-cap mr-2"></i> PubTracker
        </a>
        <h2 class="mt-6 text-2xl font-bold text-gray-900">เข้าสู่ระบบผู้ใช้งาน</h2>
        <p class="mt-2 text-sm text-gray-500">กรุณาเข้าสู่ระบบเพื่อดำเนินการต่อ</p>
    </div>

    <?php if ($error_message): ?>
        <div class="p-3 bg-red-100 text-red-700 border border-red-300 rounded-lg text-sm" role="alert">
            <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <form class="space-y-6" method="POST" action="login.php">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">อีเมล</label>
            <input id="email" name="email" type="email" autocomplete="email" required 
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                   placeholder="เช่น user@example.com">
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required 
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                   placeholder="••••••••">
        </div>
        <div>
            <button type="submit" 
                    class="w-full flex justify-center py-3 px-4 rounded-lg shadow-md font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <i class="fas fa-lock mr-2"></i> เข้าสู่ระบบ
            </button>
        </div>
    </form>

    <div class="text-center text-sm text-gray-500">
        <a href="index.php" class="font-medium text-indigo-600 hover:text-indigo-500">
            <i class="fas fa-arrow-left mr-1"></i> กลับสู่หน้าแรก
        </a>
    </div>
</div>

</body>
</html>
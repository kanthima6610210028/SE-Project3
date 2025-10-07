<?php
// admin_user_management.php
// (โค้ด PHP ส่วนบนเหมือนเดิม)
require_once 'auth_config.php';
require_role(['Admin']);

$stmt = $pdo->query("SELECT User_Id, UserName, UserEmail, UserRole FROM users ORDER BY User_Id ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้งาน - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', 'Tahoma', sans-serif; background-color: #f4f7f9; }
    </style>
</head>
<body class="min-h-screen">

    <header class="bg-gray-800 text-gray-100 shadow-lg sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center p-4">
            <a href="admin_dashboard.php" class="text-xl font-bold"><i class="fas fa-cogs mr-2"></i> ADMIN PANEL</a>
            <nav class="hidden md:flex space-x-4 items-center">
                <a href="admin_dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">หน้าแรก</a>
                <a href="admin_manage_publications.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">จัดการสิ่งพิมพ์</a>
                <a href="admin_user_management.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700 bg-gray-900">จัดการผู้ใช้</a>
                <a href="admin_manage_types.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">จัดการประเภท</a>
                <a href="admin_audit_log.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">บันทึกตรวจสอบ</a>
                <a href="logout.php" class="px-3 py-2 rounded-md text-sm font-medium text-red-400 hover:bg-red-700">ออกจากระบบ</a>
            </nav>
        </div>
    </header>

    <main class="container mx-auto p-6 md:p-10">
        
        <?php 
        $message = get_session_message();
        if ($message): 
            $alert_class = $message['type'] === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700';
        ?>
            <div class="mb-6 p-4 rounded-lg shadow-md border <?= $alert_class; ?>" role="alert">
                <i class="fas fa-info-circle mr-2"></i> <?= htmlspecialchars($message['message']); ?>
            </div>
        <?php endif; ?>

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-users-cog text-indigo-600 mr-2"></i> จัดการผู้ใช้งาน
            </h1>
            <a href="admin_add_user.php" class="bg-indigo-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-indigo-700 shadow-md">
                <i class="fas fa-plus mr-2"></i> เพิ่มผู้ใช้ใหม่
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-lg overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-4 font-semibold text-gray-600">ชื่อผู้ใช้งาน</th>
                        <th class="p-4 font-semibold text-gray-600">อีเมล / ล็อกอิน</th>
                        <th class="p-4 font-semibold text-gray-600">บทบาท (ROLE)</th>
                        <th class="p-4 font-semibold text-gray-600">การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="4" class="text-center p-6 text-gray-500">
                                <i class="fas fa-info-circle mr-2"></i> ยังไม่มีผู้ใช้งานในระบบ
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr class="border-t hover:bg-gray-50">
                                <td class="p-4 font-medium text-gray-800"><?= htmlspecialchars($user['UserName']) ?></td>
                                <td class="p-4 text-gray-600"><?= htmlspecialchars($user['UserEmail']) ?></td>
                                <td class="p-4 text-gray-600"><?= htmlspecialchars($user['UserRole']) ?></td>
                                <td class="p-4 space-x-2">
                                    <a href="admin_edit_user.php?id=<?= $user['User_Id'] ?>" class="text-blue-600 hover:text-blue-800 font-medium">
                                        <i class="fas fa-edit"></i> แก้ไข
                                    </a>
                                    <a href="admin_delete_user.php?id=<?= $user['User_Id'] ?>" class="text-red-600 hover:text-red-800 font-medium" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบผู้ใช้นี้?');">
                                        <i class="fas fa-trash-alt"></i> ลบ
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("db.php");

try {
    $stmt = $pdo->query("SELECT NOW() as nowtime");
    $row = $stmt->fetch();
    echo "เชื่อมต่อสำเร็จ! เวลาฐานข้อมูล: " . $row['nowtime'];
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
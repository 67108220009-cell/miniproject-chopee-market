<?php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'consign_db';

echo "<h2>ระบบตรวจสอบการเชื่อมต่อฐานข้อมูล</h2>";
echo "กำลังทดสอบเชื่อมต่อ Host: $db_host, Database: $db_name ...<br><br>";

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h3 style='color:green;'>✅ เชื่อมต่อฐานข้อมูล MySQL สำเร็จ! (Backend ปกติ)</h3>";
    echo "<p>ถ้าหน้านี้ขึ้นสีเขียว แต่หน้าเว็บยังใช้ไม่ได้ แปลว่าปัญหาอยู่ที่ <b>ข้อ 1 (การเปิดไฟล์)</b> หรือ <b>ข้อ 2 (พาท API_URL ไม่ตรง)</b> ครับ</p>";
} catch (PDOException $e) {
    echo "<h3 style='color:red;'>❌ เชื่อมต่อไม่สำเร็จ: " . $e->getMessage() . "</h3>";
    echo "<b>การแก้ไขที่เป็นไปได้:</b><ul>";

    $error = $e->getMessage();
    if (strpos($error, 'Unknown database') !== false) {
        echo "<li>คุณยังไม่ได้สร้างฐานข้อมูลชื่อ <b>$db_name</b> ใน phpMyAdmin</li>";
    } else if (strpos($error, 'Access denied') !== false) {
        echo "<li>รหัสผ่านของฐานข้อมูลผิด (ปกติ XAMPP จะปล่อยว่าง แต่ของคุณอาจจะมีการตั้งรหัสผ่านไว้ ให้ไปแก้ที่ไฟล์ api.php บรรทัดที่ 10)</li>";
    } else if (strpos($error, 'Connection refused') !== false || strpos($error, 'No connection could be made') !== false) {
        echo "<li>คุณยังไม่ได้กด Start ตัว <b>MySQL</b> ในโปรแกรม XAMPP Control Panel</li>";
    }
    echo "</ul>";
}

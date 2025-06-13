<?php
$servername = "localhost"; // أو 127.0.0.1
$username = "root";        // اسم مستخدم قاعدة البيانات الخاص بك
$password = "";            // كلمة مرور قاعدة البيانات الخاصة بك
$dbname = "car_rental_db";

// إنشاء الاتصال
$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// ضبط الترميز لضمان دعم اللغة العربية بشكل صحيح
$conn->set_charset("utf8mb4");
?>
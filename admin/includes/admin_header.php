<?php
// admin/includes/admin_header.php
// المسار إلى ملفات functions.php و db_connect.php الرئيسية
require_once dirname(__DIR__, 2) . '/includes/functions.php'; // ../../includes/functions.php
require_once dirname(__DIR__, 2) . '/includes/db_connect.php'; // ../../includes/db_connect.php

// التحقق من جلسة المسؤول (هذا مهم جداً)
require_once __DIR__ . '/admin_session_check.php'; // موجود في نفس المجلد
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'لوحة تحكم المسؤول'; ?> - تأجير سيارات</title>
    <!-- مسار CSS الرئيسي للموقع -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- مسار CSS الخاص بلوحة التحكم -->
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <!-- يمكنك إضافة مكتبات JS/CSS خاصة بالادمن هنا، مثل DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
</head>
<body class="admin-body">
    <header class="admin-header">
        <div class="admin-container">
            <div class="admin-logo">
                <a href="index.php">لوحة تحكم المسؤول</a>
            </div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="index.php">الرئيسية</a></li>
                    <li><a href="manage_cars.php">إدارة السيارات</a></li>
                    <li><a href="manage_bookings.php">إدارة الحجوزات</a></li>
                    <li><a href="manage_users.php">إدارة المستخدمين</a></li>
                    <li><a href="manage_locations.php">إدارة المواقع</a></li> <!-- صفحة إضافية مقترحة -->
                    <li><a href="manage_categories.php">إدارة الفئات</a></li> <!-- صفحة إضافية مقترحة -->
                    <li><a href="../logout.php">تسجيل الخروج (<?php echo htmlspecialchars($_SESSION['user_name']); ?>)</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main class="admin-main">
        <div class="admin-container">
        <?php
        // عرض رسائل الخطأ أو النجاح العامة إذا كانت موجودة في الجلسة
        if (isset($_SESSION['admin_message'])) {
            echo '<div class="admin-alert admin-alert-info">' . htmlspecialchars($_SESSION['admin_message']) . '</div>';
            unset($_SESSION['admin_message']);
        }
        if (isset($_SESSION['admin_error'])) {
            echo '<div class="admin-alert admin-alert-danger">' . htmlspecialchars($_SESSION['admin_error']) . '</div>';
            unset($_SESSION['admin_error']);
        }
        ?>
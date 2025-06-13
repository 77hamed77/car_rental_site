<?php
// admin/includes/admin_session_check.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// التحقق من تسجيل الدخول ومن دور المسؤول
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // إذا لم يكن مسجلاً كمسؤول، قم بتوجيهه لصفحة تسجيل الدخول الرئيسية
    // أو لصفحة خطأ "غير مصرح به"
    $_SESSION['error_message'] = "غير مصرح لك بالوصول لهذه الصفحة.";
    header("Location: ../login.php"); // العودة إلى صفحة تسجيل الدخول الرئيسية
    exit();
}

// دالة للتحقق من الدور (يمكن استخدامها لاحقاً إذا كان هناك أدوار إدارية فرعية)
// function isAdmin() {
//     // return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
// }
?>
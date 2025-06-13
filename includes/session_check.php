<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// إذا لم يكن المستخدم مسجل الدخول، قم بتوجيهه إلى صفحة تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    // يمكنك حفظ الصفحة الحالية لإعادة التوجيه إليها بعد تسجيل الدخول
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// يمكنك إضافة تحقق من دور المستخدم هنا إذا لزم الأمر لصفحات معينة
// مثال: التحقق لدور المسؤول
/*
function require_admin() {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        // يمكن توجيهه لصفحة خطأ أو الصفحة الرئيسية
        header("Location: ../index.php"); // افترض أننا في مجلد admin
        exit();
    }
}
*/
?>
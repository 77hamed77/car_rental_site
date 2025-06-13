<?php
// public/index.php

// تحديد المسار الأساسي للمشروع
define('BASE_PATH', dirname(__DIR__)); // يشير إلى مجلد car_rental

// تضمين الملفات الأساسية
require_once BASE_PATH . '/includes/db_connect.php';
require_once BASE_PATH . '/includes/functions.php';

// هذا مثال بسيط جداً. في نظام توجيه حقيقي، ستقوم بتحليل REQUEST_URI
// وتضمين الملف المناسب أو استدعاء الدالة/الكلاس المناسب.

$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME']; // /public/index.php

// إزالة اسم السكربت من بداية URI إذا كان موجودًا
if (strpos($request_uri, $script_name) === 0) {
    $request_uri = substr($request_uri, strlen($script_name));
} elseif (strpos($request_uri, dirname($script_name)) === 0) {
    // إذا كان SCRIPT_NAME هو /index.php (وليس /public/index.php)
    $request_uri = substr($request_uri, strlen(dirname($script_name)));
}


$request_uri = trim($request_uri, '/');
$segments = explode('/', $request_uri);
$page = !empty($segments[0]) ? $segments[0] : 'home'; // الصفحة الافتراضية هي 'home'

// توجيه بسيط جداً
switch ($page) {
    case 'home':
    case '': // إذا كان الرابط هو فقط /public/
        // إذا كان لديك index.php في الجذر وهو الصفحة الرئيسية فعلياً:
        require_once BASE_PATH . '/index.php';
        break;
    case 'login':
        require_once BASE_PATH . '/login.php';
        break;
    case 'register':
        require_once BASE_PATH . '/register.php';
        break;
    case 'search':
        require_once BASE_PATH . '/search_results.php';
        break;
    case 'car':
        // مثال: /public/car/123
        // يجب أن يحتوي car_details.php على منطق لأخذ الـ ID من segments[1]
        // $_GET['id'] = isset($segments[1]) ? $segments[1] : null;
        // require_once BASE_PATH . '/car_details.php';
        // هذا يتطلب تعديل car_details.php ليكون متوافقًا
        // في الوقت الحالي، الأسهل هو الاعتماد على توجيه Apache لـ car_details.php مباشرة
        echo "صفحة السيارة (تحتاج إلى تطوير التوجيه)";
        // أو ببساطة، دع Apache يتعامل معها مباشرة إذا لم يتم إعداد .htaccess لتوجيه كل شيء إلى هنا
        break;
    // أضف المزيد من الحالات حسب الحاجة
    default:
        // صفحة 404
        http_response_code(404);
        echo "<h1>404 - الصفحة غير موجودة</h1>";
        // يمكنك تضمين ملف 404.php مخصص
        // require_once BASE_PATH . '/404.php';
        break;
}

// أغلق الاتصال بقاعدة البيانات إذا كان لا يزال مفتوحاً (عادة يتم في نهاية السكربتات المضمنة)
// if (isset($conn) && $conn) {
//    $conn->close();
// }
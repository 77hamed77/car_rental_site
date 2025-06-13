<?php
// 404.php
http_response_code(404); // مهم ليعرف المتصفح ومحركات البحث أنها صفحة 404
require_once 'includes/db_connect.php'; // للاتساق، قد لا تحتاج لاتصال قاعدة بيانات هنا
require_once 'includes/functions.php';

$page_title = "404 - الصفحة غير موجودة";
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .error-page-container {
            text-align: center;
            padding: 50px 20px;
        }
        .error-page-container h1 {
            font-size: 5rem; /* حجم كبير لرقم الخطأ */
            color: #dc3545; /* أحمر */
            margin-bottom: 0;
        }
        .error-page-container h2 {
            font-size: 1.8rem;
            color: #333;
            margin-top: 10px;
            margin-bottom: 25px;
        }
        .error-page-container p {
            font-size: 1.1rem;
            color: #555;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="error-page-container">
            <h1>404</h1>
            <h2>عفواً، الصفحة التي تبحث عنها غير موجودة!</h2>
            <p>
                ربما تم حذف الصفحة، أو تغيير اسمها، أو أنها غير متاحة مؤقتًا.
                <br>
                يمكنك العودة إلى <a href="index.php" class="btn btn-primary">الصفحة الرئيسية</a> أو محاولة البحث مرة أخرى.
            </p>
            <!-- يمكنك إضافة نموذج بحث بسيط هنا إذا أردت -->
            <!-- 
            <form action="search_results.php" method="GET" class="search-form-simple" style="max-width:400px; margin:auto;">
                <input type="text" name="query" placeholder="ابحث في الموقع..." style="width:70%; padding:10px; border-radius:4px 0 0 4px; border:1px solid #ccc;">
                <button type="submit" class="btn" style="width:28%; border-radius:0 4px 4px 0; padding:10px;">بحث</button>
            </form>
             -->
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
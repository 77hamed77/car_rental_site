<?php
// forgot_password.php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$page_title = "استعادة كلمة المرور";
$message = '';
$message_type = 'info';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize_input($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "الرجاء إدخال عنوان بريد إلكتروني صالح.";
        $message_type = 'danger';
    } else {
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            // البريد موجود، قم بإنشاء رمز إعادة تعيين
            $token = bin2hex(random_bytes(32)); // رمز عشوائي آمن
            $expires_at = date('Y-m-d H:i:s', time() + (60 * 60)); // صلاحية الرمز لمدة ساعة واحدة

            // حذف أي رموز قديمة لهذا البريد
            $stmt_delete_old = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt_delete_old->bind_param("s", $email);
            $stmt_delete_old->execute();
            $stmt_delete_old->close();
            
            // إدخال الرمز الجديد
            $stmt_insert_token = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt_insert_token->bind_param("sss", $email, $token, $expires_at);
            
            if ($stmt_insert_token->execute()) {
                // إرسال البريد الإلكتروني (مثال بسيط، استخدم مكتبة مثل PHPMailer للإنتاج)
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
                $subject = "طلب إعادة تعيين كلمة المرور لموقع تأجير السيارات";
                $body = "مرحباً،\n\nلقد طلبت إعادة تعيين كلمة المرور لحسابك.\n";
                $body .= "الرجاء النقر على الرابط التالي لإعادة تعيين كلمة مرورك:\n";
                $body .= $reset_link . "\n\n";
                $body .= "إذا لم تطلب هذا، يمكنك تجاهل هذا البريد الإلكتروني.\n";
                $body .= "هذا الرابط صالح لمدة ساعة واحدة فقط.\n\nشكراً لك.";
                $headers = "From: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n"; // يجب أن يكون بريد صالح
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

                // يجب أن يكون الموضوع مرمّزًا إذا كان بالعربية
                // $subject_utf8 = "=?UTF-8?B?".base64_encode($subject)."?=";
                // if (mail($email, $subject_utf8, $body, $headers)) {
                if (mail($email, $subject, $body, $headers)) { // للاختبار البسيط
                    $message = "تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني. الرجاء التحقق من صندوق الوارد (والبريد المزعج).";
                    $message_type = 'success';
                } else {
                    $message = "حدث خطأ أثناء محاولة إرسال البريد الإلكتروني. يرجى المحاولة لاحقاً أو الاتصال بالدعم.";
                    // يمكنك تسجيل الخطأ هنا: error_log("Failed to send password reset email to $email");
                    $message_type = 'danger';
                }
            } else {
                $message = "حدث خطأ في قاعدة البيانات أثناء محاولة إنشاء رمز إعادة التعيين.";
                $message_type = 'danger';
            }
            $stmt_insert_token->close();
        } else {
            // البريد غير موجود، لا تكشف هذه المعلومة مباشرة للمستخدم لأسباب أمنية
            // يمكنك عرض رسالة عامة
            $message = "إذا كان هذا البريد الإلكتروني مسجلاً لدينا، فسيتم إرسال رابط إعادة التعيين إليه.";
            $message_type = 'info'; // أو 'success' لجعلها تبدو كأنها تمت دائماً
        }
        $stmt_check->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style> /* نفس أنماط auth-form يمكن استخدامها هنا */
        .auth-form { max-width: 500px; margin: 50px auto; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .auth-form h1 { text-align: center; margin-bottom: 25px; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main class="container">
        <div class="auth-form">
            <h1><?php echo htmlspecialchars($page_title); ?></h1>
            <p>الرجاء إدخال عنوان بريدك الإلكتروني. سنرسل لك رابطًا لإعادة تعيين كلمة المرور الخاصة بك.</p>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($message_type !== 'success'): // اعرض النموذج إذا لم تنجح العملية بالكامل ?>
            <form action="forgot_password.php" method="POST">
                <div class="form-group">
                    <label for="email">البريد الإلكتروني:</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">إرسال رابط إعادة التعيين</button>
            </form>
            <?php endif; ?>
            <p style="text-align:center; margin-top:20px;"><a href="login.php">العودة لتسجيل الدخول</a></p>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
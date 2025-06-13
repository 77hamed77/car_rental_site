<?php
// reset_password.php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$page_title = "إعادة تعيين كلمة المرور";
$token = isset($_GET['token']) ? sanitize_input($_GET['token']) : '';
$message = '';
$message_type = 'info';
$can_reset = false;
$user_email = '';

if (empty($token)) {
    $message = "رمز إعادة التعيين غير صالح أو مفقود.";
    $message_type = 'danger';
} else {
    // التحقق من صلاحية الرمز
    $stmt_check_token = $conn->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
    $stmt_check_token->bind_param("s", $token);
    $stmt_check_token->execute();
    $result_token = $stmt_check_token->get_result();

    if ($result_token->num_rows > 0) {
        $token_data = $result_token->fetch_assoc();
        $user_email = $token_data['email'];
        $expires_at = new DateTime($token_data['expires_at']);
        $now = new DateTime();

        if ($now < $expires_at) {
            $can_reset = true; // الرمز صالح
        } else {
            $message = "انتهت صلاحية رمز إعادة التعيين. يرجى طلب رابط جديد.";
            $message_type = 'danger';
            // حذف الرمز المنتهي صلاحيته
            $stmt_delete = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt_delete->bind_param("s", $token);
            $stmt_delete->execute();
            $stmt_delete->close();
        }
    } else {
        $message = "رمز إعادة التعيين غير صالح أو تم استخدامه بالفعل.";
        $message_type = 'danger';
    }
    $stmt_check_token->close();
}

if ($can_reset && $_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $form_errors = [];

    if (empty($new_password)) $form_errors['new_password'] = "كلمة المرور الجديدة مطلوبة.";
    elseif (strlen($new_password) < 6) $form_errors['new_password'] = "كلمة المرور يجب أن تكون 6 أحرف على الأقل.";
    if ($new_password !== $confirm_password) $form_errors['confirm_password'] = "كلمتا المرور غير متطابقتين.";

    if (empty($form_errors)) {
        $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt_update_pass = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt_update_pass->bind_param("ss", $hashed_new_password, $user_email);

        if ($stmt_update_pass->execute()) {
            // حذف الرمز بعد استخدامه بنجاح
            $stmt_delete_token = $conn->prepare("DELETE FROM password_resets WHERE email = ? AND token = ?"); // كن دقيقاً هنا
            $stmt_delete_token->bind_param("ss", $user_email, $token);
            $stmt_delete_token->execute();
            $stmt_delete_token->close();

            $message = "تم إعادة تعيين كلمة المرور بنجاح! يمكنك الآن تسجيل الدخول بكلمة مرورك الجديدة.";
            $message_type = 'success';
            $can_reset = false; // لمنع عرض النموذج مرة أخرى
        } else {
            $message = "حدث خطأ أثناء تحديث كلمة المرور: " . $stmt_update_pass->error;
            $message_type = 'danger';
        }
        $stmt_update_pass->close();
    } else {
        $message = "الرجاء تصحيح الأخطاء في النموذج.";
        $message_type = 'danger';
        // يمكنك تمرير $form_errors إلى HTML لعرضها بجانب الحقول
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
    <style> /* نفس أنماط auth-form */
        .auth-form { max-width: 500px; margin: 50px auto; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .auth-form h1 { text-align: center; margin-bottom: 25px; }
        .form-error { color: red; font-size: 0.9em; margin-top: 5px; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main class="container">
        <div class="auth-form">
            <h1><?php echo htmlspecialchars($page_title); ?></h1>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($can_reset): ?>
            <p>الرجاء إدخال كلمة المرور الجديدة لحسابك المرتبط بالبريد: <strong><?php echo htmlspecialchars($user_email); ?></strong></p>
            <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
                <div class="form-group">
                    <label for="new_password">كلمة المرور الجديدة:</label>
                    <input type="password" id="new_password" name="new_password" required>
                    <?php if (isset($form_errors['new_password'])): ?><p class="form-error"><?php echo $form_errors['new_password']; ?></p><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="confirm_password">تأكيد كلمة المرور الجديدة:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <?php if (isset($form_errors['confirm_password'])): ?><p class="form-error"><?php echo $form_errors['confirm_password']; ?></p><?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">إعادة تعيين كلمة المرور</button>
            </form>
            <?php elseif ($message_type === 'success'): ?>
                 <p style="text-align:center; margin-top:20px;"><a href="login.php" class="btn btn-primary">تسجيل الدخول الآن</a></p>
            <?php else: // إذا كان هناك خطأ في الرمز أو انتهت صلاحيته ?>
                <p style="text-align:center; margin-top:20px;"><a href="forgot_password.php">طلب رابط إعادة تعيين جديد</a></p>
            <?php endif; ?>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
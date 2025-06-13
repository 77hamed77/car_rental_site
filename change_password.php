<?php
// change_password.php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/session_check.php';

$page_title = "تغيير كلمة المرور";
$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';
$form_errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password)) $form_errors['current_password'] = "كلمة المرور الحالية مطلوبة.";
    if (empty($new_password)) $form_errors['new_password'] = "كلمة المرور الجديدة مطلوبة.";
    elseif (strlen($new_password) < 6) $form_errors['new_password'] = "كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل.";
    if ($new_password !== $confirm_password) $form_errors['confirm_password'] = "كلمتا المرور الجديدتان غير متطابقتين.";

    if (empty($form_errors)) {
        // التحقق من كلمة المرور الحالية
        $stmt_check = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt_check->bind_param("i", $user_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $user_db_data = $result_check->fetch_assoc();
        $stmt_check->close();

        if ($user_db_data && password_verify($current_password, $user_db_data['password'])) {
            // كلمة المرور الحالية صحيحة، قم بتحديثها
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt_update->bind_param("si", $hashed_new_password, $user_id);
            if ($stmt_update->execute()) {
                $success_message = "تم تغيير كلمة المرور بنجاح!";
            } else {
                $error_message = "حدث خطأ أثناء تحديث كلمة المرور: " . $stmt_update->error;
            }
            $stmt_update->close();
        } else {
            $error_message = "كلمة المرور الحالية غير صحيحة.";
        }
    } else {
        if (empty($error_message)) $error_message = "الرجاء تصحيح الأخطاء في النموذج.";
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
    <style> /* نفس أنماط edit_profile.php يمكن تطبيقها هنا */
        .profile-form-container { max-width: 600px; margin: 30px auto; padding: 25px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; }
        .form-group input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .form-error { color: red; font-size: 0.9em; margin-top: 5px; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="profile-form-container">
            <h1><?php echo htmlspecialchars($page_title); ?></h1>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <form action="change_password.php" method="POST">
                <div class="form-group">
                    <label for="current_password">كلمة المرور الحالية:</label>
                    <input type="password" id="current_password" name="current_password" required>
                    <?php if (isset($form_errors['current_password'])): ?><p class="form-error"><?php echo $form_errors['current_password']; ?></p><?php endif; ?>
                </div>
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
                <button type="submit" class="btn btn-primary">تغيير كلمة المرور</button>
                 <a href="user_dashboard.php" class="btn btn-secondary">العودة للوحة التحكم</a>
            </form>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
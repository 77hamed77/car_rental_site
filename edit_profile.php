<?php
// edit_profile.php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/session_check.php'; // يتأكد أن المستخدم مسجل الدخول

$page_title = "تعديل الملف الشخصي";
$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';
$form_errors = [];

// جلب بيانات المستخدم الحالية
$stmt_user = $conn->prepare("SELECT full_name, email, phone_number, address FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_data_result = $stmt_user->get_result();
if ($user_data_result->num_rows === 0) {
    // هذا لا يجب أن يحدث إذا كان session_check يعمل بشكل صحيح
    redirect('logout.php'); 
}
$user = $user_data_result->fetch_assoc();
$stmt_user->close();


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = sanitize_input($_POST['full_name'] ?? $user['full_name']);
    $phone_number = sanitize_input($_POST['phone_number'] ?? $user['phone_number']);
    $address = sanitize_input($_POST['address'] ?? $user['address']);
    // البريد الإلكتروني لا يتم تعديله هنا عادة، أو يتطلب عملية تحقق خاصة

    if (empty($full_name)) {
        $form_errors['full_name'] = "الاسم الكامل مطلوب.";
    }
    // يمكنك إضافة المزيد من التحققات هنا (مثل صيغة رقم الهاتف)

    if (empty($form_errors)) {
        $stmt_update = $conn->prepare("UPDATE users SET full_name = ?, phone_number = ?, address = ? WHERE id = ?");
        $stmt_update->bind_param("sssi", $full_name, $phone_number, $address, $user_id);
        if ($stmt_update->execute()) {
            $success_message = "تم تحديث ملفك الشخصي بنجاح!";
            // تحديث اسم المستخدم في الجلسة إذا تغير
            $_SESSION['user_name'] = $full_name;
            // إعادة جلب بيانات المستخدم المحدثة لعرضها في النموذج
            $user['full_name'] = $full_name;
            $user['phone_number'] = $phone_number;
            $user['address'] = $address;
        } else {
            $error_message = "حدث خطأ أثناء تحديث الملف الشخصي: " . $stmt_update->error;
        }
        $stmt_update->close();
    } else {
        $error_message = "الرجاء تصحيح الأخطاء في النموذج.";
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
    <style> /* يمكن نقل هذه الأنماط لملف CSS */
        .profile-form-container { max-width: 600px; margin: 30px auto; padding: 25px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; }
        .form-group input[type="text"], .form-group input[type="email"], .form-group textarea {
            width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;
        }
        .form-group input[readonly] { background-color: #f0f0f0; cursor: not-allowed; }
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

            <form action="edit_profile.php" method="POST">
                <div class="form-group">
                    <label for="email">البريد الإلكتروني (غير قابل للتعديل):</label>
                    <input type="email" id="email" name="email_display" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="full_name">الاسم الكامل:</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    <?php if (isset($form_errors['full_name'])): ?><p class="form-error"><?php echo $form_errors['full_name']; ?></p><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="phone_number">رقم الهاتف:</label>
                    <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>">
                    <?php if (isset($form_errors['phone_number'])): ?><p class="form-error"><?php echo $form_errors['phone_number']; ?></p><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="address">العنوان (اختياري):</label>
                    <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                <a href="user_dashboard.php" class="btn btn-secondary">العودة للوحة التحكم</a>
            </form>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
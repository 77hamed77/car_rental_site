<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$errors = [];
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password']; // لا تستخدم sanitize_input هنا قبل التجزئة
    $confirm_password = $_POST['confirm_password'];
    $phone_number = sanitize_input($_POST['phone_number']);

    if (empty($full_name)) $errors[] = "الاسم الكامل مطلوب.";
    if (empty($email)) $errors[] = "البريد الإلكتروني مطلوب.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "البريد الإلكتروني غير صالح.";
    if (empty($password)) $errors[] = "كلمة المرور مطلوبة.";
    if ($password !== $confirm_password) $errors[] = "كلمتا المرور غير متطابقتين.";
    if (strlen($password) < 6) $errors[] = "كلمة المرور يجب أن تكون 6 أحرف على الأقل.";

    // التحقق من وجود البريد الإلكتروني مسبقاً
    if (empty($errors)) {
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows > 0) {
            $errors[] = "هذا البريد الإلكتروني مسجل بالفعل.";
        }
        $stmt_check->close();
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, phone_number) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $full_name, $email, $hashed_password, $phone_number);

        if ($stmt->execute()) {
            $success_message = "تم التسجيل بنجاح! يمكنك الآن تسجيل الدخول.";
            // يمكنك توجيهه لصفحة تسجيل الدخول redirect('login.php');
        } else {
            $errors[] = "حدث خطأ ما، يرجى المحاولة مرة أخرى. " . $stmt->error;
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب جديد - استئجار سيارات</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- يمكنك إضافة Bootstrap أو أي إطار عمل CSS هنا لتبسيط التصميم -->
</head>
<body>
    <?php include 'includes/header.php'; // ضع هنا الـ Navbar ?>

    <div class="container auth-form">
        <h2>إنشاء حساب جديد</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="full_name">الاسم الكامل:</label>
                <input type="text" id="full_name" name="full_name" required value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="email">البريد الإلكتروني:</label>
                <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="password">كلمة المرور:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">تأكيد كلمة المرور:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="form-group">
                <label for="phone_number">رقم الهاتف (اختياري):</label>
                <input type="tel" id="phone_number" name="phone_number" value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>">
            </div>
            <button type="submit" class="btn">إنشاء حساب</button>
        </form>
        <p>لديك حساب بالفعل؟ <a href="login.php">سجل الدخول</a></p>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/script.js"></script>
</body>
</html>
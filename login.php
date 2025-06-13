<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php'; // سيقوم ببدء session_start()

$errors = [];

if (isLoggedIn()) {
    redirect('index.php'); // إذا كان مسجل دخوله بالفعل، وجهه للصفحة الرئيسية
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password']; // لا تنظف كلمة المرور هنا

    if (empty($email)) $errors[] = "البريد الإلكتروني مطلوب.";
    if (empty($password)) $errors[] = "كلمة المرور مطلوبة.";

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, full_name, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // كلمة المرور صحيحة
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                redirect('index.php'); // أو user_dashboard.php
            } else {
                $errors[] = "البريد الإلكتروني أو كلمة المرور غير صحيحة.";
            }
        } else {
            $errors[] = "البريد الإلكتروني أو كلمة المرور غير صحيحة.";
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
    <title>تسجيل الدخول - استئجار سيارات</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container auth-form">
        <h2>تسجيل الدخول</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email">البريد الإلكتروني:</label>
                <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="password">كلمة المرور:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">تسجيل الدخول</button>
        </form>
        <p>ليس لديك حساب؟ <a href="register.php">أنشئ حساباً جديداً</a></p>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/script.js"></script>
</body>
</html>
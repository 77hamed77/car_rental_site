<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php'; // لـ isLoggedIn, sanitize_input, redirect
require_once 'includes/session_check.php'; // للتأكد من أن المستخدم مسجل الدخول

$errors = [];
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_booking'])) {
    if (!isLoggedIn()) {
        // يجب أن يكون session_check.php قد قام بإعادة التوجيه بالفعل
        // ولكن كإجراء احترازي إضافي
        redirect('login.php?redirect=' . urlencode('car_details.php?id=' . $_POST['car_id'])); // افترض أن car_id موجود
    }

    $user_id = $_SESSION['user_id'];
    $car_id = isset($_POST['car_id']) ? intval($_POST['car_id']) : 0;
    $pickup_location_id = isset($_POST['pickup_location_id']) ? intval($_POST['pickup_location_id']) : 0;
    $return_location_id = isset($_POST['return_location_id']) ? intval($_POST['return_location_id']) : 0;
    $pickup_datetime_str = isset($_POST['pickup_datetime']) ? sanitize_input($_POST['pickup_datetime']) : '';
    $return_datetime_str = isset($_POST['return_datetime']) ? sanitize_input($_POST['return_datetime']) : '';
    $special_requests = isset($_POST['special_requests']) ? sanitize_input($_POST['special_requests']) : null;

    // التحقق من صحة المدخلات
    if (empty($car_id)) $errors[] = "معرف السيارة مطلوب.";
    if (empty($pickup_location_id)) $errors[] = "موقع الاستلام مطلوب.";
    if (empty($return_location_id)) $errors[] = "موقع التسليم مطلوب.";
    if (empty($pickup_datetime_str)) $errors[] = "تاريخ ووقت الاستلام مطلوب.";
    if (empty($return_datetime_str)) $errors[] = "تاريخ ووقت التسليم مطلوب.";

    $pickup_datetime = null;
    $return_datetime = null;

    if (empty($errors)) {
        try {
            $pickup_datetime = new DateTime($pickup_datetime_str);
            $return_datetime = new DateTime($return_datetime_str);

            if ($return_datetime <= $pickup_datetime) {
                $errors[] = "تاريخ التسليم يجب أن يكون بعد تاريخ الاستلام.";
            }
            // تحقق من أن تاريخ الاستلام ليس في الماضي
            $now = new DateTime();
            if ($pickup_datetime < $now) {
                $errors[] = "تاريخ الاستلام لا يمكن أن يكون في الماضي.";
            }

        } catch (Exception $e) {
            $errors[] = "صيغة التاريخ والوقت غير صالحة.";
        }
    }
    
    // جلب سعر السيارة للتحقق وحساب الإجمالي
    $car_daily_rate = 0;
    if ($car_id > 0 && empty($errors)) {
        $stmt_car = $conn->prepare("SELECT daily_rate FROM cars WHERE id = ?");
        $stmt_car->bind_param("i", $car_id);
        $stmt_car->execute();
        $result_car = $stmt_car->get_result();
        if ($result_car->num_rows > 0) {
            $car_data = $result_car->fetch_assoc();
            $car_daily_rate = (float)$car_data['daily_rate'];
        } else {
            $errors[] = "السيارة المطلوبة غير موجودة.";
        }
        $stmt_car->close();
    }

    // التحقق من تداخل الحجوزات (مهم جداً!)
    if (empty($errors) && $pickup_datetime && $return_datetime) {
        $stmt_check_avail = $conn->prepare(
            "SELECT id FROM bookings 
             WHERE car_id = ? AND status != 'cancelled' AND (
                (pickup_datetime <= ? AND return_datetime >= ?) OR
                (pickup_datetime >= ? AND pickup_datetime < ?) OR
                (return_datetime > ? AND return_datetime <= ?)
             )"
        );
        // التواريخ يجب أن تكون بصيغة Y-m-d H:i:s لقاعدة البيانات
        $pickup_db_format = $pickup_datetime->format('Y-m-d H:i:s');
        $return_db_format = $return_datetime->format('Y-m-d H:i:s');

        $stmt_check_avail->bind_param("issssss", $car_id, 
            $return_db_format, $pickup_db_format, // الحجز المطلوب يقع بالكامل ضمن حجز آخر
            $pickup_db_format, $return_db_format, // بداية الحجز المطلوب تقع ضمن حجز آخر
            $pickup_db_format, $return_db_format  // نهاية الحجز المطلوب تقع ضمن حجز آخر
        );
        $stmt_check_avail->execute();
        $result_check_avail = $stmt_check_avail->get_result();
        if ($result_check_avail->num_rows > 0) {
            $errors[] = "عفواً، السيارة محجوزة بالفعل في الفترة المحددة. يرجى اختيار تواريخ أخرى.";
        }
        $stmt_check_avail->close();
    }


    if (empty($errors) && $pickup_datetime && $return_datetime && $car_daily_rate > 0) {
        // حساب المدة والسعر الإجمالي
        $interval = $pickup_datetime->diff($return_datetime);
        $days = $interval->days;
        if ($interval->h > 0 || $interval->i > 0 || $interval->s > 0) { // إذا كان هناك ساعات أو دقائق إضافية، اعتبرها يوماً كاملاً
            $days++;
        }
        if ($days <= 0) $days = 1; // على الأقل يوم واحد

        $total_price = $days * $car_daily_rate;

        // إدخال الحجز في قاعدة البيانات
        $stmt_insert = $conn->prepare("INSERT INTO bookings (user_id, car_id, pickup_location_id, return_location_id, pickup_datetime, return_datetime, total_price, special_requests, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt_insert->bind_param("iiiissds", $user_id, $car_id, $pickup_location_id, $return_location_id, $pickup_db_format, $return_db_format, $total_price, $special_requests);

        if ($stmt_insert->execute()) {
            $booking_id = $stmt_insert->insert_id;
            $success_message = "تم تأكيد حجزك بنجاح! رقم الحجز الخاص بك هو: " . $booking_id . ". السعر الإجمالي: " . $total_price . " ريال.";
            // يمكنك توجيه المستخدم لصفحة تأكيد أو لوحة التحكم الخاصة به
            // redirect('user_dashboard.php?booking_success=' . $booking_id);
        } else {
            $errors[] = "حدث خطأ أثناء محاولة حفظ الحجز. يرجى المحاولة مرة أخرى. " . $stmt_insert->error;
        }
        $stmt_insert->close();
    }

} elseif ($_SERVER["REQUEST_METHOD"] != "POST") {
    // إذا تم الوصول للصفحة عبر GET، ربما عرض ملخص أو توجيه لمكان آخر
    // redirect('index.php'); // أو car_details.php إذا لم تكن هناك بيانات كافية
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأكيد الحجز - استئجار سيارات</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container booking-confirmation-page">
        <h1>حالة الحجز</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h4>حدثت الأخطاء التالية:</h4>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <p>
                    <a href="javascript:history.back()" class="btn">العودة وتعديل البيانات</a> أو 
                    <a href="car_details.php?id=<?php echo isset($_POST['car_id']) ? intval($_POST['car_id']) : ''; ?>" class="btn">العودة لصفحة السيارة</a>
                </p>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <p><?php echo $success_message; ?></p>
                <p><a href="user_dashboard.php" class="btn">عرض حجوزاتي</a></p>
            </div>
        <?php endif; ?>

        <?php if (empty($errors) && empty($success_message) && $_SERVER["REQUEST_METHOD"] == "POST"): ?>
            <div class="alert alert-warning">
                <p>لم يتم إكمال عملية الحجز. إذا كنت تعتقد أن هذا خطأ، يرجى المحاولة مرة أخرى أو الاتصال بنا.</p>
                 <p><a href="javascript:history.back()" class="btn">العودة والمحاولة مرة أخرى</a></p>
            </div>
        <?php elseif ($_SERVER["REQUEST_METHOD"] != "POST"): ?>
             <div class="alert alert-info">
                <p>لإجراء حجز، يرجى اختيار سيارة وتحديد تفاصيل الحجز من <a href="index.php">صفحة البحث</a> أو <a href="search_results.php">صفحة السيارات</a>.</p>
            </div>
        <?php endif; ?>

    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
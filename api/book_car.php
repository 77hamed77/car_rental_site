<?php
// api/book_car.php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/includes/db_connect.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// ابدأ الجلسة للتحقق من تسجيل الدخول
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$response = ['success' => false, 'message' => '', 'booking_id' => null, 'redirect_url' => null];

// تحقق من أن الطلب هو POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $response['message'] = "يجب أن يكون الطلب من نوع POST.";
    echo json_encode($response);
    exit();
}

// تحقق من تسجيل الدخول
if (!isLoggedIn()) {
    $response['message'] = "يجب تسجيل الدخول أولاً لإتمام الحجز.";
    // يمكنك اقتراح رابط تسجيل الدخول الذي يعيد المستخدم إلى الصفحة الحالية
    // إذا كنت تستخدم GET لـ car_id من form data:
    $redirect_page = isset($_POST['current_page_url']) ? $_POST['current_page_url'] : 'index.php';
    $response['redirect_url'] = 'login.php?redirect=' . urlencode($redirect_page);
    echo json_encode($response);
    exit();
}

try {
    // افترض أن البيانات تأتي كـ JSON body أو form-data
    // إذا كانت JSON body: $data = json_decode(file_get_contents('php://input'), true);
    // إذا كانت form-data: استخدم $_POST
    $data = $_POST; 

    $user_id = $_SESSION['user_id'];
    $car_id = isset($data['car_id']) ? intval($data['car_id']) : 0;
    $pickup_location_id = isset($data['pickup_location_id']) ? intval($data['pickup_location_id']) : 0;
    $return_location_id = isset($data['return_location_id']) ? intval($data['return_location_id']) : 0;
    $pickup_datetime_str = isset($data['pickup_datetime']) ? sanitize_input($data['pickup_datetime']) : '';
    $return_datetime_str = isset($data['return_datetime']) ? sanitize_input($data['return_datetime']) : '';
    $special_requests = isset($data['special_requests']) ? sanitize_input($data['special_requests']) : null;

    if (empty($car_id) || empty($pickup_location_id) || empty($return_location_id) || empty($pickup_datetime_str) || empty($return_datetime_str)) {
        throw new Exception("الرجاء ملء جميع الحقول المطلوبة للحجز.");
    }

    $pickup_datetime = new DateTime($pickup_datetime_str);
    $return_datetime = new DateTime($return_datetime_str);

    if ($return_datetime <= $pickup_datetime) {
        throw new Exception("تاريخ التسليم يجب أن يكون بعد تاريخ الاستلام.");
    }
    $now = new DateTime();
    if ($pickup_datetime < $now) {
        throw new Exception("تاريخ الاستلام لا يمكن أن يكون في الماضي.");
    }
    
    // جلب سعر السيارة
    $stmt_car = $conn->prepare("SELECT daily_rate FROM cars WHERE id = ?");
    $stmt_car->bind_param("i", $car_id);
    $stmt_car->execute();
    $result_car = $stmt_car->get_result();
    if ($result_car->num_rows == 0) {
        throw new Exception("السيارة المطلوبة غير موجودة.");
    }
    $car_data = $result_car->fetch_assoc();
    $car_daily_rate = (float)$car_data['daily_rate'];
    $stmt_car->close();

    // التحقق من تداخل الحجوزات
    $pickup_db_format = $pickup_datetime->format('Y-m-d H:i:s');
    $return_db_format = $return_datetime->format('Y-m-d H:i:s');
    $stmt_check_avail = $conn->prepare(
        "SELECT id FROM bookings 
         WHERE car_id = ? AND status != 'cancelled' AND (
            (pickup_datetime < ? AND return_datetime > ?) OR
            (pickup_datetime >= ? AND pickup_datetime < ?) OR
            (return_datetime > ? AND return_datetime <= ?)
         )"
    );
    $stmt_check_avail->bind_param("issssss", $car_id, $return_db_format, $pickup_db_format, $pickup_db_format, $return_db_format, $pickup_db_format, $return_db_format);
    $stmt_check_avail->execute();
    if ($stmt_check_avail->get_result()->num_rows > 0) {
        throw new Exception("عفواً، السيارة محجوزة بالفعل في الفترة المحددة. يرجى اختيار تواريخ أخرى.");
    }
    $stmt_check_avail->close();

    // حساب المدة والسعر الإجمالي
    $interval = $pickup_datetime->diff($return_datetime);
    $days = $interval->days;
    if ($interval->h > 0 || $interval->i > 0 || $interval->s > 0) $days++;
    if ($days <= 0) $days = 1;
    $total_price = $days * $car_daily_rate;

    // إدخال الحجز
    $stmt_insert = $conn->prepare("INSERT INTO bookings (user_id, car_id, pickup_location_id, return_location_id, pickup_datetime, return_datetime, total_price, special_requests, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt_insert->bind_param("iiiissds", $user_id, $car_id, $pickup_location_id, $return_location_id, $pickup_db_format, $return_db_format, $total_price, $special_requests);

    if ($stmt_insert->execute()) {
        $booking_id = $stmt_insert->insert_id;
        $response['success'] = true;
        $response['message'] = "تم تأكيد حجزك بنجاح! رقم الحجز: #" . $booking_id;
        $response['booking_id'] = $booking_id;
        $response['total_price'] = $total_price;
        // يمكنك إضافة رابط لصفحة تأكيد أو لوحة التحكم
        $response['confirmation_url'] = 'user_dashboard.php?booking_success=' . $booking_id;
    } else {
        throw new Exception("حدث خطأ أثناء حفظ الحجز: " . $stmt_insert->error);
    }
    $stmt_insert->close();

} catch (Exception $e) {
    $response['message'] = "خطأ في عملية الحجز: " . $e->getMessage();
}

$conn->close();
echo json_encode($response);
exit();
?>
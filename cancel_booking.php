<?php
// cancel_booking.php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/session_check.php';

$user_id = $_SESSION['user_id'];
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$message_type = 'info'; // 'info', 'success', 'error'

if ($booking_id > 0) {
    // التحقق من أن الحجز يخص المستخدم الحالي وأنه قابل للإلغاء
    $stmt_check = $conn->prepare("SELECT id, status FROM bookings WHERE id = ? AND user_id = ?");
    $stmt_check->bind_param("ii", $booking_id, $user_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $booking = $result_check->fetch_assoc();
        // يمكنك إضافة شروط إضافية هنا (مثلاً: يمكن الإلغاء فقط إذا كان 'pending' أو 'confirmed' وقبل موعد الاستلام بـ X ساعة)
        if ($booking['status'] === 'pending' || $booking['status'] === 'confirmed') {
            // (اختياري) التحقق من وقت الإلغاء المسموح به
            // $pickup_stmt = $conn->prepare("SELECT pickup_datetime FROM bookings WHERE id = ?"); ...
            // $pickup_time = new DateTime(...); $now = new DateTime();
            // if ($pickup_time > $now && $now->diff($pickup_time)->h >= 24) { // مثال: قبل 24 ساعة

            $stmt_cancel = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
            $stmt_cancel->bind_param("i", $booking_id);
            if ($stmt_cancel->execute()) {
                $message = "تم إلغاء الحجز رقم #" . $booking_id . " بنجاح.";
                $message_type = 'success';
            } else {
                $message = "حدث خطأ أثناء محاولة إلغاء الحجز.";
                $message_type = 'error';
            }
            $stmt_cancel->close();
            // } else {
            //    $message = "عفواً، لا يمكن إلغاء هذا الحجز الآن (الوقت المسموح به للإلغاء قد انتهى).";
            //    $message_type = 'error';
            // }
        } else {
            $message = "لا يمكن إلغاء هذا الحجز لأنه بحالة '" . $booking['status'] . "'.";
            $message_type = 'warning';
        }
    } else {
        $message = "لم يتم العثور على الحجز أو أنك لا تملك صلاحية إلغائه.";
        $message_type = 'error';
    }
    $stmt_check->close();
} else {
    $message = "معرف الحجز غير صالح.";
    $message_type = 'error';
}
$conn->close();

// حفظ الرسالة في الجلسة وإعادة التوجيه إلى لوحة التحكم
$_SESSION['dashboard_message'] = $message;
$_SESSION['dashboard_message_type'] = $message_type;
redirect('user_dashboard.php');
exit();

// لا حاجة لـ HTML هنا لأنه سيتم إعادة التوجيه
?>
<?php
// api/user_actions.php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/includes/db_connect.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$response = ['success' => false, 'message' => ''];
$action = isset($_REQUEST['action']) ? sanitize_input($_REQUEST['action']) : ''; // استخدم REQUEST لقبول GET أو POST

try {
    switch ($action) {
        case 'check_email_availability':
            $email = isset($_REQUEST['email']) ? sanitize_input($_REQUEST['email']) : '';
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = "البريد الإلكتروني غير صالح.";
                break;
            }

            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $response['available'] = false;
                $response['message'] = "هذا البريد الإلكتروني مسجل بالفعل.";
            } else {
                $response['available'] = true;
                $response['message'] = "البريد الإلكتروني متاح.";
            }
            $response['success'] = true; // العملية تمت بنجاح حتى لو كان البريد غير متاح
            $stmt->close();
            break;

        case 'update_profile': // مثال آخر (يتطلب تسجيل الدخول)
            if (!isLoggedIn()) {
                $response['message'] = "يجب تسجيل الدخول لتحديث الملف الشخصي.";
                break;
            }
            // افترض أن البيانات قادمة من POST
            $user_id = $_SESSION['user_id'];
            $full_name = isset($_POST['full_name']) ? sanitize_input($_POST['full_name']) : null;
            $phone_number = isset($_POST['phone_number']) ? sanitize_input($_POST['phone_number']) : null;
            $address = isset($_POST['address']) ? sanitize_input($_POST['address']) : null;

            if (empty($full_name)) {
                 $response['message'] = "الاسم الكامل مطلوب.";
                 break;
            }
            
            $update_fields = [];
            $update_params = [];
            $update_types = "";

            if ($full_name !== null) { $update_fields[] = "full_name = ?"; $update_params[] = $full_name; $update_types .= "s"; }
            if ($phone_number !== null) { $update_fields[] = "phone_number = ?"; $update_params[] = $phone_number; $update_types .= "s"; }
            if ($address !== null) { $update_fields[] = "address = ?"; $update_params[] = $address; $update_types .= "s"; }

            if (!empty($update_fields)) {
                $update_params[] = $user_id; // لشرط WHERE
                $update_types .= "i";
                $sql = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($update_types, ...$update_params);
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = "تم تحديث الملف الشخصي بنجاح.";
                    // تحديث اسم المستخدم في الجلسة إذا تم تغييره
                    if ($full_name !== null) $_SESSION['user_name'] = $full_name;
                } else {
                    $response['message'] = "خطأ في تحديث الملف الشخصي: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $response['message'] = "لا توجد بيانات لتحديثها.";
            }
            break;
            
        // يمكنك إضافة المزيد من الحالات (actions) هنا

        default:
            $response['message'] = "الإجراء المطلوب غير معروف.";
            break;
    }
} catch (Exception $e) {
    $response['message'] = "حدث خطأ: " . $e->getMessage();
}

$conn->close();
echo json_encode($response);
exit();
?>
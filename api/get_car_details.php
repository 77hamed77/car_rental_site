<?php
// api/get_car_details.php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/includes/db_connect.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$response = ['success' => false, 'car' => null, 'message' => ''];
$car_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($car_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT c.*, cat.name as category_name, loc.name as location_name, loc.city as location_city, loc.address as location_address
                                FROM cars c
                                LEFT JOIN car_categories cat ON c.category_id = cat.id
                                LEFT JOIN locations loc ON c.location_id = loc.id
                                WHERE c.id = ?");
        $stmt->bind_param("i", $car_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $car_data = $result->fetch_assoc();
            // إضافة مسار كامل للصورة
            $car_data['image_full_url'] = 'assets/images/cars/' . ($car_data['image_url'] ? $car_data['image_url'] : 'default_car.png');
            // تحويل الميزات من نص مفصول بفاصلة إلى مصفوفة
            if (!empty($car_data['features'])) {
                $car_data['features_list'] = array_map('trim', explode(',', $car_data['features']));
            } else {
                $car_data['features_list'] = [];
            }
            
            $response['success'] = true;
            $response['car'] = $car_data;
        } else {
            $response['message'] = "لم يتم العثور على السيارة المطلوبة.";
        }
        $stmt->close();
    } catch (Exception $e) {
        $response['message'] = "حدث خطأ: " . $e->getMessage();
    }
} else {
    $response['message'] = "معرف السيارة غير صالح.";
}

$conn->close();
echo json_encode($response);
exit();
?>
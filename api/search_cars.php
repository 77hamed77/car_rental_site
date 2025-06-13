<?php
// api/search_cars.php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/includes/db_connect.php'; // ../includes/db_connect.php
require_once dirname(__DIR__) . '/includes/functions.php'; // ../includes/functions.php

$response = ['success' => false, 'cars' => [], 'message' => ''];

try {
    $pickup_location_id = isset($_GET['pickup_location_id']) ? intval($_GET['pickup_location_id']) : null;
    $return_location_id = isset($_GET['return_location_id']) ? intval($_GET['return_location_id']) : $pickup_location_id; // Default to pickup if not set
    $pickup_datetime_str = isset($_GET['pickup_date']) ? sanitize_input($_GET['pickup_date']) : null;
    $return_datetime_str = isset($_GET['return_date']) ? sanitize_input($_GET['return_date']) : null;
    
    // يمكنك إضافة المزيد من الفلاتر هنا: category, make, model, price_range, etc.
    $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;
    $min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : null;
    $max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : null;


    $sql = "SELECT c.id, c.make, c.model, c.year, c.transmission, c.fuel_type, c.seats, c.daily_rate, c.image_url, 
                   cat.name as category_name, loc.name as current_location_name, loc.city as current_location_city
            FROM cars c
            LEFT JOIN car_categories cat ON c.category_id = cat.id
            LEFT JOIN locations loc ON c.location_id = loc.id
            WHERE c.availability_status = 'available'";

    $params = [];
    $types = "";

    if ($pickup_location_id) {
        // هذا يفترض أن السيارة يجب أن تكون متاحة في موقع الاستلام المحدد
        // أو أن موقعها الحالي هو موقع الاستلام
        $sql .= " AND c.location_id = ?";
        $params[] = $pickup_location_id;
        $types .= "i";
    }
    
    if ($category_id) {
        $sql .= " AND c.category_id = ?";
        $params[] = $category_id;
        $types .= "i";
    }

    if ($min_price !== null) {
        $sql .= " AND c.daily_rate >= ?";
        $params[] = $min_price;
        $types .= "d";
    }
    if ($max_price !== null) {
        $sql .= " AND c.daily_rate <= ?";
        $params[] = $max_price;
        $types .= "d";
    }


    // فلترة السيارات التي *ليست* محجوزة خلال التواريخ المطلوبة
    if ($pickup_datetime_str && $return_datetime_str) {
        $pickup_datetime = new DateTime($pickup_datetime_str);
        $return_datetime = new DateTime($return_datetime_str);

        if ($return_datetime <= $pickup_datetime) {
            throw new Exception("تاريخ التسليم يجب أن يكون بعد تاريخ الاستلام.");
        }
        $now = new DateTime();
        if ($pickup_datetime < $now) {
             // throw new Exception("تاريخ الاستلام لا يمكن أن يكون في الماضي."); // أو تجاهل الفلترة إذا كان تاريخ قديم للبحث العام
        }

        $pickup_db_format = $pickup_datetime->format('Y-m-d H:i:s');
        $return_db_format = $return_datetime->format('Y-m-d H:i:s');

        $sql .= " AND c.id NOT IN (
                    SELECT car_id FROM bookings
                    WHERE status != 'cancelled' AND (
                        (pickup_datetime < ? AND return_datetime > ?) OR -- Existing booking overlaps
                        (pickup_datetime >= ? AND pickup_datetime < ?) OR
                        (return_datetime > ? AND return_datetime <= ?)
                    )
                )";
        // الترتيب الصحيح للمعلمات هنا مهم
        array_push($params, $return_db_format, $pickup_db_format, $pickup_db_format, $return_db_format, $pickup_db_format, $return_db_format);
        $types .= "ssssss";
    }

    $sql .= " ORDER BY c.daily_rate ASC"; // أو أي ترتيب آخر

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $cars_data = [];
    if ($result) {
        while($row = $result->fetch_assoc()) {
            // إضافة مسار كامل للصورة
            $row['image_full_url'] = 'assets/images/cars/' . ($row['image_url'] ? $row['image_url'] : 'default_car.png');
            // رابط تفاصيل السيارة يمكن بناؤه هنا إذا أردت
            $row['details_url'] = 'car_details.php?id=' . $row['id'];
            if($pickup_datetime_str && $return_datetime_str){
                $row['details_url'] .= '&pickup_date=' . urlencode($pickup_datetime_str) . '&return_date=' . urlencode($return_datetime_str);
            }
             if($pickup_location_id){
                $row['details_url'] .= '&pickup_location_id=' . $pickup_location_id;
            }
            $cars_data[] = $row;
        }
        $response['success'] = true;
        $response['cars'] = $cars_data;
        if(empty($cars_data)){
            $response['message'] = 'لم يتم العثور على سيارات تطابق معايير البحث.';
        }
    } else {
        $response['message'] = "خطأ في تنفيذ البحث: " . $conn->error;
    }
    $stmt->close();

} catch (Exception $e) {
    $response['message'] = "حدث خطأ: " . $e->getMessage();
}

$conn->close();
echo json_encode($response);
exit();
?>
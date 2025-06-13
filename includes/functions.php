<?php
// ابدأ الجلسة في كل الصفحات التي تحتاجها
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

// دالة لتنظيف المدخلات (حماية أساسية من XSS)
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// دالة لجلب السيارات (مثال مبسط)
function getCars($conn, $limit = 10, $filters = []) {
    $sql = "SELECT c.*, cat.name as category_name, loc.name as location_name
            FROM cars c
            LEFT JOIN car_categories cat ON c.category_id = cat.id
            LEFT JOIN locations loc ON c.location_id = loc.id
            WHERE c.availability_status = 'available'";

    // يمكنك إضافة فلاتر هنا بناءً على $filters
    // مثال: if (!empty($filters['location_id'])) { $sql .= " AND c.location_id = " . intval($filters['location_id']); }

    $sql .= " ORDER BY c.created_at DESC LIMIT " . intval($limit);

    $result = $conn->query($sql);
    $cars = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $cars[] = $row;
        }
    }
    return $cars;
}
?>
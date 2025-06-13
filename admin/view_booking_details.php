<?php
$page_title = 'تفاصيل الحجز';
require_once __DIR__ . '/includes/admin_header.php';

$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$booking_details = null;
$error_message = '';

if ($booking_id > 0) {
    $stmt = $conn->prepare("
        SELECT 
            b.*, 
            u.full_name as user_full_name, u.email as user_email, u.phone_number as user_phone,
            c.make as car_make, c.model as car_model, c.year as car_year, c.image_url as car_image_url,
            c_cat.name as car_category_name,
            pick_loc.name as pickup_location_name, pick_loc.address as pickup_location_address, pick_loc.city as pickup_location_city,
            ret_loc.name as return_location_name, ret_loc.address as return_location_address, ret_loc.city as return_location_city
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN cars c ON b.car_id = c.id
        LEFT JOIN car_categories c_cat ON c.category_id = c_cat.id
        JOIN locations pick_loc ON b.pickup_location_id = pick_loc.id
        JOIN locations ret_loc ON b.return_location_id = ret_loc.id
        WHERE b.id = ?
    ");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $booking_details = $result->fetch_assoc();
    } else {
        $error_message = "لم يتم العثور على الحجز المطلوب.";
    }
    $stmt->close();
} else {
    $error_message = "معرف الحجز غير صالح.";
}

?>

<style>
    .booking-detail-section { margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
    .booking-detail-section h3 { color: #34495e; margin-bottom: 10px; }
    .booking-detail-section p { margin-bottom: 8px; line-height: 1.6; }
    .booking-detail-section strong { min-width: 120px; display: inline-block; }
    .car-image-small { max-width: 200px; max-height: 150px; border-radius: 5px; margin-top: 10px; }
</style>

<h2>تفاصيل الحجز رقم #<?php echo htmlspecialchars($booking_id); ?></h2>

<?php if ($error_message): ?>
    <div class="admin-alert admin-alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <p><a href="manage_bookings.php" class="btn btn-secondary">« العودة إلى قائمة الحجوزات</a></p>
<?php elseif ($booking_details): ?>
    <div class="booking-details-container">
        <div class="booking-detail-section">
            <h3>معلومات الحجز الأساسية</h3>
            <p><strong>رقم الحجز:</strong> #<?php echo htmlspecialchars($booking_details['id']); ?></p>
            <p><strong>تاريخ الإنشاء:</strong> <?php echo htmlspecialchars((new DateTime($booking_details['created_at']))->format('Y-m-d H:i:s')); ?></p>
            <p><strong>الحالة الحالية:</strong> 
                <span class="status-badge status-<?php echo htmlspecialchars($booking_details['status']); ?>">
                    <?php
                        switch ($booking_details['status']) {
                            case 'pending': echo 'قيد المراجعة'; break;
                            case 'confirmed': echo 'مؤكد'; break;
                            case 'completed': echo 'مكتمل'; break;
                            case 'cancelled': echo 'ملغى'; break;
                            default: echo htmlspecialchars($booking_details['status']);
                        }
                    ?>
                </span>
            </p>
            <p><strong>السعر الإجمالي:</strong> <?php echo htmlspecialchars($booking_details['total_price']); ?> ريال</p>
            <?php if ($booking_details['special_requests']): ?>
                <p><strong>طلبات خاصة:</strong> <?php echo nl2br(htmlspecialchars($booking_details['special_requests'])); ?></p>
            <?php endif; ?>
        </div>

        <div class="booking-detail-section">
            <h3>معلومات العميل</h3>
            <p><strong>اسم العميل:</strong> <?php echo htmlspecialchars($booking_details['user_full_name']); ?></p>
            <p><strong>البريد الإلكتروني:</strong> <?php echo htmlspecialchars($booking_details['user_email']); ?></p>
            <p><strong>رقم الهاتف:</strong> <?php echo htmlspecialchars($booking_details['user_phone'] ?? '-'); ?></p>
        </div>

        <div class="booking-detail-section">
            <h3>تفاصيل السيارة</h3>
            <p><strong>السيارة:</strong> <?php echo htmlspecialchars($booking_details['car_make'] . ' ' . $booking_details['car_model'] . ' (' . $booking_details['car_year'] . ')'); ?></p>
            <p><strong>الفئة:</strong> <?php echo htmlspecialchars($booking_details['car_category_name'] ?? 'غير محددة'); ?></p>
            <?php if ($booking_details['car_image_url']): ?>
                <img src="../assets/images/cars/<?php echo htmlspecialchars($booking_details['car_image_url']); ?>" alt="صورة السيارة" class="car-image-small">
            <?php endif; ?>
        </div>

        <div class="booking-detail-section">
            <h3>تفاصيل الاستلام</h3>
            <p><strong>موقع الاستلام:</strong> <?php echo htmlspecialchars($booking_details['pickup_location_name'] . ' - ' . $booking_details['pickup_location_city']); ?></p>
            <p><strong>عنوان الاستلام:</strong> <?php echo htmlspecialchars($booking_details['pickup_location_address']); ?></p>
            <p><strong>تاريخ ووقت الاستلام:</strong> <?php echo htmlspecialchars((new DateTime($booking_details['pickup_datetime']))->format('Y-m-d H:i')); ?></p>
        </div>

        <div class="booking-detail-section">
            <h3>تفاصيل التسليم</h3>
            <p><strong>موقع التسليم:</strong> <?php echo htmlspecialchars($booking_details['return_location_name'] . ' - ' . $booking_details['return_location_city']); ?></p>
            <p><strong>عنوان التسليم:</strong> <?php echo htmlspecialchars($booking_details['return_location_address']); ?></p>
            <p><strong>تاريخ ووقت التسليم:</strong> <?php echo htmlspecialchars((new DateTime($booking_details['return_datetime']))->format('Y-m-d H:i')); ?></p>
        </div>
        
        <p style="margin-top: 20px;">
            <a href="manage_bookings.php" class="btn btn-secondary">« العودة إلى قائمة الحجوزات</a>
            <!-- يمكنك إضافة زر لطباعة تفاصيل الحجز هنا إذا أردت -->
            <!-- <button onclick="window.print();" class="btn">طباعة التفاصيل</button> -->
        </p>
    </div>
<?php else: ?>
    <p>لا يمكن عرض تفاصيل الحجز.</p>
    <p><a href="manage_bookings.php" class="btn btn-secondary">« العودة إلى قائمة الحجوزات</a></p>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
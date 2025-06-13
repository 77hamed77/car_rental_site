<?php
// user_dashboard.php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/session_check.php'; // يتأكد أن المستخدم مسجل الدخول

$page_title = "لوحة التحكم";
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// لعرض الرسائل القادمة من صفحات أخرى (مثل cancel_booking.php)
$dashboard_message = '';
$dashboard_message_type = 'info'; // 'info', 'success', 'error', 'warning'
if (isset($_SESSION['dashboard_message'])) {
    $dashboard_message = $_SESSION['dashboard_message'];
    $dashboard_message_type = $_SESSION['dashboard_message_type'] ?? 'info';
    unset($_SESSION['dashboard_message']);
    unset($_SESSION['dashboard_message_type']);
}

// جلب حجوزات المستخدم
$bookings = [];
$stmt_bookings = $conn->prepare("SELECT b.*, c.make, c.model, c.year, c.image_url, 
                                pick_loc.name as pickup_location_name, pick_loc.city as pickup_city,
                                ret_loc.name as return_location_name, ret_loc.city as return_city
                                FROM bookings b
                                JOIN cars c ON b.car_id = c.id
                                JOIN locations pick_loc ON b.pickup_location_id = pick_loc.id
                                JOIN locations ret_loc ON b.return_location_id = ret_loc.id
                                WHERE b.user_id = ?
                                ORDER BY b.pickup_datetime DESC");
$stmt_bookings->bind_param("i", $user_id);
$stmt_bookings->execute();
$result_bookings = $stmt_bookings->get_result();
if ($result_bookings->num_rows > 0) {
    while($row = $result_bookings->fetch_assoc()) {
        $bookings[] = $row;
    }
}
$stmt_bookings->close();

// إحصائية بسيطة: إجمالي عدد الحجوزات للمستخدم
$total_user_bookings_stmt = $conn->prepare("SELECT COUNT(*) as total_count FROM bookings WHERE user_id = ?");
$total_user_bookings_stmt->bind_param("i", $user_id);
$total_user_bookings_stmt->execute();
$total_user_bookings_count = $total_user_bookings_stmt->get_result()->fetch_assoc()['total_count'] ?? 0;
$total_user_bookings_stmt->close();

$conn->close();

// دالة مساعدة لترجمة حالة الحجز وعرضها بلون مناسب
function getBookingStatusBadge($status) {
    $status_text = '';
    $badge_class = '';
    switch ($status) {
        case 'pending': $status_text = 'قيد المراجعة'; $badge_class = 'status-pending'; break;
        case 'confirmed': $status_text = 'مؤكد'; $badge_class = 'status-confirmed'; break;
        case 'completed': $status_text = 'مكتمل'; $badge_class = 'status-completed'; break;
        case 'cancelled': $status_text = 'ملغى'; $badge_class = 'status-cancelled'; break;
        default: $status_text = htmlspecialchars($status); $badge_class = 'status-unknown';
    }
    return '<span class="booking-status-badge ' . $badge_class . '">' . $status_text . '</span>';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title . ' - ' . $user_name); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .dashboard-container { padding-top: 20px; }
        .dashboard-header { margin-bottom: 30px; }
        .dashboard-header h1 { margin-bottom: 5px; }
        .dashboard-header p { font-size: 1.1em; color: #555; }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .dashboard-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .dashboard-card h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.5em;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .booking-item {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap; /* للسماح بالتفاف العناصر في الشاشات الصغيرة */
        }
        .booking-item-image {
            flex-shrink: 0; /* لمنع الصورة من الانكماش */
        }
        .booking-item-image img {
            width: 120px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
        .booking-item-details {
            flex-grow: 1; /* لجعل التفاصيل تأخذ المساحة المتبقية */
        }
        .booking-item-details h3 {
            margin-top: 0;
            margin-bottom: 8px;
            font-size: 1.1em;
            color: #007bff;
        }
        .booking-item-details p {
            font-size: 0.9em;
            color: #555;
            margin-bottom: 5px;
            line-height: 1.5;
        }
        .booking-status-badge {
            display: inline-block;
            padding: 4px 10px;
            font-size: 0.85em;
            font-weight: bold;
            border-radius: 12px;
            color: #fff;
        }
        .status-pending { background-color: #ffc107; color: #212529; }
        .status-confirmed { background-color: #28a745; }
        .status-completed { background-color: #6c757d; }
        .status-cancelled { background-color: #dc3545; }
        .status-unknown { background-color: #adb5bd; }

        .actions-list li { margin-bottom: 10px; }
        .actions-list a.btn { display: block; text-align: center; }
        
        .no-bookings { text-align: center; padding: 20px; background-color: #f9f9f9; border-radius: 6px;}
        .no-bookings p { margin-bottom:15px; font-size: 1.1em; }

        .dashboard-stats {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
        .dashboard-stats p { font-size: 1.2em; color: #495057; margin:0; }
        .dashboard-stats strong { color: #007bff; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container dashboard-container">
        <div class="dashboard-header">
            <h1>مرحباً بك مجدداً، <?php echo htmlspecialchars($user_name); ?>!</h1>
            <p>هنا يمكنك عرض وإدارة حجوزاتك وتفاصيل حسابك.</p>
        </div>

        <?php if ($dashboard_message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($dashboard_message_type); ?>" style="margin-bottom: 20px;">
                <?php echo htmlspecialchars($dashboard_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['booking_success'])): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;">
                تم تأكيد حجزك رقم #<?php echo htmlspecialchars($_GET['booking_success']); ?> بنجاح!
            </div>
        <?php endif; ?>

        <div class="dashboard-stats">
            <p>لديك <strong><?php echo $total_user_bookings_count; ?></strong> حجز إجمالاً.</p>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card bookings-card">
                <h2>حجوزاتي</h2>
                <?php if (!empty($bookings)): ?>
                    <div class="bookings-list">
                        <?php foreach ($bookings as $booking): ?>
                            <div class="booking-item">
                                <div class="booking-item-image">
                                    <img src="assets/images/cars/<?php echo htmlspecialchars($booking['image_url'] ? $booking['image_url'] : 'default_car.png'); ?>" alt="<?php echo htmlspecialchars($booking['make'] . ' ' . $booking['model']); ?>">
                                </div>
                                <div class="booking-item-details">
                                    <h3><?php echo htmlspecialchars($booking['make'] . ' ' . $booking['model'] . ' (' . $booking['year'] . ')'); ?></h3>
                                    <p><strong>رقم الحجز:</strong> #<?php echo htmlspecialchars($booking['id']); ?></p>
                                    <p>
                                        <strong>الاستلام:</strong> <?php echo htmlspecialchars($booking['pickup_location_name'] . ', ' . $booking['pickup_city']); ?>
                                        <br><small>في: <?php echo htmlspecialchars((new DateTime($booking['pickup_datetime']))->format('Y-m-d \ا\ل\س\ا\ع\ة H:i')); ?></small>
                                    </p>
                                    <p>
                                        <strong>التسليم:</strong> <?php echo htmlspecialchars($booking['return_location_name'] . ', ' . $booking['return_city']); ?>
                                        <br><small>في: <?php echo htmlspecialchars((new DateTime($booking['return_datetime']))->format('Y-m-d \ا\ل\س\ا\ع\ة H:i')); ?></small>
                                    </p>
                                    <p><strong>السعر الإجمالي:</strong> <?php echo htmlspecialchars(number_format($booking['total_price'], 2)); ?> ريال</p>
                                    <p><strong>الحالة:</strong> <?php echo getBookingStatusBadge($booking['status']); ?></p>
                                    
                                    <?php 
                                    $can_cancel = false;
                                    $minimum_hours_before_cancellation = 24; // الحد الأدنى للساعات قبل الاستلام للسماح بالإلغاء
                                    $cancellation_message = '';

                                    if ($booking['status'] === 'pending' || $booking['status'] === 'confirmed') {
                                        $pickup_datetime_obj = new DateTime($booking['pickup_datetime']);
                                        $now_datetime_obj = new DateTime();

                                        if ($pickup_datetime_obj > $now_datetime_obj) {
                                            $interval = $now_datetime_obj->diff($pickup_datetime_obj);
                                            $hours_to_pickup = ($interval->days * 24) + $interval->h + ($interval->i / 60);

                                            if ($hours_to_pickup >= $minimum_hours_before_cancellation) {
                                                $can_cancel = true;
                                            } else {
                                                $cancellation_message = 'الوقت المتبقي للاستلام أقل من ' . $minimum_hours_before_cancellation . ' ساعة.';
                                            }
                                        } else {
                                            $cancellation_message = 'فات موعد الاستلام.';
                                        }
                                    }
                                    ?>
                                    <?php if ($can_cancel): ?>
                                        <a href="cancel_booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-danger btn-sm" style="margin-top:5px;" onclick="return confirm('هل أنت متأكد أنك تريد إلغاء هذا الحجز؟ قد يتم تطبيق رسوم إلغاء حسب الشروط.');">إلغاء الحجز</a>
                                    <?php elseif (!empty($cancellation_message) && ($booking['status'] === 'pending' || $booking['status'] === 'confirmed')): ?>
                                         <small style="color: #777; display: block; margin-top: 5px;"><?php echo htmlspecialchars($cancellation_message); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-bookings">
                        <p>ليس لديك أي حجوزات حالياً.</p>
                        <a href="index.php" class="btn btn-primary">ابحث عن سيارة الآن!</a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="dashboard-card account-actions-card">
                <h2>إدارة الحساب</h2>
                <ul class="actions-list" style="list-style:none; padding:0;">
                    <li><a href="edit_profile.php" class="btn btn-secondary">تعديل الملف الشخصي</a></li>
                    <li><a href="change_password.php" class="btn btn-secondary">تغيير كلمة المرور</a></li>
                    <li><a href="logout.php" class="btn btn-danger">تسجيل الخروج</a></li>
                </ul>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
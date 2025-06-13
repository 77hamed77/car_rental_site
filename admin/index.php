<?php
$page_title = 'لوحة التحكم الرئيسية';
require_once __DIR__ . '/includes/admin_header.php'; // يتضمن db_connect و session_check

// جلب بعض الإحصائيات الأساسية
$total_users_query = "SELECT COUNT(*) as total FROM users";
$total_users_result = $conn->query($total_users_query);
$total_users = $total_users_result->fetch_assoc()['total'];

$total_cars_query = "SELECT COUNT(*) as total FROM cars";
$total_cars_result = $conn->query($total_cars_query);
$total_cars = $total_cars_result->fetch_assoc()['total'];

$total_bookings_query = "SELECT COUNT(*) as total FROM bookings";
$total_bookings_result = $conn->query($total_bookings_query);
$total_bookings = $total_bookings_result->fetch_assoc()['total'];

$pending_bookings_query = "SELECT COUNT(*) as total FROM bookings WHERE status = 'pending'";
$pending_bookings_result = $conn->query($pending_bookings_query);
$pending_bookings = $pending_bookings_result->fetch_assoc()['total'];
?>

<h1>مرحباً بك في لوحة التحكم</h1>
<p>هنا يمكنك إدارة محتوى موقع تأجير السيارات الخاص بك.</p>

<div class="admin-dashboard-stats">
    <div class="stat-card">
        <h3>إجمالي المستخدمين</h3>
        <p><?php echo $total_users; ?></p>
        <a href="manage_users.php">إدارة المستخدمين »</a>
    </div>
    <div class="stat-card">
        <h3>إجمالي السيارات</h3>
        <p><?php echo $total_cars; ?></p>
        <a href="manage_cars.php">إدارة السيارات »</a>
    </div>
    <div class="stat-card">
        <h3>إجمالي الحجوزات</h3>
        <p><?php echo $total_bookings; ?></p>
        <a href="manage_bookings.php">إدارة الحجوزات »</a>
    </div>
    <div class="stat-card">
        <h3>حجوزات قيد المراجعة</h3>
        <p><?php echo $pending_bookings; ?></p>
        <a href="manage_bookings.php?status=pending">عرض الحجوزات المعلقة »</a>
    </div>
</div>

<h2>روابط سريعة</h2>
<ul class="quick-links">
    <li><a href="manage_cars.php?action=add" class="btn">إضافة سيارة جديدة</a></li>
    <li><a href="manage_bookings.php" class="btn">عرض أحدث الحجوزات</a></li>
    <li><a href="manage_users.php?action=add" class="btn">إضافة مستخدم جديد</a></li>
    <li><a href="reports.php" class="btn">التقارير</a></li>    
</ul>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
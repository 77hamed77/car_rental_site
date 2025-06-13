<?php
$page_title = 'إدارة الحجوزات';
require_once __DIR__ . '/includes/admin_header.php';

// التعامل مع تحديث حالة الحجز
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $booking_id = intval($_POST['booking_id']);
    $new_status = sanitize_input($_POST['new_status']);
    $allowed_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];

    if ($booking_id > 0 && in_array($new_status, $allowed_statuses)) {
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $booking_id);
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = "تم تحديث حالة الحجز #" . $booking_id . " بنجاح.";
        } else {
            $_SESSION['admin_error'] = "خطأ في تحديث حالة الحجز: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['admin_error'] = "بيانات تحديث الحجز غير صالحة.";
    }
    header("Location: manage_bookings.php" . (isset($_GET['status']) ? '?status='.$_GET['status'] : '')); // ابق على نفس الفلتر
    exit();
}


// فلترة الحجوزات حسب الحالة
$filter_status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$where_clause = "";
if (!empty($filter_status)) {
    $where_clause = "WHERE b.status = '" . $conn->real_escape_string($filter_status) . "'";
}


$bookings_query = "SELECT b.*, u.full_name as user_name, u.email as user_email, 
                   c.make, c.model, 
                   pick_loc.name as pickup_location_name, 
                   ret_loc.name as return_location_name
                   FROM bookings b
                   JOIN users u ON b.user_id = u.id
                   JOIN cars c ON b.car_id = c.id
                   JOIN locations pick_loc ON b.pickup_location_id = pick_loc.id
                   JOIN locations ret_loc ON b.return_location_id = ret_loc.id
                   $where_clause
                   ORDER BY b.created_at DESC";
$bookings_result = $conn->query($bookings_query);

?>
<h2>إدارة الحجوزات <?php if($filter_status) echo "(الحالة: " . htmlspecialchars($filter_status) . ")"; ?></h2>

<div class="filters">
    <a href="manage_bookings.php" class="btn <?php echo empty($filter_status) ? 'btn-primary' : 'btn-secondary'; ?>">كل الحجوزات</a>
    <a href="manage_bookings.php?status=pending" class="btn <?php echo ($filter_status == 'pending') ? 'btn-primary' : 'btn-secondary'; ?>">قيد المراجعة</a>
    <a href="manage_bookings.php?status=confirmed" class="btn <?php echo ($filter_status == 'confirmed') ? 'btn-primary' : 'btn-secondary'; ?>">مؤكدة</a>
    <a href="manage_bookings.php?status=completed" class="btn <?php echo ($filter_status == 'completed') ? 'btn-primary' : 'btn-secondary'; ?>">مكتملة</a>
    <a href="manage_bookings.php?status=cancelled" class="btn <?php echo ($filter_status == 'cancelled') ? 'btn-primary' : 'btn-secondary'; ?>">ملغاة</a>
</div>


<table id="bookingsTable" class="admin-table display">
    <thead>
        <tr>
            <th>رقم الحجز</th>
            <th>العميل</th>
            <th>السيارة</th>
            <th>الاستلام</th>
            <th>التسليم</th>
            <th>الإجمالي</th>
            <th>الحالة</th>
            <th>تاريخ الإنشاء</th>
            <th>إجراءات</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($bookings_result && $bookings_result->num_rows > 0): ?>
            <?php while($booking = $bookings_result->fetch_assoc()): ?>
            <tr>
                <td>#<?php echo $booking['id']; ?></td>
                <td><?php echo htmlspecialchars($booking['user_name']); ?><br><small><?php echo htmlspecialchars($booking['user_email']); ?></small></td>
                <td><?php echo htmlspecialchars($booking['make'] . ' ' . $booking['model']); ?></td>
                <td><?php echo htmlspecialchars($booking['pickup_location_name']); ?><br><small><?php echo (new DateTime($booking['pickup_datetime']))->format('Y-m-d H:i'); ?></small></td>
                <td><?php echo htmlspecialchars($booking['return_location_name']); ?><br><small><?php echo (new DateTime($booking['return_datetime']))->format('Y-m-d H:i'); ?></small></td>
                <td><?php echo htmlspecialchars($booking['total_price']); ?> ريال</td>
                <td>
                    <form action="manage_bookings.php<?php echo ($filter_status ? '?status='.$filter_status : ''); ?>" method="POST" style="display:inline;">
                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                        <select name="new_status" onchange="this.form.submit()">
                            <option value="pending" <?php if($booking['status'] == 'pending') echo 'selected'; ?>>قيد المراجعة</option>
                            <option value="confirmed" <?php if($booking['status'] == 'confirmed') echo 'selected'; ?>>مؤكد</option>
                            <option value="completed" <?php if($booking['status'] == 'completed') echo 'selected'; ?>>مكتمل</option>
                            <option value="cancelled" <?php if($booking['status'] == 'cancelled') echo 'selected'; ?>>ملغى</option>
                        </select>
                        <input type="hidden" name="update_status" value="1">
                         <!-- <button type="submit" name="update_status" class="btn btn-sm">تحديث</button> -->
                    </form>
                </td>
                <td><?php echo (new DateTime($booking['created_at']))->format('Y-m-d H:i'); ?></td>
                <td>
                    <a href="view_booking_details.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm">عرض التفاصيل</a>
                    <!-- يمكنك إضافة زر حذف للحجوزات الملغاة أو القديمة -->
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="9">لا توجد حجوزات تطابق هذا الفلتر.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
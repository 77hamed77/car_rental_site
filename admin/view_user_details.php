<?php
// admin/view_user_details.php
$page_title = 'تفاصيل المستخدم';
require_once __DIR__ . '/includes/admin_header.php'; // يتضمن db_connect و admin_session_check

$user_id_to_view = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_details = null;
$user_bookings = [];
$error_message = '';

if ($user_id_to_view > 0) {
    // جلب تفاصيل المستخدم
    $stmt_user = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $user_id_to_view);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($result_user->num_rows > 0) {
        $user_details = $result_user->fetch_assoc();

        // جلب حجوزات هذا المستخدم
        $stmt_bookings = $conn->prepare("
            SELECT b.*, c.make, c.model, 
                   pick_loc.name as pickup_location_name, ret_loc.name as return_location_name
            FROM bookings b
            JOIN cars c ON b.car_id = c.id
            JOIN locations pick_loc ON b.pickup_location_id = pick_loc.id
            JOIN locations ret_loc ON b.return_location_id = ret_loc.id
            WHERE b.user_id = ?
            ORDER BY b.created_at DESC
            LIMIT 10 -- يمكنك إضافة ترقيم صفحات إذا كان هناك الكثير من الحجوزات
        ");
        $stmt_bookings->bind_param("i", $user_id_to_view);
        $stmt_bookings->execute();
        $result_bookings = $stmt_bookings->get_result();
        while ($row = $result_bookings->fetch_assoc()) {
            $user_bookings[] = $row;
        }
        $stmt_bookings->close();

    } else {
        $error_message = "لم يتم العثور على المستخدم المطلوب.";
    }
    $stmt_user->close();
} else {
    $error_message = "معرف المستخدم غير صالح.";
}

?>

<style> /* يمكن نقلها إلى admin_style.css */
    .detail-section { margin-bottom: 25px; padding: 15px; background-color: #f9f9f9; border-radius: 5px; }
    .detail-section h3 { color: #34495e; margin-top:0; margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 8px;}
    .detail-section p { margin-bottom: 8px; line-height: 1.6; }
    .detail-section strong { min-width: 150px; display: inline-block; }
    .bookings-table-condensed th, .bookings-table-condensed td { font-size: 0.9em; padding: 6px; }
</style>

<h2>تفاصيل المستخدم: <?php echo $user_details ? htmlspecialchars($user_details['full_name']) : 'غير معروف'; ?></h2>

<?php if ($error_message): ?>
    <div class="admin-alert admin-alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <p><a href="manage_users.php" class="btn btn-secondary">« العودة إلى قائمة المستخدمين</a></p>
<?php elseif ($user_details): ?>
    <div class="user-details-container">
        <div class="detail-section">
            <h3>المعلومات الشخصية</h3>
            <p><strong>الرقم التعريفي:</strong> #<?php echo htmlspecialchars($user_details['id']); ?></p>
            <p><strong>الاسم الكامل:</strong> <?php echo htmlspecialchars($user_details['full_name']); ?></p>
            <p><strong>البريد الإلكتروني:</strong> <?php echo htmlspecialchars($user_details['email']); ?></p>
            <p><strong>رقم الهاتف:</strong> <?php echo htmlspecialchars($user_details['phone_number'] ?? '-'); ?></p>
            <p><strong>العنوان:</strong> <?php echo htmlspecialchars($user_details['address'] ?? '-'); ?></p>
            <p><strong>الدور:</strong> <?php echo htmlspecialchars($user_details['role'] === 'admin' ? 'مسؤول' : 'عميل'); ?></p>
            <p><strong>تاريخ التسجيل:</strong> <?php echo htmlspecialchars((new DateTime($user_details['created_at']))->format('Y-m-d H:i:s')); ?></p>
        </div>

        <div class="detail-section">
            <h3>حجوزات المستخدم (آخر 10 حجوزات)</h3>
            <?php if (!empty($user_bookings)): ?>
                <table class="admin-table bookings-table-condensed">
                    <thead>
                        <tr>
                            <th>رقم الحجز</th>
                            <th>السيارة</th>
                            <th>الاستلام</th>
                            <th>التسليم</th>
                            <th>الإجمالي</th>
                            <th>الحالة</th>
                            <th>إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($user_bookings as $booking): ?>
                        <tr>
                            <td>#<?php echo $booking['id']; ?></td>
                            <td><?php echo htmlspecialchars($booking['make'] . ' ' . $booking['model']); ?></td>
                            <td><?php echo htmlspecialchars((new DateTime($booking['pickup_datetime']))->format('Y-m-d')); ?></td>
                            <td><?php echo htmlspecialchars((new DateTime($booking['return_datetime']))->format('Y-m-d')); ?></td>
                            <td><?php echo htmlspecialchars($booking['total_price']); ?> ريال</td>
                            <td>
                                <span class="status-badge status-<?php echo htmlspecialchars($booking['status']); ?>">
                                <?php
                                    switch ($booking['status']) {
                                        case 'pending': echo 'قيد المراجعة'; break;
                                        case 'confirmed': echo 'مؤكد'; break;
                                        case 'completed': echo 'مكتمل'; break;
                                        case 'cancelled': echo 'ملغى'; break;
                                        default: echo htmlspecialchars($booking['status']);
                                    }
                                ?>
                                </span>
                            </td>
                            <td><a href="view_booking_details.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm">عرض</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>لا توجد حجوزات لهذا المستخدم.</p>
            <?php endif; ?>
        </div>
        
        <p style="margin-top: 20px;">
            <a href="manage_users.php" class="btn btn-secondary">« العودة إلى قائمة المستخدمين</a>
            <!-- يمكنك إضافة زر لتعديل المستخدم مباشرة من هنا إذا أردت -->
            <!-- <a href="manage_users.php?action=edit&id=<?php echo $user_details['id']; ?>" class="btn">تعديل المستخدم</a> -->
        </p>
    </div>
<?php else: ?>
    <p>لا يمكن عرض تفاصيل المستخدم.</p>
    <p><a href="manage_users.php" class="btn btn-secondary">« العودة إلى قائمة المستخدمين</a></p>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
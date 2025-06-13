<?php
$page_title = 'إدارة المواقع';
require_once __DIR__ . '/includes/admin_header.php';

$message = '';
$error = '';
$edit_location = null;
$action = isset($_GET['action']) ? $_GET['action'] : 'list'; // list, add, edit
$location_id_to_edit = isset($_GET['id']) ? intval($_GET['id']) : 0;

// التعامل مع طلبات POST (إضافة أو تعديل موقع)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $location_id = isset($_POST['location_id']) ? intval($_POST['location_id']) : 0;
    $name = sanitize_input($_POST['name']);
    $address = sanitize_input($_POST['address']);
    $city = sanitize_input($_POST['city']);
    $country = sanitize_input($_POST['country']);

    if (empty($name) || empty($address) || empty($city) || empty($country)) {
        $error = "جميع الحقول (اسم الموقع، العنوان، المدينة، الدولة) مطلوبة.";
    }
    
    // يمكن إضافة تحقق من عدم تكرار اسم الموقع في نفس المدينة هنا إذا أردت

    if (empty($error)) {
        if ($location_id > 0) { // تعديل
            $stmt = $conn->prepare("UPDATE locations SET name = ?, address = ?, city = ?, country = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $name, $address, $city, $country, $location_id);
            if ($stmt->execute()) {
                $_SESSION['admin_message'] = "تم تحديث الموقع بنجاح.";
            } else {
                $_SESSION['admin_error'] = "خطأ في تحديث الموقع: " . $stmt->error;
            }
            $stmt->close();
        } else { // إضافة
            $stmt = $conn->prepare("INSERT INTO locations (name, address, city, country) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $address, $city, $country);
            if ($stmt->execute()) {
                $_SESSION['admin_message'] = "تمت إضافة الموقع بنجاح.";
            } else {
                $_SESSION['admin_error'] = "خطأ في إضافة الموقع: " . $stmt->error;
            }
            $stmt->close();
        }
        header("Location: manage_locations.php");
        exit();
    } else {
        $edit_location = $_POST;
    }
}

// التعامل مع طلبات GET (حذف أو تحضير للتعديل)
if ($action === 'edit' && $location_id_to_edit > 0) {
    $stmt = $conn->prepare("SELECT * FROM locations WHERE id = ?");
    $stmt->bind_param("i", $location_id_to_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_location = $result->fetch_assoc();
    } else {
        $_SESSION['admin_error'] = "لم يتم العثور على الموقع المطلوب للتعديل.";
        header("Location: manage_locations.php");
        exit();
    }
    $stmt->close();
} elseif ($action === 'delete' && $location_id_to_edit > 0) {
    // تحقق مما إذا كان الموقع مستخدماً في أي سيارة أو حجز
    $check_cars_stmt = $conn->prepare("SELECT COUNT(*) as count FROM cars WHERE location_id = ?");
    $check_cars_stmt->bind_param("i", $location_id_to_edit);
    $check_cars_stmt->execute();
    $cars_count = $check_cars_stmt->get_result()->fetch_assoc()['count'];
    $check_cars_stmt->close();

    $check_bookings_stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE pickup_location_id = ? OR return_location_id = ?");
    $check_bookings_stmt->bind_param("ii", $location_id_to_edit, $location_id_to_edit);
    $check_bookings_stmt->execute();
    $bookings_count = $check_bookings_stmt->get_result()->fetch_assoc()['count'];
    $check_bookings_stmt->close();

    if ($cars_count > 0 || $bookings_count > 0) {
        $error_msg = "لا يمكن حذف الموقع لأنه مستخدم في: ";
        if ($cars_count > 0) $error_msg .= $cars_count . " سيارة. ";
        if ($bookings_count > 0) $error_msg .= $bookings_count . " حجز. ";
        $error_msg .= "يرجى تعديل هذه السجلات أولاً.";
        $_SESSION['admin_error'] = $error_msg;
    } else {
        $stmt = $conn->prepare("DELETE FROM locations WHERE id = ?");
        $stmt->bind_param("i", $location_id_to_edit);
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = "تم حذف الموقع بنجاح.";
        } else {
            $_SESSION['admin_error'] = "خطأ في حذف الموقع: " . $stmt->error;
        }
        $stmt->close();
    }
    header("Location: manage_locations.php");
    exit();
}

// جلب قائمة المواقع للعرض
$locations_query = "SELECT * FROM locations ORDER BY country, city, name ASC";
$locations_result = $conn->query($locations_query);
?>

<?php if ($action === 'add' || ($action === 'edit' && $edit_location)): ?>
    <h2><?php echo ($action === 'add' ? 'إضافة موقع جديد' : 'تعديل الموقع: ' . htmlspecialchars($edit_location['name'] ?? '')); ?></h2>
    
    <?php if ($error): ?>
        <div class="admin-alert admin-alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="manage_locations.php" method="POST" class="admin-form">
        <?php if ($action === 'edit'): ?>
            <input type="hidden" name="location_id" value="<?php echo $edit_location['id']; ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="name">اسم الموقع (مثل: فرع المطار، فرع وسط المدينة):</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($edit_location['name'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="address">العنوان التفصيلي:</label>
            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($edit_location['address'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="city">المدينة:</label>
            <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($edit_location['city'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="country">الدولة:</label>
            <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($edit_location['country'] ?? ''); ?>" required>
        </div>
        <button type="submit" class="btn"><?php echo ($action === 'add' ? 'إضافة الموقع' : 'تحديث الموقع'); ?></button>
        <a href="manage_locations.php" class="btn btn-secondary">إلغاء</a>
    </form>
<?php else: ?>
    <h2>قائمة المواقع</h2>
    <p><a href="manage_locations.php?action=add" class="btn btn-primary">إضافة موقع جديد</a></p>

    <table id="locationsTable" class="admin-table display">
        <thead>
            <tr>
                <th>الرقم</th>
                <th>اسم الموقع</th>
                <th>العنوان</th>
                <th>المدينة</th>
                <th>الدولة</th>
                <th>إجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($locations_result && $locations_result->num_rows > 0): ?>
                <?php while($location = $locations_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $location['id']; ?></td>
                    <td><?php echo htmlspecialchars($location['name']); ?></td>
                    <td><?php echo htmlspecialchars($location['address']); ?></td>
                    <td><?php echo htmlspecialchars($location['city']); ?></td>
                    <td><?php echo htmlspecialchars($location['country']); ?></td>
                    <td>
                        <a href="manage_locations.php?action=edit&id=<?php echo $location['id']; ?>" class="btn btn-sm">تعديل</a>
                        <a href="manage_locations.php?action=delete&id=<?php echo $location['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد أنك تريد حذف هذا الموقع؟');">حذف</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6">لا توجد مواقع لعرضها حالياً.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
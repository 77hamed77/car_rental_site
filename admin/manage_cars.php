<?php
$page_title = 'إدارة السيارات';
require_once __DIR__ . '/includes/admin_header.php';

// متغيرات للرسائل ونموذج التعديل
$message = '';
$error = '';
$edit_car = null;
$action = isset($_GET['action']) ? $_GET['action'] : 'list'; // list, add, edit
$car_id_to_edit = isset($_GET['id']) ? intval($_GET['id']) : 0;

// جلب الفئات والمواقع للاستخدام في النماذج
$categories_query = "SELECT id, name FROM car_categories ORDER BY name";
$categories_result = $conn->query($categories_query);
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row;
}

$locations_query = "SELECT id, name, city FROM locations ORDER BY city, name";
$locations_result = $conn->query($locations_query);
$locations = [];
while ($row = $locations_result->fetch_assoc()) {
    $locations[] = $row;
}


// التعامل مع طلبات POST (إضافة أو تعديل سيارة)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_id = isset($_POST['car_id']) ? intval($_POST['car_id']) : 0;
    $make = sanitize_input($_POST['make']);
    $model = sanitize_input($_POST['model']);
    $year = intval($_POST['year']);
    $category_id = intval($_POST['category_id']);
    $transmission = sanitize_input($_POST['transmission']);
    $fuel_type = sanitize_input($_POST['fuel_type']);
    $seats = intval($_POST['seats']);
    $daily_rate = floatval($_POST['daily_rate']);
    $location_id = intval($_POST['location_id']);
    $availability_status = sanitize_input($_POST['availability_status']);
    $description = sanitize_input($_POST['description']);
    $features = sanitize_input($_POST['features']); // نص مفصول بفاصلة
    
    // معالجة رفع الصورة (أساسي)
    $image_url = $_POST['current_image_url'] ?? null; // الصورة الحالية إذا كانت موجودة (للتعديل)
    if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] == 0) {
        $target_dir = "../assets/images/cars/";
        // تأكد من أن المجلد موجود وقابل للكتابة
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $image_file_name = time() . '_' . basename($_FILES["image_url"]["name"]);
        $target_file = $target_dir . $image_file_name;
        if (move_uploaded_file($_FILES["image_url"]["tmp_name"], $target_file)) {
            $image_url = $image_file_name;
        } else {
            $error = "عفواً، حدث خطأ أثناء رفع الصورة.";
        }
    }


    if (empty($make) || empty($model) || empty($year) || empty($category_id) || empty($location_id) || empty($daily_rate)) {
        $error = "الرجاء ملء جميع الحقول الإلزامية (الصانع، الموديل، السنة، الفئة، الموقع، السعر اليومي).";
    }

    if (empty($error)) {
        if ($car_id > 0) { // تعديل
            $stmt = $conn->prepare("UPDATE cars SET make=?, model=?, year=?, category_id=?, transmission=?, fuel_type=?, seats=?, daily_rate=?, image_url=?, description=?, features=?, location_id=?, availability_status=? WHERE id=?");
            $stmt->bind_param("ssiisssidsissi", $make, $model, $year, $category_id, $transmission, $fuel_type, $seats, $daily_rate, $image_url, $description, $features, $location_id, $availability_status, $car_id);
            if ($stmt->execute()) {
                $_SESSION['admin_message'] = "تم تحديث السيارة بنجاح.";
            } else {
                $_SESSION['admin_error'] = "خطأ في تحديث السيارة: " . $stmt->error;
            }
            $stmt->close();
        } else { // إضافة
            $stmt = $conn->prepare("INSERT INTO cars (make, model, year, category_id, transmission, fuel_type, seats, daily_rate, image_url, description, features, location_id, availability_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiisssidsiss", $make, $model, $year, $category_id, $transmission, $fuel_type, $seats, $daily_rate, $image_url, $description, $features, $location_id, $availability_status);
            if ($stmt->execute()) {
                $_SESSION['admin_message'] = "تمت إضافة السيارة بنجاح.";
            } else {
                $_SESSION['admin_error'] = "خطأ في إضافة السيارة: " . $stmt->error;
            }
            $stmt->close();
        }
        // إعادة توجيه لتجنب إعادة إرسال النموذج عند تحديث الصفحة
        header("Location: manage_cars.php");
        exit();
    } else {
        // إذا كان هناك خطأ، احتفظ بالبيانات لإعادة ملء النموذج
        $edit_car = $_POST; // بشكل مبسط
        $edit_car['image_url'] = $image_url; // احتفظ بالصورة المرفوعة أو الحالية
    }
}

// التعامل مع طلبات GET (حذف أو تحضير للتعديل)
if ($action === 'edit' && $car_id_to_edit > 0) {
    $stmt = $conn->prepare("SELECT * FROM cars WHERE id = ?");
    $stmt->bind_param("i", $car_id_to_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_car = $result->fetch_assoc();
    } else {
        $_SESSION['admin_error'] = "لم يتم العثور على السيارة المطلوبة للتعديل.";
        header("Location: manage_cars.php");
        exit();
    }
    $stmt->close();
} elseif ($action === 'delete' && $car_id_to_edit > 0) {
    // يجب إضافة تحقق CSRF token هنا لمزيد من الأمان
    // أولاً، تحقق مما إذا كانت السيارة مرتبطة بأي حجوزات نشطة
    $check_bookings_stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE car_id = ? AND status IN ('pending', 'confirmed')");
    $check_bookings_stmt->bind_param("i", $car_id_to_edit);
    $check_bookings_stmt->execute();
    $bookings_count = $check_bookings_stmt->get_result()->fetch_assoc()['count'];
    $check_bookings_stmt->close();

    if ($bookings_count > 0) {
        $_SESSION['admin_error'] = "لا يمكن حذف السيارة لأنها مرتبطة بحجوزات نشطة.";
    } else {
        // يمكنك حذف الصورة المرتبطة بالسيارة من المجلد هنا (اختياري)
        // $car_to_delete_stmt = $conn->prepare("SELECT image_url FROM cars WHERE id = ?"); ... $car_to_delete_stmt->fetch_assoc()['image_url']; ... unlink(...)
        
        $stmt = $conn->prepare("DELETE FROM cars WHERE id = ?");
        $stmt->bind_param("i", $car_id_to_edit);
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = "تم حذف السيارة بنجاح.";
        } else {
            $_SESSION['admin_error'] = "خطأ في حذف السيارة: " . $stmt->error;
        }
        $stmt->close();
    }
    header("Location: manage_cars.php");
    exit();
}

// جلب قائمة السيارات للعرض
$cars_query = "SELECT c.*, cat.name as category_name, loc.name as location_name, loc.city as location_city 
               FROM cars c 
               LEFT JOIN car_categories cat ON c.category_id = cat.id
               LEFT JOIN locations loc ON c.location_id = loc.id
               ORDER BY c.id DESC";
$cars_result = $conn->query($cars_query);

?>

<?php if ($action === 'add' || ($action === 'edit' && $edit_car)): ?>
    <h2><?php echo ($action === 'add' ? 'إضافة سيارة جديدة' : 'تعديل السيارة: ' . htmlspecialchars($edit_car['make'] . ' ' . $edit_car['model'])); ?></h2>
    
    <?php if ($error): ?>
        <div class="admin-alert admin-alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="manage_cars.php" method="POST" enctype="multipart/form-data" class="admin-form">
        <?php if ($action === 'edit'): ?>
            <input type="hidden" name="car_id" value="<?php echo $edit_car['id']; ?>">
            <input type="hidden" name="current_image_url" value="<?php echo htmlspecialchars($edit_car['image_url'] ?? ''); ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="make">الصانع:</label>
            <input type="text" id="make" name="make" value="<?php echo htmlspecialchars($edit_car['make'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="model">الموديل:</label>
            <input type="text" id="model" name="model" value="<?php echo htmlspecialchars($edit_car['model'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="year">سنة الصنع:</label>
            <input type="number" id="year" name="year" value="<?php echo htmlspecialchars($edit_car['year'] ?? ''); ?>" min="1990" max="<?php echo date('Y') + 1; ?>" required>
        </div>
        <div class="form-group">
            <label for="category_id">الفئة:</label>
            <select id="category_id" name="category_id" required>
                <option value="">اختر الفئة</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo (isset($edit_car['category_id']) && $edit_car['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="transmission">ناقل الحركة:</label>
            <select id="transmission" name="transmission" required>
                <option value="Automatic" <?php echo (isset($edit_car['transmission']) && $edit_car['transmission'] == 'Automatic') ? 'selected' : ''; ?>>أوتوماتيك</option>
                <option value="Manual" <?php echo (isset($edit_car['transmission']) && $edit_car['transmission'] == 'Manual') ? 'selected' : ''; ?>>يدوي</option>
            </select>
        </div>
        <div class="form-group">
            <label for="fuel_type">نوع الوقود:</label>
            <select id="fuel_type" name="fuel_type" required>
                <option value="Petrol" <?php echo (isset($edit_car['fuel_type']) && $edit_car['fuel_type'] == 'Petrol') ? 'selected' : ''; ?>>بنزين</option>
                <option value="Diesel" <?php echo (isset($edit_car['fuel_type']) && $edit_car['fuel_type'] == 'Diesel') ? 'selected' : ''; ?>>ديزل</option>
                <option value="Electric" <?php echo (isset($edit_car['fuel_type']) && $edit_car['fuel_type'] == 'Electric') ? 'selected' : ''; ?>>كهرباء</option>
                <option value="Hybrid" <?php echo (isset($edit_car['fuel_type']) && $edit_car['fuel_type'] == 'Hybrid') ? 'selected' : ''; ?>>هجين</option>
            </select>
        </div>
        <div class="form-group">
            <label for="seats">عدد المقاعد:</label>
            <input type="number" id="seats" name="seats" value="<?php echo htmlspecialchars($edit_car['seats'] ?? ''); ?>" min="1" required>
        </div>
        <div class="form-group">
            <label for="daily_rate">السعر اليومي (ريال):</label>
            <input type="number" step="0.01" id="daily_rate" name="daily_rate" value="<?php echo htmlspecialchars($edit_car['daily_rate'] ?? ''); ?>" min="0" required>
        </div>
         <div class="form-group">
            <label for="location_id">الموقع الحالي للسيارة:</label>
            <select id="location_id" name="location_id" required>
                <option value="">اختر الموقع</option>
                <?php foreach ($locations as $location): ?>
                    <option value="<?php echo $location['id']; ?>" <?php echo (isset($edit_car['location_id']) && $edit_car['location_id'] == $location['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($location['city'] . ' - ' . $location['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="availability_status">حالة التوفر:</label>
            <select id="availability_status" name="availability_status" required>
                <option value="available" <?php echo (isset($edit_car['availability_status']) && $edit_car['availability_status'] == 'available') ? 'selected' : ''; ?>>متوفرة</option>
                <option value="rented" <?php echo (isset($edit_car['availability_status']) && $edit_car['availability_status'] == 'rented') ? 'selected' : ''; ?>>مؤجرة</option>
                <option value="maintenance" <?php echo (isset($edit_car['availability_status']) && $edit_car['availability_status'] == 'maintenance') ? 'selected' : ''; ?>>صيانة</option>
            </select>
        </div>
        <div class="form-group">
            <label for="image_url">صورة السيارة:</label>
            <input type="file" id="image_url" name="image_url" accept="image/*">
            <?php if (isset($edit_car['image_url']) && !empty($edit_car['image_url'])): ?>
                <p>الصورة الحالية: <img src="../assets/images/cars/<?php echo htmlspecialchars($edit_car['image_url']); ?>" alt="صورة السيارة" style="max-width: 100px; max-height: 100px;"></p>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label for="description">الوصف:</label>
            <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($edit_car['description'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="features">الميزات (افصل بينها بفاصلة ,):</label>
            <input type="text" id="features" name="features" value="<?php echo htmlspecialchars($edit_car['features'] ?? ''); ?>" placeholder="مثال: GPS,بلوتوث,فتحة سقف">
        </div>

        <button type="submit" class="btn"><?php echo ($action === 'add' ? 'إضافة السيارة' : 'تحديث السيارة'); ?></button>
        <a href="manage_cars.php" class="btn btn-secondary">إلغاء</a>
    </form>
<?php else: ?>
    <h2>قائمة السيارات</h2>
    <p><a href="manage_cars.php?action=add" class="btn btn-primary">إضافة سيارة جديدة</a></p>

    <table id="carsTable" class="admin-table display">
        <thead>
            <tr>
                <th>الرقم</th>
                <th>الصورة</th>
                <th>الصانع والموديل</th>
                <th>السنة</th>
                <th>الفئة</th>
                <th>الموقع</th>
                <th>السعر/يوم</th>
                <th>الحالة</th>
                <th>إجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($cars_result && $cars_result->num_rows > 0): ?>
                <?php while($car = $cars_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $car['id']; ?></td>
                    <td><img src="../assets/images/cars/<?php echo htmlspecialchars($car['image_url'] ? $car['image_url'] : 'default_car.png'); ?>" alt="صورة" style="width:70px; height:auto;"></td>
                    <td><?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?></td>
                    <td><?php echo htmlspecialchars($car['year']); ?></td>
                    <td><?php echo htmlspecialchars($car['category_name']); ?></td>
                    <td><?php echo htmlspecialchars($car['location_city'] . ' - ' . $car['location_name']); ?></td>
                    <td><?php echo htmlspecialchars($car['daily_rate']); ?> ريال</td>
                    <td>
                        <?php 
                            $status_class = '';
                            $status_text = '';
                            switch ($car['availability_status']) {
                                case 'available': $status_class = 'status-available'; $status_text = 'متوفرة'; break;
                                case 'rented': $status_class = 'status-rented'; $status_text = 'مؤجرة'; break;
                                case 'maintenance': $status_class = 'status-maintenance'; $status_text = 'صيانة'; break;
                            }
                            echo '<span class="status-badge ' . $status_class . '">' . $status_text . '</span>';
                        ?>
                    </td>
                    <td>
                        <a href="manage_cars.php?action=edit&id=<?php echo $car['id']; ?>" class="btn btn-sm">تعديل</a>
                        <a href="manage_cars.php?action=delete&id=<?php echo $car['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد أنك تريد حذف هذه السيارة؟ هذا الإجراء لا يمكن التراجع عنه إذا لم تكن هناك حجوزات مرتبطة.');">حذف</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="9">لا توجد سيارات لعرضها حالياً.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
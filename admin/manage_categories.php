<?php
$page_title = 'إدارة فئات السيارات';
require_once __DIR__ . '/includes/admin_header.php'; // يتضمن db_connect و admin_session_check

$message = '';
$error = '';
$edit_category = null;
$action = isset($_GET['action']) ? $_GET['action'] : 'list'; // list, add, edit
$category_id_to_edit = isset($_GET['id']) ? intval($_GET['id']) : 0;

// التعامل مع طلبات POST (إضافة أو تعديل فئة)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description']);

    if (empty($name)) {
        $error = "اسم الفئة مطلوب.";
    }

    if (empty($error)) {
        // التحقق من عدم تكرار اسم الفئة
        $check_stmt = $conn->prepare("SELECT id FROM car_categories WHERE name = ? AND id != ?");
        $check_stmt->bind_param("si", $name, $category_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows > 0) {
            $error = "اسم الفئة هذا موجود بالفعل.";
        }
        $check_stmt->close();
    }

    if (empty($error)) {
        if ($category_id > 0) { // تعديل
            $stmt = $conn->prepare("UPDATE car_categories SET name = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $description, $category_id);
            if ($stmt->execute()) {
                $_SESSION['admin_message'] = "تم تحديث الفئة بنجاح.";
            } else {
                $_SESSION['admin_error'] = "خطأ في تحديث الفئة: " . $stmt->error;
            }
            $stmt->close();
        } else { // إضافة
            $stmt = $conn->prepare("INSERT INTO car_categories (name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $description);
            if ($stmt->execute()) {
                $_SESSION['admin_message'] = "تمت إضافة الفئة بنجاح.";
            } else {
                $_SESSION['admin_error'] = "خطأ في إضافة الفئة: " . $stmt->error;
            }
            $stmt->close();
        }
        header("Location: manage_categories.php");
        exit();
    } else {
        $edit_category = $_POST; // للاحتفاظ بالبيانات في النموذج
    }
}

// التعامل مع طلبات GET (حذف أو تحضير للتعديل)
if ($action === 'edit' && $category_id_to_edit > 0) {
    $stmt = $conn->prepare("SELECT * FROM car_categories WHERE id = ?");
    $stmt->bind_param("i", $category_id_to_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_category = $result->fetch_assoc();
    } else {
        $_SESSION['admin_error'] = "لم يتم العثور على الفئة المطلوبة للتعديل.";
        header("Location: manage_categories.php");
        exit();
    }
    $stmt->close();
} elseif ($action === 'delete' && $category_id_to_edit > 0) {
    // تحقق مما إذا كانت الفئة مستخدمة في أي سيارة
    $check_cars_stmt = $conn->prepare("SELECT COUNT(*) as count FROM cars WHERE category_id = ?");
    $check_cars_stmt->bind_param("i", $category_id_to_edit);
    $check_cars_stmt->execute();
    $cars_count = $check_cars_stmt->get_result()->fetch_assoc()['count'];
    $check_cars_stmt->close();

    if ($cars_count > 0) {
        $_SESSION['admin_error'] = "لا يمكن حذف الفئة لأنها مستخدمة في " . $cars_count . " سيارة. يرجى تغيير فئة هذه السيارات أولاً.";
    } else {
        $stmt = $conn->prepare("DELETE FROM car_categories WHERE id = ?");
        $stmt->bind_param("i", $category_id_to_edit);
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = "تم حذف الفئة بنجاح.";
        } else {
            $_SESSION['admin_error'] = "خطأ في حذف الفئة: " . $stmt->error;
        }
        $stmt->close();
    }
    header("Location: manage_categories.php");
    exit();
}

// جلب قائمة الفئات للعرض
$categories_query = "SELECT * FROM car_categories ORDER BY name ASC";
$categories_result = $conn->query($categories_query);
?>

<?php if ($action === 'add' || ($action === 'edit' && $edit_category)): ?>
    <h2><?php echo ($action === 'add' ? 'إضافة فئة جديدة' : 'تعديل الفئة: ' . htmlspecialchars($edit_category['name'] ?? '')); ?></h2>
    
    <?php if ($error): ?>
        <div class="admin-alert admin-alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="manage_categories.php" method="POST" class="admin-form">
        <?php if ($action === 'edit'): ?>
            <input type="hidden" name="category_id" value="<?php echo $edit_category['id']; ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="name">اسم الفئة:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($edit_category['name'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="description">الوصف (اختياري):</label>
            <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($edit_category['description'] ?? ''); ?></textarea>
        </div>
        <button type="submit" class="btn"><?php echo ($action === 'add' ? 'إضافة الفئة' : 'تحديث الفئة'); ?></button>
        <a href="manage_categories.php" class="btn btn-secondary">إلغاء</a>
    </form>
<?php else: ?>
    <h2>قائمة فئات السيارات</h2>
    <p><a href="manage_categories.php?action=add" class="btn btn-primary">إضافة فئة جديدة</a></p>

    <table id="categoriesTable" class="admin-table display">
        <thead>
            <tr>
                <th>الرقم</th>
                <th>اسم الفئة</th>
                <th>الوصف</th>
                <th>إجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($categories_result && $categories_result->num_rows > 0): ?>
                <?php while($category = $categories_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $category['id']; ?></td>
                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                    <td><?php echo htmlspecialchars($category['description'] ?? '-'); ?></td>
                    <td>
                        <a href="manage_categories.php?action=edit&id=<?php echo $category['id']; ?>" class="btn btn-sm">تعديل</a>
                        <a href="manage_categories.php?action=delete&id=<?php echo $category['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد أنك تريد حذف هذه الفئة؟');">حذف</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">لا توجد فئات لعرضها حالياً.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
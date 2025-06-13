<?php
$page_title = 'إدارة المستخدمين';
require_once __DIR__ . '/includes/admin_header.php';

// التعامل مع تغيير دور المستخدم أو حذفه (ببساطة)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['change_role'])) {
        $user_id_to_change = intval($_POST['user_id']);
        $new_role = sanitize_input($_POST['new_role']);
        if ($user_id_to_change > 0 && ($new_role === 'customer' || $new_role === 'admin')) {
            // لا تسمح بتغيير دور المستخدم الحالي (المسؤول) إلى customer إذا كان هو المسؤول الوحيد
            if ($user_id_to_change == $_SESSION['user_id'] && $new_role === 'customer') {
                 // تحقق مما إذا كان هناك مسؤولون آخرون
                $other_admins_q = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND id != " . $_SESSION['user_id']);
                $other_admins_count = $other_admins_q->fetch_assoc()['count'];
                if ($other_admins_count == 0) {
                    $_SESSION['admin_error'] = "لا يمكنك تغيير دورك إلى عميل لأنك المسؤول الوحيد.";
                    header("Location: manage_users.php");
                    exit();
                }
            }
            
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->bind_param("si", $new_role, $user_id_to_change);
            if ($stmt->execute()) {
                $_SESSION['admin_message'] = "تم تغيير دور المستخدم بنجاح.";
            } else {
                $_SESSION['admin_error'] = "خطأ في تغيير دور المستخدم.";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['delete_user'])) {
        $user_id_to_delete = intval($_POST['user_id']);
        if ($user_id_to_delete > 0 && $user_id_to_delete != $_SESSION['user_id']) { // لا تسمح للمسؤول بحذف نفسه
             // تحقق من الحجوزات المرتبطة، قد ترغب في عدم الحذف أو تحويلها لمستخدم عام
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id_to_delete);
            if ($stmt->execute()) {
                $_SESSION['admin_message'] = "تم حذف المستخدم بنجاح.";
            } else {
                $_SESSION['admin_error'] = "خطأ في حذف المستخدم (قد يكون مرتبط بحجوزات).";
            }
            $stmt->close();
        } elseif ($user_id_to_delete == $_SESSION['user_id']) {
            $_SESSION['admin_error'] = "لا يمكنك حذف حسابك الخاص من هنا.";
        }
    }
    header("Location: manage_users.php");
    exit();
}


$users_query = "SELECT id, full_name, email, phone_number, role, created_at FROM users ORDER BY created_at DESC";
$users_result = $conn->query($users_query);
?>

<h2>إدارة المستخدمين</h2>
<!-- يمكنك إضافة نموذج لإضافة مستخدم جديد هنا إذا أردت -->
<!-- <p><a href="manage_users.php?action=add" class="btn btn-primary">إضافة مستخدم جديد</a></p> -->

<table id="usersTable" class="admin-table display">
    <thead>
        <tr>
            <th>الرقم</th>
            <th>الاسم الكامل</th>
            <th>البريد الإلكتروني</th>
            <th>رقم الهاتف</th>
            <th>الدور</th>
            <th>تاريخ التسجيل</th>
            <th>إجراءات</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($users_result && $users_result->num_rows > 0): ?>
            <?php while($user = $users_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo htmlspecialchars($user['phone_number'] ?? '-'); ?></td>
                <td>
                    <form action="manage_users.php" method="POST" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <select name="new_role" <?php if ($user['id'] == $_SESSION['user_id']) echo 'disabled'; // لا تغير دورك مباشرة هكذا  ?> onchange="if(confirm('هل أنت متأكد من تغيير دور هذا المستخدم؟')) this.form.submit();">
                            <option value="customer" <?php if($user['role'] == 'customer') echo 'selected'; ?>>عميل</option>
                            <option value="admin" <?php if($user['role'] == 'admin') echo 'selected'; ?>>مسؤول</option>
                        </select>
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                        <input type="hidden" name="change_role" value="1">
                        <!-- <button type="submit" name="change_role" class="btn btn-sm">تغيير الدور</button> -->
                        <?php endif; ?>
                    </form>
                </td>
                <td><?php echo (new DateTime($user['created_at']))->format('Y-m-d H:i'); ?></td>
                <td>
                    <?php if ($user['id'] != $_SESSION['user_id']): // لا تسمح بحذف المسؤول لنفسه ?>
                    <form action="manage_users.php" method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد أنك تريد حذف هذا المستخدم؟ هذا الإجراء لا يمكن التراجع عنه.');">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <button type="submit" name="delete_user" class="btn btn-sm btn-danger">حذف</button>
                    </form>
                    <?php else: ?>
                        (حسابك الحالي)
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7">لا يوجد مستخدمون لعرضهم.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
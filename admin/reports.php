<?php
// admin/reports.php
$page_title = 'التقارير المتقدمة مع الرسوم البيانية';
require_once __DIR__ . '/includes/admin_header.php'; // يتضمن db_connect و admin_session_check

// ... (نفس كود الفلاتر الزمنية ودالة getDateConditions من الرد السابق) ...
$filter_period = isset($_GET['period']) ? sanitize_input($_GET['period']) : 'current_month';
$custom_start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : null;
$custom_end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : null;

function getDateConditions($conn, $period, $custom_start, $custom_end, $date_field_bookings = 'b.created_at', $date_field_completed = 'b.return_datetime') {
    $conditions = ['bookings' => '', 'completed' => ''];
    $start_date = null;
    $end_date = null;
    switch ($period) {
        case 'today': $start_date = date('Y-m-d 00:00:00'); $end_date = date('Y-m-d 23:59:59'); break;
        case 'yesterday': $start_date = date('Y-m-d 00:00:00', strtotime('-1 day')); $end_date = date('Y-m-d 23:59:59', strtotime('-1 day')); break;
        case 'last_7_days': $start_date = date('Y-m-d 00:00:00', strtotime('-6 days')); $end_date = date('Y-m-d 23:59:59'); break;
        case 'current_month': $start_date = date('Y-m-01 00:00:00'); $end_date = date('Y-m-t 23:59:59'); break;
        case 'last_month': $start_date = date('Y-m-01 00:00:00', strtotime('first day of last month')); $end_date = date('Y-m-t 23:59:59', strtotime('last day of last month')); break;
        case 'current_year': $start_date = date('Y-01-01 00:00:00'); $end_date = date('Y-12-31 23:59:59'); break;
        case 'custom_range':
            if ($custom_start && $custom_end) {
                try {
                    $start_dt_obj = new DateTime($custom_start); $end_dt_obj = new DateTime($custom_end);
                    $start_date = $start_dt_obj->format('Y-m-d 00:00:00'); $end_date = $end_dt_obj->format('Y-m-d 23:59:59');
                    if ($end_dt_obj < $start_dt_obj) { $temp = $start_date; $start_date = $end_date; $end_date = $temp; }
                } catch (Exception $e) { /* use default */ }
            } break;
        default: return $conditions;
    }
    if ($start_date && $end_date) {
        $conditions['bookings'] = " AND $date_field_bookings BETWEEN '" . $conn->real_escape_string($start_date) . "' AND '" . $conn->real_escape_string($end_date) . "'";
        $conditions['completed'] = " AND $date_field_completed BETWEEN '" . $conn->real_escape_string($start_date) . "' AND '" . $conn->real_escape_string($end_date) . "'";
    }
    return $conditions;
}
$date_conditions = getDateConditions($conn, $filter_period, $custom_start_date, $custom_end_date);
$date_condition_bookings = $date_conditions['bookings'];
$date_condition_completed_bookings = $date_conditions['completed'];


// --- إعداد البيانات للرسوم البيانية ---
$chart_data = [
    'monthly_revenue' => ['labels' => [], 'data' => []],
    'booking_status_counts' => ['labels' => [], 'data' => [], 'colors' => []]
];
$months_ar_map = ["يناير", "فبراير", "مارس", "أبريل", "مايو", "يونيو", "يوليو", "أغسطس", "سبتمبر", "أكتوبر", "نوفمبر", "ديسمبر"];
$status_colors = [ // ألوان مقترحة للرسوم البيانية
    'pending' => 'rgba(255, 193, 7, 0.7)',   // برتقالي
    'confirmed' => 'rgba(40, 167, 69, 0.7)', // أخضر
    'completed' => 'rgba(0, 123, 255, 0.7)', // أزرق
    'cancelled' => 'rgba(220, 53, 69, 0.7)'  // أحمر
];
$status_translation = [
    'pending' => 'قيد المراجعة', 'confirmed' => 'مؤكد', 
    'completed' => 'مكتمل', 'cancelled' => 'ملغى'
];


// --- 1. السيارات الأكثر تأجيراً (نفس الاستعلام السابق) ---
$top_rented_cars_query = "SELECT c.make, c.model, c.year, COUNT(b.id) as booking_count FROM cars c JOIN bookings b ON c.id = b.car_id WHERE b.status IN ('confirmed', 'completed') $date_condition_bookings GROUP BY c.id, c.make, c.model, c.year ORDER BY booking_count DESC LIMIT 10";
$top_rented_cars_result = $conn->query($top_rented_cars_query);

// --- 2. إجمالي الإيرادات (نفس الاستعلام السابق) ---
$total_revenue_query = "SELECT SUM(total_price) as total_revenue, COUNT(id) as completed_bookings_count FROM bookings b WHERE status = 'completed' $date_condition_completed_bookings";
$total_revenue_result = $conn->query($total_revenue_query);
$revenue_data = $total_revenue_result ? $total_revenue_result->fetch_assoc() : ['total_revenue' => 0, 'completed_bookings_count' => 0];
$total_revenue = $revenue_data['total_revenue'] ?? 0;
$completed_bookings_count = $revenue_data['completed_bookings_count'] ?? 0;

// --- 3. عدد الحجوزات حسب الحالة (للحجوزات المنشأة خلال الفترة) + إعداد بيانات الرسم البياني ---
$booking_status_counts_query = "SELECT status, COUNT(*) as count FROM bookings b WHERE 1 $date_condition_bookings GROUP BY status";
$booking_status_counts_result = $conn->query($booking_status_counts_query);
if ($booking_status_counts_result) {
    while($row = $booking_status_counts_result->fetch_assoc()) {
        $translated_status = $status_translation[$row['status']] ?? ucfirst($row['status']);
        $chart_data['booking_status_counts']['labels'][] = $translated_status;
        $chart_data['booking_status_counts']['data'][] = (int)$row['count'];
        $chart_data['booking_status_counts']['colors'][] = $status_colors[$row['status']] ?? 'rgba(108, 117, 125, 0.7)'; // لون افتراضي
    }
}
// إعادة مؤشر القراءة للنتيجة لاستخدامها في الجدول لاحقاً
if ($booking_status_counts_result) $booking_status_counts_result->data_seek(0);


// --- 4. الإيرادات الشهرية (للسنة الحالية أو الفترة المحددة إذا كانت ضمن سنة واحدة) + إعداد بيانات الرسم البياني ---
// إذا كان النطاق المخصص يمتد لأكثر من سنة، هذا الرسم البياني سيظل للسنة الحالية. يمكن تعقيده أكثر.
$year_for_monthly_chart = date('Y');
if ($filter_period === 'custom_range' && $custom_start_date && $custom_end_date) {
    $start_year = date('Y', strtotime($custom_start_date));
    $end_year = date('Y', strtotime($custom_end_date));
    if ($start_year === $end_year) { // إذا كان النطاق المخصص ضمن نفس السنة
        $year_for_monthly_chart = $start_year;
    } // وإلا، سيعرض بيانات السنة الحالية كافتراضي للرسم البياني الشهري
} elseif ($filter_period === 'current_year' || $filter_period === 'last_month' || $filter_period === 'current_month') {
     $year_for_monthly_chart = $filter_period === 'last_month' ? date('Y', strtotime('first day of last month')) : date('Y');
}


$monthly_revenue_query = "
    SELECT DATE_FORMAT(b.return_datetime, '%m') as month_num, SUM(b.total_price) as monthly_revenue
    FROM bookings b
    WHERE b.status = 'completed' AND DATE_FORMAT(b.return_datetime, '%Y') = '$year_for_monthly_chart'
    GROUP BY month_num
    ORDER BY month_num ASC
";
$monthly_revenue_result_for_chart = $conn->query($monthly_revenue_query);
$raw_monthly_data = [];
if ($monthly_revenue_result_for_chart) {
    while($row = $monthly_revenue_result_for_chart->fetch_assoc()) {
        $raw_monthly_data[(int)$row['month_num']] = (float)$row['monthly_revenue'];
    }
}
// ملء جميع الأشهر (حتى لو كانت صفرية) للرسم البياني
for ($m = 1; $m <= 12; $m++) {
    $chart_data['monthly_revenue']['labels'][] = $months_ar_map[$m-1];
    $chart_data['monthly_revenue']['data'][] = $raw_monthly_data[$m] ?? 0;
}
// إعادة مؤشر القراءة للنتيجة لاستخدامها في الجدول لاحقاً (إذا كان الاستعلام مختلفًا)
// هذا الاستعلام للرسم البياني، يمكن عمل استعلام آخر للجدول إذا كانت الفترات مختلفة.
// سأستخدم نفس نتيجة الاستعلام للجدول مع تعديل العرض.
$monthly_revenue_result_for_table_query = "
    SELECT DATE_FORMAT(b.return_datetime, '%Y-%m') as month, SUM(b.total_price) as monthly_revenue
    FROM bookings b
    WHERE b.status = 'completed' AND DATE_FORMAT(b.return_datetime, '%Y') = '$year_for_monthly_chart'
    GROUP BY month
    ORDER BY month ASC
";
$monthly_revenue_result_for_table = $conn->query($monthly_revenue_result_for_table_query);


// --- 5. متوسط مدة الإيجار (نفس الاستعلام السابق) ---
$avg_rental_duration_query = "SELECT AVG(DATEDIFF(b.return_datetime, b.pickup_datetime)) as avg_duration_days FROM bookings b WHERE b.status = 'completed' $date_condition_completed_bookings";
$avg_rental_duration_result = $conn->query($avg_rental_duration_query);
$avg_duration_days = $avg_rental_duration_result ? $avg_rental_duration_result->fetch_assoc()['avg_duration_days'] : 0;

// --- 6. المستخدمون الأكثر نشاطًا (نفس الاستعلام السابق) ---
$top_active_users_query = "SELECT u.full_name, u.email, COUNT(b.id) as total_bookings, SUM(b.total_price) as total_spent FROM users u JOIN bookings b ON u.id = b.user_id WHERE b.status = 'completed' $date_condition_completed_bookings GROUP BY u.id, u.full_name, u.email ORDER BY total_spent DESC LIMIT 10";
$top_active_users_result = $conn->query($top_active_users_query);

?>
<style>
    /* ... (نفس أنماط CSS من الرد السابق) ... */
    .chart-container { position: relative; height: 300px; width: 100%; margin-bottom: 30px; } /* حجم للرسم البياني */
    .grid-reports { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; }
</style>

<h2>التقارير والإحصائيات المتقدمة</h2>

<form action="reports.php" method="GET" class="filter-form">
    <!-- ... (نفس نموذج الفلاتر من الرد السابق) ... -->
    <label for="period">اختر الفترة:</label>
    <select name="period" id="period" onchange="toggleCustomDateRange(this.value)">
        <option value="all_time" <?php if ($filter_period == 'all_time') echo 'selected'; ?>>كل الأوقات</option>
        <option value="today" <?php if ($filter_period == 'today') echo 'selected'; ?>>اليوم</option>
        <option value="yesterday" <?php if ($filter_period == 'yesterday') echo 'selected'; ?>>الأمس</option>
        <option value="last_7_days" <?php if ($filter_period == 'last_7_days') echo 'selected'; ?>>آخر 7 أيام</option>
        <option value="current_month" <?php if ($filter_period == 'current_month') echo 'selected'; ?>>هذا الشهر</option>
        <option value="last_month" <?php if ($filter_period == 'last_month') echo 'selected'; ?>>الشهر الماضي</option>
        <option value="current_year" <?php if ($filter_period == 'current_year') echo 'selected'; ?>>هذه السنة</option>
        <option value="custom_range" <?php if ($filter_period == 'custom_range') echo 'selected'; ?>>نطاق مخصص</option>
    </select>
    <span id="customDateRange" style="<?php if ($filter_period != 'custom_range') echo 'display:none;'; ?>">
        <label for="start_date">من:</label> <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($custom_start_date ?? ''); ?>">
        <label for="end_date">إلى:</label> <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($custom_end_date ?? ''); ?>">
    </span>
    <button type="submit" class="btn btn-primary">تطبيق الفلتر</button>
</form>

<div class="grid-reports">
    <div class="report-section">
        <h3>نظرة عامة على الإيرادات (الفترة المحددة)</h3>
        <p>إجمالي الإيرادات من الحجوزات المكتملة: <span class="kpi-value"><?php echo number_format($total_revenue ?? 0, 2); ?> ريال</span></p>
        <p>عدد الحجوزات المكتملة: <span class="kpi-value"><?php echo $completed_bookings_count ?? 0; ?></span></p>
        <p>متوسط مدة الإيجار (أيام): <span class="kpi-value"><?php echo number_format($avg_duration_days ?? 0, 1); ?></span></p>
    </div>

    <div class="report-section">
        <h3>إحصائيات حالات الحجوزات (للحجوزات المنشأة خلال الفترة)</h3>
        <div class="chart-container">
            <canvas id="bookingStatusChart"></canvas>
        </div>
        <?php /* الجدول لا يزال مفيدًا كمرجع */ ?>
        <?php if ($booking_status_counts_result && $booking_status_counts_result->num_rows > 0): ?>
            <table class="report-table"> <!-- ... (نفس كود الجدول من الرد السابق) ... --> </table>
        <?php else: ?><p>لا توجد بيانات لعرض إحصائيات الحالات للفترة المحددة.</p><?php endif; ?>
    </div>
</div>


<div class="report-section">
    <h3>الإيرادات الشهرية (للسنة: <?php echo $year_for_monthly_chart; ?>)</h3>
    <div class="chart-container">
        <canvas id="monthlyRevenueChart"></canvas>
    </div>
    <?php /* الجدول لا يزال مفيدًا كمرجع */ ?>
    <?php if ($monthly_revenue_result_for_table && $monthly_revenue_result_for_table->num_rows > 0): ?>
        <table class="report-table"> <!-- ... (نفس كود الجدول من الرد السابق مع تعديل العرض للشهر) ... --></table>
    <?php else: ?><p>لا توجد بيانات إيرادات شهرية لهذه السنة.</p><?php endif; ?>
</div>


<div class="report-section">
    <h3>السيارات الأكثر تأجيراً (أعلى 10 خلال الفترة المحددة)</h3>
    <?php if ($top_rented_cars_result && $top_rented_cars_result->num_rows > 0): ?>
        <table class="report-table"> <!-- ... (نفس كود الجدول من الرد السابق) ... --> </table>
    <?php else: ?><p>لا توجد بيانات كافية لعرض هذا التقرير للفترة المحددة.</p><?php endif; ?>
</div>

<div class="report-section">
    <h3>المستخدمون الأكثر نشاطًا (أعلى 10 حسب قيمة الحجوزات المكتملة خلال الفترة)</h3>
    <?php if ($top_active_users_result && $top_active_users_result->num_rows > 0): ?>
        <table class="report-table"> <!-- ... (نفس كود الجدول من الرد السابق) ... --> </table>
    <?php else: ?><p>لا توجد بيانات كافية لعرض هذا التقرير للفترة المحددة.</p><?php endif; ?>
</div>


<script>
function toggleCustomDateRange(period) {
    document.getElementById('customDateRange').style.display = (period === 'custom_range') ? 'inline' : 'none';
}

document.addEventListener('DOMContentLoaded', function () {
    const chartData = <?php echo json_encode($chart_data); ?>;

    // 1. Booking Status Chart (Pie or Doughnut)
    const statusCtx = document.getElementById('bookingStatusChart');
    if (statusCtx && chartData.booking_status_counts.labels.length > 0) {
        new Chart(statusCtx, {
            type: 'doughnut', //  'pie'
            data: {
                labels: chartData.booking_status_counts.labels,
                datasets: [{
                    label: 'عدد الحجوزات',
                    data: chartData.booking_status_counts.data,
                    backgroundColor: chartData.booking_status_counts.colors,
                    borderColor: chartData.booking_status_counts.colors.map(color => color.replace('0.7', '1')), // أكثر وضوحًا للحدود
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: { display: true, text: 'توزيع حالات الحجوزات' },
                    legend: { position: 'top' }
                }
            }
        });
    }

    // 2. Monthly Revenue Chart (Bar or Line)
    const revenueCtx = document.getElementById('monthlyRevenueChart');
    if (revenueCtx && chartData.monthly_revenue.labels.length > 0) {
        new Chart(revenueCtx, {
            type: 'bar', // 'line'
            data: {
                labels: chartData.monthly_revenue.labels,
                datasets: [{
                    label: 'الإيرادات الشهرية (ريال)',
                    data: chartData.monthly_revenue.data,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)', // أزرق
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    tension: 0.1 // إذا كان line chart
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: { display: true, text: 'الإيرادات الشهرية لسنة <?php echo $year_for_monthly_chart; ?>' },
                    legend: { display: false } // يمكن إخفاء الوسيلة الإيضاحية إذا كان هناك مجموعة بيانات واحدة فقط
                },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: function(value) { return value + ' ريال'; } } }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$page_title = "نتائج البحث عن السيارات";
$cars = [];
$search_params_text_array = []; // لتجميع معايير البحث كنص

// --- استخلاص معايير البحث والفلترة ---
$pickup_location_id = isset($_GET['pickup_location_id']) ? intval($_GET['pickup_location_id']) : null;
$return_location_id = isset($_GET['return_location_id']) && !empty($_GET['return_location_id']) ? intval($_GET['return_location_id']) : $pickup_location_id;
$pickup_datetime_str = isset($_GET['pickup_date']) && !empty($_GET['pickup_date']) ? sanitize_input($_GET['pickup_date']) : null;
$return_datetime_str = isset($_GET['return_date']) && !empty($_GET['return_date']) ? sanitize_input($_GET['return_date']) : null;
$category_id_filter = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;
$make_filter = isset($_GET['make']) && !empty($_GET['make']) ? sanitize_input($_GET['make']) : null;
$min_price_filter = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? floatval($_GET['min_price']) : null;
$max_price_filter = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? floatval($_GET['max_price']) : null;
$transmission_filter = isset($_GET['transmission']) && !empty($_GET['transmission']) ? sanitize_input($_GET['transmission']) : null;
$seats_filter = isset($_GET['seats']) && is_numeric($_GET['seats']) ? intval($_GET['seats']) : null;

$show_all = isset($_GET['show_all']) && $_GET['show_all'] == 'true'; // لعرض كل السيارات إذا لم تكن هناك معايير بحث

// --- خيارات الترتيب ---
$sort_options = [
    'price_asc' => 'السعر: من الأقل للأعلى',
    'price_desc' => 'السعر: من الأعلى للأقل',
    'year_desc' => 'سنة الصنع: الأحدث أولاً',
    'year_asc' => 'سنة الصنع: الأقدم أولاً',
    // 'rating_desc' => 'التقييم: الأعلى أولاً', // إذا كان لديك نظام تقييم
];
$sort_by = isset($_GET['sort_by']) && array_key_exists($_GET['sort_by'], $sort_options) ? $_GET['sort_by'] : 'price_asc'; // الترتيب الافتراضي

// --- بناء استعلام SQL ---
$sql = "SELECT c.*, cat.name as category_name, pick_loc.name as pickup_location_name, pick_loc.city as pickup_city
        FROM cars c
        LEFT JOIN car_categories cat ON c.category_id = cat.id
        LEFT JOIN locations pick_loc ON c.location_id = pick_loc.id
        WHERE 1"; // نبدأ بـ WHERE 1 لتسهيل إضافة الشروط

$params = [];
$types = "";

if (!$show_all) { // طبق الفلاتر فقط إذا لم يكن "عرض الكل"
    $sql .= " AND c.availability_status = 'available'"; // بشكل افتراضي، السيارات المتاحة فقط

    if ($pickup_location_id) {
        $sql .= " AND c.location_id = ?";
        $params[] = $pickup_location_id;
        $types .= "i";
        // جلب اسم الموقع لعرضه
        $loc_stmt_pickup = $conn->prepare("SELECT name, city FROM locations WHERE id = ?");
        $loc_stmt_pickup->bind_param("i", $pickup_location_id);
        $loc_stmt_pickup->execute();
        $loc_result_pickup = $loc_stmt_pickup->get_result();
        if($loc_row_pickup = $loc_result_pickup->fetch_assoc()) {
            $search_params_text_array[] = "الاستلام من: " . htmlspecialchars($loc_row_pickup['city'] . ' - ' . $loc_row_pickup['name']);
        }
        $loc_stmt_pickup->close();
    }

    if ($pickup_datetime_str && $return_datetime_str) {
        try {
            $pickup_datetime = new DateTime($pickup_datetime_str);
            $return_datetime = new DateTime($return_datetime_str);
            if ($return_datetime > $pickup_datetime) {
                $pickup_db_format = $pickup_datetime->format('Y-m-d H:i:s');
                $return_db_format = $return_datetime->format('Y-m-d H:i:s');
                $sql .= " AND c.id NOT IN (
                            SELECT car_id FROM bookings
                            WHERE status != 'cancelled' AND (
                                (pickup_datetime < ? AND return_datetime > ?) OR
                                (pickup_datetime >= ? AND pickup_datetime < ?) OR
                                (return_datetime > ? AND return_datetime <= ?)
                            )
                        )";
                array_push($params, $return_db_format, $pickup_db_format, $pickup_db_format, $return_db_format, $pickup_db_format, $return_db_format);
                $types .= "ssssss";
                $search_params_text_array[] = "الفترة: " . htmlspecialchars($pickup_datetime_str) . " إلى " . htmlspecialchars($return_datetime_str);
            }
        } catch (Exception $e) { /* تجاهل التواريخ غير الصالحة */ }
    }
} else {
     $search_params_text_array[] = "جميع السيارات المتوفرة في الموقع";
}


// تطبيق الفلاتر الإضافية
if ($category_id_filter) { $sql .= " AND c.category_id = ?"; $params[] = $category_id_filter; $types .= "i"; }
if ($make_filter) { $sql .= " AND c.make LIKE ?"; $params[] = "%" . $make_filter . "%"; $types .= "s"; }
if ($min_price_filter !== null) { $sql .= " AND c.daily_rate >= ?"; $params[] = $min_price_filter; $types .= "d"; }
if ($max_price_filter !== null) { $sql .= " AND c.daily_rate <= ?"; $params[] = $max_price_filter; $types .= "d"; }
if ($transmission_filter) { $sql .= " AND c.transmission = ?"; $params[] = $transmission_filter; $types .= "s"; }
if ($seats_filter) { $sql .= " AND c.seats >= ?"; $params[] = $seats_filter; $types .= "i"; }


// تطبيق الترتيب
switch ($sort_by) {
    case 'price_desc': $sql .= " ORDER BY c.daily_rate DESC"; break;
    case 'year_desc': $sql .= " ORDER BY c.year DESC"; break;
    case 'year_asc': $sql .= " ORDER BY c.year ASC"; break;
    // case 'rating_desc': $sql .= " ORDER BY c.rating DESC"; break; // تحتاج حقل تقييم
    case 'price_asc':
    default: $sql .= " ORDER BY c.daily_rate ASC"; break;
}

// (اختياري) الترقيم - Pagination (هذا مثال مبسط جداً)
$results_per_page = 9; // عدد السيارات في كل صفحة
$current_page_number = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($current_page_number < 1) $current_page_number = 1;
$offset = ($current_page_number - 1) * $results_per_page;

// جلب العدد الإجمالي للنتائج قبل LIMIT للترقيم
$count_query = str_replace("SELECT c.*, cat.name as category_name, pick_loc.name as pickup_location_name, pick_loc.city as pickup_city", "SELECT COUNT(DISTINCT c.id) as total_count", $sql);
$stmt_count = $conn->prepare($count_query);
if (!empty($params)) { $stmt_count->bind_param($types, ...$params); }
$stmt_count->execute();
$total_results = $stmt_count->get_result()->fetch_assoc()['total_count'] ?? 0;
$total_pages = ceil($total_results / $results_per_page);
$stmt_count->close();


$sql .= " LIMIT ? OFFSET ?";
$params[] = $results_per_page;
$params[] = $offset;
$types .= "ii";


$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while($row = $result->fetch_assoc()) {
        $cars[] = $row;
    }
}
$stmt->close();

// جلب بيانات للفلاتر الديناميكية
$available_categories_q = "SELECT id, name FROM car_categories ORDER BY name";
$available_categories_res = $conn->query($available_categories_q);
$filter_categories = [];
if ($available_categories_res) while($r = $available_categories_res->fetch_assoc()) $filter_categories[] = $r;

$available_makes_q = "SELECT DISTINCT make FROM cars ORDER BY make";
$available_makes_res = $conn->query($available_makes_q);
$filter_makes = [];
if ($available_makes_res) while($r = $available_makes_res->fetch_assoc()) $filter_makes[] = $r['make'];


$conn->close();

$search_summary_text = empty($search_params_text_array) ? "عرض كل السيارات" : implode("، ", $search_params_text_array);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - موقع تأجير السيارات</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .search-results-page-layout {
            display: flex;
            gap: 25px;
            padding: 30px 0;
        }
        .filters-sidebar {
            flex: 0 0 280px; /* عرض ثابت للشريط الجانبي */
            background-color: #fff;
            padding: 20px;
            border-radius: var(--border-radius-base);
            box-shadow: var(--card-shadow);
            height: fit-content; /* ليأخذ ارتفاع المحتوى فقط */
        }
        .filters-sidebar h3 {
            font-size: 1.3em;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }
        .filter-group { margin-bottom: 20px; }
        .filter-group label { display: block; font-weight: 500; margin-bottom: 8px; font-size: 0.95em; }
        .filter-group select, .filter-group input[type="text"], .filter-group input[type="number"] {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 0.9em;
        }
        .filter-group .price-range-inputs { display: flex; gap: 10px; }
        .filter-group .price-range-inputs input { width: calc(50% - 5px); }

        .main-results-content { flex-grow: 1; }
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background-color: var(--light-bg-color);
            border-radius: var(--border-radius-base);
        }
        .results-header .results-summary p { margin: 0; font-size: 0.95em; color: #555; }
        .results-header .sort-options label { margin-left: 8px; font-size: 0.9em; }
        .results-header .sort-options select { padding: 6px 8px; border-radius: 4px; border: 1px solid var(--border-color); }

        .no-results-message {
            text-align: center;
            padding: 40px;
            background-color: #fff;
            border-radius: var(--border-radius-base);
            box-shadow: var(--card-shadow);
        }
        .no-results-message i { font-size: 3rem; color: var(--primary-color); margin-bottom: 20px; }
        .no-results-message h4 { font-size: 1.5rem; margin-bottom: 10px; }
        .no-results-message p { color: #666; }

        /* Pagination styles */
        .pagination-nav { text-align: center; margin-top: 30px; }
        .pagination-nav ul { list-style: none; padding: 0; display: inline-block; }
        .pagination-nav li { display: inline-block; margin: 0 3px; }
        .pagination-nav li a, .pagination-nav li span {
            display: block;
            padding: 8px 14px;
            color: var(--primary-color);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-sm);
            text-decoration: none;
            transition: background-color 0.2s, color 0.2s;
        }
        .pagination-nav li a:hover { background-color: var(--primary-color-light); color: #fff; border-color: var(--primary-color-light); }
        .pagination-nav li.active span { background-color: var(--primary-color); color: #fff; border-color: var(--primary-color); }
        .pagination-nav li.disabled span { color: #aaa; border-color: #ddd; background-color: #f9f9f9; cursor: not-allowed; }

        /* استخدام نفس كلاس car-card-v2 من index.php للتوحيد */
        /* .modern-car-grid, .car-card-v2 (من أنماط index.php) */

        @media (max-width: 992px) {
            .search-results-page-layout { flex-direction: column; }
            .filters-sidebar { flex: 0 0 auto; width: 100%; margin-bottom: 25px; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="search-results-page-layout">
            <aside class="filters-sidebar">
                <h3><i class="fas fa-filter"></i> فلترة النتائج</h3>
                <form action="search_results.php" method="GET" id="filterForm">
                    <!-- تمرير معايير البحث الأصلية للحفاظ عليها عند الفلترة -->
                    <?php if ($pickup_location_id): ?><input type="hidden" name="pickup_location_id" value="<?php echo $pickup_location_id; ?>"><?php endif; ?>
                    <?php if ($return_location_id): ?><input type="hidden" name="return_location_id" value="<?php echo $return_location_id; ?>"><?php endif; ?>
                    <?php if ($pickup_datetime_str): ?><input type="hidden" name="pickup_date" value="<?php echo $pickup_datetime_str; ?>"><?php endif; ?>
                    <?php if ($return_datetime_str): ?><input type="hidden" name="return_date" value="<?php echo $return_datetime_str; ?>"><?php endif; ?>
                    <?php if ($show_all): ?><input type="hidden" name="show_all" value="true"><?php endif; ?>
                    <input type="hidden" name="sort_by" value="<?php echo $sort_by; ?>"> <!-- للحفاظ على الترتيب -->

                    <div class="filter-group">
                        <label for="filter_category_id">فئة السيارة:</label>
                        <select name="category_id" id="filter_category_id" onchange="this.form.submit()">
                            <option value="">كل الفئات</option>
                            <?php foreach ($filter_categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php if ($category_id_filter == $cat['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="filter_make">الماركة (الصانع):</label>
                        <select name="make" id="filter_make" onchange="this.form.submit()">
                            <option value="">كل الماركات</option>
                            <?php foreach ($filter_makes as $mk): ?>
                                <option value="<?php echo htmlspecialchars($mk); ?>" <?php if ($make_filter == $mk) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($mk); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>نطاق السعر اليومي (ريال):</label>
                        <div class="price-range-inputs">
                            <input type="number" name="min_price" placeholder="من" value="<?php echo htmlspecialchars($min_price_filter ?? ''); ?>" min="0">
                            <input type="number" name="max_price" placeholder="إلى" value="<?php echo htmlspecialchars($max_price_filter ?? ''); ?>" min="0">
                        </div>
                    </div>

                    <div class="filter-group">
                        <label for="filter_transmission">ناقل الحركة:</label>
                        <select name="transmission" id="filter_transmission" onchange="this.form.submit()">
                            <option value="">الكل</option>
                            <option value="Automatic" <?php if ($transmission_filter == 'Automatic') echo 'selected'; ?>>أوتوماتيك</option>
                            <option value="Manual" <?php if ($transmission_filter == 'Manual') echo 'selected'; ?>>يدوي</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="filter_seats">الحد الأدنى للمقاعد:</label>
                        <input type="number" name="seats" id="filter_seats" value="<?php echo htmlspecialchars($seats_filter ?? ''); ?>" min="1" placeholder="مثال: 4">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="margin-top:15px;"><i class="fas fa-check"></i> تطبيق الفلاتر</button>
                    <a href="search_results.php?<?php echo http_build_query(array_intersect_key($_GET, array_flip(['pickup_location_id', 'return_location_id', 'pickup_date', 'return_date', 'show_all']))); ?>" class="btn btn-secondary btn-block" style="margin-top:10px;">إعادة تعيين الفلاتر</a>
                </form>
            </aside>

            <div class="main-results-content">
                <div class="results-header">
                    <div class="results-summary">
                        <p><strong>معايير البحث:</strong> <?php echo htmlspecialchars($search_summary_text); ?></p>
                        <p>تم العثور على <strong><?php echo $total_results; ?></strong> سيارة.</p>
                    </div>
                    <div class="sort-options">
                        <form action="search_results.php" method="GET" id="sortForm">
                            <!-- تمرير كل معايير البحث والفلاتر الحالية للحفاظ عليها عند تغيير الترتيب -->
                            <?php foreach ($_GET as $key => $value): if($key != 'sort_by' && $key != 'page'): ?>
                                <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                            <?php endif; endforeach; ?>
                            <label for="sort_by_select">ترتيب حسب:</label>
                            <select name="sort_by" id="sort_by_select" onchange="this.form.submit()">
                                <?php foreach ($sort_options as $key => $value): ?>
                                    <option value="<?php echo $key; ?>" <?php if ($sort_by == $key) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($value); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                </div>

                <?php if (!empty($cars)): ?>
                    <div class="car-grid modern-car-grid"> <?php // استخدام نفس كلاس index.php المطور ?>
                        <?php foreach ($cars as $car): ?>
                            <div class="car-card-v2">
                                <div class="car-card-v2-image">
                                    <img src="assets/images/cars/<?php echo htmlspecialchars($car['image_url'] ? $car['image_url'] : 'default_car.png'); ?>" alt="<?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?>">
                                    <span class="car-card-v2-price-tag"><?php echo htmlspecialchars(number_format($car['daily_rate'])); ?> ريال/يوم</span>
                                </div>
                                <div class="car-card-v2-content">
                                    <h3 class="car-card-v2-title"><?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?> <small>(<?php echo htmlspecialchars($car['year']); ?>)</small></h3>
                                    <p class="car-card-v2-category"><i class="fas fa-car"></i> <?php echo htmlspecialchars($car['category_name']); ?></p>
                                    <div class="car-card-v2-features">
                                        <span><i class="fas fa-cog"></i> <?php echo htmlspecialchars($car['transmission']); ?></span>
                                        <span><i class="fas fa-user-friends"></i> <?php echo htmlspecialchars($car['seats']); ?> مقاعد</span>
                                        <span><i class="fas fa-gas-pump"></i> <?php echo htmlspecialchars($car['fuel_type']); ?></span>
                                    </div>
                                    <a href="car_details.php?id=<?php echo $car['id']; ?>&pickup_date=<?php echo urlencode($pickup_datetime_str ?? ''); ?>&return_date=<?php echo urlencode($return_datetime_str ?? ''); ?>&pickup_location_id=<?php echo urlencode($pickup_location_id ?? ''); ?>" class="btn btn-outline-primary btn-block">عرض التفاصيل والحجز</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav class="pagination-nav" aria-label="Page navigation">
                        <ul>
                            <?php if ($current_page_number > 1): ?>
                                <li><a href="?page=<?php echo $current_page_number - 1; ?>&<?php echo http_build_query(array_merge($_GET, ['page' => $current_page_number - 1])); ?>">« السابق</a></li>
                            <?php else: ?>
                                <li class="disabled"><span>« السابق</span></li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): 
                                // عرض عدد محدود من أرقام الصفحات
                                if ($i == 1 || $i == $total_pages || ($i >= $current_page_number - 2 && $i <= $current_page_number + 2)):
                            ?>
                                <li class="<?php if ($i == $current_page_number) echo 'active'; ?>">
                                    <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php elseif (($i == $current_page_number - 3 && $current_page_number -3 > 1) || ($i == $current_page_number + 3 && $current_page_number + 3 < $total_pages)): ?>
                                <li class="disabled"><span>...</span></li>
                            <?php endif; endfor; ?>

                            <?php if ($current_page_number < $total_pages): ?>
                                <li><a href="?page=<?php echo $current_page_number + 1; ?>&<?php echo http_build_query(array_merge($_GET, ['page' => $current_page_number + 1])); ?>">التالي »</a></li>
                            <?php else: ?>
                                <li class="disabled"><span>التالي »</span></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="no-results-message">
                        <i class="fas fa-search-minus"></i>
                        <h4>عفواً، لم يتم العثور على سيارات تطابق بحثك.</h4>
                        <p>يرجى محاولة تعديل معايير البحث أو الفلاتر، أو <a href="search_results.php?show_all=true">عرض جميع السيارات المتاحة</a>.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/script.js"></script>
    <!-- لا حاجة لتضمين flatpickr هنا إذا كان موجودًا في footer.php -->
</body>
</html>
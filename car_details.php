<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$car_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$car = null;
$error_message = '';

if ($car_id > 0) {
    $stmt = $conn->prepare("SELECT c.*, cat.name as category_name, loc.name as location_name, loc.city as location_city, loc.address as location_address
                            FROM cars c
                            LEFT JOIN car_categories cat ON c.category_id = cat.id
                            LEFT JOIN locations loc ON c.location_id = loc.id
                            WHERE c.id = ?"); //  AND c.availability_status != 'maintenance' (يمكن إضافته إذا أردت)
    $stmt->bind_param("i", $car_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $car = $result->fetch_assoc();
    } else {
        $error_message = "لم يتم العثور على السيارة المطلوبة أو أنها غير متاحة حالياً.";
    }
    $stmt->close();
} else {
    $error_message = "معرف السيارة غير صالح.";
}

// جلب المواقع لنموذج الحجز (نفس الكود السابق)
$locations_sql = "SELECT id, name, city FROM locations ORDER BY city, name";
$locations_result = $conn->query($locations_sql);
$locations_options = [];
if ($locations_result && $locations_result->num_rows > 0) {
    while($row = $locations_result->fetch_assoc()) {
        $locations_options[] = $row;
    }
}

// استرجاع تواريخ وموقع البحث إذا تم تمريرها
$pickup_date_val = isset($_GET['pickup_date']) ? htmlspecialchars(sanitize_input($_GET['pickup_date'])) : '';
$return_date_val = isset($_GET['return_date']) ? htmlspecialchars(sanitize_input($_GET['return_date'])) : '';
$pickup_location_id_val = isset($_GET['pickup_location_id']) ? intval($_GET['pickup_location_id']) : ($car ? $car['location_id'] : '');

$page_title = $car ? (htmlspecialchars($car['make'] . ' ' . $car['model']) . " - تفاصيل السيارة") : 'تفاصيل السيارة';

$conn->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - موقع تأجير السيارات</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> <!-- للأيقونات -->
    <style>
        /* --- Car Details Page Specific Styles --- */
        .car-details-page-container {
            padding-top: 30px;
            padding-bottom: 50px;
        }
        .breadcrumb-nav { /* شريط التنقل (اختياري) */
            margin-bottom: 25px;
            font-size: 0.9em;
        }
        .breadcrumb-nav a { color: var(--primary-color); }
        .breadcrumb-nav span { color: #777; }

        .car-main-layout {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            align-items: flex-start; /* محاذاة العناصر للأعلى */
        }

        .car-gallery-column .main-car-image-wrapper {
            border-radius: var(--border-radius-base);
            overflow: hidden;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
            position: relative; /* لعلامة السعر إذا أردت */
        }
        .main-car-image-wrapper img {
            width: 100%;
            height: auto; /* أو ارتفاع ثابت مع object-fit: cover; */
            display: block;
        }
        /* .thumbnail-gallery { display: flex; gap: 10px; flex-wrap: wrap; } */
        /* .thumbnail-gallery img { width: 80px; height: 60px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid transparent; } */
        /* .thumbnail-gallery img.active-thumb { border-color: var(--primary-color); } */

        .car-info-column h1.car-title {
            font-size: 2.2rem;
            color: var(--text-color);
            margin-bottom: 10px;
        }
        .car-year-badge {
            font-size: 0.9em;
            background-color: var(--secondary-color);
            color: #fff;
            padding: 3px 8px;
            border-radius: 4px;
            vertical-align: middle;
            margin-right: 10px;
        }
        .car-price-daily {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--accent-color);
            margin-bottom: 20px;
        }
        .car-price-daily small { font-size: 0.7em; color: #777; }

        .car-specs-list { list-style: none; padding: 0; margin: 0 0 20px 0; }
        .car-specs-list li {
            display: flex;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
            font-size: 0.95em;
        }
        .car-specs-list li:last-child { border-bottom: none; }
        .car-specs-list li i {
            color: var(--primary-color);
            margin-left: 12px;
            width: 20px; /* لعرض الأيقونات بشكل متناسق */
            text-align: center;
        }
        .car-specs-list li strong { color: #555; } /* اسم الخاصية */

        .car-description, .car-features-section { margin-top: 25px; }
        .car-description h3, .car-features-section h3 {
            font-size: 1.3em;
            margin-bottom: 10px;
            color: #444;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .car-features-list {
            list-style: none;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .car-features-list li {
            background-color: #e9ecef;
            padding: 5px 12px;
            border-radius: 15px; /* شكل حبة الدواء */
            font-size: 0.85em;
            color: #495057;
        }
        .car-features-list li i { margin-left: 5px; }

        .booking-form-card {
            background-color: var(--light-bg-color);
            padding: 25px;
            border-radius: var(--border-radius-base);
            box-shadow: 0 2px 10px rgba(0,0,0,0.07);
        }
        .booking-form-card h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 1.6rem;
            color: var(--primary-color);
        }
        .booking-form-card .form-group { margin-bottom: 15px; }
        .booking-form-card label { font-weight: 500; margin-bottom: 6px; font-size: 0.9em; }
        .booking-form-card input[type="text"],
        .booking-form-card select,
        .booking-form-card textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 0.95em;
        }
        .booking-form-card textarea { min-height: 80px; }
        .booking-form-card .btn-primary { width: 100%; padding: 12px; font-size: 1.1em; }
        #booking-form-messages .alert { margin-top: 15px; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container car-details-page-container">
        <?php if ($error_message): ?>
            <div class="alert alert-danger text-center" style="margin-top: 30px;"><?php echo htmlspecialchars($error_message); ?></div>
            <p class="text-center" style="margin-top: 20px;"><a href="index.php" class="btn btn-primary">العودة للرئيسية</a></p>
        <?php elseif ($car): ?>
            <nav class="breadcrumb-nav" aria-label="breadcrumb">
                <a href="index.php">الرئيسية</a> »
                <a href="search_results.php?category_id=<?php echo $car['category_id']; ?>"><?php echo htmlspecialchars($car['category_name']); ?></a> »
                <span><?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?></span>
            </nav>

            <div class="car-main-layout">
                <div class="car-gallery-column">
                    <div class="main-car-image-wrapper">
                        <img src="assets/images/cars/<?php echo htmlspecialchars($car['image_url'] ? $car['image_url'] : 'default_car.png'); ?>" 
                             alt="صورة <?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?>" class="main-car-image">
                    </div>
                    <!-- 
                    <div class="thumbnail-gallery">
                        <img src="path/to/thumb1.jpg" alt="Thumbnail 1" class="active-thumb">
                        <img src="path/to/thumb2.jpg" alt="Thumbnail 2">
                    </div>
                    -->
                </div>

                <div class="car-info-column">
                    <h1 class="car-title">
                        <?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?>
                        <span class="car-year-badge"><?php echo htmlspecialchars($car['year']); ?></span>
                    </h1>
                    <p class="car-price-daily"><?php echo htmlspecialchars(number_format($car['daily_rate'], 2)); ?> <small>ريال/يوم</small></p>
                    
                    <ul class="car-specs-list">
                        <li><i class="fas fa-car"></i> <strong>الفئة:</strong> <?php echo htmlspecialchars($car['category_name']); ?></li>
                        <li><i class="fas fa-cog"></i> <strong>ناقل الحركة:</strong> <?php echo htmlspecialchars($car['transmission']); ?></li>
                        <li><i class="fas fa-gas-pump"></i> <strong>نوع الوقود:</strong> <?php echo htmlspecialchars($car['fuel_type']); ?></li>
                        <li><i class="fas fa-users"></i> <strong>عدد المقاعد:</strong> <?php echo htmlspecialchars($car['seats']); ?></li>
                        <li><i class="fas fa-map-marker-alt"></i> <strong>الموقع الحالي:</strong> <?php echo htmlspecialchars($car['location_city'] . ' - ' . $car['location_name']); ?></li>
                    </ul>

                    <?php if (!empty($car['description'])): ?>
                    <div class="car-description">
                        <h3><i class="fas fa-info-circle"></i> وصف السيارة</h3>
                        <p><?php echo nl2br(htmlspecialchars($car['description'])); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($car['features'])): ?>
                    <div class="car-features-section">
                        <h3><i class="fas fa-star"></i> الميزات الإضافية</h3>
                        <ul class="car-features-list">
                            <?php 
                            $features_list = array_map('trim', explode(',', $car['features']));
                            foreach ($features_list as $feature): ?>
                                <li><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <section class="booking-form-section" id="bookingSection">
                <div class="booking-form-card">
                    <h2>احجز هذه السيارة الآن</h2>
                    <div id="booking-form-messages"></div> <!-- لعرض رسائل نجاح/خطأ الحجز من AJAX -->
                    
                    <form action="booking.php" method="POST" id="carBookingForm"> <!-- إذا كنت ستستخدم الإرسال التقليدي أو AJAX -->
                        <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                        
                        <div class="form-group">
                            <label for="pickup_location_id">مكان الاستلام:</label>
                            <select name="pickup_location_id" id="pickup_location_id" required>
                                <option value="" disabled <?php if(empty($pickup_location_id_val)) echo 'selected';?>>اختر موقع الاستلام</option>
                                <?php foreach ($locations_options as $loc): ?>
                                    <option value="<?php echo $loc['id']; ?>" <?php if($loc['id'] == $pickup_location_id_val) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($loc['city'] . ' - ' . $loc['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="return_location_id">مكان التسليم:</label>
                            <select name="return_location_id" id="return_location_id" required>
                                <option value="" disabled <?php if(empty($pickup_location_id_val) && empty($_GET['return_location_id'])) echo 'selected';?>>اختر موقع التسليم</option>
                                <?php foreach ($locations_options as $loc): ?>
                                    <option value="<?php echo $loc['id']; ?>" <?php if($loc['id'] == ($pickup_location_id_val && empty($_GET['return_location_id']) ? $pickup_location_id_val : ($_GET['return_location_id'] ?? ''))) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($loc['city'] . ' - ' . $loc['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small>إذا كان نفس مكان الاستلام، اختره مرة أخرى.</small>
                        </div>

                        <div class="form-group">
                            <label for="pickup_datetime">تاريخ ووقت الاستلام:</label>
                            <input type="text" name="pickup_datetime" id="pickup_datetime" class="datepicker" placeholder="اختر تاريخ ووقت" value="<?php echo $pickup_date_val; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="return_datetime">تاريخ ووقت التسليم:</label>
                            <input type="text" name="return_datetime" id="return_datetime" class="datepicker" placeholder="اختر تاريخ ووقت" value="<?php echo $return_date_val; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="special_requests">طلبات خاصة (اختياري):</label>
                            <textarea name="special_requests" id="special_requests" rows="3"><?php echo htmlspecialchars($_POST['special_requests'] ?? ''); ?></textarea>
                        </div>

                        <?php if (isLoggedIn()): ?>
                            <button type="submit" name="submit_booking" class="btn btn-primary btn-lg"><i class="fas fa-calendar-check"></i> تأكيد الحجز</button>
                        <?php else: ?>
                            <p class="alert alert-info" style="font-size: 0.95em;">
                                يجب <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">تسجيل الدخول</a> أو <a href="register.php">إنشاء حساب</a> للمتابعة.
                            </p>
                            <button type="button" class="btn btn-primary btn-lg" onclick="window.location.href='login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>'"><i class="fas fa-sign-in-alt"></i> سجل الدخول للمتابعة</button>
                        <?php endif; ?>
                    </form>
                </div>
            </section>

        <?php else: // إذا لم يتم العثور على السيارة أو خطأ في ID ?>
            <p class="text-center" style="margin-top: 20px;"><a href="index.php" class="btn btn-primary">العودة للرئيسية</a></p>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ar.js"></script>
    <script src="assets/js/script.js"></script> <!-- تأكد أن هذا الملف يتضمن كود AJAX لـ #carBookingForm إذا كنت ستستخدمه -->
    <script>
        // تفعيل منتقي التاريخ والوقت (يمكن وضعه في script.js إذا كان عامًا)
        // أو تخصيصه هنا إذا لزم الأمر
        flatpickr(".datepicker", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: "today",
            locale: "ar",
            time_24hr: true,
            minuteIncrement: 30,
             onChange: function(selectedDates, dateStr, instance) {
                if (instance.input.id === 'pickup_datetime') {
                    const returnDatepicker = document.getElementById("return_datetime");
                    if(returnDatepicker && returnDatepicker._flatpickr){ // تحقق من وجود flatpickr
                        returnDatepicker._flatpickr.set('minDate', dateStr);
                    }
                }
            }
        });
    </script>
</body>
</html>
<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php'; // سيقوم ببدء session_start()

// جلب السيارات المميزة (يمكنك إضافة حقل 'is_featured' في جدول السيارات لفلترة أفضل)
// أو اختيار السيارات الأعلى تقييماً أو الأحدث
$featured_cars_query = "
    SELECT c.*, cat.name as category_name, loc.name as location_name
    FROM cars c
    LEFT JOIN car_categories cat ON c.category_id = cat.id
    LEFT JOIN locations loc ON c.location_id = loc.id
    WHERE c.availability_status = 'available' 
    ORDER BY c.daily_rate ASC, c.created_at DESC 
    LIMIT 6"; // جلب 6 سيارات
$featured_cars_result = $conn->query($featured_cars_query);
$featured_cars = [];
if ($featured_cars_result) {
    while($row = $featured_cars_result->fetch_assoc()) {
        $featured_cars[] = $row;
    }
}

// جلب المواقع لنموذج البحث
$locations_sql = "SELECT id, name, city FROM locations ORDER BY city, name";
$locations_result = $conn->query($locations_sql);
$locations_options = [];
if ($locations_result && $locations_result->num_rows > 0) {
    while($row = $locations_result->fetch_assoc()) {
        $locations_options[] = $row;
    }
}

// جلب فئات السيارات لعرضها
$categories_sql = "SELECT id, name, description FROM car_categories ORDER BY name LIMIT 5"; // جلب 5 فئات مثلاً
$categories_result = $conn->query($categories_sql);
$car_categories_list = [];
if ($categories_result && $categories_result->num_rows > 0) {
    while($row = $categories_result->fetch_assoc()) {
        // يمكنك إضافة أيقونة أو صورة مصغرة لكل فئة هنا إذا خزنتها في قاعدة البيانات
        // $row['icon_class'] = 'fa fa-car'; // مثال إذا كنت تستخدم FontAwesome
        $car_categories_list[] = $row;
    }
}


$conn->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>موقع تأجير السيارات الأول - ابحث، قارن، واحجز بسهولة</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- FontAwesome للأيقونات (اختياري) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- يمكنك إضافة مكتبة سلايدر مثل SwiperJS إذا أردت سلايدر للسيارات المميزة -->
    <!-- <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css"> -->
</head>
<body class="homepage">
    <?php include 'includes/header.php'; // تأكد أن الهيدر متناسق بصرياً ?>

    <main>
        <!-- قسم الـ Hero المطور -->
        <section class="hero-section modern-hero">
            <div class="hero-background-image"></div> <!-- يمكن التحكم بها عبر CSS -->
            <div class="hero-overlay"></div> <!-- طبقة لتغميق الخلفية وإبراز النص -->
            <div class="container hero-content">
                <h1 class="hero-title">سيارتك المثالية بانتظارك</h1>
                <p class="hero-subtitle">استأجر سيارة بسهولة وأمان مع أفضل الأسعار والخيارات المتنوعة.</p>
                
                <form action="search_results.php" method="GET" class="main-search-form elevated-form">
                    <div class="form-row">
                        <div class="form-group-inline">
                            <label for="pickup_location_hero"><i class="fas fa-map-marker-alt"></i> مكان الاستلام</label>
                            <select name="pickup_location_id" id="pickup_location_hero" required>
                                <option value="" disabled selected>اختر موقعًا</option>
                                <?php foreach ($locations_options as $loc): ?>
                                    <option value="<?php echo $loc['id']; ?>"><?php echo htmlspecialchars($loc['city'] . ' - ' . $loc['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group-inline">
                            <label for="return_location_hero"><i class="fas fa-map-marker-alt"></i> مكان التسليم <small>(نفس الاستلام إذا فارغ)</small></label>
                            <select name="return_location_id" id="return_location_hero">
                                <option value="" selected>نفس موقع الاستلام</option>
                                 <?php foreach ($locations_options as $loc): ?>
                                    <option value="<?php echo $loc['id']; ?>"><?php echo htmlspecialchars($loc['city'] . ' - ' . $loc['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group-inline">
                            <label for="pickup_date_hero"><i class="fas fa-calendar-alt"></i> تاريخ ووقت الاستلام</label>
                            <input type="text" name="pickup_date" id="pickup_date_hero" class="datepicker hero-datepicker" placeholder="اختر تاريخ ووقت" required>
                        </div>
                        <div class="form-group-inline">
                            <label for="return_date_hero"><i class="fas fa-calendar-alt"></i> تاريخ ووقت التسليم</label>
                            <input type="text" name="return_date" id="return_date_hero" class="datepicker hero-datepicker" placeholder="اختر تاريخ ووقت" required>
                        </div>
                    </div>
                    <div class="form-row submit-row">
                        <button type="submit" class="btn btn-primary btn-hero-search"><i class="fas fa-search"></i> ابحث الآن</button>
                    </div>
                </form>
            </div>
        </section>

        <!-- قسم الميزات الرئيسية -->
        <section class="features-section section-padding">
            <div class="container">
                <h2 class="section-title">لماذا تختارنا؟</h2>
                <div class="features-grid">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-car-side fa-2x"></i></div>
                        <h3>أسطول متنوع وحديث</h3>
                        <p>نقدم مجموعة واسعة من السيارات الجديدة لتلبية جميع احتياجاتك وميزانيتك.</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-tags fa-2x"></i></div>
                        <h3>أسعار تنافسية</h3>
                        <p>نضمن لك أفضل الأسعار مع عروض وخصومات مستمرة بدون رسوم خفية.</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-headset fa-2x"></i></div>
                        <h3>خدمة عملاء 24/7</h3>
                        <p>فريق دعم متخصص جاهز لمساعدتك في أي وقت على مدار الساعة.</p>
                    </div>
                     <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-shield-alt fa-2x"></i></div>
                        <h3>حجز آمن وسهل</h3>
                        <p>عملية حجز سريعة وآمنة عبر الإنترنت مع تأكيد فوري لحجزك.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- قسم السيارات المميزة (بتصميم كارت محسن) -->
        <section class="featured-cars-section section-padding bg-light">
            <div class="container">
                <h2 class="section-title">سياراتنا المميزة</h2>
                <div class="car-grid modern-car-grid">
                    <?php if (!empty($featured_cars)): ?>
                        <?php foreach ($featured_cars as $car): ?>
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
                                    <a href="car_details.php?id=<?php echo $car['id']; ?>" class="btn btn-outline-primary btn-block">عرض التفاصيل والحجز</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center">لا توجد سيارات مميزة متاحة حالياً. يرجى التحقق لاحقًا!</p>
                    <?php endif; ?>
                </div>
                <?php if (!empty($featured_cars)): ?>
                <div class="text-center" style="margin-top: 30px;">
                    <a href="search_results.php?show_all=true" class="btn btn-primary btn-lg">تصفح جميع السيارات</a>
                </div>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- قسم أنواع السيارات المتاحة -->
        <section class="car-categories-section section-padding">
            <div class="container">
                <h2 class="section-title">اكتشف سياراتنا حسب الفئة</h2>
                <div class="categories-grid">
                    <?php if (!empty($car_categories_list)): ?>
                        <?php foreach ($car_categories_list as $category): ?>
                            <a href="search_results.php?category_id=<?php echo $category['id']; ?>" class="category-card">
                                <!-- يمكنك إضافة أيقونة أو صورة هنا -->
                                <!-- <div class="category-icon"><i class="<?php echo $category['icon_class'] ?? 'fas fa-car-alt'; ?> fa-3x"></i></div> -->
                                <div class="category-icon"><i class="fas fa-<?php 
                                    // أيقونات افتراضية بسيطة بناءً على الاسم
                                    if(stripos($category['name'], 'سيدان') !== false) echo 'car-side';
                                    elseif(stripos($category['name'], 'SUV') !== false || stripos($category['name'], 'دفع رباعي') !== false) echo 'truck-pickup';
                                    elseif(stripos($category['name'], 'فان') !== false || stripos($category['name'], 'عائلية') !== false) echo 'bus-alt';
                                    elseif(stripos($category['name'], 'فاخرة') !== false) echo 'gem';
                                    elseif(stripos($category['name'], 'رياضية') !== false) echo 'space-shuttle'; // كمثال طريف
                                    else echo 'car-alt';
                                ?> fa-3x"></i></div>
                                <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                                <p><?php echo htmlspecialchars(substr($category['description'] ?? '', 0, 50) . '...'); ?></p>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>لا توجد فئات سيارات لعرضها حالياً.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- قسم كيف يعمل (يمكن إعادة تصميمه) -->
        <section class="how-it-works-v2 section-padding bg-dark text-light">
            <div class="container">
                <h2 class="section-title">استئجار سيارة في 3 خطوات بسيطة</h2>
                <div class="steps-grid-v2">
                    <div class="step-item-v2">
                        <div class="step-icon-v2"><span class="step-number">1</span><i class="fas fa-search"></i></div>
                        <h3>ابحث وقارن</h3>
                        <p>اختر موقعك، تواريخك، وقارن بين مجموعة واسعة من السيارات والأسعار.</p>
                    </div>
                    <div class="step-item-v2">
                        <div class="step-icon-v2"><span class="step-number">2</span><i class="fas fa-calendar-check"></i></div>
                        <h3>احجز وأكد</h3>
                        <p>اختر سيارتك المفضلة وأكمل حجزك الآمن عبر الإنترنت في دقائق.</p>
                    </div>
                    <div class="step-item-v2">
                        <div class="step-icon-v2"><span class="step-number">3</span><i class="fas fa-car-alt"></i></div>
                        <h3>استلم وانطلق</h3>
                        <p>استلم سيارتك من الموقع المحدد وانطلق في رحلتك بكل راحة وسهولة.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- قسم آراء العملاء (Testimonials - اختياري) -->
        <!-- 
        <section class="testimonials-section section-padding">
            <div class="container">
                <h2 class="section-title">ماذا يقول عملاؤنا؟</h2>
                <div class="testimonials-grid">
                    // ... testimonial items ... 
                </div>
            </div>
        </section>
        -->

    </main>

    <?php include 'includes/footer.php'; // تأكد أن الفوتر متناسق بصرياً ?>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ar.js"></script>
    <!-- <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script> -->
    <script src="assets/js/script.js"></script>
    <script>
        // تفعيل منتقي التاريخ والوقت (يمكن وضعه في script.js إذا كان عامًا)
        flatpickr(".hero-datepicker", { // استهداف منتقي التاريخ في قسم الـ Hero فقط إذا كان له تصميم خاص
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: "today",
            locale: "ar",
            time_24hr: true,
            minuteIncrement: 30, // كل نصف ساعة
            onChange: function(selectedDates, dateStr, instance) {
                if (instance.input.id === 'pickup_date_hero') {
                    const returnDatepickerHero = document.getElementById('return_date_hero');
                    if(returnDatepickerHero && returnDatepickerHero._flatpickr){
                        returnDatepickerHero._flatpickr.set('minDate', dateStr);
                    }
                }
            }
        });

        // (اختياري) كود لجعل قسم الـ Hero يتغير أو لإضافة تأثيرات تمرير
        // window.addEventListener('scroll', function() {
        //     var heroSection = document.querySelector('.hero-section');
        //     if (heroSection) { // التأكد من وجود العنصر
        //         var scrollPosition = window.pageYOffset;
        //         var heroBgImage = heroSection.querySelector('.hero-background-image');
        //         if (heroBgImage) {
        //              heroBgImage.style.transform = 'translateY(' + scrollPosition * 0.4 + 'px)';
        //         }
        //     }
        // });
    </script>
</body>
</html>
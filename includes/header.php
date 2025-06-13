<?php
// includes/header.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);

// تعريف متغيرات الألوان الأساسية المستخدمة في الهيدر كمتغيرات PHP لسهولة التعديل
$navbar_bg_color = '#fff'; // أو '#007bff' للخلفية الزرقاء
$navbar_text_color = ($navbar_bg_color == '#fff') ? '#333' : '#fff';
$primary_color = '#007bff';
$accent_color = '#ff6000';
$navbar_link_hover_color = $primary_color;
$border_color_navbar = '#dee2e6';
?>
<head>
    <!-- ... (بقية وسوم head مثل charset, viewport, title, link لـ style.css الرئيسي, FontAwesome) ... -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- يجب أن يكون لديك وسم title في الصفحة التي تستدعي هذا الهيدر -->
    <link rel="stylesheet" href="assets/css/style.css"> <!-- لا يزال من الجيد ربط ملف CSS الرئيسي -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* --- Navbar Styles (أنماط شريط التنقل المضمنة) --- */
        .navbar {
            background-color: <?php echo $navbar_bg_color; ?>;
            color: <?php echo $navbar_text_color; ?>;
            padding: 0.8rem 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.07);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 90%; /* لضمان تطبيقها قبل تحميل style.css الرئيسي */
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .navbar .logo {
            color: <?php echo $primary_color; ?>;
            font-size: 1.7rem;
            font-weight: 700;
            text-decoration: none;
            order: 1; /* للشعار ليكون على اليمين في RTL */
        }
        .navbar .logo .fa-car-alt {
            margin-left: 8px;
        }

        .navbar-menu-wrapper {
            display: flex;
            align-items: center;
            order: 2;
        }

        .navbar .nav-links {
            list-style: none;
            display: flex;
            align-items: center;
            margin: 0;
            padding: 0;
        }

        .navbar .nav-links li {
            margin-left: 25px;
        }
        .navbar .nav-links li:first-child {
             margin-left: 0;
        }

        .navbar .nav-links a {
            color: <?php echo $navbar_text_color; ?>;
            font-weight: 500;
            padding: 8px 0;
            position: relative;
            text-decoration: none;
            white-space: nowrap;
        }
        .navbar .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -3px;
            right: 0;
            width: 0;
            height: 2px;
            background-color: <?php echo $navbar_link_hover_color; ?>;
            transition: width 0.3s ease;
        }
        .navbar .nav-links a:hover::after,
        .navbar .nav-links a.active::after {
            width: 100%;
        }
        .navbar .nav-links a:hover,
        .navbar .nav-links a.active {
            color: <?php echo $navbar_link_hover_color; ?>;
        }

        /* أنماط الأزرار داخل Navbar */
        .navbar .nav-links .btn {
            display: inline-block; /* مهم لكي تعمل الحشوة بشكل صحيح */
            font-weight: 500;
            text-align: center;
            vertical-align: middle;
            cursor: pointer;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.4rem 0.9rem; /* تصغير حشوة الأزرار قليلاً لتناسب النافبار */
            font-size: 0.9em;
            line-height: 1.5;
            border-radius: 6px; /* var(--border-radius-base) */
            transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            margin-right: 10px;
        }
         .navbar .nav-links .btn:hover { text-decoration: none; }

        .navbar .nav-links .btn-outline-primary {
            color: <?php echo $primary_color; ?>;
            border-color: <?php echo $primary_color; ?>;
            background-color: transparent;
        }
        .navbar .nav-links .btn-outline-primary:hover {
            color: #fff;
            background-color: <?php echo $primary_color; ?>;
        }
        .navbar .nav-links .btn-accent {
            color: #fff;
            background-color: <?php echo $accent_color; ?>;
            border-color: <?php echo $accent_color; ?>;
        }
        .navbar .nav-links .btn-accent:hover {
            background-color: #e05500; /* accent-color-dark */
            border-color: #e05500;
        }
         .navbar .nav-links .btn-danger { /* لزر تسجيل الخروج */
            color: #fff;
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .navbar .nav-links .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }


        /* زر القائمة المنسدلة للهواتف (Hamburger) */
        .navbar-toggler {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: <?php echo $navbar_text_color; ?>;
            cursor: pointer;
            padding: 5px;
            margin-right: 15px;
            order: 0;
        }

        /* Responsive Adjustments for Navbar */
        @media (max-width: 992px) {
            .navbar-menu-wrapper {
                /* يمكن أن يأخذ كامل العرض المتبقي */
            }
            .navbar .nav-links {
                display: none; 
                flex-direction: column;
                width: 100%;
                background-color: <?php echo $navbar_bg_color; ?>;
                position: absolute;
                top: 100%; 
                right: 0;
                left: 0;
                box-shadow: 0 5px 10px rgba(0,0,0,0.1);
                padding: 10px 0;
                border-top: 1px solid <?php echo $border_color_navbar; ?>;
            }
            .navbar .nav-links.active {
                display: flex; 
            }
            .navbar .nav-links li {
                margin-left: 0;
                width: 100%;
            }
            .navbar .nav-links a { /* يشمل الأزرار هنا */
                display: block;
                padding: 12px 20px;
                width: 100%;
                text-align: right;
                border-bottom: 1px solid #f0f0f0;
            }
             .navbar .nav-links li .btn { /* تعديل للأزرار داخل القائمة المنسدلة */
                margin-right: 0; /* إزالة الهامش */
                margin-top: 5px;
                margin-bottom: 5px;
                display: block; /* لجعلها تأخذ كامل العرض */
                width: calc(100% - 40px); /* لتتناسب مع حشوة a */
                margin-left: auto; /* إذا كان النص باليمين */
                margin-right: auto; /* إذا كان النص باليمين */
            }
            .navbar .nav-links li:last-child a {
                border-bottom: none;
            }
            .navbar .nav-links a::after {
                display: none; 
            }
            .navbar-toggler {
                display: block;
            }
        }
         @media (max-width: 768px) {
            .navbar .logo {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body> <?php // لا تقم بإغلاق body هنا، يتم إغلاقه في footer.php ?>

<header>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">
                <i class="fas fa-car-alt"></i> 
                موقع تأجير السيارات
            </a>

            <div class="navbar-menu-wrapper">
                <button class="navbar-toggler" type="button" aria-label="Toggle navigation" aria-expanded="false">
                    <i class="fas fa-bars"></i>
                </button>

                <ul class="nav-links">
                    <li><a href="index.php" class="<?php if ($current_page == 'index.php') echo 'active'; ?>">الرئيسية</a></li>
                    <li><a href="search_results.php?show_all=true" class="<?php if ($current_page == 'search_results.php') echo 'active'; ?>">جميع السيارات</a></li>
                    <li><a href="about_us.php" class="<?php if ($current_page == 'about_us.php') echo 'active'; ?>">من نحن</a></li>
                    <li><a href="contact_us.php" class="<?php if ($current_page == 'contact_us.php') echo 'active'; ?>">اتصل بنا</a></li>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item-user">
                            <a href="user_dashboard.php" class="user-name-display <?php if ($current_page == 'user_dashboard.php' || $current_page == 'edit_profile.php' || $current_page == 'change_password.php') echo 'active'; ?>">
                                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                            </a>
                        </li>
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                            <li><a href="admin/index.php" class="<?php if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) echo 'active'; ?>"><i class="fas fa-tachometer-alt"></i> لوحة الإدارة</a></li>
                        <?php endif; ?>
                        <li><a href="logout.php" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a></li>
                    <?php else: ?>
                        <li><a href="login.php" class="btn btn-outline-primary btn-sm <?php if ($current_page == 'login.php') echo 'active'; ?>">تسجيل الدخول</a></li>
                        <li><a href="register.php" class="btn btn-accent btn-sm <?php if ($current_page == 'register.php') echo 'active'; ?>">إنشاء حساب</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>
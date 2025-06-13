<?php
// about_us.php
require_once 'includes/db_connect.php'; // للاتساق
require_once 'includes/functions.php';

$page_title = "من نحن - اكتشف قصة موقع تأجير السيارات";
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="assets/css/style.css"> <!-- ملف CSS الرئيسي -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> <!-- للأيقونات -->
    <style>
        /* --- About Us Page Specific Styles --- */
        .about-us-header {
            background-color: var(--light-bg-color); /* أو لون مميز خفيف */
            /* background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('assets/images/about-hero-bg.jpg'); */ /* صورة خلفية اختيارية */
            background-size: cover;
            background-position: center;
            padding: 60px 20px;
            text-align: center;
            color: var(--text-color); /* أو var(--light-text-color) إذا كانت الخلفية داكنة */
            margin-bottom: 40px;
        }
        .about-us-header h1 {
            font-size: 2.8rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        .about-us-header .breadcrumb-nav { /* شريط تنقل مصغر */
            font-size: 0.9em;
            color: #666; /* أو أفتح إذا كانت الخلفية داكنة */
        }
        .about-us-header .breadcrumb-nav a { color: var(--primary-color); }

        .about-content-section {
            padding: 40px 0;
        }
        .about-content-section .container {
            max-width: 900px; /* عرض أقصى للمحتوى النصي */
        }

        .mission-vision-values {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        .mvv-item { /* Mission, Vision, Value item */
            background-color: #fff;
            padding: 25px;
            border-radius: var(--border-radius-base);
            box-shadow: var(--card-shadow);
            text-align: center;
        }
        .mvv-item .mvv-icon {
            font-size: 2.5rem;
            color: var(--accent-color);
            margin-bottom: 15px;
        }
        .mvv-item h3 {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        .mvv-item p {
            font-size: 0.95rem;
            color: var(--text-color-muted);
            line-height: 1.7;
        }

        .why-choose-us ul {
            list-style: none;
            padding: 0;
        }
        .why-choose-us ul li {
            display: flex;
            align-items: flex-start; /* محاذاة الأيقونة مع بداية النص */
            margin-bottom: 15px;
            font-size: 1.05rem;
        }
        .why-choose-us ul li .list-icon {
            color: var(--primary-color);
            margin-left: 12px; /* مسافة بين الأيقونة والنص */
            font-size: 1.2em;
            padding-top: 3px; /* لضبط محاذاة الأيقونة عمودياً قليلاً */
        }

        .team-section {
            background-color: var(--light-bg-color);
            padding: 60px 0;
        }
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }
        .team-member-card {
            background-color: #fff;
            border-radius: var(--border-radius-base);
            box-shadow: var(--card-shadow);
            text-align: center;
            padding: 20px;
            overflow: hidden; /* لضمان بقاء الصورة داخل الحدود */
        }
        .team-member-card img {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 4px solid var(--primary-color-light);
            transition: transform 0.3s ease;
        }
        .team-member-card:hover img {
            transform: scale(1.05);
        }
        .team-member-card h4 {
            font-size: 1.2rem;
            color: var(--text-color);
            margin-bottom: 5px;
        }
        .team-member-card .team-role {
            font-size: 0.9em;
            color: var(--text-color-muted);
            margin-bottom: 10px;
        }
        .team-member-social-links a {
            color: var(--secondary-color);
            margin: 0 8px;
            font-size: 1.1rem;
        }
        .team-member-social-links a:hover { color: var(--primary-color); }

        .about-cta-section {
            padding: 50px 0;
            text-align: center;
            background-color: var(--primary-color); /* خلفية بلون أساسي */
            color: var(--light-text-color);
        }
        .about-cta-section h2 {
            font-size: 1.8rem;
            margin-bottom: 25px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="about-us-header">
        <div class="container">
            <nav class="breadcrumb-nav" aria-label="breadcrumb">
                <a href="index.php">الرئيسية</a> »
                <span>من نحن</span>
            </nav>
            <h1>تعرف علينا أكثر</h1>
            <p class="hero-subtitle" style="font-size: 1.2rem; color: #555;">نحن أكثر من مجرد شركة لتأجير السيارات، نحن شركاؤك في كل رحلة.</p>
        </div>
    </div>

    <main class="about-content-section">
        <div class="container">
            <section class="company-story" style="margin-bottom: 40px;">
                <h2 class="section-title" style="text-align: center; padding-bottom:10px; margin-bottom:20px;">قصتنا</h2>
                <p style="font-size: 1.1rem; line-height: 1.8; color: #444;">
                    تأسست [اسم موقعك لتأجير السيارات] في عام [سنة التأسيس] بشغف لتقديم تجارب سفر مريحة وموثوقة. بدأنا بأسطول صغير ورؤية كبيرة، وهي أن نجعل عملية استئجار السيارات بسيطة، شفافة، ومتاحة للجميع. على مر السنين، وبفضل ثقة عملائنا، نمونا لنصبح أحد الأسماء الرائدة في مجال تأجير السيارات في [المنطقة/الدولة].
                </p>
                <p style="font-size: 1.1rem; line-height: 1.8; color: #444;">
                    نحن نؤمن بأن كل رحلة يجب أن تكون تجربة لا تُنسى، ودورنا هو توفير الوسيلة المثالية لتحقيق ذلك. فريقنا يعمل بتفانٍ لضمان أن كل سيارة في أسطولنا تتم صيانتها بأعلى المعايير وأن كل عميل يتلقى خدمة شخصية تليق به.
                </p>
            </section>

            <section class="mission-vision-values">
                <div class="mvv-item">
                    <div class="mvv-icon"><i class="fas fa-bullseye"></i></div>
                    <h3>مهمتنا</h3>
                    <p>توفير حلول تأجير سيارات مبتكرة وعالية الجودة تلبي احتياجات عملائنا المتنوعة، مع الالتزام بأعلى معايير الخدمة والموثوقية.</p>
                </div>
                <div class="mvv-item">
                    <div class="mvv-icon"><i class="fas fa-eye"></i></div>
                    <h3>رؤيتنا</h3>
                    <p>أن نكون الشركة الرائدة والمفضلة لتأجير السيارات في المنطقة، والمعروفة بتميزها في خدمة العملاء وتنوع أسطولها الحديث.</p>
                </div>
                <div class="mvv-item">
                    <div class="mvv-icon"><i class="fas fa-heart"></i></div>
                    <h3>قيمنا الأساسية</h3>
                    <p>النزاهة، التركيز على العميل، الجودة، الابتكار، والمسؤولية المجتمعية هي الركائز التي توجه جميع أعمالنا وقراراتنا.</p>
                </div>
            </section>

            <section class="why-choose-us" style="margin-bottom: 40px;">
                <h2 class="section-title" style="text-align: center; padding-bottom:10px; margin-bottom:20px;">لماذا تختار [اسم موقعك]؟</h2>
                <ul>
                    <li><span class="list-icon"><i class="fas fa-check-circle"></i></span><strong>أسطول سيارات حديث ومتنوع:</strong> من السيارات الاقتصادية إلى الفاخرة وسيارات الدفع الرباعي.</li>
                    <li><span class="list-icon"><i class="fas fa-check-circle"></i></span><strong>أسعار شفافة وتنافسية:</strong> لا رسوم خفية، ما تراه هو ما تدفعه.</li>
                    <li><span class="list-icon"><i class="fas fa-check-circle"></i></span><strong>عملية حجز سهلة وآمنة:</strong> خطوات بسيطة لحجز سيارتك عبر الإنترنت أو الهاتف.</li>
                    <li><span class="list-icon"><i class="fas fa-check-circle"></i></span><strong>خدمة عملاء استثنائية:</strong> فريق دعم ودود ومحترف لمساعدتك على مدار الساعة.</li>
                    <li><span class="list-icon"><i class="fas fa-check-circle"></i></span><strong>مواقع متعددة ومريحة:</strong> استلم سيارتك من أقرب فرع إليك أو من المطار.</li>
                    <li><span class="list-icon"><i class="fas fa-check-circle"></i></span><strong>مرونة في خيارات الإيجار:</strong> مدد إيجار يومية، أسبوعية، وشهرية تناسب احتياجاتك.</li>
                </ul>
            </section>
        </div>
    </main>
    
    <!-- قسم الفريق (اختياري تمامًا) -->
    
    <section class="team-section">
        <div class="container">
            <h2 class="section-title">تعرف على فريقنا الملهم</h2>
            <div class="team-grid">
                <div class="team-member-card">
                    <img src="assets/images/team/member1.png" alt="اسم العضو الأول">
                    <h4>اسم العضو الأول</h4>
                    <p class="team-role">المؤسس والرئيس التنفيذي</p>
                    <div class="team-member-social-links">
                        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
                <div class="team-member-card">
                    <img src="assets/images/team/member2.png" alt="اسم العضو الثاني">
                    <h4>اسم العضو الثاني</h4>
                    <p class="team-role">مدير العمليات</p>
                     <div class="team-member-social-links">
                        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                // ... المزيد من أعضاء الفريق ... 
            </div>
        </div>
    </section>
   

    <section class="about-cta-section">
        <div class="container">
            <h2>هل أنت مستعد لرحلتك القادمة؟</h2>
            <p style="margin-bottom: 30px; opacity: 0.9;">اكتشف أسطولنا المتنوع واحجز سيارتك المثالية اليوم بأفضل الأسعار.</p>
            <a href="search_results.php?show_all=true" class="btn btn-accent btn-lg" style="background-color: var(--accent-color); color: #fff; padding: 15px 35px; font-size:1.1rem;">تصفح جميع السيارات</a>
            <a href="contact_us.php" class="btn btn-outline-light btn-lg" style="border-color: #fff; color: #fff; padding: 15px 35px; font-size:1.1rem; margin-right:15px;">أو اتصل بنا</a>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
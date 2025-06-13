<?php
// includes/footer.php

// يمكنك جلب بعض البيانات الديناميكية هنا إذا لزم الأمر، مثل آخر الأخبار أو العروض
// ولكن للفوتر القياسي، عادة ما يكون المحتوى ثابتًا.
?>
<footer class="site-footer">
    <div class="container">
        <div class="footer-widgets-area"> <?php // منطقة لعناصر الفوتر إذا كانت متعددة ?>
            <div class="footer-widget about-widget">
                <h4 class="widget-title">
                    <a href="index.php" class="footer-logo">
                        <i class="fas fa-car-alt"></i> <?php // يمكنك استخدام شعار صورة هنا أيضًا ?>
                        موقع تأجير السيارات
                    </a>
                </h4>
                <p class="about-text">
                    نقدم أفضل حلول تأجير السيارات بأسعار تنافسية وخدمة عملاء متميزة. هدفنا هو جعل تجربة استئجار سيارتك سهلة ومريحة.
                </p>
                <div class="footer-social-icons">
                    <a href="#" aria-label="Facebook" title="فيسبوك"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Twitter" title="تويتر"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="Instagram" title="انستغرام"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="LinkedIn" title="لينكدإن"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>

            <div class="footer-widget links-widget">
                <h4 class="widget-title">روابط سريعة</h4>
                <ul class="footer-links-list">
                    <li><a href="index.php">الرئيسية</a></li>
                    <li><a href="search_results.php?show_all=true">جميع السيارات</a></li>
                    <li><a href="about_us.php">من نحن</a></li>
                    <li><a href="contact_us.php">اتصل بنا</a></li>
                    <li><a href="faq.php">الأسئلة الشائعة</a></li>
                </ul>
            </div>

            <div class="footer-widget links-widget">
                <h4 class="widget-title">معلومات قانونية</h4>
                <ul class="footer-links-list">
                    <li><a href="privacy_policy.php">سياسة الخصوصية</a></li>
                    <li><a href="terms_conditions.php">الشروط والأحكام</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="user_dashboard.php">لوحة التحكم</a></li>
                    <?php else: ?>
                        <li><a href="login.php">تسجيل الدخول</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="footer-widget contact-widget">
                <h4 class="widget-title">ابقى على تواصل</h4>
                <ul class="footer-contact-info">
                    <li><i class="fas fa-map-marker-alt"></i> <span>[عنوانك هنا]، [المدينة]، [الدولة]</span></li>
                    <li><i class="fas fa-phone-alt"></i> <a href="tel:+966000000000">+966 000 000 000</a></li>
                    <li><i class="fas fa-envelope"></i> <a href="mailto:info@example.com">info@example.com</a></li>
                </ul>
                <!-- يمكنك إضافة نموذج اشتراك في النشرة الإخبارية هنا -->
                <!-- 
                <form action="#" method="post" class="newsletter-form">
                    <input type="email" name="newsletter_email" placeholder="بريدك الإلكتروني للاشتراك" required>
                    <button type="submit"><i class="fas fa-paper-plane"></i></button>
                </form>
                 -->
            </div>
        </div>

        <div class="footer-bottom">
            <p>© <?php echo date("Y"); ?> جميع الحقوق محفوظة لـ <a href="index.php">موقع تأجير السيارات</a>.</p>
            <!-- <p>تصميم وتطوير: [اسمك أو اسم شركتك]</p> -->
        </div>
    </div>
</footer>

<!-- ملفات JavaScript الأساسية -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script> <!-- إذا كنت تستخدم jQuery لميزات أخرى -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ar.js"></script>
<!-- <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script> --> <!-- إذا كنت تستخدم SwiperJS -->

<!-- ملف الجافاسكربت الرئيسي للموقع -->
<script src="assets/js/script.js" defer></script>

<?php
// إذا كانت هناك أكواد جافاسكربت خاصة بالصفحة الحالية، يمكن تضمينها هنا
// مثال: if (isset($page_specific_js_footer)) { echo $page_specific_js_footer; }
?>

</body>
</html>

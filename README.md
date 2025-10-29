# 🚗 منصة متكاملة لتأجير السيارات | Full-Stack Car Rental Platform

**تطبيق ويب متكامل (Full-Stack) تم تطويره باستخدام PHP و MySQL، يوفر نظامًا شاملاً لإدارة وحجز السيارات عبر الإنترنت، مع لوحة تحكم قوية للمسؤولين.**

<!-- 🎬 IMPORTANT: Add a GIF or screenshots here showing the user interface and the admin panel. This is the most critical part for showcasing your work! -->
<!-- Example: <p align="center"><img src="assets/images/project-demo.gif" width="800"></p> -->

---

### 🛠️ التقنيات المستخدمة (Tech Stack)

| Frontend | Backend | Database | Environment |
| :---: | :---: | :---: | :---: |
| ![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white) | ![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white) | ![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white) | ![Apache](https://img.shields.io/badge/Apache-D22128?style=for-the-badge&logo=Apache&logoColor=white) |
| ![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white) | | | ![XAMPP](https://img.shields.io/badge/XAMPP-FB7A24?style=for-the-badge&logo=xampp&logoColor=white) |
| ![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black) | | | |
| ![Chart.js](https://img.shields.io/badge/Chart.js-FF6384?style=for-the-badge&logo=chartdotjs&logoColor=white) | | | |

---

### ✨ الميزات الرئيسية (Key Features)

#### للمستخدمين (User Features)
- **🔍 بحث متقدم وفلترة:** نظام بحث سهل للعثور على السيارات حسب النوع والموقع والتاريخ.
- **👤 نظام حسابات متكامل:** تسجيل، تسجيل دخول، لوحة تحكم شخصية لإدارة الحجوزات والملف الشخصي.
- **📅 عملية حجز سهلة:** واجهة بسيطة لحجز السيارات وتأكيدها.
- **🔐 استعادة كلمة المرور:** آلية آمنة لإعادة تعيين كلمة المرور المنسية.
- **📱 تصميم متجاوب:** تجربة مستخدم سلسة على جميع الأجهزة من الهواتف إلى الشاشات الكبيرة.

#### للمسؤولين (Admin Dashboard Features)
- **🚗 إدارة أسطول السيارات:** لوحة تحكم لإضافة وتعديل وحذف السيارات وفئاتها ومواقعها.
- **🧾 إدارة الحجوزات:** عرض جميع حجوزات العملاء وتحديث حالتها (مؤكد، ملغي، مكتمل).
- **👥 إدارة المستخدمين:** التحكم في حسابات المستخدمين وأدوارهم.
- **📈 تقارير وإحصائيات:** رسوم بيانية لعرض تقارير أساسية حول أداء الموقع.
- **🛡️ أمان أساسي:** حماية ضد ثغرات SQL Injection و XSS باستخدام Prepared Statements.

---

<details>
<summary>🚀 <strong>دليل الإعداد والتشغيل (Setup & Run)</strong></summary>

لتشغيل المشروع على بيئة تطوير محلية، اتبع الخطوات التالية:

#### 1. المتطلبات
- بيئة تطوير محلية مثل **XAMPP** أو WAMP (تتضمن Apache, PHP, MySQL).
- PHP 7.4 أو أحدث.
- MySQL 5.7 أو أحدث.

#### 2. إعداد المشروع
1.  **الحصول على الملفات:** قم بتنزيل ملفات المشروع وضعها في مجلد `car_rental` داخل المجلد الجذر لخادم الويب (عادةً `htdocs` في XAMPP).
2.  **إنشاء قاعدة البيانات:**
    -   اذهب إلى `http://localhost/phpmyadmin`.
    -   أنشئ قاعدة بيانات جديدة باسم `car_rental_db` بترميز `utf8mb4_unicode_ci`.
    -   استورد ملف `database_schema.sql` (إن وجد) أو قم بتنفيذ أوامر SQL لإنشاء الجداول اللازمة.
3.  **تكوين الاتصال:**
    -   افتح ملف `includes/db_connect.php`.
    -   تأكد من أن معلومات الاتصال (`$servername`, `$username`, `$password`, `$dbname`) تطابق إعدادات MySQL المحلية. (الافتراضي في XAMPP هو `root` بدون كلمة مرور).

#### 3. تشغيل المشروع
-   افتح متصفح الويب واذهب إلى: `http://localhost/car_rental/`

#### 4. الوصول كمسؤول
-   أنشئ حساباً جديداً، ثم قم بتغيير قيمة حقل `role` إلى `admin` في جدول `users` عبر phpMyAdmin.
-   **مهم:** تأكد من أن كلمة المرور مخزنة بشكل مجزأ (hashed) باستخدام `password_hash()` في PHP.

</details>

<details>
<summary>📁 <strong>نظرة على هيكلية المشروع (Project Architecture)</strong></summary>
car_rental/
├── admin/ # لوحة تحكم المسؤول
├── api/ # ملفات PHP لطلبات AJAX
├── assets/ # ملفات (CSS, JS, Images) العامة
├── includes/ # ملفات PHP مشتركة (اتصال DB، دوال)
├── index.php # الصفحة الرئيسية
├── login.php # صفحة تسجيل الدخول
├── register.php # صفحة إنشاء حساب
├── car_details.php # صفحة تفاصيل السيارة
├── user_dashboard.php# لوحة تحكم المستخدم
└── ... # باقي صفحات الموقع
code
Code
</details>

---

### 🔮 نقاط للتطوير المستقبلي (Future Improvements)

-   تكامل مع بوابات دفع إلكتروني.
-   نظام تقييم ومراجعات للسيارات من قبل المستخدمين.
-   دعم متعدد اللغات (Internationalization).
-   نظام إشعارات عبر البريد الإلكتروني.
-   تحسينات أمان متقدمة (CSRF protection, rate limiting).

---

### 🤝 المساهمة (Contributing)

المساهمات مرحب بها! يرجى اتباع الإجراءات القياسية لطلبات الدمج (Pull Requests).

---

### 📄 الترخيص (License)

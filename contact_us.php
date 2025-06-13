<?php
// contact_us.php
require_once 'includes/db_connect.php'; // للاتساق
require_once 'includes/functions.php';

$page_title = "اتصل بنا - موقع استئجار السيارات";
$message_sent = false;
$error_message_submit = ''; // لتفادي التعارض مع متغيرات أخرى قد تكون باسم error_message
$form_errors = [];
$form_data = []; // لتخزين بيانات النموذج لإعادة ملئها عند الخطأ

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $form_data['name'] = sanitize_input($_POST['name'] ?? '');
    $form_data['email'] = sanitize_input($_POST['email'] ?? '');
    $form_data['subject'] = sanitize_input($_POST['subject'] ?? '');
    $form_data['message'] = sanitize_input($_POST['message'] ?? '');

    // التحقق من صحة المدخلات
    if (empty($form_data['name'])) {
        $form_errors['name'] = "الاسم الكامل مطلوب.";
    }
    if (empty($form_data['email'])) {
        $form_errors['email'] = "البريد الإلكتروني مطلوب.";
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $form_errors['email'] = "صيغة البريد الإلكتروني غير صحيحة.";
    }
    if (empty($form_data['subject'])) {
        $form_errors['subject'] = "موضوع الرسالة مطلوب.";
    }
    if (empty($form_data['message'])) {
        $form_errors['message'] = "محتوى الرسالة مطلوب.";
    }

    if (empty($form_errors)) {
        $to = "your-email@example.com"; // **استبدل ببريدك الإلكتروني الفعلي**
        $email_subject = "رسالة من نموذج الاتصال بموقع تأجير السيارات: " . $form_data['subject'];
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n"; // لإرسال بريد HTML
        $headers .= 'From: ' . $form_data['name'] . ' <' . $form_data['email'] . '>' . "\r\n";
        $headers .= 'Reply-To: ' . $form_data['email'] . "\r\n";

        $email_body_html = "<html><body dir='rtl' style='font-family: Arial, sans-serif;'>";
        $email_body_html .= "<h2>رسالة جديدة من نموذج الاتصال:</h2>";
        $email_body_html .= "<p><strong>الاسم:</strong> " . htmlspecialchars($form_data['name']) . "</p>";
        $email_body_html .= "<p><strong>البريد الإلكتروني:</strong> " . htmlspecialchars($form_data['email']) . "</p>";
        $email_body_html .= "<p><strong>الموضوع:</strong> " . htmlspecialchars($form_data['subject']) . "</p>";
        $email_body_html .= "<h3>الرسالة:</h3>";
        $email_body_html .= "<p style='border: 1px solid #eee; padding: 10px; background-color: #f9f9f9;'>" . nl2br(htmlspecialchars($form_data['message'])) . "</p>";
        $email_body_html .= "</body></html>";
        
        // استخدام mb_encode_mimeheader للموضوع إذا كان يحتوي على حروف عربية
        $encoded_subject = mb_encode_mimeheader($email_subject, 'UTF-8', 'B');

        if (mail($to, $encoded_subject, $email_body_html, $headers)) {
            $message_sent = true;
            $form_data = []; // مسح بيانات النموذج بعد الإرسال الناجح
        } else {
            $error_message_submit = "عفواً، حدث خطأ أثناء محاولة إرسال رسالتك. يرجى المحاولة مرة أخرى لاحقاً أو الاتصال بنا مباشرة.";
        }
    } else {
        $error_message_submit = "الرجاء تصحيح الأخطاء في النموذج والمحاولة مرة أخرى.";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> <!-- للأيقونات -->
    <style>
        .contact-page-section { padding: 60px 0; }
        .contact-header { text-align: center; margin-bottom: 50px; }
        .contact-header h1 { font-size: 2.5rem; margin-bottom: 10px; }
        .contact-header p { font-size: 1.1rem; color: #666; max-width: 700px; margin: 0 auto; }

        .contact-grid-layout {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 40px;
            align-items: flex-start;
        }

        .contact-info-card {
            background-color: #fff;
            padding: 30px;
            border-radius: var(--border-radius-base);
            box-shadow: var(--card-shadow);
        }
        .contact-info-card h2 {
            font-size: 1.8rem;
            color: var(--primary-color);
            margin-bottom: 25px;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            display: inline-block;
        }
        .contact-info-item {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            font-size: 1rem;
        }
        .contact-info-item i {
            font-size: 1.5rem;
            color: var(--accent-color);
            margin-left: 15px; /* مسافة بين الأيقونة والنص */
            width: 30px; /* لتوسيط الأيقونات */
            text-align: center;
        }
        .contact-info-item a { color: var(--text-color); }
        .contact-info-item a:hover { color: var(--accent-color); }

        .contact-form-card {
            background-color: #fff;
            padding: 30px;
            border-radius: var(--border-radius-base);
            box-shadow: var(--card-shadow);
        }
        .contact-form-card h2 { font-size: 1.8rem; margin-bottom: 25px; text-align: center; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #555; }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-base);
            box-sizing: border-box;
            font-family: inherit;
            font-size: 1rem;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
            outline: none;
        }
        .form-group textarea { resize: vertical; min-height: 150px; }
        .form-error { color: #dc3545; font-size: 0.875em; margin-top: 5px; }
        
        .submit-button-container { text-align: center; /* أو right إذا أردت */ }
        .btn-submit-contact { padding: 12px 30px; font-size: 1.1em; background-color: var(--accent-color); border-color: var(--accent-color); }
        .btn-submit-contact:hover { background-color: var(--accent-color-dark); border-color: var(--accent-color-dark); }

        /* Map placeholder */
        .map-container {
            margin-top: 40px;
            border-radius: var(--border-radius-base);
            overflow: hidden;
            box-shadow: var(--card-shadow);
            height: 350px; /* ارتفاع الخريطة */
            background-color: #e9ecef; /* لون مؤقت للخريطة */
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-style: italic;
        }
        
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="contact-page-section">
        <div class="container">
            <div class="contact-header">
                <h1>تواصل معنا</h1>
                <p>نحن هنا للإجابة على جميع استفساراتك ومساعدتك في كل ما يتعلق بخدمات تأجير السيارات. لا تتردد في الاتصال بنا!</p>
            </div>

            <?php if ($message_sent): ?>
                <div class="alert alert-success text-center">
                    <h4><i class="fas fa-check-circle"></i> شكراً لك!</h4>
                    تم إرسال رسالتك بنجاح. سنتواصل معك في أقرب وقت ممكن.
                </div>
            <?php endif; ?>

            <?php if ($error_message_submit && !$message_sent): ?>
                 <div class="alert alert-danger text-center">
                    <h4><i class="fas fa-exclamation-triangle"></i> خطأ في الإرسال</h4>
                    <?php echo htmlspecialchars($error_message_submit); ?>
                </div>
            <?php endif; ?>


            <div class="contact-grid-layout">
                <div class="contact-info-card">
                    <h2>معلومات الاتصال</h2>
                    <div class="contact-info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>[اكتب عنوان مكتبك الكامل هنا]، [المدينة]، [الدولة]</span>
                    </div>
                    <div class="contact-info-item">
                        <i class="fas fa-phone-alt"></i>
                        <a href="tel:+966000000000">+966 000 000 000</a> <!-- استبدل بالرقم الصحيح -->
                    </div>
                    <div class="contact-info-item">
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:info@example.com">info@example.com</a> <!-- استبدل بالبريد الصحيح -->
                    </div>
                    <div class="contact-info-item">
                        <i class="fas fa-clock"></i>
                        <span>ساعات العمل: السبت - الخميس، 9:00 صباحًا - 6:00 مساءً</span>
                    </div>
                    <!-- يمكنك إضافة روابط لوسائل التواصل الاجتماعي هنا -->
                    <!-- 
                    <div class="social-links" style="margin-top: 20px; text-align: center;">
                        <a href="#" aria-label="Facebook" style="font-size: 1.5em; margin: 0 10px;"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Twitter" style="font-size: 1.5em; margin: 0 10px;"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Instagram" style="font-size: 1.5em; margin: 0 10px;"><i class="fab fa-instagram"></i></a>
                    </div>
                    -->
                </div>

                <?php if (!$message_sent): // اعرض النموذج فقط إذا لم يتم إرسال الرسالة بنجاح ?>
                <div class="contact-form-card">
                    <h2>أرسل لنا رسالة</h2>
                    <form action="contact_us.php#form-anchor" method="POST" id="contactUsForm"> <!-- #form-anchor للانتقال إلى النموذج بعد الإرسال -->
                        <div class="form-group">
                            <label for="name">الاسم الكامل <span style="color:red;">*</span></label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>" required>
                            <?php if (isset($form_errors['name'])): ?><p class="form-error"><?php echo $form_errors['name']; ?></p><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="email">البريد الإلكتروني <span style="color:red;">*</span></label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required>
                            <?php if (isset($form_errors['email'])): ?><p class="form-error"><?php echo $form_errors['email']; ?></p><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="subject">الموضوع <span style="color:red;">*</span></label>
                            <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($form_data['subject'] ?? ''); ?>" required>
                            <?php if (isset($form_errors['subject'])): ?><p class="form-error"><?php echo $form_errors['subject']; ?></p><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="message">رسالتك <span style="color:red;">*</span></label>
                            <textarea id="message" name="message" required><?php echo htmlspecialchars($form_data['message'] ?? ''); ?></textarea>
                            <?php if (isset($form_errors['message'])): ?><p class="form-error"><?php echo $form_errors['message']; ?></p><?php endif; ?>
                        </div>
                        <div class="submit-button-container">
                            <button type="submit" class="btn btn-primary btn-submit-contact"><i class="fas fa-paper-plane"></i> إرسال الرسالة</button>
                        </div>
                    </form>
                    <a name="form-anchor"></a> <!-- نقطة مرجعية للنموذج -->
                </div>
                <?php endif; ?>
            </div>

            <!-- قسم الخريطة (اختياري) -->
            
            <div class="map-container">
                 <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3624.801170019106!2d46.675295915000004!3d24.71355198415081!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3e2f0389699a1555%3A0x403818031619bb0!2sRiyadh!5e0!3m2!1sen!2ssa!4v1678886400000!5m2!1sen!2ssa" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
           
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
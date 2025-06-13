<?php
// faq.php
require_once 'includes/db_connect.php'; // للاتساق
require_once 'includes/functions.php';

$page_title = "الأسئلة الشائعة - موقع استئجار السيارات";
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style> /* نفس أنماط static-page-container */
        .static-page-container { max-width: 800px; margin: 30px auto; padding: 25px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); line-height: 1.7; }
        .static-page-container h1 { margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .faq-item { margin-bottom: 25px; }
        .faq-item h3 { /* السؤال */
            font-size: 1.2em;
            color: #007bff; /* أزرق مميز للسؤال */
            margin-bottom: 8px;
            cursor: pointer; /* للإشارة إلى أنه قابل للنقر إذا أضفت تفاعلية JS */
            position: relative;
            padding-right: 25px; /* مساحة للأيقونة */
        }
        .faq-item h3::before { /* أيقونة زائد/ناقص (يمكن تحسينها بـ SVG أو FontAwesome) */
            content: '+'; /* أو أيقونة + */
            position: absolute;
            right: 0;
            top: 1px;
            font-weight: bold;
            transition: transform 0.2s ease-in-out;
        }
        .faq-item.active h3::before {
            content: '-'; /* أو أيقونة - */
            /* transform: rotate(45deg); لتأثير X إذا كانت الأيقونة + */
        }
        .faq-item .answer { /* الإجابة */
            padding-right: 25px; /* محاذاة مع السؤال */
            color: #555;
            display: none; /* مخفية بشكل افتراضي، تظهر بـ JS أو تبقى ظاهرة */
        }
         .faq-item.active .answer {
            display: block;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="static-page-container">
            <h1><?php echo htmlspecialchars($page_title); ?></h1>

            <div class="faq-list">
                <div class="faq-item">
                    <h3>ما هي المستندات المطلوبة لاستئجار سيارة؟</h3>
                    <div class="answer">
                        <p>بشكل عام، ستحتاج إلى تقديم المستندات التالية:</p>
                        <ul>
                            <li>رخصة قيادة سارية المفعول (للسائق الرئيسي وأي سائقين إضافيين).</li>
                            <li>بطاقة هوية وطنية سارية أو جواز سفر ساري (للزوار الدوليين).</li>
                            <li>بطاقة ائتمان باسم السائق الرئيسي (لإجراء الدفع والوديعة التأمينية).</li>
                        </ul>
                        <p>قد تختلف المتطلبات قليلاً بناءً على موقع الاستلام ونوع السيارة. يرجى التحقق من الشروط المحددة عند الحجز.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <h3>هل يمكنني استئجار سيارة إذا كان عمري أقل من 25 عامًا؟</h3>
                    <div class="answer">
                        <p>نعم، في معظم الحالات يمكن للسائقين الذين تتراوح أعمارهم بين [حدد السن الأدنى، مثلاً 21] و 24 عامًا استئجار سيارة، ولكن قد يتم تطبيق "رسوم سائق شاب" إضافية. قد تكون هناك قيود على أنواع معينة من السيارات للسائقين الشباب. يرجى مراجعة الشروط عند الحجز أو الاتصال بنا لمزيد من المعلومات.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <h3>ماذا يشمل سعر الإيجار؟</h3>
                    <div class="answer">
                        <p>عادةً ما يشمل سعر الإيجار اليومي ما يلي:</p>
                        <ul>
                            <li>عدد كيلومترات محدد (أو غير محدود، حسب العرض).</li>
                            <li>تأمين أساسي ضد الغير ومسؤولية محدودة عن الأضرار (CDW/LDW) مع مبلغ تحمل.</li>
                            <li>الضرائب المحلية ورسوم المطار (إذا كانت مطبقة).</li>
                        </ul>
                        <p>لا يشمل السعر عادةً الوقود، رسوم السائق الإضافي، تأمينات إضافية اختيارية، رسوم المرور، أو أي غرامات. سيتم توضيح كل التفاصيل في عرض السعر الخاص بك.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <h3>هل يمكنني إضافة سائق إضافي؟</h3>
                    <div class="answer">
                        <p>نعم، يمكنك إضافة سائقين إضافيين إلى اتفاقية الإيجار. يجب أن يستوفي السائق الإضافي نفس متطلبات العمر ورخصة القيادة مثل السائق الرئيسي. قد يتم تطبيق رسوم يومية إضافية لكل سائق إضافي.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <h3>ما هي سياسة الوقود؟</h3>
                    <div class="answer">
                        <p>عادةً ما يتم تسليم السيارة بخزان وقود ممتلئ (أو مستوى معين)، ويجب إعادتها بنفس مستوى الوقود. إذا تم إرجاع السيارة بكمية وقود أقل، فسيتم محاسبتك على الوقود المفقود بسعر أعلى من سعر السوق، بالإضافة إلى رسوم خدمة التزويد بالوقود. تتوفر خيارات أخرى أحيانًا مثل الدفع المسبق للوقود.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <h3>ماذا أفعل في حالة وقوع حادث أو عطل بالسيارة؟</h3>
                    <div class="answer">
                        <p>في حالة وقوع حادث، يجب عليك أولاً التأكد من سلامة جميع الركاب. ثم اتصل بالشرطة (إذا لزم الأمر) وأبلغنا على الفور باستخدام رقم الطوارئ الموجود في مستندات الإيجار الخاصة بك. لا تقم بإصلاح السيارة بنفسك دون موافقتنا.</p>
                        <p>في حالة حدوث عطل ميكانيكي، اتصل بنا على الفور للحصول على المساعدة على الطريق أو لترتيب سيارة بديلة إذا لزم الأمر.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <h3>كيف يمكنني إلغاء أو تعديل حجزي؟</h3>
                    <div class="answer">
                        <p>يمكنك إلغاء أو تعديل حجزك من خلال قسم "حجوزاتي" في لوحة تحكم المستخدم الخاصة بك على موقعنا (إذا كنت قد أنشأت حسابًا). يمكنك أيضًا الاتصال بنا مباشرة. يرجى ملاحظة أن سياسة الإلغاء والتعديل قد تتضمن رسومًا، خاصة إذا تم ذلك قبل وقت قصير من موعد الاستلام. يتم توضيح هذه السياسات أثناء عملية الحجز.</p>
                    </div>
                </div>

                <!-- أضف المزيد من الأسئلة والأجوبة حسب الحاجة -->

            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script>
        // جافاسكربت بسيط لجعل الأسئلة قابلة للفتح والإغلاق
        document.querySelectorAll('.faq-item h3').forEach(question => {
            question.addEventListener('click', () => {
                const item = question.parentElement;
                item.classList.toggle('active');
                // تحديث نص أيقونة الزائد/الناقص إذا كنت تستخدم content CSS
                // const icon = question.querySelector('::before'); // هذا لا يعمل مباشرة مع ::before
                // إذا أردت تغيير الأيقونة بشكل ديناميكي أكثر، استخدم عنصر span للأيقونة داخل h3
            });
        });
        // فتح أول سؤال بشكل افتراضي (اختياري)
        // const firstFaqItem = document.querySelector('.faq-item');
        // if(firstFaqItem) firstFaqItem.classList.add('active');
    </script>
</body>
</html>
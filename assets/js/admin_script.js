// assets/js/admin_script.js
$(document).ready(function() {
    // تفعيل DataTables للجداول التي لديها ID محدد
    // يمكنك تخصيص خيارات DataTables حسب الحاجة (مثل اللغة، الترتيب الافتراضي، إلخ)
    if ($('#carsTable').length) {
        $('#carsTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json" // للغة العربية
            },
            "order": [[0, "desc"]] // ترتيب حسب العمود الأول (ID) تنازلياً
        });
    }

    if ($('#bookingsTable').length) {
        $('#bookingsTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json"
            },
            "order": [[0, "desc"]] // ترتيب حسب رقم الحجز تنازلياً
        });
    }

    if ($('#usersTable').length) {
        $('#usersTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json"
            },
            "order": [[0, "desc"]] // ترتيب حسب ID المستخدم تنازلياً
        });
    }
    
    // يمكنك إضافة أي تفاعلات أخرى خاصة بلوحة التحكم هنا
    // مثال: تأكيد قبل الحذف (على الرغم من أننا وضعناه inline في بعض الحالات)
    $('.btn-danger[onclick*="confirm"]').on('click', function() {
        // يمكن أن يكون هناك منطق إضافي هنا إذا لم يتم استخدام onclick inline
    });

    // إخفاء رسائل التنبيه بعد فترة
    setTimeout(function() {
        $('.admin-alert').fadeOut('slow');
    }, 5000); // 5 ثواني

});
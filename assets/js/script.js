// assets/js/script.js

document.addEventListener('DOMContentLoaded', function() {
    
    // --- الدوال المساعدة ---
    /**
     * دالة عامة لإجراء طلبات API
     * @param {string} endpoint - نقطة نهاية API (e.g., 'api/search_cars.php')
     * @param {object} options - خيارات fetch (method, headers, body, etc.)
     * @param {URLSearchParams|FormData|object} [params] - معلمات GET أو بيانات POST
     * @returns {Promise<object>} - Promise يحل إلى بيانات JSON من الرد
     */
    async function fetchAPI(endpoint, options = {}, params = null) {
        const defaultHeaders = {
            'X-Requested-With': 'XMLHttpRequest', // للإشارة إلى أنه طلب AJAX
        };

        // إذا كان params من نوع FormData، لا تقم بتعيين Content-Type، المتصفح سيفعل ذلك
        if (!(params instanceof FormData)) {
            defaultHeaders['Content-Type'] = 'application/json';
        }

        options.headers = { ...defaultHeaders, ...options.headers };

        let url = endpoint;
        if (options.method === 'GET' && params) {
            url += '?' + (params instanceof URLSearchParams ? params.toString() : new URLSearchParams(params).toString());
        } else if (params && (options.method === 'POST' || options.method === 'PUT')) {
            options.body = (params instanceof FormData) ? params : JSON.stringify(params);
        }
        
        try {
            const response = await fetch(url, options);
            if (!response.ok) {
                // محاولة قراءة الخطأ كـ JSON إذا أمكن
                let errorData;
                try {
                    errorData = await response.json();
                } catch (e) {
                    errorData = { message: `HTTP error! Status: ${response.status}` };
                }
                throw errorData; 
            }
            return await response.json();
        } catch (error) {
            console.error(`API Error (${endpoint}):`, error);
            // أعد طرح الخطأ ليتم التعامل معه بواسطة المستدعي إذا كان يحتوي على رسالة
            throw error.message ? error : { message: 'Network error or invalid JSON response.' };
        }
    }

    /**
     * دالة لعرض رسائل التنبيه للمستخدم
     * @param {string} message - الرسالة المراد عرضها
     * @param {string} type - 'success', 'error', 'info' (يجب أن يكون لديك CSS لهذه الكلاسات)
     * @param {HTMLElement} [container=null] - الحاوية التي ستعرض فيها الرسالة (إذا لم تحدد، قد تستخدم alert)
     * @param {number} [timeout=5000] - مدة عرض الرسالة بالمللي ثانية (0 لتبقى ظاهرة)
     */
    function showAlert(message, type = 'info', container = null, timeout = 5000) {
        if (container) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`; // افترض أن لديك كلاسات CSS: alert, alert-success, alert-error, alert-info
            alertDiv.textContent = message;
            // إزالة أي تنبيهات سابقة في نفس الحاوية
            const existingAlert = container.querySelector('.alert');
            if(existingAlert) existingAlert.remove();
            
            container.prepend(alertDiv); // إضافة التنبيه في بداية الحاوية

            if (timeout > 0) {
                setTimeout(() => {
                    alertDiv.style.opacity = '0';
                    setTimeout(() => alertDiv.remove(), 500); // إزالة بعد التلاشي
                }, timeout);
            }
        } else {
            alert(`[${type.toUpperCase()}] ${message}`); // fallback to browser alert
        }
    }


    // --- تهيئة عامة ---
    // تهيئة Flatpickr لجميع العناصر التي لديها كلاس .datepicker
    // (إذا لم يتم تهيئتها بالفعل في كل صفحة على حدة)
    if (typeof flatpickr !== 'undefined') {
        const datePickers = document.querySelectorAll('.datepicker');
        if (datePickers.length > 0 && !datePickers[0].classList.contains('flatpickr-input')) { // تحقق بسيط لتجنب إعادة التهيئة
             flatpickr(".datepicker", {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                minDate: "today",
                locale: "ar", // تأكد من تضمين ملف اللغة العربية لـ flatpickr
                time_24hr: true,
                minuteIncrement: 30,
                onChange: function(selectedDates, dateStr, instance) {
                    if (instance.input.id === 'pickup_datetime' || instance.input.id === 'pickup_date') { // دعم أسماء مختلفة
                        const returnDateInputId = instance.input.id.replace('pickup', 'return');
                        const returnDatepicker = document.getElementById(returnDateInputId);
                        if(returnDatepicker && returnDatepicker._flatpickr){ // إذا كان قد تم تهيئته
                             returnDatepicker._flatpickr.set('minDate', dateStr);
                        }
                    }
                }
            });
        }
    }


    // --- التفاعلات مع API ---

    // 1. التحقق من توفر البريد الإلكتروني في صفحة التسجيل
    const emailInput = document.getElementById('email'); // افترض أن حقل البريد في التسجيل له هذا الـ id
    const emailAvailabilityMsg = document.getElementById('email-availability-msg'); // عنصر لعرض رسالة التوفر

    if (emailInput && emailAvailabilityMsg) {
        emailInput.addEventListener('blur', async function() {
            const email = this.value.trim();
            if (email.length > 5 && email.includes('@')) { // تحقق بسيط قبل إرسال الطلب
                emailAvailabilityMsg.textContent = 'يتم التحقق...';
                emailAvailabilityMsg.className = 'form-message form-message-checking';
                try {
                    const data = await fetchAPI('api/user_actions.php', { method: 'POST' }, { action: 'check_email_availability', email: email });
                    if (data.success) {
                        emailAvailabilityMsg.textContent = data.message;
                        emailAvailabilityMsg.className = data.available ? 'form-message form-message-success' : 'form-message form-message-error';
                    } else {
                        emailAvailabilityMsg.textContent = data.message || 'خطأ في التحقق.';
                        emailAvailabilityMsg.className = 'form-message form-message-error';
                    }
                } catch (error) {
                    console.error("Error checking email:", error);
                    emailAvailabilityMsg.textContent = 'خطأ في الاتصال بالخادم.';
                    emailAvailabilityMsg.className = 'form-message form-message-error';
                }
            } else {
                emailAvailabilityMsg.textContent = ''; // مسح الرسالة إذا كان البريد غير صالح
            }
        });
    }

    // 2. التعامل مع نموذج الحجز (في car_details.php)
    const carBookingForm = document.getElementById('carBookingForm'); // افترض أن نموذج الحجز له هذا الـ id
    const bookingFormMessagesContainer = document.getElementById('booking-form-messages'); // عنصر لعرض رسائل النموذج

    if (carBookingForm && bookingFormMessagesContainer) {
        carBookingForm.addEventListener('submit', async function(event) {
            event.preventDefault(); // منع الإرسال التقليدي
            
            const submitButton = carBookingForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'جاري المعالجة...';

            const formData = new FormData(carBookingForm);
            // إضافة رابط الصفحة الحالية لإعادة التوجيه بعد تسجيل الدخول إذا لزم الأمر
            formData.append('current_page_url', window.location.href);

            try {
                const data = await fetchAPI('api/book_car.php', { method: 'POST' }, formData);
                if (data.success) {
                    showAlert(data.message + (data.total_price ? ` الإجمالي: ${data.total_price} ريال.` : ''), 'success', bookingFormMessagesContainer);
                    // يمكنك إخفاء النموذج أو إعادة توجيه المستخدم
                    carBookingForm.reset(); // مسح النموذج
                    if (data.confirmation_url) {
                        setTimeout(() => { window.location.href = data.confirmation_url; }, 3000);
                    }
                } else {
                    if (data.redirect_url) {
                        showAlert(data.message + " سيتم توجيهك لصفحة تسجيل الدخول.", 'info', bookingFormMessagesContainer);
                        setTimeout(() => { window.location.href = data.redirect_url; }, 3000);
                    } else {
                        showAlert(data.message || 'فشل الحجز. يرجى التحقق من البيانات المدخلة.', 'error', bookingFormMessagesContainer);
                    }
                }
            } catch (error) {
                showAlert(error.message || 'حدث خطأ غير متوقع أثناء محاولة الحجز.', 'error', bookingFormMessagesContainer);
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            }
        });
    }


    // 3. البحث الديناميكي عن السيارات (إذا كان لديك زر بحث مخصص و div لعرض النتائج)
    const ajaxSearchForm = document.getElementById('ajaxSearchForm'); // افترض نموذج بحث AJAX
    const ajaxSearchResultsContainer = document.getElementById('ajaxSearchResults'); // حاوية عرض النتائج

    if (ajaxSearchForm && ajaxSearchResultsContainer) {
        ajaxSearchForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            const searchButton = ajaxSearchForm.querySelector('button[type="submit"]');
            searchButton.disabled = true;
            ajaxSearchResultsContainer.innerHTML = '<p>جاري البحث عن السيارات...</p>';

            const formData = new FormData(ajaxSearchForm);
            const params = new URLSearchParams(formData);

            try {
                const data = await fetchAPI('api/search_cars.php', { method: 'GET' }, params);
                ajaxSearchResultsContainer.innerHTML = ''; // مسح رسالة "جاري البحث"
                if (data.success && data.cars.length > 0) {
                    data.cars.forEach(car => {
                        const carCard = `
                            <div class="car-card">
                                <img src="${car.image_full_url}" alt="${car.make} ${car.model}">
                                <h3>${car.make} ${car.model} (${car.year})</h3>
                                <p>الفئة: ${car.category_name}</p>
                                <p>السعر اليومي: ${car.daily_rate} ريال</p>
                                <a href="${car.details_url}" class="btn">عرض التفاصيل والحجز</a>
                            </div>
                        `;
                        ajaxSearchResultsContainer.innerHTML += carCard;
                    });
                } else {
                    ajaxSearchResultsContainer.innerHTML = `<p>${data.message || 'لم يتم العثور على سيارات تطابق بحثك.'}</p>`;
                }
            } catch (error) {
                ajaxSearchResultsContainer.innerHTML = `<p class="alert alert-error">${error.message || 'حدث خطأ أثناء البحث.'}</p>`;
            } finally {
                searchButton.disabled = false;
            }
        });
    }


    // 4. تحديث الملف الشخصي (في لوحة تحكم المستخدم)
    const profileUpdateForm = document.getElementById('profileUpdateForm'); // افترض نموذج تحديث الملف الشخصي
    const profileMessagesContainer = document.getElementById('profile-messages');

    if (profileUpdateForm && profileMessagesContainer) {
        profileUpdateForm.addEventListener('submit', async function(event){
            event.preventDefault();
            const submitButton = profileUpdateForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;

            const formData = new FormData(profileUpdateForm);
            formData.append('action', 'update_profile'); // إضافة الإجراء المطلوب لـ user_actions.php

            try {
                const data = await fetchAPI('api/user_actions.php', { method: 'POST' }, formData);
                if(data.success){
                    showAlert(data.message, 'success', profileMessagesContainer);
                    // يمكنك تحديث الاسم المعروض في الهيدر إذا تغير
                    if(formData.has('full_name') && document.querySelector('.navbar .user-name-display')){
                         document.querySelector('.navbar .user-name-display').textContent = formData.get('full_name');
                    }
                } else {
                    showAlert(data.message || 'فشل تحديث الملف الشخصي.', 'error', profileMessagesContainer);
                }
            } catch (error) {
                 showAlert(error.message || 'حدث خطأ غير متوقع.', 'error', profileMessagesContainer);
            } finally {
                submitButton.disabled = false;
            }
        });
    }

    // 5. مثال: عرض تفاصيل السيارة في Modal عند النقر (يتطلب HTML و CSS لـ Modal)
    // افترض أن لديك أزرار بالـ class `view-car-details-btn` وقيمة `data-car-id`
    // و Modal بالـ id `carDetailsModal` ومحتوى داخلي لعرض التفاصيل
    const carDetailsModal = document.getElementById('carDetailsModal');
    const carDetailsModalContent = document.getElementById('carDetailsModalContent');
    const closeModalButton = document.querySelector('.close-modal-button'); // زر إغلاق الـ modal

    if (carDetailsModal && carDetailsModalContent && closeModalButton) {
        document.querySelectorAll('.view-car-details-btn').forEach(button => {
            button.addEventListener('click', async function() {
                const carId = this.dataset.carId;
                if (!carId) return;

                carDetailsModalContent.innerHTML = '<p>جاري تحميل التفاصيل...</p>';
                carDetailsModal.style.display = 'block'; // عرض الـ modal

                try {
                    const data = await fetchAPI('api/get_car_details.php', { method: 'GET' }, { id: carId });
                    if (data.success && data.car) {
                        const car = data.car;
                        // بناء محتوى HTML لعرض تفاصيل السيارة
                        carDetailsModalContent.innerHTML = `
                            <h2>${car.make} ${car.model} (${car.year})</h2>
                            <img src="${car.image_full_url}" alt="${car.make} ${car.model}" style="max-width:100%; height:auto; border-radius:5px;">
                            <p><strong>الفئة:</strong> ${car.category_name}</p>
                            <p><strong>ناقل الحركة:</strong> ${car.transmission}</p>
                            <p><strong>السعر اليومي:</strong> ${car.daily_rate} ريال</p>
                            <p><strong>الموقع:</strong> ${car.location_city} - ${car.location_name}</p>
                            <p><strong>الوصف:</strong> ${car.description || 'لا يوجد وصف'}</p>
                            ${car.features_list && car.features_list.length > 0 ? 
                                `<h3>الميزات:</h3><ul>${car.features_list.map(f => `<li>${f}</li>`).join('')}</ul>` : ''}
                            <a href="car_details.php?id=${car.id}" class="btn btn-primary" style="margin-top:15px;">الانتقال للحجز الكامل</a>
                        `;
                    } else {
                        carDetailsModalContent.innerHTML = `<p class="alert alert-error">${data.message || 'فشل تحميل تفاصيل السيارة.'}</p>`;
                    }
                } catch (error) {
                    carDetailsModalContent.innerHTML = `<p class="alert alert-error">${error.message || 'حدث خطأ.'}</p>`;
                }
            });
        });

        // إغلاق الـ Modal
        closeModalButton.addEventListener('click', () => {
            carDetailsModal.style.display = 'none';
        });
        // إغلاق الـ Modal عند النقر خارج المحتوى (اختياري)
        window.addEventListener('click', (event) => {
            if (event.target === carDetailsModal) {
                carDetailsModal.style.display = 'none';
            }
        });
    }


    // --- التحقق من صحة النماذج من جانب العميل (كمثال عام) ---
    // هذا مجرد مثال بسيط، يمكنك استخدام مكتبات أكثر قوة للتحقق
    const formsToValidate = document.querySelectorAll('form.needs-validation'); // أضف كلاس 'needs-validation' للنماذج
    formsToValidate.forEach(form => {
        form.addEventListener('submit', function(event) {
            let isValid = true;
            form.querySelectorAll('[required]').forEach(input => {
                // إزالة علامات الخطأ السابقة
                input.classList.remove('is-invalid');
                const existingErrorMsg = input.parentNode.querySelector('.invalid-feedback');
                if(existingErrorMsg) existingErrorMsg.remove();

                if (!input.value.trim()) {
                    isValid = false;
                    input.classList.add('is-invalid'); // افترض أن لديك CSS لـ .is-invalid
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'invalid-feedback'; // افترض أن لديك CSS لـ .invalid-feedback
                    errorMsg.textContent = 'هذا الحقل مطلوب.';
                    input.parentNode.appendChild(errorMsg);
                } else if (input.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value)) {
                    isValid = false;
                    input.classList.add('is-invalid');
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'invalid-feedback';
                    errorMsg.textContent = 'الرجاء إدخال بريد إلكتروني صالح.';
                    input.parentNode.appendChild(errorMsg);
                }
                // يمكنك إضافة المزيد من قواعد التحقق هنا (مثل طول كلمة المرور، تطابق كلمات المرور، إلخ)
            });

            if (!isValid) {
                event.preventDefault(); // منع الإرسال إذا لم يكن صالحاً
                // يمكنك عرض رسالة عامة أعلى النموذج أيضاً
                // showAlert('الرجاء تصحيح الأخطاء المميزة في النموذج.', 'error', form.querySelector('.form-messages-container'));
            }
        });
    });


}); // نهاية DOMContentLoaded
// في assets/js/script.js، داخل DOMContentLoaded

const navbarToggler = document.querySelector('.navbar-toggler');
const navLinks = document.querySelector('.navbar .nav-links');

if (navbarToggler && navLinks) {
    navbarToggler.addEventListener('click', () => {
        navLinks.classList.toggle('active'); // إضافة أو إزالة كلاس active
        // (اختياري) تغيير أيقونة الـ toggler
        if (navbarToggler.querySelector('i')) { // إذا كان الـ toggler يحتوي على أيقونة
            const icon = navbarToggler.querySelector('i');
            if (navLinks.classList.contains('active')) {
                icon.classList.remove('fa-bars'); // افترض أن الأيقونة الافتراضية هي fa-bars
                icon.classList.add('fa-times');   // تغييرها إلى fa-times (إغلاق)
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        }
    });
}
// تأكد من أن لديك عنصر button أو a مع كلاس navbar-toggler في header.php
// مثال في header.php:
// <button class="navbar-toggler" type="button" aria-label="Toggle navigation">
//     <i class="fas fa-bars"></i>
// </button>
<?php
// admin/includes/admin_footer.php
?>
        </div> <!-- إغلاق admin-container من admin_header.php -->
    </main>
    <footer class="admin-footer">
        <div class="admin-container">
            <p>© <?php echo date("Y"); ?> لوحة تحكم موقع استئجار السيارات.</p>
        </div>
    </footer>

    <!-- jQuery ضروري لـ DataTables وميزات أخرى -->
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    
    <!-- Chart.js (مكتبة الرسوم البيانية) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- ملف الجافاسكربت الخاص بلوحة التحكم -->
    <script src="../assets/js/admin_script.js"></script>
    
    <?php
    // إذا كانت هناك أكواد جافاسكربت خاصة بالصفحة الحالية، يمكن تضمينها هنا
    // مثال: if (isset($page_specific_js)) { echo $page_specific_js; }
    ?>
</body>
</html>
<?php
// إغلاق اتصال قاعدة البيانات إذا كان مفتوحاً (عادة يتم ذلك تلقائياً عند نهاية السكربت)
// التحقق من أن $conn موجود وأنه كائن mysqli قبل محاولة إغلاقه
if (isset($conn) && $conn instanceof mysqli && $conn->thread_id) { // thread_id للتحقق من أن الاتصال لا يزال نشطاً
    $conn->close();
}
?>
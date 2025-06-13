<?php
$plain_password = 'Rax222Rax'; // اختر كلمة مرور قوية
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
echo "كلمة المرور الأصلية: " . $plain_password . "<br>";
echo "الهاش: " . $hashed_password;
?>
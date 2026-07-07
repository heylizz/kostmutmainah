<?php
// Jalankan file ini sekali di browser: localhost/kost_mutmainah/generate_password.php
// Lalu copy hash yang muncul ke database

$passwords = [
    'admin'    => 'admin123',
    'penghuni' => 'penghuni123',
];

foreach ($passwords as $user => $pass) {
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    echo "<b>$user</b> (password: $pass)<br>";
    echo "Hash: $hash<br><br>";
    echo "SQL: UPDATE user SET password='$hash' WHERE username='$user';<br><hr>";
}
?>

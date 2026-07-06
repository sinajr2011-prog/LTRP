<?php
$host = 'localhost';
$dbname = 'dhiizqnl_qoqnoos_new';
$username = 'dhiizqnl_user_new';
$password = '12345678';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT * FROM students WHERE national_code = '1234567890'");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ کاربر پیدا شد: " . $user['full_name'] . "\n";
        echo "رمز ذخیره شده: " . $user['password'] . "\n";
        
        $plain_password = '123456';
        if (password_verify($plain_password, $user['password'])) {
            echo "✅ رمز '123456' درست است!\n";
        } else {
            echo "❌ رمز '123456' اشتباه است\n";
        }
    } else {
        echo "❌ کاربر پیدا نشد\n";
    }
} catch(PDOException $e) {
    echo "❌ خطا: " . $e->getMessage();
}

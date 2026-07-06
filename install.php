<?php
$host = 'localhost';
$dbname = 'dhiizqnl_ghoghnos';
$username = 'dhiizqnl_sina';
$password = 'sina09945417131';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("USE `$dbname`");
    echo "✅ اتصال به دیتابیس موفق!<br>";
    
    // جداول
    $pdo->exec("CREATE TABLE IF NOT EXISTS students (
        id INT PRIMARY KEY AUTO_INCREMENT,
        national_code VARCHAR(10) UNIQUE NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        class_level ENUM('7','8','9') NOT NULL,
        school_code VARCHAR(50) NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS teachers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        teacher_code VARCHAR(20) UNIQUE NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        school_code VARCHAR(50) NOT NULL,
        subject VARCHAR(50),
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS exams (
        id INT PRIMARY KEY AUTO_INCREMENT,
        exam_id VARCHAR(50) UNIQUE NOT NULL,
        title VARCHAR(200) NOT NULL,
        subject VARCHAR(50) NOT NULL,
        class_level ENUM('7','8','9') NOT NULL,
        duration INT NOT NULL,
        end_time DATETIME NOT NULL,
        questions JSON NOT NULL,
        teacher_id VARCHAR(20) NOT NULL,
        school_code VARCHAR(50) NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS exam_results (
        id INT PRIMARY KEY AUTO_INCREMENT,
        exam_id VARCHAR(50) NOT NULL,
        student_national_code VARCHAR(10) NOT NULL,
        score INT NOT NULL,
        total_score INT NOT NULL,
        percentage DECIMAL(5,2) NOT NULL,
        answers JSON NOT NULL,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_result (exam_id, student_national_code)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT PRIMARY KEY AUTO_INCREMENT,
        sender_type ENUM('teacher','developer','student') NOT NULL,
        sender_id VARCHAR(50) NOT NULL,
        receiver_type ENUM('teacher','student','developer','all') NOT NULL,
        receiver_id VARCHAR(50),
        title VARCHAR(200),
        content TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // دانش‌آموز نمونه
    $hashed = password_hash('123456', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO students (national_code, full_name, class_level, school_code, password) 
                           VALUES ('1234567890', 'دانش‌آموز نمونه', '7', 'ghoghnos', ?)");
    $stmt->execute([$hashed]);
    
    // معلم نمونه
    $hashed = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO teachers (teacher_code, full_name, school_code, subject, password) 
                           VALUES ('GH7K3M9P', 'معلم نمونه', 'ghoghnos', 'ریاضی', ?)");
    $stmt->execute([$hashed]);
    
    echo "✅ نصب کامل شد!<br>";
    echo "👤 دانش‌آموز: کد 1234567890، رمز 123456<br>";
    echo "👨‍🏫 معلم: کد GH7K3M9P، رمز admin123<br>";
    echo "<a href='student-login.html'>ورود دانش‌آموز</a> | <a href='teacher-login.html'>ورود معلم</a>";
    
} catch(PDOException $e) {
    echo "❌ خطا: " . $e->getMessage();
}
?>
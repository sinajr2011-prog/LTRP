<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$host = 'localhost';
$dbname = 'dhiizqnl_qoqnoos_new';
$username = 'dhiizqnl_user_new';
$password = '12345678';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);
$response = ['success' => false, 'error' => 'Unknown action'];

// ============================================================
// ۱. لاگین
// ============================================================
if ($action === 'login') {
    if (!isset($input['identifier'], $input['password'], $input['role'])) {
        $response['error'] = 'Missing fields';
        echo json_encode($response);
        exit;
    }
    
    $identifier = $input['identifier'];
    $password = $input['password'];
    $role = $input['role'];
    $schoolCode = $input['school_code'] ?? '';
    
    if ($role === 'student') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE national_code = ? AND role = 'student' AND school_code = ?");
        $stmt->execute([$identifier, $schoolCode]);
    } elseif ($role === 'teacher') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE national_code = ? AND role = 'teacher' AND school_code = ?");
        $stmt->execute([$identifier, $schoolCode]);
    } elseif ($role === 'developer') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE (national_code = ? OR email = ?) AND role = 'developer'");
        $stmt->execute([$identifier, $identifier]);
    } else {
        $response['error'] = 'Invalid role';
        echo json_encode($response);
        exit;
    }
    
    $user = $stmt->fetch();
    
    if ($user) {
        if ($password === $user['password']) {
            unset($user['password']);
            $response = ['success' => true, 'user' => $user];
        } else {
            $response['error'] = 'اطلاعات وارد شده صحیح نیست';
        }
    } else {
        $response['error'] = 'اطلاعات وارد شده صحیح نیست';
    }
    
    echo json_encode($response);
    exit;
}

// ============================================================
// ۲. دریافت کاربران
// ============================================================
if ($action === 'get_users') {
    $schoolCode = $input['school_code'] ?? '';
    $role = $input['role'] ?? '';
    $query = "SELECT id, national_code, full_name, role, email, phone, class_level, subject, teacher_code, school_code, status, created_at FROM users WHERE 1=1";
    $params = [];
    if ($schoolCode) {
        $query .= " AND school_code = ?";
        $params[] = $schoolCode;
    }
    if ($role) {
        $query .= " AND role = ?";
        $params[] = $role;
    }
    $query .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $response = ['success' => true, 'users' => $stmt->fetchAll()];
    echo json_encode($response);
    exit;
}

// ============================================================
// ۳. دریافت مدارس
// ============================================================
if ($action === 'get_schools') {
    $stmt = $pdo->query("SELECT * FROM schools ORDER BY created_at DESC");
    $response = ['success' => true, 'schools' => $stmt->fetchAll()];
    echo json_encode($response);
    exit;
}

// ============================================================
// ۴. دریافت آمار
// ============================================================
if ($action === 'get_stats') {
    $schoolCode = $input['school_code'] ?? '';
    $stats = [];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE school_code = ? AND role = 'student'");
    $stmt->execute([$schoolCode]);
    $stats['students'] = (int)$stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE school_code = ? AND role = 'teacher'");
    $stmt->execute([$schoolCode]);
    $stats['teachers'] = (int)$stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE school_code = ? AND role = 'admin'");
    $stmt->execute([$schoolCode]);
    $stats['admins'] = (int)$stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE school_code = ? AND role = 'developer'");
    $stmt->execute([$schoolCode]);
    $stats['developers'] = (int)$stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM exams WHERE school_code = ?");
    $stmt->execute([$schoolCode]);
    $stats['exams'] = (int)$stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM exam_results WHERE school_code = ?");
    $stmt->execute([$schoolCode]);
    $stats['results'] = (int)$stmt->fetch()['total'];
    
    $response = ['success' => true, 'stats' => $stats];
    echo json_encode($response);
    exit;
}

// ============================================================
// ۵. ایجاد کاربر
// ============================================================
if ($action === 'create_user') {
    if (!isset($input['national_code'], $input['full_name'], $input['role'], $input['school_code'])) {
        $response['error'] = 'Missing fields';
        echo json_encode($response);
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO users (national_code, full_name, role, password, email, phone, class_level, subject, teacher_code, school_code) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $input['national_code'],
        $input['full_name'],
        $input['role'],
        $input['password'] ?? '123456',
        $input['email'] ?? null,
        $input['phone'] ?? null,
        $input['class_level'] ?? null,
        $input['subject'] ?? null,
        $input['teacher_code'] ?? null,
        $input['school_code']
    ]);
    $response = ['success' => true, 'message' => 'کاربر با موفقیت ایجاد شد'];
    echo json_encode($response);
    exit;
}

// ============================================================
// ۶. ایجاد مدرسه
// ============================================================
if ($action === 'create_school') {
    if (!isset($input['school_code'], $input['school_name'])) {
        $response['error'] = 'Missing fields';
        echo json_encode($response);
        exit;
    }
    $stmt = $pdo->prepare("INSERT INTO schools (school_code, school_name, city, phone, admin_password) 
                          VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $input['school_code'],
        $input['school_name'],
        $input['city'] ?? null,
        $input['phone'] ?? null,
        $input['admin_password'] ?? 'admin123'
    ]);
    $response = ['success' => true, 'message' => 'مدرسه با موفقیت ایجاد شد'];
    echo json_encode($response);
    exit;
}

// ============================================================
// ۷. ایجاد آزمون
// ============================================================
if ($action === 'create_exam') {
    if (!isset($input['title'], $input['teacher_id'], $input['school_code'])) {
        $response['error'] = 'Missing fields';
        echo json_encode($response);
        exit;
    }
    
    $examId = 'exam_' . uniqid();
    $stmt = $pdo->prepare("INSERT INTO exams (id, exam_id, teacher_id, title, subject, class_level, duration, end_time, questions, school_code) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $examId,
        $examId,
        $input['teacher_id'],
        $input['title'],
        $input['subject'] ?? 'عمومی',
        $input['class_level'] ?? '7',
        $input['duration'] ?? 30,
        $input['end_time'] ?? date('Y-m-d H:i:s', strtotime('+7 days')),
        json_encode($input['questions'] ?? []),
        $input['school_code']
    ]);
    $response = ['success' => true, 'exam_id' => $examId];
    echo json_encode($response);
    exit;
}

// ============================================================
// ۸. دریافت آزمون‌ها
// ============================================================
if ($action === 'get_exams') {
    $schoolCode = $input['school_code'] ?? '';
    $query = "SELECT * FROM exams WHERE 1=1";
    $params = [];
    if ($schoolCode) {
        $query .= " AND school_code = ?";
        $params[] = $schoolCode;
    }
    $query .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $exams = $stmt->fetchAll();
    foreach ($exams as &$e) {
        $e['questions'] = json_decode($e['questions'], true);
    }
    $response = ['success' => true, 'exams' => $exams];
    echo json_encode($response);
    exit;
}

// ============================================================
// ۹. ثبت نتیجه
// ============================================================
if ($action === 'submit_result') {
    if (!isset($input['exam_id'], $input['student_national_code'], $input['answers'])) {
        $response['error'] = 'Missing fields';
        echo json_encode($response);
        exit;
    }
    $resultId = 'result_' . uniqid();
    $stmt = $pdo->prepare("INSERT INTO exam_results (id, exam_id, exam_title, student_id, student_national_code, student_name, score, total_score, percentage, answers, school_code) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
                          ON DUPLICATE KEY UPDATE score=?, percentage=?, answers=?");
    $stmt->execute([
        $resultId,
        $input['exam_id'],
        $input['exam_title'],
        $input['student_id'],
        $input['student_national_code'],
        $input['student_name'],
        $input['score'],
        $input['total_score'],
        $input['percentage'],
        json_encode($input['answers']),
        $input['school_code'] ?? 'ghoghnos',
        $input['score'],
        $input['percentage'],
        json_encode($input['answers'])
    ]);
    $response = ['success' => true, 'message' => 'نتیجه با موفقیت ثبت شد'];
    echo json_encode($response);
    exit;
}

// ============================================================
// ۱۰. دریافت نتایج
// ============================================================
if ($action === 'get_results') {
    $examId = $input['exam_id'] ?? '';
    $studentCode = $input['student_national_code'] ?? '';
    $query = "SELECT * FROM exam_results WHERE 1=1";
    $params = [];
    if ($examId) {
        $query .= " AND exam_id = ?";
        $params[] = $examId;
    }
    if ($studentCode) {
        $query .= " AND student_national_code = ?";
        $params[] = $studentCode;
    }
    $query .= " ORDER BY submitted_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
    foreach ($results as &$r) {
        $r['answers'] = json_decode($r['answers'], true);
    }
    $response = ['success' => true, 'results' => $results];
    echo json_encode($response);
    exit;
}

// ============================================================
// ۱۱. هوش مصنوعی
// ============================================================
if ($action === 'ai_ask') {
    if (!isset($input['question'])) {
        $response['error'] = 'Missing question';
        echo json_encode($response);
        exit;
    }
    
    $question = $input['question'];
    $role = $input['role'] ?? 'student';
    
    $AI_API_KEY = 'sk-ovzOyVmvlCXVg8EV6Low6Rm6LD2ikymwcBBbGf29WuIdYiFv';
    $AI_API_URL = 'https://api.gapgpt.app/v1/chat/completions';
    $AI_MODEL = 'gpt-4o-mini';
    
    $systemPrompt = "شما یک دستیار هوشمند در سیستم آموزشی هستید. پاسخ‌ها به فارسی باشد.";
    if ($role === 'teacher') {
        $systemPrompt = "شما یک معلم حرفه‌ای هستید. پاسخ‌ها با لحن آموزشی و مثال‌های عملی باشد.";
    } elseif ($role === 'student') {
        $systemPrompt = "شما یک معلم خصوصی هستید. پاسخ‌ها ساده، روان و با مثال‌های جذاب باشد.";
    } elseif ($role === 'developer') {
        $systemPrompt = "شما یک توسعه‌دهنده ارشد هستید. پاسخ‌ها فنی و با راهکارهای عملی باشد.";
    }
    
    $data = [
        'model' => $AI_MODEL,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $question]
        ],
        'temperature' => 0.7,
        'max_tokens' => 2000
    ];
    
    $ch = curl_init($AI_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $AI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $aiResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200 && $aiResponse) {
        $result = json_decode($aiResponse, true);
        $answer = $result['choices'][0]['message']['content'] ?? 'پاسخ دریافت نشد';
        $response = ['success' => true, 'answer' => $answer];
    } else {
        $response = ['success' => true, 'answer' => '🤖 هوش مصنوعی: در حال حاضر پاسخ آفلاین است. لطفاً بعداً تلاش کنید.'];
    }
    
    echo json_encode($response);
    exit;
}

// ============================================================
// ۱۲. ارسال پیام
// ============================================================
if ($action === 'send_message') {
    if (!isset($input['sender_id'], $input['content'], $input['school_code'])) {
        $response['error'] = 'Missing fields';
        echo json_encode($response);
        exit;
    }
    
    $msgId = 'msg_' . uniqid();
    $senderType = $input['sender_type'] ?? 'developer';
    $receiverType = $input['receiver_type'] ?? 'all';
    
    $stmt = $pdo->prepare("INSERT INTO messages (id, sender_type, sender_id, sender_name, receiver_type, receiver_id, receiver_name, title, content, type, school_code) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $msgId,
        $senderType,
        $input['sender_id'],
        $input['sender_name'] ?? '',
        $receiverType,
        $input['receiver_id'] ?? null,
        $input['receiver_name'] ?? null,
        $input['title'] ?? 'پیام جدید',
        $input['content'],
        $input['type'] ?? 'general',
        $input['school_code']
    ]);
    $response = ['success' => true, 'message' => 'پیام با موفقیت ارسال شد'];
    echo json_encode($response);
    exit;
}

// ============================================================
// ۱۳. دریافت پیام‌ها
// ============================================================
if ($action === 'get_messages') {
    $schoolCode = $input['school_code'] ?? '';
    $receiverId = $input['receiver_id'] ?? '';
    
    $query = "SELECT * FROM messages WHERE 1=1";
    $params = [];
    
    if ($schoolCode && $schoolCode !== 'all') {
        $query .= " AND school_code = ?";
        $params[] = $schoolCode;
    }
    
    if ($receiverId) {
        $query .= " AND (receiver_id = ? OR receiver_type = 'all')";
        $params[] = $receiverId;
    }
    $query .= " ORDER BY created_at DESC LIMIT 50";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $response = ['success' => true, 'messages' => $stmt->fetchAll()];
    echo json_encode($response);
    exit;
}

// ============================================================
// پاسخ پیش‌فرض
// ============================================================
$response['error'] = 'Action not found';
echo json_encode($response);
// ============================================================
// ۷.۵ دریافت یک آزمون (get_exam)
// ============================================================
if ($action === 'get_exam') {
    $examId = $input['exam_id'] ?? '';
    if (!$examId) {
        $response['error'] = 'Missing exam_id';
        echo json_encode($response);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ? OR exam_id = ?");
    $stmt->execute([$examId, $examId]);
    $exam = $stmt->fetch();
    
    if ($exam) {
        $exam['questions'] = json_decode($exam['questions'], true);
        $response = ['success' => true, 'exam' => $exam];
    } else {
        $response['error'] = 'Exam not found';
    }
    echo json_encode($response);
    exit;
}
// ============================================================
// ۱۰.۵ دریافت تمام نتایج یک آزمون (get_all_results)
// ============================================================
if ($action === 'get_all_results') {
    $examId = $input['exam_id'] ?? '';
    if (!$examId) {
        $response['error'] = 'Missing exam_id';
        echo json_encode($response);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM exam_results WHERE exam_id = ? ORDER BY percentage DESC");
    $stmt->execute([$examId]);
    $results = $stmt->fetchAll();
    foreach ($results as &$r) {
        $r['answers'] = json_decode($r['answers'], true);
    }
    $response = ['success' => true, 'results' => $results];
    echo json_encode($response);
    exit;
}

<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$input = json_decode(file_get_contents('php://input'), true);
$question = $input['question'] ?? '';
$subject = $input['subject'] ?? 'general';
$grade = $input['grade'] ?? '7';
$role = $input['role'] ?? 'student';

$subjectNames = [
    'math' => 'ریاضی',
    'science' => 'علوم',
    'persian' => 'فارسی',
    'general' => 'عمومی'
];
$subjectName = $subjectNames[$subject] ?? 'عمومی';

// پرامپت اختصاصی هر نقش
if ($role === 'student') {
    $prompt = "تو یک معلم دلسوز برای دانش‌آموز پایه $grade هستی. درس: $subjectName. به سوال زیر به زبان فارسی ساده و روان پاسخ بده. با مثال و تشویق. سوال: $question";
} 
elseif ($role === 'teacher') {
    $prompt = "تو یک مشاور آموزشی حرفه‌ای برای معلم پایه $grade هستی. درس: $subjectName. به سوال زیر پاسخ تخصصی بده، روش تدریس بگو، منابع معرفی کن. سوال: $question";
} 
elseif ($role === 'developer') {
    $prompt = "تو یک تحلیلگر ارشد سیستم هستی. به سوال زیر پاسخ کاملاً فنی بده، معماری، امنیت، بهینه‌سازی و کد نمونه بده. سوال: $question";
} 
else {
    $prompt = $question;
}

$api_key = "sk-ovzOyVmvlCXVg8EV6Low6Rm6LD2ikymwcBBbGf29WuIdYiFv";
$api_url = "https://api.gapgpt.app/v1/chat/completions";

$payload = json_encode([
    "model" => "gpt-4o-mini",
    "messages" => [["role" => "user", "content" => $prompt]],
    "max_tokens" => 800
]);

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $api_key",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
$answer = $result['choices'][0]['message']['content'] ?? "خطا";

echo json_encode(["answer" => $answer]);
?>

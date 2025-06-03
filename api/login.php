<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

// Получаем входные данные (POST JSON)
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['sid'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing sid']);
    exit();
}

$passw = trim($data['sid']); // Можно вызвать вашу функцию FormChars(), если она нужна

// Поиск пользователя по паролю
$stmt = $CONNECT->prepare("SELECT id, wallet FROM members WHERE passw = ?");
$stmt->bind_param("s", $passw);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['status' => 'error', 'message' => 'Your SID is wrong. Please check this information.']);
    exit();
}

$user_id = (int)$row['id'];
$wallet = $row['wallet'];

// Генерируем токен
$token = bin2hex(random_bytes(32));

// Обновляем токен в базе
$update = $CONNECT->prepare("UPDATE members SET session_token = ? WHERE id = ?");
$update->bind_param("si", $token, $user_id);
if (!$update->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Error updating token']);
    exit();
}
$update->close();

// Возвращаем данные для мобильного приложения
echo json_encode([
    'status' => 'ok',
    'user_id' => $user_id,
    'wallet' => $wallet,
    'token' => $token
]);
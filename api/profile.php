<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

// Подключение к БД
$CONNECT = mysqli_connect(HOST, USER, PASS, DB);
if (!$CONNECT) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Обработка JSON тела запроса
$input = json_decode(file_get_contents("php://input"), true);
if (json_last_error() === JSON_ERROR_NONE && is_array($input)) {
    $_POST = array_merge($_POST, $input);
}

// Получение user_id и token
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : (isset($_GET['user_id']) ? intval($_GET['user_id']) : 0);
$token   = isset($_POST['token'])   ? $_POST['token']           : (isset($_GET['token'])   ? $_GET['token']   : '');

if (!$user_id || !$token) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Проверка токена
$stmt = $CONNECT->prepare("SELECT id, username, wallet, balance, passw, session_token FROM members WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// Сравнение токена
if ($user['session_token'] !== $token) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

// Отправка данных
echo json_encode([
    'success' => true,
    'data' => [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'wallet' => $user['wallet'],
        'balance' => (float)$user['balance'],
        'seed_phrase' => $user['passw'] // можно скрыть, если нужно
    ]
]);

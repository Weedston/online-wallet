<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config.php';


// Генерация сид-фразы (18 слов)
function generateSidPhrase($wordCount = 18) {
    $wordlist = file(__DIR__ . '/../wordlist.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$wordlist || count($wordlist) < $wordCount) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Wordlist not found or insufficient words']);
        exit();
    }
    shuffle($wordlist);
    return implode(' ', array_slice($wordlist, 0, $wordCount));
}

$sidPhrase = generateSidPhrase();

try {
    $newAddress = bitcoinRPC('getnewaddress');
    $privkey = bitcoinRPC('dumpprivkey', [$newAddress]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Bitcoin RPC error: ' . $e->getMessage()]);
    exit();
}

// Создаём пользователя в БД
$stmt = $CONNECT->prepare("INSERT INTO members (passw, wallet, privkey) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $sidPhrase, $newAddress, $privkey);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $stmt->error]);
    exit();
}
$stmt->close();

// Получаем созданного пользователя
$stmt = $CONNECT->prepare("SELECT id, wallet FROM members WHERE passw = ? LIMIT 1");
$stmt->bind_param("s", $sidPhrase);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row || !$row['id']) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Registration failed']);
    exit();
}

$user_id = $row['id'];
$wallet = $row['wallet'];
$token = bin2hex(random_bytes(32));

// Сохраняем токен в БД
$update = $CONNECT->prepare("UPDATE members SET session_token = ? WHERE id = ?");
$update->bind_param("si", $token, $user_id);
if (!$update->execute()) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error updating token']);
    exit();
}
$update->close();

// Можно также сохранить user_id, wallet, token в сессию, если нужно для сайта
$_SESSION['user_id'] = $user_id;
$_SESSION['wallet'] = $wallet;
$_SESSION['token'] = $token;

// Отправляем ответ приложению
echo json_encode([
    'status'     => 'ok',
    'sid_phrase' => $sidPhrase,
    'wallet'     => $wallet,
    'privkey'    => $privkey,
    'user_id'    => $user_id,
    'token'      => $token
]);
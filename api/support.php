<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'error' => null];

// Параметры запроса
$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$user_id = intval($_GET['user_id'] ?? $_POST['user_id'] ?? 0);
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

// Проверка токена
$stmt = $CONNECT->prepare("SELECT session_token FROM members WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($storedToken);
$stmt->fetch();
$stmt->close();

if ($token !== $storedToken) {
    $response['error'] = "Invalid token";
    echo json_encode($response);
    exit;
}

if ($action === 'send' && $method === 'POST') {
    $message = trim($_POST['message'] ?? '');
    


    if (!empty($message)) {
        $stmt = $CONNECT->prepare("INSERT INTO support_requests (user_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $message);
        $stmt->execute();
        $response['success'] = true;
    } else {
        $response['error'] = "Message cannot be empty";
    }

    echo json_encode($response);
    exit;
}

if ($action === 'get' && $method === 'GET') {
    $stmt = $CONNECT->prepare("SELECT message, response, created_at FROM support_requests WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'message' => $row['message'],
            'response' => $row['response'],
            'created_at' => $row['created_at']
        ];
    }

    $response['success'] = true;
    $response['messages'] = $messages;

    echo json_encode($response);
    exit;
}

// Если action не поддерживается
$response['error'] = "Invalid action";
echo json_encode($response);
exit;

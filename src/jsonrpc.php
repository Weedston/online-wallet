<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config.php';

header('Content-Type: application/json');

// Подключение к базе данных
$CONNECT = mysqli_connect(HOST, USER, PASS, DB);
if (!$CONNECT) {
    echo json_encode(['error' => 'Failed to connect to database']);
    exit();
}

$request = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'Invalid JSON']);
    exit();
}

$method = $request['method'] ?? '';
$params = $request['params'] ?? [];
$user_id = intval($params['user_id'] ?? 0);

if ($method === 'getUnreadNotificationsCount' && $user_id > 0) {
    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = '$user_id' AND is_read = 0";
    $result = mysqli_query($CONNECT, $query);
    if ($result) {
        $count = mysqli_fetch_assoc($result)['count'];
        echo json_encode(['result' => ['unread_count' => $count]]);
    } else {
        echo json_encode(['error' => 'Query failed: ' . mysqli_error($CONNECT)]);
    }
} else {
    echo json_encode(['error' => 'Invalid method or missing parameters']);
}
?>
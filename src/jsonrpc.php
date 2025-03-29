<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config.php';

$request = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'Invalid JSON']);
    exit();
}

$method = $request['method'] ?? '';
$params = $request['params'] ?? [];

switch ($method) {
    case 'getNotifications':
        getNotifications($params);
        break;
    case 'markNotificationsAsRead':
        markNotificationsAsRead($params);
        break;
    default:
        echo json_encode(['error' => 'Unknown method']);
        break;
}

function getNotifications($params) {
    global $CONNECT;
    $user_id = $params['user_id'] ?? 0;
    $result = mysqli_query($CONNECT, "SELECT * FROM notifications WHERE user_id = '$user_id' AND is_read = 0 ORDER BY created_at DESC");
    $notifications = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }
    echo json_encode(['result' => ['notifications' => $notifications]]);
}

function markNotificationsAsRead($params) {
    global $CONNECT;
    $user_id = $params['user_id'] ?? 0;
    mysqli_query($CONNECT, "UPDATE notifications SET is_read = 1 WHERE user_id = '$user_id' AND is_read = 0");
    echo json_encode(['result' => 'success']);
}
?>
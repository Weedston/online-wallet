<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config.php';
require_once 'functions.php';

session_start();

$request = json_decode(file_get_contents('php://input'), true);



if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'Invalid JSON']);
    exit();
}

$method = $request['method'] ?? null;
$params = $request['params'] ?? [];
$user_id = intval($params['user_id'] ?? 0);


if (!$method) {
    echo json_encode(['error' => 'Missing parameters: method']);
    exit();
}

switch ($method) {
    case 'getUnreadNotificationCount':
        if ($user_id > 0) {
            $result = getUnreadNotificationCount($user_id);
            echo json_encode(['result' => ['count' => $result]]);
        } else {
            echo json_encode(['error' => 'Missing parameters: user_id']);
        }
        break;
    case 'getNotifications':
        if ($user_id > 0) {
            $result = getNotifications($user_id);
            echo json_encode(['result' => ['notifications' => $result]]);
        } else {
            echo json_encode(['error' => 'Missing parameters: user_id']);
        }
        break;
    case 'markNotificationsAsRead':
        if ($user_id > 0) {
            $result = markNotificationsAsRead($user_id);
            echo json_encode(['result' => $result]);
        } else {
            echo json_encode(['error' => 'Missing parameters: user_id']);
        }
        break;
    default:
        echo json_encode(['error' => 'Unknown method']);
}

function getUnreadNotificationCount($user_id) {
    global $CONNECT;
    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = '$user_id[user_id]' AND is_read = 0";
    $result = mysqli_query($CONNECT, $query);
    $row = mysqli_fetch_assoc($result);

    if ($row === null) {
        return 0; // Возвращаем 0, если нет результатов
    }

    return $row['count'];
}

function getNotifications($user_id) {
    global $CONNECT;

    $query = "SELECT * FROM notifications WHERE user_id = '$user_id[user_id]' AND is_read = 0 ORDER BY created_at DESC";
    $result = mysqli_query($CONNECT, $query);
    $notifications = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }

    return $notifications;
}

function markNotificationsAsRead($user_id) {
    global $CONNECT;

    $query = "UPDATE notifications SET is_read = 1 WHERE user_id = '$user_id[user_id]' AND is_read = 0";
    $result = mysqli_query($CONNECT, $query);

    return $result ? true : false;
}
?>
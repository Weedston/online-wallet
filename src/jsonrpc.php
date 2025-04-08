<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config.php';
require_once 'functions.php';

//$user_id = $_SESSION['user_id'];
error_log("___--jsonrpc.php. user_id = SESSION['user_id']--____ user_id: " . print_r($user_id, true));

$request = json_decode(file_get_contents('php://input'), true);

error_log("RAW JSON: " . file_get_contents('php://input')); // Логируем входящий JSON
error_log("РАЗОБРАННЫЙ JSON: " . print_r($request, true));  // Логируем массив после json_decode()

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("Ошибка JSON: " . json_last_error_msg());
    echo json_encode(['error' => 'Invalid JSON']);
    exit();
}

$method = $request['method'] ?? null;
$params = $request['params'] ?? [];
$user_id = intval($params['user_id'] ?? 0);

error_log("ПАРАМЕТРЫ: method=$method, user_id=$user_id");

if (!$method) {
    echo json_encode(['error' => 'Missing parameters: method']);
    exit();
}

switch ($method) {
    case 'getUnreadNotificationCount':
        if (is_array($user_id) && isset($user_id['user_id'])) {
            $user_id = intval($user_id['user_id']);
        }
        if ($user_id > 0) {
            $result = getUnreadNotificationCount($user_id);
            error_log("Unread notification count: " . print_r($result, true)); // Логируем результат
            echo json_encode(['result' => ['count' => $result]]);
        } else {
            echo json_encode(['error' => 'Missing parameters: user_id']);
        }
        break;
    case 'getNotifications':
        if (is_array($user_id) && isset($user_id['user_id'])) {
            $user_id = intval($user_id['user_id']);
        }
        if ($user_id > 0) {
            $result = getNotifications($user_id);
            error_log("Notifications: " . print_r($result, true)); // Логируем результат
            echo json_encode(['result' => ['notifications' => $result]]);
        } else {
            echo json_encode(['error' => 'Missing parameters: user_id']);
        }
        break;
    case 'markNotificationsAsRead':
        if (is_array($user_id) && isset($user_id['user_id'])) {
            $user_id = intval($user_id['user_id']);
        }
        if ($user_id > 0) {
            $result = markNotificationsAsRead($user_id);
            error_log("Mark notifications as read result: " . json_encode($result)); // Логируем результат
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
    error_log("___--getUnreadNotificationCount--____1 user_id: " . print_r($user_id, true));
    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = '$user_id' AND is_read = 0";
    $result = mysqli_query($CONNECT, $query);
    $row = mysqli_fetch_assoc($result);

    if ($row === null) {
        return 0; // Возвращаем 0, если нет результатов
    }

    return $row['count'];
}

function getNotifications($user_id) {
    global $CONNECT;
    error_log("___--getNotifications--____2 user_id: " . print_r($user_id, true));

    $query = "SELECT * FROM notifications WHERE user_id = '$user_id' ORDER BY created_at DESC";
    $result = mysqli_query($CONNECT, $query);
    $notifications = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }

    return $notifications;
}

function markNotificationsAsRead($user_id) {
    global $CONNECT;
    error_log("___--markNotificationsAsRead--____3 user_id: " . print_r($user_id, true));

    $query = "UPDATE notifications SET is_read = 1 WHERE user_id = '$user_id' AND is_read = 0";
    $result = mysqli_query($CONNECT, $query);

    return $result ? true : false;
}
?>
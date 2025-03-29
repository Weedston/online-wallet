<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once '../config.php';

$request = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'Invalid JSON']);
    exit();
}

$method = $request['method'] ?? '';
$params = $request['params'] ?? [];
$notification_user_id = intval($params['user_id'] ?? 0);

if ($method === 'getUnreadNotificationsCount' && $notification_user_id > 0) {
    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = '$notification_user_id' AND is_read = 0";
    $result = mysqli_query($CONNECT, $query);
    if ($result) {
        $count = mysqli_fetch_assoc($result)['count'];
        echo json_encode(['result' => ['unread_count' => $count]]);
    } else {
        echo json_encode(['error' => 'Query failed: ' . mysqli_error($CONNECT)]);
    }
} elseif ($method === 'getNotifications' && $notification_user_id > 0) {
    $query = "SELECT message FROM notifications WHERE user_id = '$notification_user_id' ORDER BY created_at DESC";
    $result = mysqli_query($CONNECT, $query);
    if ($result) {
        $notifications = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $notifications[] = $row;
        }
        echo json_encode(['result' => ['notifications' => $notifications]]);
    } else {
        echo json_encode(['error' => 'Query failed: ' . mysqli_error($CONNECT)]);
    }
} else {
    echo json_encode(['error' => 'Invalid method or missing parameters']);
}
?>
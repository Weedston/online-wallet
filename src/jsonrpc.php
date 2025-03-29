<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$CONNECT = mysqli_connect(HOST, USER, PASS, DB);

$request = json_decode(file_get_contents('php://input'), true);

if ($request['method'] == 'getUnreadNotificationsCount') {
    $user_id = intval($request['params']['user_id']);
    $unread_notifications_result = mysqli_query($CONNECT, "SELECT COUNT(*) as count FROM notifications WHERE user_id = '$user_id' AND is_read = 0");
    $unread_notifications = mysqli_fetch_assoc($unread_notifications_result)['count'];

    $response = [
        'jsonrpc' => '2.0',
        'result' => ['unread_count' => $unread_notifications],
        'id' => $request['id']
    ];
    echo json_encode($response);
} else {
    echo json_encode([
        'jsonrpc' => '2.0',
        'error' => ['code' => -32601, 'message' => 'Method not found'],
        'id' => $request['id']
    ]);
}
?>
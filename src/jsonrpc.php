<?php
require_once '../config.php';
require_once 'functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$request = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'jsonrpc' => '2.0',
        'error' => [
            'code' => -32700,
            'message' => 'Invalid JSON'
        ],
        'id' => null
    ]);
    exit();
}

$method = $request['method'];
$params = $request['params'];
$id = $request['id'];

$response = [
    'jsonrpc' => '2.0',
    'id' => $id
];

switch ($method) {
    case 'getNotifications':
        $user_id = intval($params['user_id']);
        $result = mysqli_query($CONNECT, "SELECT * FROM notifications WHERE user_id = '$user_id' AND is_read = 0 ORDER BY created_at DESC");
        
        $notifications = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $notifications[] = $row;
        }
        
        $response['result'] = ['notifications' => $notifications];
        break;
        
    case 'markNotificationsAsRead':
        $user_id = intval($params['user_id']);
        mysqli_query($CONNECT, "UPDATE notifications SET is_read = 1 WHERE user_id = '$user_id'");
        
        $response['result'] = ['success' => true];
        break;
        
    default:
        $response['error'] = [
            'code' => -32601,
            'message' => 'Method not found'
        ];
        break;
}

echo json_encode($response);
?>
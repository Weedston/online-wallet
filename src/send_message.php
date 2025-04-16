<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once '../config.php';
require_once 'functions.php'; // Подключаем файл с функцией add_notification



$request = json_decode(file_get_contents('php://input'), true);


if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'Invalid JSON']);
    exit();
}

$ad_id = intval($request['params']['ad_id'] ?? 0);
$sender_id = intval($request['params']['sender_id'] ?? 0);
$recipient_id = intval($request['params']['recipient_id'] ?? 0);
$message = trim($request['params']['message'] ?? '');
$method = $request['method'] ?? '';


if ($method === 'send_message' && $ad_id > 0 && $sender_id > 0 && $recipient_id > 0 && !empty($message)) {
    $message = mysqli_real_escape_string($CONNECT, $message);
    $query = "INSERT INTO messages (ad_id, user_id, recipient_id, message, created_at) VALUES ('$ad_id', '$sender_id', '$recipient_id', '$message', NOW())";
    $result = mysqli_query($CONNECT, $query);
    if ($result) {
        if (add_notification($recipient_id, "A new chat message based on the ad #$ad_id. Go to the <a href=\"p2p-trade_history\">Trade history</a> section to continue the transaction.")) {
         
        } else {
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Query failed: ' . mysqli_error($CONNECT)]);
    }
} elseif ($method === 'loadMessages' && $ad_id > 0) {
    $messages = mysqli_query($CONNECT, "SELECT * FROM messages WHERE ad_id = '$ad_id' ORDER BY created_at ASC");
    $response = [];
    while ($message = mysqli_fetch_assoc($messages)) {
        $username = ($message['user_id'] == $_SESSION['user_id']) ? 'You' : 'Not you';
        $response[] = ['username' => $username, 'message' => htmlspecialchars($message['message'])];
    }
    echo json_encode(['result' => $response]);
} else {
    echo json_encode(['error' => 'Missing parameters']);
}
?>
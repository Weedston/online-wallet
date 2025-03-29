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

$sender_id = intval($request['sender_id'] ?? 0);
$recipient_id = intval($request['recipient_id'] ?? 0);
$message = $request['message'] ?? '';

if ($sender_id > 0 && $recipient_id > 0 && !empty($message)) {
    $query = "INSERT INTO messages (user_id, recipient_id, message, created_at) VALUES ('$sender_id', '$recipient_id', '$message', NOW())";
    $result = mysqli_query($CONNECT, $query);
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Query failed: ' . mysqli_error($CONNECT)]);
    }
} else {
    echo json_encode(['error' => 'Missing parameters']);
}
?>
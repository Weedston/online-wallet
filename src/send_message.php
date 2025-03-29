<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once '../config.php';

$request = json_decode(file_get_contents('php://input'), true);

error_log("RAW JSON: " . file_get_contents('php://input')); // Логируем входящий JSON
error_log("РАЗОБРАННЫЙ JSON: " . print_r($request, true));  // Логируем массив после json_decode()

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("Ошибка JSON: " . json_last_error_msg());
    echo json_encode(['error' => 'Invalid JSON']);
    exit();
}

$ad_id = intval($request['ad_id'] ?? 0);
$sender_id = intval($request['sender_id'] ?? 0);
$recipient_id = intval($request['recipient_id'] ?? 0);
$message = trim($request['message'] ?? '');

error_log("ПАРАМЕТРЫ: ad_id=$ad_id, sender_id=$sender_id, recipient_id=$recipient_id, message=$message");

if ($ad_id > 0 && $sender_id > 0 && $recipient_id > 0 && !empty($message)) {
    $message = mysqli_real_escape_string($CONNECT, $message);
    $query = "INSERT INTO messages (ad_id, user_id, recipient_id, message, created_at) VALUES ('$ad_id', '$sender_id', '$recipient_id', '$message', NOW())";
    $result = mysqli_query($CONNECT, $query);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        error_log("Ошибка SQL: " . mysqli_error($CONNECT));
        echo json_encode(['error' => 'Query failed: ' . mysqli_error($CONNECT)]);
    }
} else {
    error_log("Ошибка: Не хватает параметров! ad_id=$ad_id, sender_id=$sender_id, recipient_id=$recipient_id, message='$message'");
    echo json_encode(['error' => 'Missing parameters']);
}
?>
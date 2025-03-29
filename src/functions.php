<?php
require_once __DIR__ . '/../config.php';

function add_notification($user_id, $message) {
    global $CONNECT; // Declare global variable

    // Добавление логов для проверки параметров
    error_log("add_notification called with user_id: $user_id, message: $message");
    
    if (!$CONNECT) {
        error_log("Error: Database connection is missing.");
        return false;
    }

    // Проверка параметров
    if (empty($user_id) || empty($message)) {
        error_log("Error: user_id or message is empty.");
        return false;
    }

    $stmt = $CONNECT->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    
    if (!$stmt) {
        error_log("Error preparing statement: " . $CONNECT->error);
        return false;
    }

    $stmt->bind_param("is", $user_id, $message);
    
    if (!$stmt->execute()) {
        error_log("Error executing statement: " . $stmt->error);
        $stmt->close();
        return false;
    }

    $stmt->close();
    error_log("add_notification executed successfully for user_id: $user_id, message: $message");
    return true; // Successful execution
}
?>
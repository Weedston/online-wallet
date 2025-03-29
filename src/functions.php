<?php
function add_notification($recipient_id, $message) {
    global $CONNECT; // Используем глобальное подключение к базе данных

    $stmt = $CONNECT->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->bind_param("is", $recipient_id, $message);

    if (!$stmt->execute()) {
        error_log("Ошибка при добавлении уведомления: " . $stmt->error);
    }
}
?>
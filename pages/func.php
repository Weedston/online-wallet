<?php
// Пример функции для создания объявления
function createAd($userId, $amountBtc, $rate, $paymentMethod) {
    global $db; // подключение к базе данных

    // Создаем запрос на добавление объявления
    $stmt = $db->prepare("INSERT INTO ads (user_id, amount_btc, rate, payment_method) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idss", $userId, $amountBtc, $rate, $paymentMethod);

    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
}

// Пример функции для обновления статуса объявления
function updateAdStatus($adId, $status) {
    global $db; // подключение к базе данных

    // Создаем запрос на обновление статуса
    $stmt = $db->prepare("UPDATE ads SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $adId);

    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
}

// Пример функции для логирования действий администратора
function logAdminAction($adminId, $action) {
    global $db; // подключение к базе данных

    // Создаем запрос на добавление логов
    $stmt = $db->prepare("INSERT INTO logs (admin_id, action) VALUES (?, ?)");
    $stmt->bind_param("is", $adminId, $action);

    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
}



?>
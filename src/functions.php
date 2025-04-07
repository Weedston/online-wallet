<?php
require_once __DIR__ . '/../config.php';

function add_notification($user_id, $message) {
    global $CONNECT;

    error_log("add_notification called with user_id: $user_id, message: $message");

    if (!$CONNECT) {
        error_log("Error: Database connection is missing.");
        return false;
    }

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

    if ($stmt->affected_rows === 0) {
        error_log("Error: No rows affected.");
        $stmt->close();
        return false;
    }

    $stmt->close();
    error_log("add_notification executed successfully for user_id: $user_id, message: $message");
    return true;
}

function get_ad_info($ad_id) {
    global $CONNECT;

    $stmt = $CONNECT->prepare("SELECT * FROM ads WHERE id = ?");
    $stmt->bind_param("i", $ad_id);
    $stmt->execute();
    $ad_result = $stmt->get_result();

    if (!$ad_result) {
        return ['error' => 'Query failed', 'mysqli_error' => mysqli_error($CONNECT)];
    }

    $ad = mysqli_fetch_assoc($ad_result);

    if (!$ad) {
        return ['error' => 'Ad not found', 'ad_id' => $ad_id];
    }

    return $ad;
}

function get_escrow_status($ad_id) {
    global $CONNECT;

    if (empty($ad_id)) {
        return ['error' => 'ad_id is missing'];
    }

    $stmt = $CONNECT->prepare("SELECT status FROM escrow_deposits WHERE ad_id = ?");
    $stmt->bind_param("i", $ad_id);
    $stmt->execute();
    $escrow_result = $stmt->get_result();
    $escrow = mysqli_fetch_assoc($escrow_result);

    if (!$escrow) {
        return ['error' => 'Escrow not found', 'ad_id' => $ad_id];
    }

    return ['status' => $escrow['status']];
}

function send_message($ad_id, $user_id, $message) {
    global $CONNECT;

    $stmt = $CONNECT->prepare("INSERT INTO messages (ad_id, user_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $ad_id, $user_id, htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    if ($stmt->execute()) {
        $recipient_id = get_recipient_id($ad_id, $user_id);
        add_notification($recipient_id, "Новое сообщение в чате по объявлению #$ad_id");
        return ['result' => 'Message sent successfully'];
    } else {
        return ['error' => 'Error: ' . mysqli_error($CONNECT)];
    }
}

function load_messages($ad_id) {
    global $CONNECT;

    $messages = mysqli_query($CONNECT, "SELECT * FROM messages WHERE ad_id = '$ad_id' ORDER BY created_at ASC");
    $response = [];
    while ($message = mysqli_fetch_assoc($messages)) {
        $username = ($message['user_id'] == $_SESSION['user_id']) ? 'You' : 'Not you';
        $response[] = ['username' => $username, 'message' => htmlspecialchars($message['message'])];
    }
    return ['result' => $response];
}

function get_recipient_id($ad_id, $sender_id) {
    global $CONNECT;

    $stmt = $CONNECT->prepare("SELECT user_id, buyer_id FROM ads WHERE id = ?");
    $stmt->bind_param("i", $ad_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ad = mysqli_fetch_assoc($result);

    return ($sender_id == $ad['user_id']) ? $ad['buyer_id'] : $ad['user_id'];
}

function confirmTrade($ad_id, $user_id) {
    global $CONNECT;

    // Проверка существования записи сделки
    $stmt = $CONNECT->prepare("SELECT * FROM escrow_deposits WHERE ad_id = ?");
    $stmt->bind_param("i", $ad_id);
    $stmt->execute();
    $escrow_result = $stmt->get_result();
    $escrow = mysqli_fetch_assoc($escrow_result);

    if (!$escrow) {
        return ['error' => 'Escrow not found', 'ad_id' => $ad_id];
    }

    // Обновление состояния подтверждения сделки
    if ($user_id == $escrow['buyer_id']) {
        $stmt = $CONNECT->prepare("UPDATE escrow_deposits SET buyer_confirmed = 1 WHERE ad_id = ?");
    } elseif ($user_id == $escrow['seller_id']) {
        $stmt = $CONNECT->prepare("UPDATE escrow_deposits SET seller_confirmed = 1 WHERE ad_id = ?");
    } elseif ($user_id == 182) { // Арбитр
        $stmt = $CONNECT->prepare("UPDATE escrow_deposits SET arbiter_confirmed = 1 WHERE ad_id = ?");
    } else {
        return ['error' => 'Invalid user'];
    }

    $stmt->bind_param("i", $ad_id);
    $stmt->execute();

    // Проверка, подтверждена ли сделка всеми участниками
    $stmt = $CONNECT->prepare("SELECT * FROM escrow_deposits WHERE ad_id = ?");
    $stmt->bind_param("i", $ad_id);
    $stmt->execute();
    $escrow_result = $stmt->get_result();
    $escrow = mysqli_fetch_assoc($escrow_result);

    if ($escrow['buyer_confirmed'] && $escrow['seller_confirmed']) {
        // Логика отправки BTC получателю
        // ...
        return ['result' => 'Payment confirmed and BTC sent to recipient'];
    }

    return ['result' => 'Payment confirmed'];
}

// Функция для отмены сделки
function cancelTrade($ad_id, $user_id) {
    global $CONNECT;

    // Проверка существования записи сделки
    $stmt = $CONNECT->prepare("SELECT * FROM escrow_deposits WHERE ad_id = ?");
    $stmt->bind_param("i", $ad_id);
    $stmt->execute();
    $escrow_result = $stmt->get_result();
    $escrow = mysqli_fetch_assoc($escrow_result);

    if (!$escrow) {
        return ['error' => 'Escrow not found', 'ad_id' => $ad_id];
    }

    // Обновление состояния отмены сделки
    if ($user_id == $escrow['buyer_id']) {
        $stmt = $CONNECT->prepare("UPDATE escrow_deposits SET buyer_cancelled = 1 WHERE ad_id = ?");
    } elseif ($user_id == $escrow['seller_id']) {
        $stmt = $CONNECT->prepare("UPDATE escrow_deposits SET seller_cancelled = 1 WHERE ad_id = ?");
    } elseif ($user_id == 182) { // Арбитр
        $stmt = $CONNECT->prepare("UPDATE escrow_deposits SET arbiter_cancelled = 1 WHERE ad_id = ?");
    } else {
        return ['error' => 'Invalid user'];
    }

    $stmt->bind_param("i", $ad_id);
    $stmt->execute();

    // Проверка, отменена ли сделка всеми участниками
    $stmt = $CONNECT->prepare("SELECT * FROM escrow_deposits WHERE ad_id = ?");
    $stmt->bind_param("i", $ad_id);
    $stmt->execute();
    $escrow_result = $stmt->get_result();
    $escrow = mysqli_fetch_assoc($escrow_result);

    if ($escrow['buyer_cancelled'] && $escrow['seller_cancelled']) {
        // Логика возврата BTC отправителю
        // ...
        return ['result' => 'Trade cancelled and BTC returned to sender'];
    }

    return ['result' => 'Trade cancelled'];
}
?>
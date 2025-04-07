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

    // Проверка количества затронутых строк
    if ($stmt->affected_rows === 0) {
        error_log("Error: No rows affected.");
        $stmt->close();
        return false;
    }

    $stmt->close();
    error_log("add_notification executed successfully for user_id: $user_id, message: $message");
    return true; // Successful execution
}

<?php
// Функция для подтверждения сделки
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
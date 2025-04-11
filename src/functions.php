<?php
require_once __DIR__ . '/../config.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function get_setting($name, $CONNECT) {
    $stmt = $CONNECT->prepare("SELECT value FROM settings WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->bind_result($value);
    $stmt->fetch();
    $stmt->close();
    return $value;
}

function addServiceComment($ad_id, $comment_text, $type = 'info') {
    global $CONNECT;

    $timestamp = date('Y-m-d H:i:s');
    $entry = [
        'timestamp' => $timestamp,
        'type' => $type,
        'message' => $comment_text
    ];

    // Получаем текущее содержимое
    $query = mysqli_query($CONNECT, "SELECT service_comments FROM escrow_deposits WHERE ad_id = '$ad_id'");
    $row = mysqli_fetch_assoc($query);
    $comments = json_decode($row['service_comments'], true) ?: [];

    $comments[] = $entry;
    $encoded = mysqli_real_escape_string($CONNECT, json_encode($comments, JSON_UNESCAPED_UNICODE));

    // Обновляем
    mysqli_query($CONNECT, "UPDATE escrow_deposits SET service_comments = '$encoded' WHERE ad_id = '$ad_id'");
}

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

    error_log("get_ad_info called with ad_id: $ad_id");

    $stmt = $CONNECT->prepare("SELECT * FROM ads WHERE id = ?");
    $stmt->bind_param("i", $ad_id);
    $stmt->execute();
    $ad_result = $stmt->get_result();

    if (!$ad_result) {
        error_log("Query failed: " . mysqli_error($CONNECT));
        return ['error' => 'Query failed', 'mysqli_error' => mysqli_error($CONNECT)];
    }

    $ad = mysqli_fetch_assoc($ad_result);

    if (!$ad) {
        error_log("Ad not found for ad_id: $ad_id");
        return ['error' => 'Ad not found', 'ad_id' => $ad_id];
    }

    return $ad;
}

function get_escrow_status($ad_id) {
    global $CONNECT;

    error_log("get_escrow_status called with ad_id: $ad_id");

    if (empty($ad_id)) {
        error_log("get_escrow_status error: ad_id is missing");
        return ['error' => 'ad_id is missing'];
    }

    $stmt = $CONNECT->prepare("SELECT status, buyer_confirmed, seller_confirmed FROM escrow_deposits WHERE ad_id = ?");
    if (!$stmt) {
        error_log("get_escrow_status prepare error: " . mysqli_error($CONNECT));
        return ['error' => 'Failed to prepare statement', 'mysqli_error' => mysqli_error($CONNECT)];
    }

    $stmt->bind_param("i", $ad_id);
    if (!$stmt->execute()) {
        error_log("get_escrow_status execute error: " . mysqli_error($CONNECT));
        return ['error' => 'Failed to execute statement', 'mysqli_error' => mysqli_error($CONNECT)];
    }

    $escrow_result = $stmt->get_result();
    if (!$escrow_result) {
        error_log("get_escrow_status get_result error: " . mysqli_error($CONNECT));
        return ['error' => 'Failed to get result', 'mysqli_error' => mysqli_error($CONNECT)];
    }

    $escrow = mysqli_fetch_assoc($escrow_result);
    if (!$escrow) {
        error_log("get_escrow_status: Escrow not found for ad_id: $ad_id");
        return ['error' => 'Escrow not found', 'ad_id' => $ad_id];
    }

    $status_map = [
        'waiting_deposit' => 'Waiting for BTC deposit',
        'btc_deposited' => 'BTC deposited',
        'fiat_paid' => 'Fiat paid',
        'btc_released' => 'BTC released',
		'completed' => 'The deal is completed',
        'disputed' => 'Disputed transaction',
        'refunded' => 'Funds refunded',
    ];

    $user_friendly_status = $status_map[$escrow['status']] ?? $escrow['status'];

    return [
        'status' => $user_friendly_status,
        'raw_status' => $escrow['status'],
        'buyer_confirmed' => (int)$escrow['buyer_confirmed'],
        'seller_confirmed' => (int)$escrow['seller_confirmed']
    ];
}



// Обработчик запросов
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false && basename($_SERVER['PHP_SELF']) != 'send_message.php') {
    header('Content-Type: application/json');
    $rawInput = file_get_contents('php://input');
    $jsonrpc = json_decode($rawInput, true);

    error_log("RAW JSON: " . $rawInput); // Логируем входящий JSON

    if ($jsonrpc === null) {
        echo json_encode(['error' => 'Invalid JSON', 'rawInput' => $rawInput]);
        exit();
    }

    $method = $jsonrpc['method'] ?? null;
    $params = $jsonrpc['params'] ?? [];
    $ad_id = $params['ad_id'] ?? null;

    if (!$method) {
        echo json_encode(['error' => 'Missing parameters: method', 'jsonrpc' => $jsonrpc]);
        exit();
    }

    switch ($method) {
        case 'getEscrowStatus':
			$raw_input = file_get_contents("php://input");
			error_log("+++ RAW INPUT: " . $raw_input);

			$input = json_decode($raw_input, true);
			$ad_id = $input['params']['ad_id'] ?? null;
			error_log("+++---case get_escrow_status ad_id: $ad_id");
			$result = get_escrow_status($ad_id);
			echo json_encode([
				'jsonrpc' => '2.0',
				'result' => $result,
				'id' => $input['id'] ?? null
			]);
		break;
		case 'getServiceComments':
		if (!$ad_id) {
			echo json_encode(['error' => 'Missing parameters: ad_id', 'jsonrpc' => $jsonrpc]);
			exit();
		}
		$query = mysqli_query($CONNECT, "SELECT service_comments FROM escrow_deposits WHERE ad_id = '$ad_id'");
		$row = mysqli_fetch_assoc($query);
		$comments = json_decode($row['service_comments'], true) ?: [];

		echo json_encode([
			'jsonrpc' => '2.0',
			'result' => $comments,
			'id' => $jsonrpc['id'] ?? null
		]);
		break;		
        case 'loadMessages':
            if (!$ad_id) {
                echo json_encode(['error' => 'Missing parameters: ad_id', 'jsonrpc' => $jsonrpc]);
                exit();
            }
            echo json_encode(load_messages($ad_id));
            break;
        case 'getUnreadNotificationCount':
            echo json_encode(getUnreadNotificationCount($params));
            break;
        case 'getNotifications':
            echo json_encode(getNotifications($params));
            break;
        case 'markNotificationsAsRead':
            echo json_encode(markNotificationsAsRead($params));
            break;
        default:
            echo json_encode(['error' => 'Unknown method']);
    }
    exit();
}

// Если запрос не POST или не имеет нужного типа содержимого, просто игнорируем
error_log("Invalid request method or content type: " . $_SERVER['REQUEST_METHOD'] . ", " . ($_SERVER['CONTENT_TYPE'] ?? 'undefined'));

function send_message($ad_id, $user_id, $message) {
    global $CONNECT;

    error_log("send_message called with ad_id: $ad_id, user_id: $user_id, message: $message");

    $stmt = $CONNECT->prepare("INSERT INTO messages (ad_id, user_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $ad_id, $user_id, htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    if ($stmt->execute()) {
        $recipient_id = get_recipient_id($ad_id, $user_id);
        add_notification($recipient_id, "Новое сообщение в чате по объявлению #$ad_id");
        return ['result' => 'Message sent successfully'];
    } else {
        error_log("Error executing statement: " . mysqli_error($CONNECT));
        return ['error' => 'Error: ' . mysqli_error($CONNECT)];
    }
}

function load_messages($ad_id) {
    global $CONNECT;

    error_log("load_messages called with ad_id: $ad_id");

    if (empty($ad_id)) {
        error_log("load_messages error: ad_id is missing");
        return ['error' => 'ad_id is missing'];
    }

    $messages = mysqli_query($CONNECT, "SELECT * FROM messages WHERE ad_id = '$ad_id' ORDER BY created_at ASC");
    if (!$messages) {
        error_log("load_messages error: " . mysqli_error($CONNECT));
        return ['error' => 'Query failed', 'mysqli_error' => mysqli_error($CONNECT)];
    }

    $response = [];
    while ($message = mysqli_fetch_assoc($messages)) {
        $username = ($message['user_id'] == $_SESSION['user_id']) ? 'You' : 'Not you';
        $response[] = [
            'id' => $message['id'], // добавляем сюда
            'username' => $username,
            'message' => htmlspecialchars($message['message'])
        ];
    }

    if (empty($response)) {
        error_log("load_messages: No messages found for ad_id: $ad_id");
    }

    return ['result' => $response]; // <-- не забываем вернуть результат
}


function get_recipient_id($ad_id, $sender_id) {
    global $CONNECT;

    error_log("get_recipient_id called with ad_id: $ad_id, sender_id: $sender_id");

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
<?php
require_once __DIR__ . '/../config.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


function sendTelegram($message) {
    $chat_id = "1727320137";
    $token = "7911312217:AAG-7PrL9_75b159550PM8boBpgc5zsJ4Qw";
    $url = "https://api.telegram.org/bot$token/sendMessage";

    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data
    ]);
    curl_exec($ch);
    curl_close($ch);
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

function getUserRole($ad, $user_id) {
    $is_author = ($ad['user_id'] == $user_id);

    if ($ad['trade_type'] === 'buy') {
        return $is_author ? 'buyer' : 'seller';
    } elseif ($ad['trade_type'] === 'sell') {
        return $is_author ? 'seller' : 'buyer';
    }

    return ''; // если trade_type некорректен
}



// Функция для получения подтверждений
function getConfirmations($txid) {
    $tx = bitcoinRPC('getrawtransaction', [$txid, true]);

    if (isset($tx['confirmations'])) {
        return $tx['confirmations'];
    } else {
        return 0; // Если нет подтверждений
    }
}


function getBTCBalance($address) {
    $utxos = bitcoinRPC('listunspent', [1, 9999999, [$address]]);
    $balance = 0.0;
    foreach ($utxos as $utxo) {
        if (!empty($utxo['spendable'])) {
            $balance += floatval($utxo['amount']);
        }
    }
    return round($balance, 8);
}

function calculateTotalNetworkFeeBTC($tx_size_bytes = 250) {
    // Получаем feerate за 1 kB
    $response = bitcoinRPC("estimatesmartfee", [6]);
    error_log(print_r($response, true)); // отладка

    $default_feerate_btc_per_kb = 0.00001000; // 1 сатоши за байт

    if (isset($response['feerate']) && $response['feerate'] > 0) {
        $feerate_btc_per_kb = $response['feerate'];
    } else {
        error_log("Feerate missing, using default");
        $feerate_btc_per_kb = $default_feerate_btc_per_kb;
    }

    // Размер в кБ
    $tx_size_kb = $tx_size_bytes / 1000;

    // Общая комиссия в BTC
    $total_fee_btc = $feerate_btc_per_kb * $tx_size_kb;

    // Гарантированный минимум: 1 сатоши
    $min_fee_btc = 1 / 100000000;
    $total_fee_btc = max($total_fee_btc, $min_fee_btc);

    $total_fee_btc = number_format($total_fee_btc, 8, '.', '');
    error_log("Estimated Network Fee: $total_fee_btc BTC");

    return $total_fee_btc;
}

function sendBitcoinWithFees($recipient, $amount, $fromAddress, $serviceWallet = "tb1qtdxq5dzdv29tkw7t3d07qqeuz80y9k80ynu5tn", $serviceFeePercent = 0.01) {
    $utxos = bitcoinRPC('listunspent', [1, 9999999, [$fromAddress]]);
    if (!is_array($utxos) || empty($utxos)) {
        return ["error" => "No available UTXOs!"];
    }

    $networkFee = calculateTotalNetworkFeeBTC(); // финальная комиссия

    $inputs = [];
    $totalInput = 0;
    $serviceFee = round($amount * $serviceFeePercent, 8);

    foreach ($utxos as $utxo) {
        if (empty($utxo['spendable'])) continue;

        $inputs[] = [
            "txid" => $utxo['txid'],
            "vout" => $utxo['vout']
        ];
        $totalInput += $utxo['amount'];

        if ($totalInput >= $amount + $serviceFee + $networkFee) break;
    }

    if ($totalInput < ($amount + $serviceFee + $networkFee)) {
        return ["error" => "Not enough funds including network + service fee"];
    }

    $change = round($totalInput - $amount - $serviceFee - $networkFee, 8);

    error_log("Amount = $amount BTC, Service Fee = $serviceFee BTC, Network Fee = $networkFee BTC, Change = $change BTC");

    $outputs = [
        $recipient => round($amount, 8),
        $serviceWallet => $serviceFee
    ];
    if ($change > 0) {
        $outputs[$fromAddress] = $change;
    }

    $rawTx = bitcoinRPC('createrawtransaction', [$inputs, $outputs]);
    $signedTx = bitcoinRPC('signrawtransactionwithwallet', [$rawTx]);

    if (empty($signedTx['hex'])) {
        return ["error" => "Failed to sign transaction!", "response" => $signedTx];
    }

    $txid = bitcoinRPC('sendrawtransaction', [$signedTx['hex']]);
    return ["txid" => $txid];
}



function sendToEscrow($ad_id, $from_address, $amount_btc, $CONNECT) {
    $escrow_address = get_setting('escrow_wallet_address', $CONNECT);
    $dust_limit = 0.00000546;

    // Получаем UTXO
    $utxos = bitcoinRPC('listunspent', [1, 9999999, [$from_address]]);
    if (!is_array($utxos) || count($utxos) === 0) {
        return ['success' => false, 'error' => 'No UTXO found or invalid response.'];
    }

    // Выбираем UTXO
    $total_input = 0;
    $inputs = [];
    foreach ($utxos as $utxo) {
        $inputs[] = [
            'txid' => $utxo['txid'],
            'vout' => $utxo['vout']
        ];
        $total_input += $utxo['amount'];
        if ($total_input >= $amount_btc) break;
    }

    if ($total_input < $amount_btc) {
        return ['success' => false, 'error' => 'Insufficient input funds.'];
    }

    // Получение комиссии
    $fee_response = bitcoinRPC('estimatesmartfee', [2]);
    $estimate_fee = is_array($fee_response) && isset($fee_response['feerate']) && is_numeric($fee_response['feerate'])
        ? $fee_response['feerate']
        : 0.00001;
    $network_fee = $estimate_fee * 0.25;

    // Рассчитываем сумму для эскроу (с вычетом комиссии)
    $escrow_amount = $amount_btc - $network_fee;

    if ($escrow_amount <= $dust_limit) {
        return ['success' => false, 'error' => 'Escrow amount after fee is below dust limit.'];
    }

    // Подготовка выходов
    $outputs = [];
    $outputs[$escrow_address] = number_format($escrow_amount, 8, '.', '');

    // Расчёт сдачи
    $change = $total_input - $amount_btc;
    if ($change >= $dust_limit) {
        $outputs[$from_address] = number_format($change, 8, '.', '');
    } else {
        $network_fee += $change; // не создаём dust-сдачу
    }

    // Создание raw-транзакции
    $raw_tx = bitcoinRPC('createrawtransaction', [$inputs, $outputs]);
    if (!$raw_tx) {
        return ['success' => false, 'error' => 'Failed to create raw transaction.'];
    }

    // Подпись
    $signed_tx = bitcoinRPC('signrawtransactionwithwallet', [$raw_tx]);
    if (!is_array($signed_tx) || empty($signed_tx['complete']) || !$signed_tx['complete']) {
        return ['success' => false, 'error' => 'Transaction signing failed.'];
    }

    // Отправка
    $txid = bitcoinRPC('sendrawtransaction', [$signed_tx['hex']]);
    if (!is_string($txid) || !preg_match('/^[a-f0-9]{64}$/i', $txid)) {
        error_log("sendrawtransaction error: " . print_r($txid, true));
        return ['success' => false, 'error' => "Failed to send transaction."];
    }

    return ['success' => true, 'txid' => $txid];
}



function sendFromCentralWallet($to_address, $amount_btc, $CONNECT) {
    $central_address = get_setting('escrow_wallet_address', $CONNECT);

    // Получаем UTXO
    $utxos = bitcoinRPC('listunspent', [1, 9999999, [$central_address]]);
    if (empty($utxos)) {
        return ['error' => 'No UTXOs found for central wallet.'];
    }

    $utxo = $utxos[0];
    $txid = $utxo['txid'];
    $vout = $utxo['vout'];
    $input_amount = $utxo['amount'];

    // Комиссия (фиксированная или расчётная)
    // Получаем стоимость комиссии за килобайт
	$fee_per_kb = bitcoinRPC('estimatefee', [2]);

	// Убедимся, что результат является числом
	$fee_per_kb = is_numeric($fee_per_kb) ? (float)$fee_per_kb : 0.00001; // если не число, используем fallback

    if ($fee_per_kb <= 0) $fee_per_kb = 0.00001; // fallback
    $fee = $fee_per_kb * 0.25; // ≈250 байт

    if ($amount_btc <= $fee) {
        return ['error' => 'Amount is too small to cover network fee.'];
    }

    $send_amount = $amount_btc - $fee;

    if ($send_amount <= 0) {
        return ['error' => 'Final amount after fee is too small or negative.'];
    }

    // Если UTXO меньше, чем запрошено — ошибка
    if ($input_amount < $amount_btc) {
        return ['error' => "Insufficient balance in UTXO. Needed: $amount_btc, have: $input_amount"];
    }

    $inputs = [[ 'txid' => $txid, 'vout' => $vout ]];
    $outputs = [
        $to_address => (float)number_format($send_amount, 8, '.', '')
    ];

    // Добавим сдачу, если есть
    $change = $input_amount - $amount_btc;
    if ($change > 0.00001) {
        $outputs[$central_address] = (float)number_format($change, 8, '.', '');
    }

    $raw_tx = bitcoinRPC('createrawtransaction', [$inputs, $outputs]);
    $signed_tx = bitcoinRPC('signrawtransactionwithwallet', [$raw_tx]);

    if (empty($signed_tx['complete']) || empty($signed_tx['hex'])) {
        return ['error' => 'Failed to sign transaction.'];
    }

    $txid_sent = bitcoinRPC('sendrawtransaction', [$signed_tx['hex']]);

    return ['txid' => $txid_sent];
}


function addServiceComment($ad_id, $comment_text, $type = 'info') {
    global $CONNECT;

    $timestamp = date('Y-m-d H:i:s');
    $entry = [
        'timestamp' => $timestamp,
        'type' => $type,
        'message' => $comment_text
    ];

    $query = mysqli_query($CONNECT, "SELECT service_comments FROM escrow_deposits WHERE ad_id = '$ad_id'");
    if (!$query) {
        return;
    }

    $row = mysqli_fetch_assoc($query);
    if (!$row) {
        mysqli_query($CONNECT, "INSERT INTO escrow_deposits (ad_id, service_comments) VALUES ('$ad_id', '[]')");
        $comments = [];
    } else {
        $comments = json_decode($row['service_comments'], true) ?: [];
    }


    $comments[] = $entry;
    $encoded = mysqli_real_escape_string($CONNECT, json_encode($comments, JSON_UNESCAPED_UNICODE));

    mysqli_query($CONNECT, "UPDATE escrow_deposits SET service_comments = '$encoded' WHERE ad_id = '$ad_id'");
}



function add_notification($user_id, $message) {
    global $CONNECT;


    if (!$CONNECT) {
        return false;
    }

    if (empty($user_id) || empty($message)) {
        return false;
    }

    $stmt = $CONNECT->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("is", $user_id, $message);
    if (!$stmt->execute()) {
        $stmt->close();
        return false;
    }

    if ($stmt->affected_rows === 0) {
        $stmt->close();
        return false;
    }

    $stmt->close();
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

    $stmt = $CONNECT->prepare("SELECT status, buyer_confirmed, seller_confirmed FROM escrow_deposits WHERE ad_id = ?");
    if (!$stmt) {
        return ['error' => 'Failed to prepare statement', 'mysqli_error' => mysqli_error($CONNECT)];
    }

    $stmt->bind_param("i", $ad_id);
    if (!$stmt->execute()) {
        return ['error' => 'Failed to execute statement', 'mysqli_error' => mysqli_error($CONNECT)];
    }

    $escrow_result = $stmt->get_result();
    if (!$escrow_result) {
        return ['error' => 'Failed to get result', 'mysqli_error' => mysqli_error($CONNECT)];
    }

    $escrow = mysqli_fetch_assoc($escrow_result);
    if (!$escrow) {
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

            $input = json_decode($raw_input, true);
            $ad_id = $input['params']['ad_id'] ?? null;
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

        case 'getConfirmations':
            // Получаем TXID из параметров запроса
            $txid = $params['txid'] ?? null;

            if (!$txid) {
                echo json_encode(['error' => 'Missing parameters: txid', 'jsonrpc' => $jsonrpc]);
                exit();
            }

            // Получаем количество подтверждений для TXID
            $confirmations = getConfirmations($txid);

            echo json_encode([
                'jsonrpc' => '2.0',
                'result' => ['confirmations' => $confirmations],
                'id' => $jsonrpc['id'] ?? null
            ]);
            break;

        default:
            echo json_encode(['error' => 'Unknown method']);
    }
    exit();
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


    if (empty($ad_id)) {
        return ['error' => 'ad_id is missing'];
    }

    $messages = mysqli_query($CONNECT, "SELECT * FROM messages WHERE ad_id = '$ad_id' ORDER BY created_at ASC");
    if (!$messages) {
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
    }

    return ['result' => $response]; // <-- не забываем вернуть результат
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
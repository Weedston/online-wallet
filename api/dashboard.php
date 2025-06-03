<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config.php';

// Обработка JSON тела запроса
$input = json_decode(file_get_contents("php://input"), true);
if (json_last_error() === JSON_ERROR_NONE && is_array($input)) {
    $_POST = array_merge($_POST, $input);
}

// Проверка токена: можно принимать через POST/GET или через заголовок Authorization
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : (isset($_GET['user_id']) ? intval($_GET['user_id']) : 0);
$token   = isset($_POST['token'])   ? $_POST['token']           : (isset($_GET['token'])   ? $_GET['token']   : '');

if (!$user_id || !$token) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Проверяем токен в БД
$stmt = $CONNECT->prepare("SELECT wallet, session_token FROM members WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($btc_address, $storedToken);
$stmt->fetch();
$stmt->close();

if ($token !== $storedToken) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

// Получение баланса по UTXO
try {
    $utxos = bitcoinRPC('listunspent', [1, 9999999, [$btc_address]]);
    $balance = 0;
    foreach ($utxos as $utxo) {
        $balance += $utxo['amount'];
    }
    $balanceFormatted = number_format($balance, 8, '.', '');
    // Обновляем баланс в members
    mysqli_query($CONNECT, "UPDATE `members` SET `balance`='$balanceFormatted' WHERE `id` = '$user_id';");
} catch (Exception $e) {
    echo json_encode(['error' => 'Bitcoin RPC error: ' . $e->getMessage()]);
    exit;
}

// Получение последних транзакций (6, как на сайте)
try {
    $tx_response = bitcoinRPC("listtransactions", ["*", 6]);
    $transactions = $tx_response["result"] ?? $tx_response; // в зависимости от вашей реализации

    // Фильтруем только по этому адресу
    $filtered_txs = array_filter($transactions, function ($tx) use ($btc_address) {
        return isset($tx["address"]) && $tx["address"] == $btc_address;
    });

    // Приводим к индексному массиву
    $filtered_txs = array_values($filtered_txs);

    if (empty($filtered_txs)) {
        echo json_encode([
			"btc_address" => $btc_address,
            "balance" => $balanceFormatted,
			"error" => "No transactions found for this address"
        ]);
        exit;
    }

    echo json_encode([
        "balance" => $balanceFormatted,
		"btc_address" => $btc_address,
        "transactions" => $filtered_txs
    ]);
    exit;
} catch (Exception $e) {
    echo json_encode(['error' => 'Bitcoin RPC error: ' . $e->getMessage()]);
    exit;
}
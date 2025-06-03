<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/functions.php'; // где лежит sendBitcoinWithFees и calculateTotalNetworkFeeBTC

// --- Проверка токена авторизации ---
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : (isset($_GET['user_id']) ? intval($_GET['user_id']) : 0);
$token   = isset($_POST['token'])   ? $_POST['token']           : (isset($_GET['token'])   ? $_GET['token']   : '');

if (!$user_id || !$token) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$stmt = $CONNECT->prepare("SELECT wallet, session_token FROM members WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($wallet_address, $storedToken);
$stmt->fetch();
$stmt->close();

if ($token !== $storedToken) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

// --- Получение баланса и комиссий (ajax/info-запрос) ---
if (
    (isset($_GET['ajax']) && $_GET['ajax'] == '1') ||
    ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_POST))
) {
    try {
        $utxos = bitcoinRPC('listunspent', [1, 9999999, [$wallet_address]]);
        $balance = 0;
        foreach ($utxos as $utxo) {
            $balance += $utxo['amount'];
        }
        $site_fee_percentage = 0.01; // 1%
        $network_fee = calculateTotalNetworkFeeBTC();

        if ($balance <= 0 || $balance <= $network_fee) {
            $max_withdrawable = 0.0;
        } else {
            $max_withdrawable = ($balance - $network_fee) / (1 + $site_fee_percentage);
        }
        echo json_encode([
            "balance" => number_format($balance, 8, '.', ''),
            "network_fee" => number_format($network_fee, 8, '.', ''),
            "max_withdrawable" => number_format(max($max_withdrawable, 0), 8, '.', '')
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['error' => 'Bitcoin RPC error: ' . $e->getMessage()]);
        exit;
    }
}

// --- Отправка транзакции ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['amount'], $_POST['recipient'])) {
    $amount = floatval($_POST['amount']);
    $recipient = trim($_POST['recipient']);

    // Проверка на валидность суммы и адреса (при необходимости можно расширить)
    if ($amount <= 0 || !$recipient) {
        echo json_encode(['error' => 'Invalid amount or recipient']);
        exit;
    }

    try {
        $result = sendBitcoinWithFees($recipient, $amount, $wallet_address);

        if (isset($result['error'])) {
            echo json_encode(['error' => $result['error'], 'response' => $result['response'] ?? null]);
            exit;
        } else {
            echo json_encode(['success' => true, 'txid' => $result['txid']]);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Send error: ' . $e->getMessage()]);
        exit;
    }
}

// --- Иначе ---
echo json_encode(['error' => 'Unknown request']);
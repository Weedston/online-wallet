<?php
require_once __DIR__ . '/../config.php';
if (session_status() == PHP_SESSION_NONE) session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['ad_id'])) {
    echo "Неверный запрос.";
    exit;
}

$ad_id = intval($_POST['ad_id']);

// Устанавливаем seller_confirmed
$stmt = mysqli_prepare($CONNECT, "UPDATE escrow_deposits SET seller_confirmed = 1 WHERE ad_id = ?");
mysqli_stmt_bind_param($stmt, "i", $ad_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Получаем состояние сделки
$stmt = mysqli_prepare($CONNECT, "SELECT buyer_confirmed, seller_confirmed, btc_amount, transaction_id FROM escrow_deposits WHERE ad_id = ?");
mysqli_stmt_bind_param($stmt, "i", $ad_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (!$result || mysqli_num_rows($result) === 0) {
    echo "Ошибка: Сделка не найдена.";
    exit;
}
$status = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Проверка: уже ли отправлены BTC
if (!empty($status['transaction_id'])) {
    echo "BTC уже отправлены. TXID: " . htmlspecialchars($status['transaction_id']);
    exit;
}

// Если оба подтвердили — продолжаем
if ($status['buyer_confirmed'] == 1 && $status['seller_confirmed'] == 1) {
    // buyer_id
    $stmt = mysqli_prepare($CONNECT, "SELECT buyer_id FROM ads WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $ad_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (!$result || mysqli_num_rows($result) === 0) {
        echo "Ошибка: Покупатель не найден.";
        exit;
    }
    $buyer_id = mysqli_fetch_assoc($result)['buyer_id'];
    mysqli_stmt_close($stmt);

    // BTC-кошелёк покупателя
    $stmt = mysqli_prepare($CONNECT, "SELECT wallet FROM members WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $buyer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (!$result || mysqli_num_rows($result) === 0) {
        echo "Ошибка: Кошелек покупателя не найден.";
        exit;
    }
    $buyer_wallet = mysqli_fetch_assoc($result)['wallet'];
    mysqli_stmt_close($stmt);

    // Эскроу-адрес
    $stmt = mysqli_prepare($CONNECT, "SELECT value FROM settings WHERE name = 'escrow_wallet_address'");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $escrow_address = mysqli_fetch_assoc($result)['value'] ?? null;
    mysqli_stmt_close($stmt);

    try {
    $btc_amount = floatval($status['btc_amount']);
    $result_b = sendFromCentralWallet($buyer_wallet, $btc_amount, $CONNECT);

    if (isset($result_b['error'])) {
        throw new Exception("Ошибка при отправке BTC: " . $result_b['error']);
    }

    $txid = $result_b['txid'];

	addServiceComment($ad_id, "BTC deposited to buyer wallet. TXID: $txid, Amount: $btc_amount BTC", 'deposit');
    // Обновление БД
    $stmt = mysqli_prepare($CONNECT, "UPDATE escrow_deposits SET status = 'btc_released', transaction_id = ? WHERE ad_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $txid, $ad_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $update_query = "UPDATE ads SET status = 'completed', buyer_id = '$buyer_id', amount_btc = '$btc_amount' WHERE id = '$ad_id'";
                        if (mysqli_query($CONNECT, $update_query)) {
                            add_notification($ad['user_id'], "Your ad #$ad_id has been accepted and is in the completed status.");
                            header("Location: /p2p-trade_details?ad_id=$ad_id");
                            exit();
                        } else {
                            $error_message = "Error updating ad status: " . mysqli_error($CONNECT);
                        }
} catch (Exception $e) {
    echo "Ошибка при отправке BTC: " . $e->getMessage();
    exit;
}

}

header("Location: /p2p-trade_details?ad_id=$ad_id");
exit;

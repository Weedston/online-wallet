<?php
require_once __DIR__ . '/../config.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ad_id'])) {
    $ad_id = intval($_POST['ad_id']);

    // Установить seller_confirmed = 1
    $stmt = mysqli_prepare($CONNECT, "UPDATE escrow_deposits SET seller_confirmed = 1 WHERE ad_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $ad_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Получить подтверждения обеих сторон
    $stmt = mysqli_prepare($CONNECT, "SELECT buyer_confirmed, seller_confirmed, buyer_id, amount_btc FROM escrow_deposits WHERE ad_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $ad_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $status = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    // Если оба подтвердили, завершаем сделку
    if ($status['buyer_confirmed'] == 1 && $status['seller_confirmed'] == 1) {
        // Перевести BTC на адрес покупателя
        $buyer_id = $status['buyer_id'];
        $amount_btc = $status['amount_btc'];

        // Получаем BTC-кошелек покупателя
        $stmt = mysqli_prepare($CONNECT, "SELECT wallet FROM members WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $buyer_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $buyer_wallet = mysqli_fetch_assoc($result)['wallet'];
        mysqli_stmt_close($stmt);

        // Получаем адрес для отправки BTC (из escrow_address)
        $stmt = mysqli_prepare($CONNECT, "SELECT address FROM escrow_address WHERE ad_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $ad_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $escrow_address = mysqli_fetch_assoc($result)['address'];
        mysqli_stmt_close($stmt);

        // Вызываем функцию Bitcoin RPC для перевода средств
        try {
            // Переводим BTC с адреса escrow_address на адрес покупателя
            $txid = bitcoinRPC('sendfrom', [$escrow_address, $buyer_wallet, $amount_btc]);

            // Обновляем статус сделки и сохраняем transaction_id
            $stmt = mysqli_prepare($CONNECT, "UPDATE escrow_deposits SET status = 'btc_released', transaction_id = ? WHERE ad_id = ?");
            mysqli_stmt_bind_param($stmt, "si", $txid, $ad_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // (опционально) лог, уведомление и т.п.
        } catch (Exception $e) {
            // Обработка ошибки, если перевод не удался
            echo "Ошибка при отправке BTC: " . $e->getMessage();
            exit;
        }
    }

    header("Location: /p2p-trade_details?ad_id=$ad_id");
    exit;
} else {
    echo "Неверный запрос.";
}

header("Location: /p2p-trade_details?ad_id=$ad_id");  // Редирект на страницу сделки
exit;
?>
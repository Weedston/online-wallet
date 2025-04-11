<?php
require_once __DIR__ . '/../config.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ad_id'])) {
    $ad_id = intval($_POST['ad_id']);

    // Установить seller_confirmed = 1
    $stmt = mysqli_prepare($CONNECT, "UPDATE escrow_deposits SET seller_confirmed = 1 WHERE ad_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $ad_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Получить подтверждения обеих сторон
    $stmt = mysqli_prepare($CONNECT, "SELECT buyer_confirmed, seller_confirmed FROM escrow_deposits WHERE ad_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $ad_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $status = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    // Если оба подтвердили, завершаем сделку
    if ($status['buyer_confirmed'] == 1 && $status['seller_confirmed'] == 1) {
        $stmt = mysqli_prepare($CONNECT, "UPDATE escrow_deposits SET status = 'btc_released' WHERE ad_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $ad_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // (опционально) лог, уведомление и т.п.
    }

    header("Location: ../p2p-trade_details?ad_id=$ad_id");
    exit;
} else {
    echo "Неверный запрос.";
}
?>
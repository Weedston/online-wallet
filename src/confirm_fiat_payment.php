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

   //header("Location: ../p2p-trade_details?ad_id=$ad_id");
    exit;
} else {
    echo "Неверный запрос.";
}

// Подключаемся к RPC-серверу
$rpc = bitcoinRPC();  // Функция для работы с Bitcoin RPC (предположительно, ты её уже реализовал)

$query = "SELECT * FROM escrow_deposits WHERE ad_id = ?";
$stmt = mysqli_prepare($CONNECT, $query);
mysqli_stmt_bind_param($stmt, "i", $ad_id);
mysqli_stmt_execute($stmt);
$escrow = mysqli_stmt_get_result($stmt);
$escrow_data = mysqli_fetch_assoc($escrow);
mysqli_stmt_close($stmt);

// Проверка статуса сделки
if ($escrow_data['status'] !== 'btc_released') {
    die('Сделка ещё не оплачена.');
}

// Получаем адрес покупателя и продавца, а также их приватные ключи
$buyer_id = $escrow_data['buyer_id'];
$seller_id = $escrow_data['seller_id'];

// Получаем данные о пользователях
$query = "SELECT * FROM members WHERE id IN (?, ?)";
$stmt = mysqli_prepare($CONNECT, $query);
mysqli_stmt_bind_param($stmt, "ii", $buyer_id, $seller_id);
mysqli_stmt_execute($stmt);
$members = mysqli_stmt_get_result($stmt);
$member_data = mysqli_fetch_all($members, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$buyer_privkey = $member_data[0]['privkey']; // Приватный ключ покупателя
$seller_privkey = $member_data[1]['privkey']; // Приватный ключ продавца
$buyer_address = $member_data[0]['wallet']; // Адрес покупателя

// Создаём raw транзакцию
$txid = $escrow_data['txid'];
$vout_index = findVoutIndex($rpc, $txid, $escrow_data['escrow_address']);  // Функция для нахождения vout (выхода) по multisig-адресу

$fee = 0.00001;  // Примерная комиссия
$rawTx = $rpc->createrawtransaction(
    [["txid" => $txid, "vout" => $vout_index]],  // Указываем выход из мультисиг
    [$buyer_address => floatval($escrow_data['btc_amount'] - $fee)]  // Переводим сумму минус комиссия
);

// Подписываем транзакцию продавцом
$signed1 = $rpc->signrawtransactionwithkey($rawTx, [$seller_privkey]);

// Подписываем транзакцию покупателем
$signed2 = $rpc->signrawtransactionwithkey($signed1['hex'], [$buyer_privkey]);

if ($signed2['complete'] !== true) {
    die('Не удалось подписать транзакцию.');
}

// Отправляем транзакцию в сеть
$txid_sent = $rpc->sendrawtransaction($signed2['hex']);

// Обновляем статус сделки в базе данных
$stmt = mysqli_prepare($CONNECT, "UPDATE escrow_deposits SET status = 'btc_released', txid = ? WHERE ad_id = ?");
mysqli_stmt_bind_param($stmt, "si", $txid_sent, $ad_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Дополнительно можно добавить логи и уведомления

header("Location: ../p2p-trade_details?ad_id=$ad_id");  // Редирект на страницу сделки
exit;

?>
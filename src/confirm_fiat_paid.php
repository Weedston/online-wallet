<?php
require_once __DIR__ . '/../config.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fiat_paid'], $_POST['ad_id'])) {
    $ad_id = intval($_POST['ad_id']);

    // Проверим текущий статус
    $stmt = $CONNECT->prepare("SELECT status FROM escrow_deposits WHERE ad_id = ?");
    $stmt->bind_param("i", $ad_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $escrow = $result->fetch_assoc();

    if (!$escrow) {
        error_log("confirm_fiat_paid: Эскроу не найден для ad_id = $ad_id");
        die("Ошибка: Эскроу не найден.");
    }

    if ($escrow['status'] !== 'btc_deposited') {
        error_log("confirm_fiat_paid: Нельзя подтвердить оплату, статус = {$escrow['status']}");
        die("Неверный статус сделки.");
    }

    // Обновим статус на fiat_paid
    $update = $CONNECT->prepare("UPDATE escrow_deposits SET status = 'fiat_paid', buyer_confirmed = 1, updated_at = NOW() WHERE ad_id = ?");
    $update->bind_param("i", $ad_id);
    if ($update->execute()) {
        error_log("confirm_fiat_paid: Статус обновлён до fiat_paid для ad_id = $ad_id");
    } else {
        error_log("confirm_fiat_paid: Ошибка обновления статуса: " . $CONNECT->error);
        die("Ошибка при обновлении статуса.");
    }

    header("Location: ../p2p-trade_details?ad_id=$ad_id");
    exit();
} else {
    die("Недопустимый запрос.");
}

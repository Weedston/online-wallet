<?php
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../config.php';

// ==== 1. Уведомления о новых BTC транзакциях ====
$transactions = bitcoinRPC('listtransactions', ['*', 50]);

if (is_array($transactions)) {
    foreach ($transactions as $tx) {
        $txid = $tx['txid'];
        $amount = $tx['amount'];
        $address = $tx['address'];
        $confirmations = $tx['confirmations'];

        $stmt = $CONNECT->prepare("SELECT id FROM btc_notifications WHERE txid = ?");
        $stmt->bind_param("s", $txid);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $stmt = $CONNECT->prepare("INSERT INTO btc_notifications (txid, address, amount, confirmations) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssdi", $txid, $address, $amount, $confirmations);
            $stmt->execute();

            $msg = "📥 <b>New BTC Transaction</b>\n\n" .
                   "🔐 Address: <code>$address</code>\n" .
                   "💰 Amount: <b>$amount BTC</b>\n" .
                   "⛓ Confirmations: <b>$confirmations</b>\n" .
                   "🔗 TXID: <code>$txid</code>";
            sendTelegram($msg);
        }
    }
}

// ==== 2. Уведомления о новых регистрациях ====
$result = $CONNECT->query("SELECT id, wallet, created_at FROM members WHERE notified = 0");

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $username = htmlspecialchars($row['wallet']);
    $date = $row['created_at'];

    $msg = "👤 <b>New User Registered</b>\n\n" .
           "🆔 ID: <code>$id</code>\n" .
           "🧑 Wallet: <code>$username</code>\n" .
           "🕒 Date: <code>$date</code>";
    sendTelegram($msg);

    $stmt = $CONNECT->prepare("UPDATE members SET notified = 1 WHERE id = ?");
    $stmt->bind_param("i", $row['id']);
    $stmt->execute();
}

// ==== 3. Уведомления о новых сообщениях в техподдержку ====
$result = $CONNECT->query("SELECT sr.id, sr.user_id, sr.message, sr.created_at, m.wallet 
                           FROM support_requests sr 
                           LEFT JOIN members m ON sr.user_id = m.id 
                           WHERE sr.notified = 0");

while ($row = $result->fetch_assoc()) {
    $req_id = $row['id'];
    $user_id = $row['user_id'];
    $wallet = htmlspecialchars($row['wallet'] ?? '—');
    $message = htmlspecialchars($row['message']);
    $created = $row['created_at'];

    $msg = "📩 <b>New Support Request</b>\n\n" .
           "🧑 User ID: <code>$user_id</code>\n" .
           "🔐 Wallet: <code>$wallet</code>\n" .
           "🕒 Date: <code>$created</code>\n" .
           "✉ Message:\n<pre>$message</pre>";

    sendTelegram($msg);

    $stmt = $CONNECT->prepare("UPDATE support_requests SET notified = 1 WHERE id = ?");
    $stmt->bind_param("i", $req_id);
    $stmt->execute();
}	
	// ==== 0. Автоматическое включение режима обслуживания при высоком балансе ====
$balance = bitcoinRPC('getbalance');


if ($balance >= 0.1) {
    // Отправка уведомления в Telegram
    $msg = "⚠ <b>Уведомление о балансе BTC кошелька</b>\n\n" .
           "💰 Balance: <b>$balance BTC</b>\n" .
           "🔧 Maintenance mode <b>ENABLED</b> automatically.";
    sendTelegram($msg);

    // Включаем режим обслуживания в таблице settings
    $maintenance_mode = 'on';
    $stmt = $CONNECT->prepare("UPDATE settings SET value = ? WHERE name = 'maintenance_mode'");
    $stmt->bind_param("s", $maintenance_mode);
    $stmt->execute();

    // Разлогиниваем всех пользователей кроме id=7, очищая session_token
    $exclude_id = 7;
    $stmt = $CONNECT->prepare("UPDATE members SET session_token = NULL WHERE id != ?");
    $stmt->bind_param("i", $exclude_id);
    $stmt->execute();
}



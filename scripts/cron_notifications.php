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

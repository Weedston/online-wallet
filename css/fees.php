<?php
	$rpc_User = 'wikly';
	$rpc_Password = '8A08d423';
	$rpc_Host = '127.0.0.1';
	$rpc_Port = 8332;
		$wallet_address = $_SESSION['wallet'];
function bitcoinRPC($method, $params = []) {
    global $rpc_user, $rpc_password, $rpc_host, $rpc_port;

    $url = "http://$rpc_user:$rpc_password@$rpc_host:$rpc_port/";
    $payload = json_encode([
        "jsonrpc" => "1.0",
        "id" => "curltest",
        "method" => $method,
        "params" => $params
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/plain']);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// 1️⃣ Получаем баланс
$balance_response = bitcoinRPC("getreceivedbyaddress", [$wallet_address]);
$balance = $balance_response["result"] ?? 0.0; // Если ошибка, баланс = 0.0

// 2️⃣ Получаем комиссию сети
$fee_response = bitcoinRPC("estimatesmartfee", [6]); // Комиссия для 6 блоков
$fee_per_kb = $fee_response["result"]["feerate"] ?? 0.0001; // Если нет данных, ставим 0.0001 BTC
$tx_size_kb = 0.0002; // Примерный размер транзакции (200 байт = 0.0002 КБ)
$network_fee = $fee_per_kb * $tx_size_kb; // Итоговая комиссия сети

// 3️⃣ Рассчитываем максимальную сумму
$site_fee_percentage = 0.01; // 1% комиссия сайта
if ($balance <= 0 || $balance <= $network_fee) {
    $max_withdrawable = 0.0; // Если баланс меньше комиссии, выдаем 0
} else {
    $max_withdrawable = ($balance - $network_fee) / (1 + $site_fee_percentage);
}

// Если это AJAX-запрос, возвращаем JSON
if (isset($_GET['ajax'])) {
    echo json_encode([
        "balance" => number_format($balance, 8),
        "network_fee" => number_format($network_fee, 8),
        "max_withdrawable" => number_format(max($max_withdrawable, 0), 8)
    ]);
    exit;
}

?>
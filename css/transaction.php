<?php

// Настройки RPC
	$rpc_User = 'wikly';
	$rpc_Password = '8A08d423';
	$rpc_Host = '127.0.0.1';
	$rpc_Port = 8332;

// BTC-адрес для отслеживания
$btc_address = $_SESSION['wallet']; 

// Функция JSON-RPC запроса
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

// Если запрос AJAX — возвращаем JSON
if (isset($_GET['ajax'])) {

	$tx_response = bitcoinRPC("listtransactions", ["*", 10]); // Получаем 10 последних транзакций
    $transactions = $tx_response["result"] ?? [];

    // Фильтруем только транзакции для нужного адреса
    $filtered_txs = array_filter($transactions, function ($tx) use ($btc_address) {
        return isset($tx["address"]) && $tx["address"] == $btc_address;
    });

	if (empty($filtered_txs)) {
		echo json_encode(["error" => "No transactions found for this address"]);
		exit;
}


    echo json_encode(array_values($filtered_txs));
    exit;
}

?>
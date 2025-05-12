<?php
define ('HOST', 'localhost');
define ('USER', 'wikly');
define ('PASS', '8A08d423');
define ('DB', 'wallet_btc');

$CONNECT = mysqli_connect(HOST, USER, PASS, DB);

// Проверка соединения
if (!$CONNECT) {
    die("Ошибка подключения к БД: " . mysqli_connect_error());
}

function bitcoinRPC($method, $params = []) {

    $rpc_user = 'wikly';
    $rpc_password = '8A08d423';
    $rpc_host = '127.0.0.1';
    $rpc_port = 48332;

    // Данные для аутентификации и запроса
    $data = json_encode([
        'jsonrpc' => '1.0',
        'id' => 'phpRPC',
        'method' => $method,  // Метод, который передается в функцию
        'params' => $params   // Параметры для метода
    ]);

    // Заголовки для запроса
    $headers = [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($rpc_user . ':' . $rpc_password)
    ];

    // Инициализация cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://$rpc_host:$rpc_port/");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Выполнение запроса
    $response = curl_exec($ch);

    // Проверка ошибок cURL
    if(curl_errno($ch)) {
        echo 'cURL error: ' . curl_error($ch);
        curl_close($ch);
        return null;
    }

    // Закрытие cURL
    curl_close($ch);

    // Декодирование ответа от сервера
    $response_data = json_decode($response, true);

    // Отладка - выводим полный ответ
    // var_dump($response_data);

    // Проверка на наличие ошибок в ответе
    if (isset($response_data['error'])) {
        // Если ошибка в ответе, возвращаем её
        return 'Error: ' . $response_data['error']['message'];
    }

    // Если результат существует, возвращаем его
    if (isset($response_data['result'])) {
        return $response_data['result']; // Возвращаем результат выполнения запроса
    }

    // Если нет результата и нет ошибки, возвращаем неизвестную ошибку
    return 'Unknown ERROR: ' . $response;
}

?>

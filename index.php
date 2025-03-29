<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
session_start();

require 'vendor/autoload.php';

use JsonRPC\Client;

$rpcUser = 'wikly';
$rpcPassword = '8A08d423';
$rpcHost = '127.0.0.1';
$rpcPort = 48332;

// Подключаемся к bitcoind
$client = new Client("http://$rpcHost:$rpcPort/");
$client->authentication($rpcUser, $rpcPassword);

function FormChars($p1)
{
	return nl2br(htmlspecialchars(trim($p1), ENT_QUOTES), false);
}

function add_notification($user_id, $message) {
    global $CONNECT; // Declare global variable

    if (!$CONNECT) {
        error_log("Error: Database connection is missing.");
        return false;
    }

    $stmt = $CONNECT->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    
    if (!$stmt) {
        error_log("Error preparing statement: " . $CONNECT->error);
        return false;
    }

    $stmt->bind_param("is", $user_id, $message);
    
    if (!$stmt->execute()) {
        error_log("Error executing statement: " . $stmt->error);
        $stmt->close();
        return false;
    }

    $stmt->close();
    return true; // Successful execution
}




if ($_SERVER['REQUEST_URI'] == '/') {
	$Page = 'index';
	$Module = 'index';

	} else {

	$URL_Path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	$URL_Parts = explode('/', trim($URL_Path, ' /'));
	$Page = array_shift($URL_Parts);
	$Module = array_shift($URL_Parts);


	if (!empty($Module)) {
	$Param = array();
	for ($i = 0; $i < count($URL_Parts); $i++) 
			{
				$Param[$URL_Parts[$i]] = $URL_Parts[++$i];
			}
		}
	}

$btc_address = $_SESSION['wallet']; 

	
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



//include 'pages/top.php';

if ($Page == 'index' ) include 'pages/index.php'; 
else if ($Page == 'login') include 'pages/login.php';
else if ($Page == 'register') include 'pages/register.php';
else if ($Page == 'dashboard') include 'pages/dashboard.php';
else if ($Page == 'logout') include 'pages/logout.php';
else if ($Page == 'transfer') include 'pages/send.php';
else if ($Page == 'support') include 'pages/support.php';
else if ($Page == 'adm_support') include 'pages/admin_support.php';
else if ($Page == 'profile') include 'pages/profile.php';
else if ($Page == 'p2p') include 'pages/p2p/index.php';
else if ($Page == 'p2p-create') include 'pages/p2p/create_ad.php';
else if ($Page == 'p2p-history') include 'pages/p2p/history.php';
else if ($Page == 'p2p-profile') include 'pages/p2p/profile.php';
else if ($Page == 'p2p-trade_details') include 'pages/p2p/trade_details.php';

else include 'pages/index.php';

include 'pages/bottom.php';

?>
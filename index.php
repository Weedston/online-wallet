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

if (isset ($_SESSION['wallet'])) {
    $btc_address = $_SESSION['wallet']; 
}

// Функция для создания мультиподписного кошелька
function createMultisigAddress($keys) {
    $rpc = new BitcoinRPC();
    $response = $rpc->createmultisig(2, $keys);
    return $response->address;
}

// Функция для депонирования BTC
function createEscrowTransaction($inputs, $outputs) {
    $rpc = new BitcoinRPC();
    $raw_tx = $rpc->createrawtransaction($inputs, $outputs);
    return $raw_tx;
}

// Функция для подписания транзакции
function signTransaction($raw_tx, $keys, $inputs) {
    $rpc = new BitcoinRPC();
    $signed_tx = $rpc->signrawtransactionwithkey($raw_tx, $keys, $inputs);
    return $signed_tx->hex;
}

// Функция для отправки транзакции
function sendTransaction($signed_tx) {
    $rpc = new BitcoinRPC();
    $txid = $rpc->sendrawtransaction($signed_tx);
    return $txid;
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
else if ($Page == 'p2p-trade_history') include 'pages/p2p/trade_history.php';
else if ($Page == 'p2p-profile') include 'pages/p2p/profile.php';
else if ($Page == 'p2p-trade_details') include 'pages/p2p/trade_details.php';
else if ($Page == 'notifications') include 'pages/p2p/notifications.php';
else if ($Page == 'adm_settings') include 'pages/p2p/admin_settings.php';



else include 'pages/index.php';

include 'pages/bottom.php';

?>
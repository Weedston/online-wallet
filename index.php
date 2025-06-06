<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

require_once 'config.php';
session_start();

require 'vendor/autoload.php';

use JsonRPC\Client;

$rpcUser = 'wikly';
$rpcPassword = '8A08d423';
$rpcHost = '127.0.0.1';
$rpcPort = 8332;

// Подключаемся к bitcoind
$client = new Client("http://$rpcHost:$rpcPort/");
$client->authentication($rpcUser, $rpcPassword);

function FormChars($p1)
{
	return nl2br(htmlspecialchars(trim($p1), ENT_QUOTES), false);
}

function loadLanguage($lang = 'en') {
    $path = __DIR__ . "/languages/$lang.php";

    if (file_exists($path)) {
        return include $path;
    }

    // Если файл языка не найден, возвращаем английский по умолчанию
    return include __DIR__ . '/languages/en.php';
}

$default_language = 'en';

// Если пользователь выбирает язык через GET параметр, сохраняем его в сессии
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Получаем язык из сессии или используем язык по умолчанию
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : $default_language;

// Пример использования
//$lang = isset($_GET['lang']) ? $_GET['lang'] : 'en'; // Например, выбираем язык через GET параметр
$translations = loadLanguage($lang);

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


//include 'pages/top.php';

if ($Page == 'index' ) include 'pages/index.php'; 
//else if ($Page == 'login') include 'pages/login.php';
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
else if ($Page == 'download') include 'pages/download.php';
else if ($Page == 'privacy') include 'pages/privacy.php';
else if ($Page == 'anonbtcapk') include 'pages/anonbtc.php';
else if ($Page == 'review') include 'pages/reviews.php';



else include 'pages/index.php';

include 'pages/bottom.php';

?>
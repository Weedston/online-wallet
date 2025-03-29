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
?>

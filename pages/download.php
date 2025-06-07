<?php
session_start();

// Подключение к БД
require_once __DIR__ . '/../config.php';

// Путь к файлу
$filepath = __DIR__ . '/../api/AnonBTCWallet.apk';
$filename = 'AnonBTCWallet.apk';

// Проверка существования
if (!file_exists($filepath)) {
    http_response_code(404);
    echo "Файл не найден.";
    exit;
}

// Сохраняем запись о скачивании
$ip = $_SERVER['REMOTE_ADDR'];
$stmt = $CONNECT->prepare("INSERT INTO downloads (file_name, ip_address) VALUES (?, ?)");
$stmt->bind_param("ss", $filename, $ip);
$stmt->execute();
$stmt->close();

// Заголовки
header('Content-Description: File Transfer');
header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filepath));

flush();
readfile($filepath);
exit;

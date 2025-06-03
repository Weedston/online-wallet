<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config.php';

// === Языки ===
function loadLanguage($lang = 'en') {
    $path = __DIR__ . '/../languages/' . $lang . '.php';
    if (file_exists($path)) {
        return include $path;
    }
    return include __DIR__ . '/../languages/en.php';
}

// --- Определяем язык ---
$default_language = 'en';
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $lang;
} elseif (isset($_POST['lang'])) {
    $lang = $_POST['lang'];
    $_SESSION['lang'] = $lang;
} elseif (isset($_SESSION['lang'])) {
    $lang = $_SESSION['lang'];
} else {
    $lang = $default_language;
}
$translations = loadLanguage($lang);

// === Получаем сообщения и настройки из БД ===
$stmt = $CONNECT->prepare("SELECT name, value FROM settings WHERE name IN ('message', 'message_ru', 'message_display', 'maintenance_mode')");
$stmt->execute();
$result = $stmt->get_result();
$settings = [];
while ($row = $result->fetch_assoc()) {
    $settings[$row['name']] = $row['value'];
}
$stmt->close();

// Сообщение пользователя
$message = ($lang === 'ru' && !empty($settings['message_ru'])) ? $settings['message_ru'] : ($settings['message'] ?? '');
$message_display = $settings['message_display'] ?? '0';

// Режим обслуживания (maintenance)
$maintenance_mode = ($settings['maintenance_mode'] ?? 'off') === 'on';

// Текст для блока обслуживания
$maintenance_title = $translations['maintenance_h1'] ?? '';
$maintenance_text  = $translations['maintenance_text'] ?? '';

// Формируем JSON-ответ
$response = [
    "lang"                  => $lang,
    "welcome_heading"       => $translations['welcome_heading']      ?? '',
    "welcome_subheading"    => $translations['welcome_subheading']   ?? '',
    "advantages_heading"    => $translations['advantages_heading']   ?? '',
    "advantages_text1"      => $translations['advantages_text1']     ?? '',
    "advantages_text2"      => $translations['advantages_text2']     ?? '',
    "advantages_text3"      => $translations['advantages_text3']     ?? '',
    "advantages_text4"      => $translations['advantages_text4']     ?? '',
    "advantages_text5"      => $translations['advantages_text5']     ?? '',
    "advantages_text6"      => $translations['advantages_text6']     ?? '',
    "message_display"       => $message_display,
    "message"               => ($message_display === '1' && !empty($message)) ? $message : '',
    "maintenance_mode"      => $maintenance_mode ? 'on' : 'off',
    "maintenance_title"     => $translations['maintenance_h1'],
    "maintenance_text"      => $translations['maintenance_text']
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);	
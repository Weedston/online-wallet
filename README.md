# Проект Online Wallet

## Структура файлов

```
├── .htaccess
├── README.md
├── composer.json
├── composer.lock
├── config.php
├── css/
│   ├── styles.css
├── images/
│   ├── notyf.png
│   ├── qrcode.png
├── index.php
├── js/
│   ├── notification_sound.mp3
├── pages/
│   ├── admin_support.php
│   ├── bottom.php
│   ├── composer.json
│   ├── dashboard.php
│   ├── deposit.php
│   ├── func.php
│   ├── index.php
│   ├── login.php
│   ├── logout.php
│   ├── menu-wallet.php
│   ├── p2p/
│   │   ├── create_ad.php
│   │   ├── exchange.php
│   │   ├── notifications.php
│   │   ├── index.php
│   │   ├── menu.php
│   │   ├── process_offer.php
│   │   ├── profile.php
│   │   ├── trade.php
│   │   ├── trade_details.php
│   │   ├── trade_history.php
│   ├── profile.php
│   ├── register.php
│   ├── send.php
│   ├── sid_generator.php
│   ├── support.php
│   ├── top.php
│   ├── transfer.php
├── robots.txt
├── src/
│   ├── jsonrpc.php
│   ├── send_message.php
│   ├── functions.php
│   ├── get_btc_rates.php
├── update_readme.py
├── wallet_btc.sql
├── wordlist.txt
```

## Описание файлов

### Файл: src/get_btc_rates.php

#### Назначение
Этот файл отвечает за получение текущих курсов Bitcoin (BTC) по отношению к различным фиатным валютам (USD, EUR, RUB) с использованием API CoinGecko.

#### Основной функционал
- Функция `getBtcRates()`: Отправляет запрос к API CoinGecko и получает курсы BTC.
- Возвращает JSON-объект с текущими курсами BTC.

#### Примеры использования
Файл вызывается AJAX-запросом из других частей проекта для получения актуальных курсов BTC. Например, он используется на странице `create_ad.php` для отображения текущих курсов BTC в реальном времени.

#### Зависимости
- Доступ к интернету для отправки запросов к API CoinGecko.
- Функции PHP для работы с HTTP-запросами и JSON.

#### Примечания
- Убедитесь, что настройки PHP позволяют отправлять HTTP-запросы.
- Обработайте возможные ошибки, связанные с запросами к API, например, отсутствие ответа или некорректный формат данных.

Пример кода:
```php
<?php
require_once '../config.php';

function getBtcRates() {
    $apiUrl = 'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=usd,eur,rub';
    $response = file_get_contents($apiUrl);
    if ($response === FALSE) {
        return null;
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }

    if (isset($data['bitcoin'])) {
        return $data['bitcoin'];
    }

    return null;
}

$btcRates = getBtcRates();
header('Content-Type: application/json');
echo json_encode($btcRates);
?>
```

### Файл: src/jsonrpc.php

#### Назначение
Этот файл реализует обработку JSON-RPC запросов для взаимодействия с базой данных и выполнения различных операций, таких как получение уведомлений, отметка уведомлений как прочитанных и получение количества непрочитанных уведомлений.

#### Основной функционал
- Функция `getNotifications($user_id)`: Получает уведомления пользователя из базы данных.
- Функция `markNotificationsAsRead($user_id)`: Отмечает уведомления пользователя как прочитанные.
- Функция `getUnreadNotificationCount($user_id)`: Получает количество непрочитанных уведомлений пользователя.

#### Примеры использования
Файл используется для обработки AJAX-запросов на получение и обновление уведомлений на клиентской стороне.

#### Зависимости
- Подключение к базе данных.
- Функции PHP для работы с HTTP-запросами и JSON.

#### Примечания
- Убедитесь, что настройки PHP позволяют отправлять HTTP-запросы и обрабатывать JSON.

Пример кода:
```php
<?php
require_once '../config.php';

function getNotifications($user_id) {
    global $CONNECT;
    $query = "SELECT message FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
    $stmt = mysqli_prepare($CONNECT, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $notifications = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $notifications;
}

function markNotificationsAsRead($user_id) {
    global $CONNECT;
    $query = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
    $stmt = mysqli_prepare($CONNECT, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return true;
}

function getUnreadNotificationCount($user_id) {
    global $CONNECT;
    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = mysqli_prepare($CONNECT, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $count = mysqli_fetch_assoc($result)['count'];
    mysqli_stmt_close($stmt);
    return $count;
}

$input = json_decode(file_get_contents('php://input'), true);

if ($input['method'] === 'getNotifications') {
    $user_id = $input['params']['user_id'];
    $notifications = getNotifications($user_id);
    echo json_encode(['jsonrpc' => '2.0', 'result' => ['notifications' => $notifications], 'id' => $input['id']]);
} elseif ($input['method'] === 'markNotificationsAsRead') {
    $user_id = $input['params']['user_id'];
    $result = markNotificationsAsRead($user_id);
    echo json_encode(['jsonrpc' => '2.0', 'result' => $result, 'id' => $input['id']]);
} elseif ($input['method'] === 'getUnreadNotificationCount') {
    $user_id = $input['params']['user_id'];
    $count = getUnreadNotificationCount($user_id);
    echo json_encode(['jsonrpc' => '2.0', 'result' => ['count' => $count], 'id' => $input['id']]);
} else {
    echo json_encode(['jsonrpc' => '2.0', 'error' => ['code' => -32601, 'message' => 'Method not found'], 'id' => $input['id']]);
}
?>
```

### Файл: pages/p2p/menu.php

#### Назначение
Этот файл генерирует навигационное меню для страниц раздела P2P, а также включает функционал для отображения и обработки уведомлений.

#### Основной функционал
- Отображение навигационного меню.
- Функции для получения и отображения уведомлений пользователя.
- Обработка событий кликов для значка уведомлений.

#### Примеры использования
Файл используется для включения навигационного меню на страницах P2P, таких как `create_ad.php` и `notifications.php`.

#### Зависимости
- Подключение к базе данных.
- Функции PHP и JavaScript для работы с уведомлениями.

#### Примечания
- Убедитесь, что настройки PHP позволяют работать с сессиями и подключаться к базе данных.

Пример кода:
```php
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'];
?>

<nav>
    <ul>
        <li><a href="dashboard">Dashboard</a></li>
        <li><a href="transfer">Transfer</a></li>
        <li><a href="support">Support</a></li>
        <li><a href="p2p">P2P Exchange</a></li>
        <li><a href="p2p-create">Create Ad</a></li>
        <li><a href="p2p-trade_history">Trade History</a></li>
        <li><a href="p2p-profile">Profile</a></li>
        <li><a href="logout">Logout</a></li>
        <li>
            <a href="#" id="notification-bell">
                <img src="../../images/notyf.png" alt="Notifications" width="24" height="24">
                <span class="notification-badge" id="notification-count"></span>
            </a>
            <div id="notification-popup" class="notification-popup">
                <h4>Notifications</h4>
                <ul id="notification-list">
                    <!-- Уведомления будут загружены тут -->
                </ul>
                <a href="notifications.php" class="view-all-link">View All Notifications</a>
            </div>
        </li>
    </ul>
</nav>
<div class="nav-bar"></div> <!-- Добавление полоски -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработчик для значка уведомлений
    document.getElementById('notification-bell').addEventListener('click', function() {
        var popup = document.getElementById('notification-popup');
        if (popup.style.display === 'none' || popup.style.display === '') {
            fetchNotifications();
            markNotificationsAsRead(); // Добавлено для отметки уведомлений как прочитанных
            popup.style.display = 'block';
        } else {
            popup.style.display = 'none';
        }
    });

    // Функция для загрузки уведомлений
    function fetchNotifications() {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/src/jsonrpc.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.result) {
                            var notifications = response.result.notifications;
                            var notificationList = document.getElementById('notification-list');
                            notificationList.innerHTML = '';
                            notifications.forEach(function(notification) {
                                var listItem = document.createElement('li');
                                listItem.innerHTML = notification.message; // Используем innerHTML для рендеринга HTML
                                notificationList.appendChild(listItem);
                            });
                        } else if (response.error) {
                            console.error("Error: " + response.error.message);
                        }
                    } catch (e) {
                        console.error("Parsing error:", e);
                        console.error("Response:", xhr.responseText);
                    }
                } else {
                    console.error("Request failed with status:", xhr.status);
                }
            }
        };
        xhr.onerror = function() {
            console.error("Request failed");
        };
        xhr.send(JSON.stringify({
            jsonrpc: "2.0",
            method: "getNotifications",
            params: { user_id: <?php echo $user_id; ?> },
            id: 1
        }));
    }

    // Функция для отметки уведомлений как прочитанных
    function markNotificationsAsRead() {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/src/jsonrpc.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.result) {
                            console.log("Notifications marked as read");
                            fetchUnreadNotificationCount(); // Обновить количество непрочитанных уведомлений
                        } else if (response.error) {
                            console.error("Error: " + response.error.message);
                        }
                    } catch (e) {
                        console.error("Parsing error:", e);
                        console.error("Response:", xhr.responseText);
                    }
                } else {
                    console.error("Request failed with status:", xhr.status);
                }
            }
        };
        xhr.onerror = function() {
            console.error("Request failed");
        };
        xhr.send(JSON.stringify({
            jsonrpc: "2.0",
            method: "markNotificationsAsRead",
            params: { user_id: <?php echo $user_id; ?> },
            id: 1
        }));
    }

    // Функция для получения количества непрочитанных уведомлений
    function fetchUnreadNotificationCount() {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/src/jsonrpc.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.result) {
                            var count = response.result.count;
                            document.getElementById('notification-count').textContent = count;
                        } else if (response.error) {
                            console.error("Error: " + response.error.message);
                        }
                    } catch (e) {
                        console.error("Parsing error:", e);
                        console.error("Response:", xhr.responseText);
                    }
                } else {
                    console.error("Request failed with status:", xhr.status);
                }
            }
        };
        xhr.onerror = function() {
            console.error("Request failed");
        };
        xhr.send(JSON.stringify({
            jsonrpc: "2.0",
            method: "getUnreadNotificationCount",
            params: { user_id: <?php echo $user_id; ?> },
            id: 1
        }));
    }

    fetchUnreadNotificationCount();
    setInterval(fetchUnreadNotificationCount, 5000);
});
</script>
```

### Файл: pages/p2p/notifications.php

#### Назначение
Этот файл отображает полный список всех уведомлений пользователя.

#### Основной функционал
- Получение всех уведомлений пользователя из базы данных.
- Отображение уведомлений в виде списка.

#### Примеры использования
Страница используется для просмотра всех уведомлений пользователя.

#### Зависимости
- Подключение к базе данных.
- Функции PHP для работы с базой данных и HTML.

#### Примечания
- Убедитесь, что настройки PHP позволяют работать с сессиями и подключаться к базе данных.

Пример кода:
```php
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'];

$CONNECT = mysqli_connect(HOST, USER, PASS, DB);

function fetchAllNotifications($user_id, $CONNECT) {
    $query = "SELECT message, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = mysqli_prepare($CONNECT, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $notifications = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $notifications;
}

$notifications = fetchAllNotifications($user_id, $CONNECT);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Notifications</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
<div class="container">
    <?php include 'menu.php'; ?>
    <div class="container notifications-container">
        <h2>All Notifications</h2>
        <ul>
            <?php foreach ($notifications as $notification) { ?>
                <li>
                    <span><?php echo htmlspecialchars($notification['message']); ?></span>
                    <span class="notification-date"><?php echo htmlspecialchars($notification['created_at']); ?></span>
                </li>
            <?php } ?>
        </ul>
    </div>
</div>
</body>
</html>
```
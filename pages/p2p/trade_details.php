<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$ad_id = null;

require_once 'src/functions.php';

// Убедитесь, что переменная ad_id передается и обрабатывается правильно
if (isset($_GET['ad_id'])) {
    $ad_id = intval($_GET['ad_id']);
} elseif (isset($_POST['ad_id'])) {
    $ad_id = intval($_POST['ad_id']);
} else {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        $rawInput = file_get_contents('php://input');
        $jsonrpc = json_decode($rawInput, true);

        if ($jsonrpc !== null && isset($jsonrpc['params']['ad_id'])) {
            $ad_id = intval($jsonrpc['params']['ad_id']);
        }
    }
}

if (!isset($ad_id)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ad_id is missing']);
    exit();
}

$CONNECT = mysqli_connect(HOST, USER, PASS, DB);

if (!$CONNECT) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to connect to database']);
    exit();
}

$stmt = $CONNECT->prepare("SELECT * FROM ads WHERE id = ?");
$stmt->bind_param("i", $ad_id);
$stmt->execute();
$ad_result = $stmt->get_result();


if (!$ad_result) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Query failed', 'mysqli_error' => mysqli_error($CONNECT)]);
    exit();
}

$ad = mysqli_fetch_assoc($ad_result);

if (!$ad) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Ad not found', 'ad_id' => $ad_id]);
    exit();
}

$seller_id = $ad['user_id'];
$buyer_id = $ad['buyer_id'];
$sender_id = $_SESSION['user_id'];
$recipient_id = ($sender_id == $seller_id) ? $buyer_id : $seller_id;

// Получение статуса сделки из таблицы escrow_deposits
$stmt = $CONNECT->prepare("SELECT status FROM escrow_deposits WHERE ad_id = ?");
$stmt->bind_param("i", $ad_id);
$stmt->execute();
$escrow_result = $stmt->get_result();
$escrow = mysqli_fetch_assoc($escrow_result);
$escrow_status = $escrow['status'];

$current_user_id = $_SESSION['user_id'];

$is_buyer = ($current_user_id == $ad['buyer_id']);
$is_seller = ($current_user_id == $ad['user_id']);

if ($is_buyer) {
    $current_user_role = 'buyer';
} elseif ($is_seller) {
    $current_user_role = 'seller';
} 

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    header('Content-Type: application/json');
    $rawInput = file_get_contents('php://input');
    $jsonrpc = json_decode($rawInput, true);

    if ($jsonrpc === null) {
        echo json_encode(['error' => 'Invalid JSON', 'rawInput' => $rawInput]);
        exit();
    }
    if (!isset($jsonrpc['params']['ad_id'])) {
        echo json_encode(['error' => 'ad_id is missing in params', 'jsonrpc' => $jsonrpc]);
        exit();
    }
    $ad_id = intval($jsonrpc['params']['ad_id']);
    if ($jsonrpc['method'] == 'sendMessage') {
        $message = htmlspecialchars($jsonrpc['params']['message'], ENT_QUOTES, 'UTF-8');
        $stmt = $CONNECT->prepare("INSERT INTO messages (ad_id, user_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $ad_id, $sender_id, $message);
        if ($stmt->execute()) {
            // Определяем, кто отправляет сообщение, и отправляем уведомление другому пользователю
            
            error_log("Sending notification to user ID: $recipient_id");
            add_notification($recipient_id, "Новое сообщение в чате по объявлению #$ad_id");
            echo json_encode(['result' => 'Message sent successfully']);
        } else {
            echo json_encode(['error' => 'Error: ' . mysqli_error($CONNECT)]);
        }
    } elseif ($jsonrpc['method'] == 'loadMessages') {
        $messages = mysqli_query($CONNECT, "SELECT * FROM messages WHERE ad_id = '$ad_id' ORDER BY created_at ASC");
        $response = [];
        while ($message = mysqli_fetch_assoc($messages)) {
            $username = ($message['user_id'] == $_SESSION['user_id']) ? 'You' : 'Not you';
            $response[] = ['username' => $username, 'message' => htmlspecialchars($message['message'])];
        }
        echo json_encode(['result' => $response]);
    } else {
        echo json_encode(['error' => 'Unknown method']);
    }
    exit();
} else {
    error_log("Invalid request method or content type: " . $_SERVER['REQUEST_METHOD'] . ", " . ($_SERVER['CONTENT_TYPE'] ?? 'undefined'));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trade Details</title>
    <link rel="stylesheet" href="../../css/styles.css">
    <style>
        .trade-details-table {
            width: 95%;
            margin: 0 auto;
            font-size: 95%;
        }
        .trade-details-table th, .trade-details-table td {
            font-size: 95%;
            height: auto;
        }
        .chat-box {
            border: 1px solid #ddd;
            padding: 10px;
            height: 200px; /* Увеличим высоту для лучшего отображения */
            overflow-y: scroll;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'pages/p2p/menu.php'; ?>
        <h2>Trade Details</h2>
        <table class="trade-details-table">
            <tr>
                <th>Trade ID</th>
                <td><?php echo htmlspecialchars($ad_id); ?></td>
            </tr>
            <tr>
                <th>Buyer ID</th>
                <td><?php echo htmlspecialchars($buyer_id); ?></td>
            </tr>
            <tr>
                <th>Seller ID</th>
                <td><?php echo htmlspecialchars($seller_id); ?></td>
            </tr>
            <tr>
                <th>BTC Amount</th>
                <td><?php echo htmlspecialchars($ad['amount_btc']); ?></td>
            </tr>
            <tr>
                <th>Fiat Amount</th>
                <td><?php echo number_format(htmlspecialchars($ad['rate'] * $ad['amount_btc']), 2, '.', ' '); ?></td>
            </tr>
            <tr>
                <th>Rate</th>
                <td><?php echo htmlspecialchars($ad['rate']); ?></td>
            </tr>
            <tr>
                <th>Payment Methods</th>
                <td>
                    <?php
                    $payment_methods_result = mysqli_query($CONNECT, "SELECT payment_method FROM ad_payment_methods WHERE ad_id = '$ad_id'");
                    $payment_methods = [];
                    while ($row = mysqli_fetch_assoc($payment_methods_result)) {
                        $payment_methods[] = $row['payment_method'];
                    }
                    echo htmlspecialchars(implode(', ', $payment_methods));
                    ?>
                </td>
            </tr>
            <tr>
                <th>Fiat Currency</th>
                <td><?php echo htmlspecialchars($ad['fiat_currency']); ?></td>
            </tr>
            <tr>
                <th>Trade Type</th>
                <td><?php echo htmlspecialchars($ad['trade_type'] == 'buy' ? 'Buy' : 'Sell'); ?></td>
            </tr>
            <tr>
                <th>Comment</th>
                <td><?php echo htmlspecialchars($ad['comment'] ?? ''); ?></td>
            </tr>
        </table>
		<div class="action-buttons">
            <?php
            switch ($escrow_status) {
                case 'btc_deposited':
                    if ($current_user_role === 'seller') {
                        echo '<button name="fiat_received">Подтвердить получение фиата</button>';
                    }
                    break;

                case 'fiat_paid':
                    if ($current_user_role === 'buyer') {
                        echo '<button name="release_btc">Подписать и завершить сделку</button>';
                    }
                    break;

                case 'disputed':
                    if ($current_user_role === 'admin') {
                        echo '<button name="resolve_dispute_buyer">Решить в пользу покупателя</button>';
                        echo '<button name="resolve_dispute_seller">Решить в пользу продавца</button>';
                    }
                    break;
            }
            ?>
        </div>
		
        <div class="chat">
            <h3>Transaction chat</h3>
            <div class="chat-box" id="chat-box"></div>
            <form id="chat-form">
                <input type="hidden" name="ad_id" value="<?php echo htmlspecialchars($ad_id); ?>">
                <input type="hidden" id="user-id" value="<?php echo htmlspecialchars($_SESSION['user_id']); ?>">
                <input type="hidden" id="recipient-id" value="<?php echo htmlspecialchars($recipient_id); ?>">

                <input type="text" name="message" placeholder="Type your message...">
                <button type="submit">Send</button>
            </form>
        </div>
    </div>

    <script>
	document.getElementById('cancel-trade').addEventListener('click', function() {
        if (confirm('Вы уверены, что хотите отменить сделку?')) {
            // AJAX request to cancel the trade
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'src/functions.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    alert('Сделка успешно отменена.');
                    window.location.reload();
                }
            };
            xhr.send(JSON.stringify({ ad_id: <?php echo $ad_id; ?> }));
        }
    });

    document.getElementById('confirm-payment').addEventListener('click', function() {
        if (confirm('Вы уверены, что хотите подтвердить оплату?')) {
            // AJAX request to confirm the payment
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'src/functions.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    alert('Оплата успешно подтверждена.');
                    window.location.reload();
                }
            };
            xhr.send(JSON.stringify({ ad_id: <?php echo $ad_id; ?> }));
        }
    });
	
    function displayMessage(username, message) {
        var chatBox = document.getElementById('chat-box');
        var messageElement = document.createElement('div');
        messageElement.textContent = username + ': ' + message;
        chatBox.appendChild(messageElement);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    document.addEventListener('DOMContentLoaded', function() {
        var sendMessageButton = document.querySelector("#chat-form button");
        var messageInput = document.querySelector("#chat-form input[name='message']");

        document.getElementById("chat-form").addEventListener("submit", function(event) {
            event.preventDefault(); // Останавливаем стандартное поведение формы

            var message = messageInput.value.trim();
            var recipientId = document.getElementById('recipient-id').value;
            var senderId = document.getElementById('user-id').value;

            console.log("Отправка сообщения: ", { senderId, recipientId, message }); // Дебаг

            if (!message || senderId == "0" || recipientId == "0") {
                console.error("Ошибка: Один из параметров пустой!", { senderId, recipientId, message });
                return;
            }

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'src/send_message.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    console.log("Ответ сервера:", xhr.responseText); // Дебаг ответа сервера
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                displayMessage('You', message);
                                messageInput.value = ""; // Очистка поля ввода
                            } else {
                                console.error("Ошибка сервера:", response.error);
                            }
                        } catch (e) {
                            console.error("Ошибка парсинга JSON:", e, xhr.responseText);
                        }
                    }
                }
            };
            xhr.send(JSON.stringify({
                ad_id: <?php echo htmlspecialchars($ad_id); ?>,
                sender_id: senderId,
                recipient_id: recipientId,
                message: message
            }));
        });

        // Загружаем сообщения при загрузке страницы
        function loadMessages() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'src/send_message.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.result) {
                                var chatBox = document.getElementById('chat-box');
                                chatBox.innerHTML = ''; // Очистить чат перед загрузкой сообщений
                                response.result.forEach(function(message) {
                                    displayMessage(message.username, message.message);
                                });
                            } else {
                                console.error("Ошибка загрузки сообщений:", response.error);
                            }
                        } catch (e) {
                            console.error("Ошибка парсинга JSON при загрузке сообщений:", e, xhr.responseText);
                        }
                    }
                }
            };
            xhr.send(JSON.stringify({
                ad_id: <?php echo htmlspecialchars($ad_id); ?>,
                method: 'loadMessages'
            }));
        }

        loadMessages(); // Загрузить сообщения при загрузке страницы

        // Обновляем сообщения каждые 5 секунд
        setInterval(loadMessages, 5000);

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
                params: { user_id: <?php echo $sender_id; ?> },
                id: 1
            }));
        }

        fetchUnreadNotificationCount();
        setInterval(fetchUnreadNotificationCount, 5000);
		
		// Функция для получения текущего статуса сделки
        function getEscrowStatus() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.status) {
                        document.getElementById('escrow-status').textContent = response.status;
                        // Обновление интерфейса в зависимости от статуса
                        // Например, отображение кнопок в зависимости от статуса
                        switch (response.status) {
                            case 'btc_deposited':
                                if (current_user_role === 'seller') {
                                    document.querySelector('.action-buttons').innerHTML = '<button name="fiat_received">Подтвердить получение фиата</button>';
                                }
                                break;

                            case 'fiat_paid':
                                if (current_user_role === 'buyer') {
                                    document.querySelector('.action-buttons').innerHTML = '<button name="release_btc">Подписать и завершить сделку</button>';
                                }
                                break;

                            case 'disputed':
                                if (current_user_role === 'admin') {
                                    document.querySelector('.action-buttons').innerHTML = '<button name="resolve_dispute_buyer">Решить в пользу покупателя</button><button name="resolve_dispute_seller">Решить в пользу продавца</button>';
                                }
                                break;
                        }
                    } else {
                        console.error("Ошибка получения статуса сделки:", response.error);
                    }
                }
            };
            xhr.send(JSON.stringify({
                ad_id: <?php echo htmlspecialchars($ad_id); ?>,
                method: 'getEscrowStatus'
            }));
        }

        setInterval(getEscrowStatus, 5000); // Обновление статуса сделки каждые 5 секунд
    });
	
	
    </script>

</body>
</html>
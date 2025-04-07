<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'src/functions.php';

$ad_id = null;
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

$ad = get_ad_info($ad_id);
if (isset($ad['error'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $ad['error'], 'mysqli_error' => $ad['mysqli_error'] ?? '']);
    exit();
}

$seller_id = $ad['user_id'];
$buyer_id = $ad['buyer_id'];
$sender_id = $_SESSION['user_id'];
$recipient_id = ($sender_id == $seller_id) ? $buyer_id : $seller_id;

$escrow_status_json = get_escrow_status($ad_id);
$escrow_status = json_decode($escrow_status_json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON decode error: " . json_last_error_msg());
    $status = 'unknown';
} elseif (isset($escrow_status['status'])) {
    $status = $escrow_status['status'];
} else {
    $status = 'unknown';
}

$current_user_id = $_SESSION['user_id'];
$is_buyer = ($current_user_id == $ad['buyer_id']);
$is_seller = ($current_user_id == $ad['user_id']);
$current_user_role = $is_buyer ? 'buyer' : ($is_seller ? 'seller' : '');

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
        <p id="escrow-status"></p> <!-- Добавленный элемент для отображения статуса сделки -->
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
            switch ($status) {
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
    document.addEventListener('DOMContentLoaded', function() {
        var cancelTradeButton = document.getElementById('cancel-trade');
        var confirmPaymentButton = document.getElementById('confirm-payment');
        var current_user_role = "<?php echo $current_user_role; ?>"; // Добавляем инициализацию переменной

        if (cancelTradeButton) {
            cancelTradeButton.addEventListener('click', function() {
                if (confirm('Вы уверены, что хотите отменить сделку?')) {
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
        }

        if (confirmPaymentButton) {
            confirmPaymentButton.addEventListener('click', function() {
                if (confirm('Вы уверены, что хотите подтвердить оплату?')) {
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
        }

        function displayMessage(username, message) {
            var chatBox = document.getElementById('chat-box');
            var messageElement = document.createElement('div');
            messageElement.textContent = username + ': ' + message;
            chatBox.appendChild(messageElement);
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        document.getElementById("chat-form").addEventListener("submit", function(event) {
            event.preventDefault();

            var messageInput = document.querySelector("#chat-form input[name='message']");
            var message = messageInput.value.trim();
            var recipientId = document.getElementById('recipient-id').value;
            var senderId = document.getElementById('user-id').value;

            console.log("Отправка сообщения: ", { senderId, recipientId, message });

            if (!message || senderId == "0" || recipientId == "0") {
                console.error("Ошибка: Один из параметров пустой!", { senderId, recipientId, message });
                return;
            }

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'src/send_message.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    console.log("Ответ сервера:", xhr.responseText);
                    if (xhr.status === 200) {
                        try {
                            if (xhr.responseText) {
                                var response = JSON.parse(xhr.responseText);
                                if (response.success) {
                                    displayMessage('You', message);
                                    messageInput.value = "";
                                } else {
                                    console.error("Ошибка сервера:", response.error);
                                }
                            } else {
                                console.error("Пустой ответ сервера");
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

        function loadMessages() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'src/send_message.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            if (xhr.responseText) {
                                var response = JSON.parse(xhr.responseText);
                                if (response.result) {
                                    var chatBox = document.getElementById('chat-box');
                                    chatBox.innerHTML = '';
                                    response.result.forEach(function(message) {
                                        displayMessage(message.username, message.message);
                                    });
                                } else {
                                    console.error("Ошибка загрузки сообщений:", response.error);
                                }
                            } else {
                                console.error("Пустой ответ сервера");
                            }
                        } catch (e) {
                            console.error("Ошибка парсинга JSON при загрузке сообщений:", e, xhr.responseText);
                        }
                    } else {
                        console.error("Ошибка загрузки сообщений:", xhr.statusText);
                    }
                }
            };
            xhr.send(JSON.stringify({
                ad_id: <?php echo htmlspecialchars($ad_id); ?>,
                method: 'loadMessages'
            }));
        }

        loadMessages();
        setInterval(loadMessages, 5000);

          function fetchUnreadNotificationCount() {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/src/jsonrpc.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                console.log("Ответ сервера при получении количества непрочитанных уведомлений:", xhr.responseText);
                if (xhr.status === 200) {
                    try {
                        if (xhr.responseText) {
                            var response = JSON.parse(xhr.responseText);
                            if (response.result) {
                                var count = response.result.count;
                                document.getElementById('notification-count').textContent = count;
                            } else if (response.error) {
                                console.error("Error: " + response.error.message);
                            }
                        } else {
                            console.error("Пустой ответ сервера");
                        }
                    } catch (e) {
                        console.error("Ошибка парсинга JSON:", e);
                        console.error("Response:", xhr.responseText);
                    }
                } else {
                    console.error("Request failed with status:", xhr.statusText);
                }
            }
        };
        xhr.onerror = function() {
            console.error("Request failed");
        };
        xhr.send(JSON.stringify({
            jsonrpc: "2.0",
            method: "getUnreadNotificationCount",
            params: { "user_id": <?php echo $sender_id; ?> },
            id: 1
        }));
    }

    fetchUnreadNotificationCount();
    setInterval(fetchUnreadNotificationCount, 5000);
        
        function getEscrowStatus() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'src/functions.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    console.log("Ответ сервера при получении статуса сделки:", xhr.responseText);
                    if (xhr.status === 200) {
                        try {
                            if (xhr.responseText) {
                                var response = JSON.parse(xhr.responseText);
                                if (response.status) {
                                    document.getElementById('escrow-status').textContent = response.status;
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
                            } else {
                                console.error("Пустой ответ сервера");
                            }
                        } catch (e) {
                            console.error("Ошибка парсинга JSON:", e, xhr.responseText);
                        }
                    } else {
                        console.error("Ошибка получения статуса сделки:", xhr.statusText);
                    }
                }
            };
            xhr.send(JSON.stringify({
                ad_id: <?php echo htmlspecialchars($ad_id); ?>,
                method: 'getEscrowStatus'
            }));
        }

        setInterval(getEscrowStatus, 5000);
    });
    </script>

</body>
</html>
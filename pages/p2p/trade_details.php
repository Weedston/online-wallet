<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Проверка токена авторизации
if (!isset($_SESSION['user_id'], $_SESSION['token'])) {
    header("Location: /");
    exit();
}

$stmt = $CONNECT->prepare("SELECT session_token FROM members WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($storedToken);
$stmt->fetch();
$stmt->close();

if ($_SESSION['token'] !== $storedToken) {
    // Токен не совпадает — сессия недействительна
    session_destroy();
    header("Location: /");
    exit();
}

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

require_once 'src/functions.php';


$current_user_id = $_SESSION['user_id'];
$ad_id = isset($_GET['ad_id']) ? intval($_GET['ad_id']) : 0;

if ($ad_id === 0) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'text' => 'Invalid ID.'
    ];
    header('Location: /p2p'); // Или куда перенаправлять в случае ошибки
    exit();
}

$stmt = $CONNECT->prepare("SELECT * FROM ads WHERE id = ?");
$stmt->bind_param("i", $ad_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'text' => 'Сделка не найдена.'
    ];
    header('Location: /p2p');
    exit();
}

$ad = $result->fetch_assoc();

if (
    $ad['user_id'] != $current_user_id &&
    $ad['buyer_id'] != $current_user_id &&
    $ad['seller_id'] != $current_user_id
) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'text' => "У вас нет доступа к этой сделке. Ваш ID: {$current_user_id}, buyer ID: {$ad['buyer_id']}, seller ID: {$ad['seller_id']}"
    ];
    header('Location: /p2p');
    exit();
}

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

$seller_id = $ad['seller_id'];
$buyer_id = $ad['buyer_id'];
$sender_id = $_SESSION['user_id'];
$recipient_id = ($sender_id == $seller_id) ? $buyer_id : $seller_id;

$escrow_status = get_escrow_status($ad_id); // уже массив

if (isset($escrow_status['status'])) {
    $status = $escrow_status['status'];
} else {
    $status = 'unknown';
}

$txid_confirmations = NULL;
$query_tx = mysqli_query($CONNECT, "SELECT txid FROM escrow_deposits WHERE ad_id = '$ad_id'");
$row = mysqli_fetch_assoc($query_tx);
$txid_confirmations = $row['txid'];

$current_user_id = $_SESSION['user_id'];
$current_user_role = getUserRole($ad, $current_user_id);

/*
// Определим buyer_id и seller_id на основе trade_type
if ($ad['trade_type'] === 'buy') {
    $buyer_id = $ad['user_id'];
    $seller_id = $ad['buyer_id']; // он будет заполнен после принятия
} elseif ($ad['trade_type'] === 'sell') {
    $seller_id = $ad['user_id'];
    $buyer_id = $ad['buyer_id']; // он будет заполнен после принятия
}
*/
$buyer_id_display = ($buyer_id == $current_user_id) ? "$buyer_id (You)" : $buyer_id;
$seller_id_display = ($seller_id == $current_user_id) ? "$seller_id (You)" : $seller_id;
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
            height: 200px; 
            overflow-y: scroll;
            margin-bottom: 20px;
        }
    </style>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>
<body>
    <div class="container">
        <?php include 'pages/p2p/menu.php'; ?>
        <h2>Trade Details</h2>
        <p id="escrow-status"></p> 
        <table class="trade-details-table">
            <tr>
                <th>Trade ID</th>
                <td><?php echo htmlspecialchars($ad_id); ?></td>
            </tr>
            <tr>
                <th>Buyer ID</th>
                <td><?php echo htmlspecialchars($buyer_id_display); ?></td>
            </tr>
            <tr>
                <th>Seller ID</th>
                <td><?php echo htmlspecialchars($seller_id_display); ?></td>
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
            <tr>
				<th>Deposit status</th>
				<td id="ad-status">Loading...</td>
			</tr>
        </table>
		<div id="service-log">
		
		</div>
		
		<div class="action-buttons"></div>

		
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
        var current_user_role = "<?php echo $current_user_role; ?>"; 

        if (cancelTradeButton) {
            cancelTradeButton.addEventListener('click', function() {
                if (confirm('Are you sure you want to cancel the deal?')) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'src/functions.php', true);
                    xhr.setRequestHeader('Content-Type', 'application/json');
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4 && xhr.status === 200) {
                            alert('The transaction was successfully cancelled.');
                            window.location.reload();
                        }
                    };
                    xhr.send(JSON.stringify({ ad_id: <?php echo $ad_id; ?> }));
                }
            });
        }

        if (confirmPaymentButton) {
            confirmPaymentButton.addEventListener('click', function() {
                if (confirm('Are you sure you want to confirm the payment?')) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'src/functions.php', true);
                    xhr.setRequestHeader('Content-Type', 'application/json');
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4 && xhr.status === 200) {
                            alert('The payment has been successfully confirmed.');
                            window.location.reload();
                        }
                    };
                    xhr.send(JSON.stringify({ ad_id: <?php echo $ad_id; ?> }));
                }
            });
        }

        
        document.getElementById("chat-form").addEventListener("submit", function(event) {
            event.preventDefault();

            var messageInput = document.querySelector("#chat-form input[name='message']");
            var message = messageInput.value.trim();
            var recipientId = document.getElementById('recipient-id').value;
            var senderId = document.getElementById('user-id').value;


            if (!message || senderId == "0" || recipientId == "0") {
                return;
            }

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'src/send_message.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            if (xhr.responseText) {
                                var response = JSON.parse(xhr.responseText);
                                if (response.success) {
                                    
                                    messageInput.value = "";
                                } else {
                                }
                            } else {
                            }
                        } catch (e) {
                        }
                    }
                }
            };
            xhr.send(JSON.stringify({
                jsonrpc: "2.0",
                method: "send_message",
                params: {
                    ad_id: <?php echo htmlspecialchars($ad_id); ?>,
                    sender_id: senderId,
                    recipient_id: recipientId,
                    message: message
                },
                id: 1
            }));
        });


function fetchServiceComments() {
    fetch('src/functions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            jsonrpc: '2.0',
            method: 'getServiceComments',
            params: {
                ad_id: <?= $ad_id ?>
            },
            id: 1
        })
    })
	

    .then(res => res.json())
    .then(data => {
        const comments = data.result || [];
        const container = document.getElementById('service-log');
        container.innerHTML = '';
		const header = document.createElement('h3');
		header.textContent = 'System Messages';
		container.appendChild(header);
        comments.forEach(entry => {
            const div = document.createElement('div');
            div.innerHTML = `<strong>[${entry.timestamp}]</strong> (${entry.type}): ${entry.message}`;
            container.appendChild(div);
        });
		checkConfirmations();
    });

}




         function checkConfirmations() {
        const txid = "<?php echo $txid_confirmations; ?>";

        if (!txid || txid.trim() === '') {
            document.getElementById('confirmationsResult').innerText = "TXID не указан.";
            return;
        }

        fetch('src/functions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                jsonrpc: "2.0",
                method: "getConfirmations",
                params: { txid: txid },
                id: 1
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                document.getElementById('confirmationsResult').innerText = 'Error: ' + data.error.message;
            } else {
                document.getElementById('confirmationsResult').innerText = 'Confirmations: ' + data.result.confirmations;
            }
        })
        .catch(error => {
            document.getElementById('confirmationsResult').innerText = 'Error: ' + error.message;
        });
    }

    
	fetchServiceComments();
	setInterval(fetchServiceComments, 5000);
	


let lastMessageId = 0; // 

function loadMessages() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'src/jsonrpc.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.result) {
                    var messages = response.result;
                    let newLastMessageId = lastMessageId; 
                    messages.forEach(function(message) {
                        
                        if (message.id > lastMessageId) {
                            displayMessage(message.username, message.message);
                            newLastMessageId = Math.max(newLastMessageId, message.id); 
                        }
                    });
                    lastMessageId = newLastMessageId; 
                } else if (response.error) {
                }
            } catch (e) {
            }
        }
    };
    xhr.onerror = function() {
        
    };
    xhr.send(JSON.stringify({
        jsonrpc: "2.0",
        method: "loadMessages",
        params: { ad_id: <?php echo $ad_id; ?> },
        id: 1
    }));
}

function displayMessage(username, message) {
    var chatBox = document.getElementById('chat-box');
    if (chatBox) {
        var messageElement = document.createElement('div');
        messageElement.textContent = username + ': ' + message;
        chatBox.appendChild(messageElement);
        chatBox.scrollTop = chatBox.scrollHeight;
    }
}

// Загружаем сообщения с интервалом 5 секунд
loadMessages();
setInterval(loadMessages, 2000);

		
        function getEscrowStatus() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'src/functions.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.result) {
                                document.getElementById('ad-status').textContent = response.result.status;
								const rawStatus = response.result.raw_status;
								const buyer_Confirmed = parseInt(response.result.buyer_confirmed);
								const seller_Confirmed = parseInt(response.result.seller_confirmed);
								const userRole = "<?php echo $current_user_role; ?>"; 
								const adId = "<?php echo $ad_id; ?>";
							let buttonsHtml = "";
							switch (rawStatus) {
								case 'btc_deposited':
																	
									if (userRole === 'buyer') {
										buttonsHtml = `
										<form method="POST" action="src/confirm_fiat_paid.php">
											<input type="hidden" name="ad_id" value="${adId}">
											<button type="submit" name="fiat_paid" class="btn btn-success">I paid with a fiat</button>
										</form>
										`;
									}
								break;
								
								case 'fiat_paid':
    if (userRole === 'buyer') {
        if (response.result.seller_confirmed === 1) {
            
            buttonsHtml = `
            
            `;
        } else {
            buttonsHtml = `<p class="text-muted">Confirmation of the fiat from the seller is expected</p>`;
        }
    } 

    if (userRole === 'seller') {
        buttonsHtml = `
        <form method="POST" action="src/confirm_fiat_payment.php">
            <input type="hidden" name="ad_id" value="${adId}">
            <button type="submit" name="fiat_received" class="btn btn-success">Confirm receipt of the fiat</button>
        </form>
        `;

        if (response.result.buyer_confirmed === 1 && response.result.seller_confirmed === 0) {
            buttonsHtml += `
            <form method="POST" action="src/resolve_dispute.php">
                <input type="hidden" name="ad_id" value="${adId}">
                            </form>
            `;
        }
    }
                                    
								break;
								case 'disputed':
									if (userRole === 'admin') {
										buttonsHtml = `
										<form method="POST" action="src/resolve_dispute.php">
											<input type="hidden" name="ad_id" value="${adId}">
											<button type="submit" name="resolve_dispute_buyer" class="btn btn-warning">Решить в пользу покупателя</button>
											<button type="submit" name="resolve_dispute_seller" class="btn btn-warning">Решить в пользу продавца</button>
										</form>
										`;
								}
								break;
						}
						document.querySelector('.action-buttons').innerHTML = buttonsHtml;
                            } else if (response.error) {
                            }
                        } catch (e) {
                        }
                    } else {
                    }
                }
            };
            xhr.onerror = function() {
            };

			xhr.send(JSON.stringify({
                jsonrpc: "2.0",
                method: "getEscrowStatus",
                params: { ad_id: "<?php echo $ad_id; ?>" },
                id: 1
            }));
        }
		getEscrowStatus();
        setInterval(getEscrowStatus, 5000);
    });
	
	
    </script>

</body>
</html>
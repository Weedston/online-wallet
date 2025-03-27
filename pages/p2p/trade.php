<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$CONNECT = mysqli_connect(HOST, USER, PASS, DB);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accept_ad"])) {
    $user_id = $_SESSION['user_id'];
    $ad_id = intval($_POST['ad_id']);

    // Обновляем статус объявления на "completed"
    $query = "UPDATE ads SET status = 'completed' WHERE id = '$ad_id'";

    if (mysqli_query($CONNECT, $query)) {
        echo "<p style='color:green;'>Ad accepted successfully!</p>";
    } else {
        echo "<p style='color:red;'>Error: " . mysqli_error($CONNECT) . "</p>";
    }

    // Создаем запись транзакции
    $ad = mysqli_fetch_assoc(mysqli_query($CONNECT, "SELECT * FROM ads WHERE id = '$ad_id'"));
    $seller_id = $ad['user_id'];
    $btc_amount = $ad['amount_btc'];
    $fiat_amount = $ad['rate'] * $btc_amount;

    $query = "INSERT INTO transactions (ad_id, buyer_id, seller_id, btc_amount, fiat_amount, status) VALUES ('$ad_id', '$user_id', '$seller_id', '$btc_amount', '$fiat_amount', 'pending')";

    if (mysqli_query($CONNECT, $query)) {
        echo "<p style='color:green;'>Transaction created successfully!</p>";
    } else {
        echo "<p style='color:red;'>Error: " . mysqli_error($CONNECT) . "</p>";
    }

    header("Location: trade.php?ad_id=$ad_id");
    exit();
}

// Получаем детали сделки
$ad_id = intval($_GET['ad_id']);
$ad = mysqli_fetch_assoc(mysqli_query($CONNECT, "SELECT ads.*, members.username FROM ads JOIN members ON ads.user_id = members.id WHERE ads.id = '$ad_id'"));

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trade BTC</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
    <?php include 'pages/p2p/menu.php'; ?>
    <div class="container">
        <h2>Trade Details</h2>
        <p><strong>Seller:</strong> <?php echo htmlspecialchars($ad['username']); ?></p>
        <p><strong>BTC Amount:</strong> <?php echo htmlspecialchars($ad['amount_btc']); ?></p>
        <p><strong>Rate:</strong> <?php echo htmlspecialchars($ad['rate']); ?></p>
        <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($ad['payment_method']); ?></p>
        
        <h3>Chat with Seller</h3>
        <div id="chat-box">
            <!-- Chat messages will be displayed here -->
        </div>
        <form id="chat-form">
            <input type="text" id="chat-message" placeholder="Type your message">
            <button type="submit">Send</button>
        </form>
    </div>

    <script>
        const chatForm = document.getElementById('chat-form');
        const chatBox = document.getElementById('chat-box');
        const chatMessage = document.getElementById('chat-message');

        chatForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const message = chatMessage.value;
            if (message.trim() === '') return;

            // Добавление сообщения в чат
            const messageElement = document.createElement('p');
            messageElement.textContent = message;
            chatBox.appendChild(messageElement);

            chatMessage.value = '';
        });
    </script>
</body>
</html>
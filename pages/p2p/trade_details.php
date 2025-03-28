<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$CONNECT = mysqli_connect(HOST, USER, PASS, DB);

if (!$CONNECT) {
    die('Connection failed: ' . mysqli_connect_error());
}

$trade_id = intval($_GET['trade_id']);
$trade = mysqli_fetch_assoc(mysqli_query($CONNECT, "SELECT * FROM transactions WHERE id = '$trade_id'"));

if (!$trade) {
    echo "<p style='color:red;'>Trade not found</p>";
    exit();
}

$ad = mysqli_fetch_assoc(mysqli_query($CONNECT, "SELECT * FROM ads WHERE id = '{$trade['ad_id']}'"));
$seller = mysqli_fetch_assoc(mysqli_query($CONNECT, "SELECT * FROM members WHERE id = '{$trade['seller_id']}'"));

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trade Details</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
    <div class="container">
        <?php include 'menu.php'; ?>
        <h2>Trade Details</h2>
        <div class="trade-info">
            <p><strong>Trade ID:</strong> <?php echo htmlspecialchars($trade_id); ?></p>
            <p><strong>User ID:</strong> <?php echo htmlspecialchars($trade['buyer_id']); ?></p>
            <p><strong>Seller ID:</strong> <?php echo htmlspecialchars($trade['seller_id']); ?></p>
            <p><strong>BTC Amount:</strong> <?php echo htmlspecialchars($trade['btc_amount']); ?></p>
            <p><strong>Fiat Amount:</strong> <?php echo htmlspecialchars($trade['fiat_amount']); ?></p>
            <p><strong>Rate:</strong> <?php echo htmlspecialchars($ad['rate']); ?></p>
            <p><strong>Payment Methods:</strong> 
                <?php
                $payment_methods_result = mysqli_query($CONNECT, "SELECT payment_method FROM ad_payment_methods WHERE ad_id = '{$trade['ad_id']}'");
                $payment_methods = [];
                while ($row = mysqli_fetch_assoc($payment_methods_result)) {
                    $payment_methods[] = $row['payment_method'];
                }
                echo htmlspecialchars(implode(', ', $payment_methods));
                ?>
            </p>
            <p><strong>Fiat Currency:</strong> <?php echo htmlspecialchars($ad['fiat_currency']); ?></p>
            <p><strong>Trade Type:</strong> <?php echo htmlspecialchars($ad['trade_type'] == 'buy' ? 'Buy' : 'Sell'); ?></p>
            <p><strong>Comment:</strong> <?php echo htmlspecialchars($ad['comment']); ?></p>
        </div>
        <div class="chat">
            <h3>Chat with Seller</h3>
            <div class="chat-box" id="chat-box"></div>
            <form id="chat-form">
                <input type="hidden" name="trade_id" value="<?php echo htmlspecialchars($trade_id); ?>">
                <input type="text" name="message" placeholder="Type your message...">
                <button type="submit">Send</button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('chat-form').addEventListener('submit', function(e) {
            e.preventDefault();
            var message = this.message.value;
            if (message.trim() === '') return;

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'send_message.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var chatBox = document.getElementById('chat-box');
                    chatBox.innerHTML += '<p>' + message + '</p>';
                    chatBox.scrollTop = chatBox.scrollHeight;
                }
            };
            xhr.send('trade_id=' + <?php echo htmlspecialchars($trade_id); ?> + '&message=' + encodeURIComponent(message));
            this.message.value = '';
        });

        function loadMessages() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'load_messages.php?trade_id=' + <?php echo htmlspecialchars($trade_id); ?>, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById('chat-box').innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }

        setInterval(loadMessages, 3000);
        loadMessages();
    </script>
</body>
</html>
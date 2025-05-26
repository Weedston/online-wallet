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


$CONNECT = mysqli_connect(HOST, USER, PASS, DB);

$user_id = $_SESSION['user_id'];
// Получение сделок, где текущий пользователь является создателем или покупателем
$trades = mysqli_query($CONNECT, "
    SELECT ads.*, members.id AS user_id 
    FROM ads 
    JOIN members ON ads.user_id = members.id 
    WHERE (ads.status IN ('pending', 'completed')) 
    AND (ads.user_id = '$user_id' OR ads.buyer_id = '$user_id' OR ads.seller_id = '$user_id')
    ORDER BY ads.updated_at DESC
");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($translations['p2p_history_title']) ?></title>
    <link rel="stylesheet" href="../../css/styles.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr.clickable {
            cursor: pointer;
        }
    </style>
    <script>
        function goToTradeDetails(ad_id) {
            window.location.href = `p2p-trade_details?ad_id=${ad_id}`;
        }
    </script>
</head>
<body>
    <div class="container">
        <?php include 'pages/p2p/menu.php'; ?>
        <h2><?= htmlspecialchars($translations['p2p_history_title']) ?></h2>
        <table>
            <thead>
                <tr>
                    <th><?= htmlspecialchars($translations['p2p_history_tradeid']) ?></th>
                    <th><?= htmlspecialchars($translations['p2p_history_userid']) ?></th>
                    <th><?= htmlspecialchars($translations['p2p_history_btcam']) ?></th>
                    <th><?= htmlspecialchars($translations['p2p_history_fiatam']) ?></th>
                    <th><?= htmlspecialchars($translations['p2p_history_rate']) ?></th>
                    <th><?= htmlspecialchars($translations['p2p_history_tradetype']) ?></th>
                    <th><?= htmlspecialchars($translations['p2p_history_status']) ?></th>
                    <th><?= htmlspecialchars($translations['p2p_history_update']) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php while ($trade = mysqli_fetch_assoc($trades)) { 
                    $fiat_amount = $trade['amount_btc'] * $trade['rate'];
                ?>
                    <tr class="<?php echo $trade['status'] == 'pending' ? 'clickable' : ''; ?>" onclick="<?php echo $trade['status'] == 'pending' ? 'goToTradeDetails(' . htmlspecialchars($trade['id']) . ')' : ''; ?>">
                        <td><?php echo htmlspecialchars($trade['id']); ?></td>
                        <td><?php echo htmlspecialchars($trade['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($trade['amount_btc']); ?></td>
                        <td><?php echo number_format($fiat_amount, 2, '.', ' '); ?> <?php echo htmlspecialchars($trade['fiat_currency']); ?></td>
                        <td><?php echo htmlspecialchars($trade['rate']); ?></td>
                        <td><?php echo htmlspecialchars($trade['trade_type'] == 'buy' ? 'Buy' : 'Sell'); ?></td>
                        <td><?php echo htmlspecialchars($trade['status']); ?></td>
                        <td><?php echo htmlspecialchars($trade['updated_at']); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</body>
</html>
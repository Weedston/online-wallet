<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
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
    AND (ads.user_id = '$user_id' OR ads.buyer_id = '$user_id')
    ORDER BY ads.updated_at DESC
");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trade History</title>
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
        <h2>Trade History</h2>
        <table>
            <thead>
                <tr>
                    <th>Trade ID</th>
                    <th>User ID</th>
                    <th>BTC Amount</th>
                    <th>Fiat Amount</th>
                    <th>Rate</th>
                    <th>Trade Type</th>
                    <th>Status</th>
                    <th>Updated At</th>
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
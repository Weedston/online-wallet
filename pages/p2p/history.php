<?php

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$CONNECT = mysqli_connect(HOST, USER, PASS, DB);

$user_id = $_SESSION['user_id'];

// Получение истории обменов пользователя
$transactions = mysqli_query($CONNECT, "SELECT transactions.*, ads.rate, ads.payment_method, members.username AS seller_username FROM transactions JOIN ads ON transactions.ad_id = ads.id JOIN members ON transactions.seller_id = members.id WHERE transactions.buyer_id = '$user_id'");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>P2P Exchange History</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
    <?php include 'pages/p2p/menu.php'; ?>
    <div class="container">
        <h2>P2P Exchange History</h2>
        <table>
            <thead>
                <tr>
                    <th>Seller Username</th>
                    <th>BTC Amount</th>
                    <th>Fiat Amount</th>
                    <th>Rate</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($transaction = mysqli_fetch_assoc($transactions)) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($transaction['seller_username']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['btc_amount']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['fiat_amount']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['rate']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['payment_method']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['status']); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</body>
</html>
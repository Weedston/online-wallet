<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$CONNECT = mysqli_connect(HOST, USER, PASS, DB);

// Получение всех активных объявлений
$ads = mysqli_query($CONNECT, "SELECT ads.*, members.username FROM ads JOIN members ON ads.user_id = members.id WHERE ads.status = 'active'");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>P2P Exchange BTC to Fiat</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
    <div class="container">
        <?php include 'pages/p2p/menu.php'; ?>
		<h2>Active P2P Exchange Ads</h2>
        <table>
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>BTC Amount</th>
                    <th>Rate</th>
                    <th>Payment Method</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($ad = mysqli_fetch_assoc($ads)) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ad['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($ad['amount_btc']); ?></td>
                        <td><?php echo htmlspecialchars($ad['rate']); ?></td>
                        <td><?php echo htmlspecialchars($ad['payment_method']); ?></td>
                        <td>
                            <form method="POST" action="process_offer.php">
                                <input type="hidden" name="ad_id" value="<?php echo $ad['id']; ?>">
                                <button type="submit" name="accept_ad" class="btn">Accept</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</body>
</html>
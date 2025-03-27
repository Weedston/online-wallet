<?php

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$CONNECT = mysqli_connect(HOST, USER, PASS, DB);

$user_id = $_SESSION['user_id'];

// Получение информации о пользователе
$user = mysqli_fetch_assoc(mysqli_query($CONNECT, "SELECT * FROM members WHERE id = '$user_id'"));

// Получение объявлений пользователя
$ads = mysqli_query($CONNECT, "SELECT * FROM ads WHERE user_id = '$user_id'");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
    <?php include 'pages/p2p/menu.php'; ?>
    <div class="container">
        <h2>User Profile</h2>
        <p><strong>Your ID:</strong> <?php echo htmlspecialchars($user_id); ?></p>
        <p><strong>Wallet:</strong> <?php echo htmlspecialchars($user['wallet']); ?></p>
        <p><strong>Balance:</strong> <?php echo htmlspecialchars($user['balance']); ?></p>

        <h2>Your Ads</h2>
        <table>
            <thead>
                <tr>
                    <th>BTC Amount</th>
                    <th>Rate</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($ad = mysqli_fetch_assoc($ads)) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ad['amount_btc']); ?></td>
                        <td><?php echo htmlspecialchars($ad['rate']); ?></td>
                        <td><?php echo htmlspecialchars($ad['payment_method']); ?></td>
                        <td><?php echo htmlspecialchars($ad['status']); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</body>
</html>
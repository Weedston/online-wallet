<?php

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$CONNECT = mysqli_connect(HOST, USER, PASS, DB);


// Обработка формы создания заявки
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["create_offer"])) {
    $user_id = $_SESSION['user_id'];
    $btc_amount = floatval($_POST['btc_amount']);
    $fiat_amount = floatval($_POST['fiat_amount']);
    $fiat_currency = FormChars($_POST['fiat_currency']);
    $payment_method = FormChars($_POST['payment_method']);
    $status = 'open';

    $query = "INSERT INTO p2p_offers (user_id, btc_amount, fiat_amount, fiat_currency, payment_method, status) VALUES ('$user_id', '$btc_amount', '$fiat_amount', '$fiat_currency', '$payment_method', '$status')";

    if (mysqli_query($CONNECT, $query)) {
        echo "<p style='color:green;'>Offer created successfully!</p>";
    } else {
        echo "<p style='color:red;'>Error: " . mysqli_error($CONNECT) . "</p>";
    }
}

// Получение всех открытых заявок
$offers = mysqli_query($CONNECT, "SELECT * FROM p2p_offers WHERE status = 'open'");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>P2P Exchange BTC to Fiat</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <a href="../dashboard.php" class="btn">Back to Dashboard</a>
    <div class="container">
        <h2>Create a P2P Exchange Offer</h2>
        <form method="POST">
            <label for="btc_amount">BTC Amount:</label>
            <input type="number" name="btc_amount" id="btc_amount" step="0.00000001" required>

            <label for="fiat_amount">Fiat Amount:</label>
            <input type="number" name="fiat_amount" id="fiat_amount" required>

            <label for="fiat_currency">Fiat Currency:</label>
            <input type="text" name="fiat_currency" id="fiat_currency" required>

            <label for="payment_method">Payment Method:</label>
            <input type="text" name="payment_method" id="payment_method" required>

            <button type="submit" name="create_offer" class="btn">Create Offer</button>
        </form>

        <h2>Open P2P Exchange Offers</h2>
        <table>
            <thead>
                <tr>
                    <th>BTC Amount</th>
                    <th>Fiat Amount</th>
                    <th>Fiat Currency</th>
                    <th>Payment Method</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($offer = mysqli_fetch_assoc($offers)) { ?>
                    <tr>
                        <td><?php echo $offer['btc_amount']; ?></td>
                        <td><?php echo $offer['fiat_amount']; ?></td>
                        <td><?php echo $offer['fiat_currency']; ?></td>
                        <td><?php echo $offer['payment_method']; ?></td>
                        <td>
                            <form method="POST" action="process_offer.php">
                                <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                <button type="submit" name="accept_offer" class="btn">Accept</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</body>
</html>
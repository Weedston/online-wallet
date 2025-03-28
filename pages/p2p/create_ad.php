<?php

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$CONNECT = mysqli_connect(HOST, USER, PASS, DB);

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

function getBtcRates() {
    $apiUrl = 'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=usd,eur,rub';
    $response = file_get_contents($apiUrl);
    if ($response === FALSE) {
        return null;
    }

    $data = json_decode($response, true);
    if (isset($data['bitcoin'])) {
        return $data['bitcoin'];
    }

    return null;
}

// Извлечение доступных фиатных валют
$fiat_currencies = mysqli_query($CONNECT, "SELECT * FROM fiat_currencies");

// Извлечение доступных методов оплаты
$payment_methods_result = mysqli_query($CONNECT, "SELECT method_name FROM payment_methods");
$payment_methods = [];
while ($row = mysqli_fetch_assoc($payment_methods_result)) {
    $payment_methods[] = $row['method_name'];
}

// Получение курсов BTC
$btcRates = getBtcRates();

// Обработка формы создания объявления
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["create_ad"])) { 
    $user_id = $_SESSION['user_id'];
    $amount_btc = number_format(floatval($_POST['amount_btc']), 8, '.', '');
    $rate = number_format(floatval($_POST['rate']), 2, '.', '');
    $payment_methods_selected = $_POST['payment_methods'];
    $fiat_currency = htmlspecialchars($_POST['fiat_currency'], ENT_QUOTES, 'UTF-8');
    $trade_type = htmlspecialchars($_POST['trade_type'], ENT_QUOTES, 'UTF-8');
    $status = 'active';
    
    if (!$CONNECT) {
        echo "<script>alert('Connection failed: " . mysqli_connect_error() . "'); window.location.href='create_ad.php';</script>";
        exit();
    }

    // Подготовленный запрос без payment_method
    $query = "INSERT INTO ads (user_id, amount_btc, rate, fiat_currency, trade_type, status) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($CONNECT, $query);

    if ($stmt) {
        // Привязка параметров: "i" - integer, "d" - double, "s" - string
        mysqli_stmt_bind_param($stmt, "iddsss", $user_id, $amount_btc, $rate, $fiat_currency, $trade_type, $status);

        if (mysqli_stmt_execute($stmt)) {
            $ad_id = mysqli_insert_id($CONNECT);
            foreach ($payment_methods_selected as $method) {
                $method = mysqli_real_escape_string($CONNECT, $method);
                mysqli_query($CONNECT, "INSERT INTO ad_payment_methods (ad_id, payment_method) VALUES ('$ad_id', '$method')");
            }
            echo "<script>alert('Ad created successfully!');</script>";
        } else {
            echo "<script>alert('Error: " . mysqli_stmt_error($stmt) . "'); window.location.href='create_ad.php';</script>";
            exit();
        }

        mysqli_stmt_close($stmt);
    } else {
        echo "<script>alert('Error: " . mysqli_error($CONNECT) . "'); window.location.href='create_ad.php';</script>";
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create P2P Exchange Ad</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
<div class="container">
    <?php include 'pages/p2p/menu.php'; ?>
    <div class="container ad-container">
        <!-- Информационный блок о курсах BTC -->
        <div class="btc-price-box">
            <?php if ($btcRates) { ?>
                <p>Current BTC Rates:</p>
                <ul>
                    <li>1 BTC = <?php echo number_format($btcRates['usd'], 2, '.', ' '); ?> USD</li>
                    <li>1 BTC = <?php echo number_format($btcRates['eur'], 2, '.', ' '); ?> EUR</li>
                    <li>1 BTC = <?php echo number_format($btcRates['rub'], 2, '.', ' '); ?> RUB</li>
                </ul>
            <?php } else { ?>
                <p>Unable to fetch BTC rates at this time.</p>
            <?php } ?>
        </div>

        <!-- Форма создания объявления -->
        <div class="ad-form">
                <h2>Create a P2P Exchange Ad</h2>
                <form method="POST">
                    <!-- Поля формы -->
                    <p>
                    <label for="trade_type">Trade Type:</label>
                    <select name="trade_type" id="trade_type" required>
                        <option value="buy">Buy BTC</option>
                        <option value="sell">Sell BTC</option>
                    </select>
                    </p><p>
                    
                    <label for="amount_btc">Amount of BTC:</label>
                    <input type="number" name="amount_btc" id="amount_btc" step="0.00000001" required>
                    </p><p>
                    
                    <label for="rate">Rate (Fiat per BTC):</label>
                    <input type="number" name="rate" id="rate" step="0.01" required>
                    </p><p>
                    
                    <label for="fiat_currency">Fiat Currency:</label>
                    <select name="fiat_currency" id="fiat_currency" required>
                        <?php while ($currency = mysqli_fetch_assoc($fiat_currencies)) { ?>
                            <option value="<?php echo htmlspecialchars($currency['currency_code']); ?>">
                                <?php echo htmlspecialchars($currency['currency_name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                    </p><p>
                    
                    <label for="payment_methods">Payment Methods:</label>
                    <select name="payment_methods[]" id="payment_methods" multiple required>
                        <?php foreach ($payment_methods as $method) { ?>
                            <option value="<?php echo htmlspecialchars($method); ?>">
                                <?php echo htmlspecialchars($method); ?>
                            </option>
                        <?php } ?>
                    </select>
                    </p>
                    
                    <!-- Новый блок для отображения информации о сделке -->
                    <p id="trade_info" style="color: #FFD700;"></p>
                    
                    <button type="submit" name="create_ad" class="btn">Create Ad</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('trade_type').addEventListener('change', updateTradeInfo);
        document.getElementById('amount_btc').addEventListener('input', updateTradeInfo);
        document.getElementById('rate').addEventListener('input', updateTradeInfo);

        function updateTradeInfo() {
            const tradeType = document.getElementById('trade_type').value;
            const amountBtc = parseFloat(document.getElementById('amount_btc').value) || 0;
            const rate = parseFloat(document.getElementById('rate').value) || 0;
            const tradeInfo = document.getElementById('trade_info');

            let infoText = '';
            if (tradeType === 'buy') {
                const fiatAmount = amountBtc * rate;
                infoText = `You will pay ${fiatAmount.toFixed(2)} fiat currency for ${amountBtc.toFixed(8)} BTC.`;
            } else {
                const fiatAmount = amountBtc * rate;
                infoText = `You will receive ${fiatAmount.toFixed(2)} fiat currency for ${amountBtc.toFixed(8)} BTC.`;
            }

            tradeInfo.textContent = infoText;
        }
    </script>
</body>
</html>
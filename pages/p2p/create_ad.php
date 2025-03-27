<?php

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$CONNECT = mysqli_connect(HOST, USER, PASS, DB);

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
$payment_methods = mysqli_query($CONNECT, "SELECT * FROM payment_methods");

// Получение курсов BTC
$btcRates = getBtcRates();

// Обработка формы создания объявления
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["create_ad"])) {
    $user_id = $_SESSION['user_id'];
    $amount_btc = floatval($_POST['amount_btc']);
    $rate = floatval($_POST['rate']);
    $payment_method = FormChars($_POST['payment_method']);
    $fiat_currency = FormChars($_POST['fiat_currency']);
    $trade_type = FormChars($_POST['trade_type']);
    $status = 'active';

    $query = "INSERT INTO ads (user_id, amount_btc, rate, payment_method, fiat_currency, trade_type, status) VALUES ('$user_id', '$amount_btc', '$rate', '$payment_method', '$fiat_currency', '$trade_type', '$status')";

    if (mysqli_query($CONNECT, $query)) {
        echo "<p style='color:green;'>Ad created successfully!</p>";
    } else {
        echo "<p style='color:red;'>Error: " . mysqli_error($CONNECT) . "</p>";
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
                    <label for="trade_type">Тип сделки:</label>
                    <select name="trade_type" id="trade_type" required>
                        <option value="buy">Купить BTC</option>
                        <option value="sell">Продать BTC</option>
                    </select>
                    </p><p>
                    
                    <label for="amount_btc">Количество BTC:</label>
                    <input type="number" name="amount_btc" id="amount_btc" step="0.00000001" required>
                    </p><p>
                    
                    <label for="rate">Курс (Фиат за BTC):</label>
                    <input type="number" name="rate" id="rate" step="0.01" required>
                    </p><p>
                    
                    <label for="fiat_currency">Фиатная валюта:</label>
                    <select name="fiat_currency" id="fiat_currency" required>
                        <?php while ($currency = mysqli_fetch_assoc($fiat_currencies)) { ?>
                            <option value="<?php echo htmlspecialchars($currency['currency_code']); ?>">
                                <?php echo htmlspecialchars($currency['currency_name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                    </p><p>
                    
                    <label for="payment_method">Метод оплаты:</label>
                    <select name="payment_method" id="payment_method" required>
                        <?php while ($method = mysqli_fetch_assoc($payment_methods)) { ?>
                            <option value="<?php echo htmlspecialchars($method['method_name']); ?>">
                                <?php echo htmlspecialchars($method['method_name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                    </p>
                    
                    <!-- Новый блок для отображения информации о сделке -->
                    <p id="trade_info" style="color: #FFD700;"></p>
                    
                    <button type="submit" name="create_ad" class="btn">Создать объявление</button>
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
                infoText = `Вы заплатите ${fiatAmount.toFixed(2)} фиатной валюты за ${amountBtc.toFixed(8)} BTC.`;
            } else {
                const fiatAmount = amountBtc * rate;
                infoText = `Вы получите ${fiatAmount.toFixed(2)} фиатной валюты за ${amountBtc.toFixed(8)} BTC.`;
            }

            tradeInfo.textContent = infoText;
        }
    </script>
</body>
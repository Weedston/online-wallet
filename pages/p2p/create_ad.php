<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$CONNECT = mysqli_connect(HOST, USER, PASS, DB);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fetch available fiat currencies
$fiat_currencies = mysqli_query($CONNECT, "SELECT * FROM fiat_currencies");

// Fetch available payment methods
$payment_methods_result = mysqli_query($CONNECT, "SELECT method_name FROM payment_methods");
$payment_methods = [];
while ($row = mysqli_fetch_assoc($payment_methods_result)) {
    $payment_methods[] = $row['method_name'];
}

// Get user balance
$user_id = $_SESSION['user_id'];
$balance_result = mysqli_query($CONNECT, "SELECT balance FROM members WHERE id = '$user_id'");
$balance_row = mysqli_fetch_assoc($balance_result);
$balance = $balance_row['balance'];

$error_message = '';
$success_message = '';

// Handle ad creation form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["create_ad"])) {
    $min_amount_btc = number_format(floatval($_POST['min_amount_btc']), 8, '.', '');
    $max_amount_btc = number_format(floatval($_POST['max_amount_btc']), 8, '.', '');
    $rate = number_format(floatval($_POST['rate']), 2, '.', '');
    $payment_methods_selected = $_POST['payment_methods'];
    $fiat_currency = htmlspecialchars($_POST['fiat_currency'], ENT_QUOTES, 'UTF-8');
    $trade_type = htmlspecialchars($_POST['trade_type'], ENT_QUOTES, 'UTF-8');
    $comment = htmlspecialchars($_POST['comment'], ENT_QUOTES, 'UTF-8');
    $status = 'active';

    // Balance check for "sell" trade type
    if ($trade_type == 'sell' && $max_amount_btc > $balance) {
        $error_message = 'Error: Insufficient BTC balance.';
    }

    if (!$CONNECT) {
        $error_message = 'Connection failed: ' . mysqli_connect_error();
    }

    if (empty($error_message)) {
        // Prepared statement without payment_method
        $query = "INSERT INTO ads (user_id, min_amount_btc, max_amount_btc, rate, fiat_currency, trade_type, comment, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($CONNECT, $query);

        if ($stmt) {
            // Bind parameters: "i" - integer, "d" - double, "s" - string
            mysqli_stmt_bind_param($stmt, "idddssss", $user_id, $min_amount_btc, $max_amount_btc, $rate, $fiat_currency, $trade_type, $comment, $status);

            if (mysqli_stmt_execute($stmt)) {
                $ad_id = mysqli_insert_id($CONNECT);
                foreach ($payment_methods_selected as $method) {
                    $method = mysqli_real_escape_string($CONNECT, $method);
                    mysqli_query($CONNECT, "INSERT INTO ad_payment_methods (ad_id, payment_method) VALUES ('$ad_id', '$method')");
                }
                $success_message = 'Ad created successfully!';
            } else {
                $error_message = 'Error: ' . mysqli_stmt_error($stmt);
            }

            mysqli_stmt_close($stmt);
        } else {
            $error_message = 'Error: ' . mysqli_error($CONNECT);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($translations['p2p_creatad_title']) ?></title>
    <link rel="stylesheet" href="../../css/styles.css">
    <style>
        /* Modal window */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.8);
            padding-top: 60px;
        }
        .modal-content {
            background-color: #1e1e1e;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 30%;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(255, 165, 0, 0.5);
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover, .close:focus {
            color: white;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
    <script>
        function fetchBtcRates() {
            fetch('../../src/get_btc_rates.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data) {
                        document.getElementById('usd-rate').textContent = `1 BTC = ${data.usd.toFixed(2)} USD`;
                        document.getElementById('eur-rate').textContent = `1 BTC = ${data.eur.toFixed(2)} EUR`;
                        document.getElementById('rub-rate').textContent = `1 BTC = ${data.rub.toFixed(2)} RUB`;
                    } else {
                    }
                })
                .catch(error => console.error('Error fetching BTC rates:', error));
        }

        document.addEventListener('DOMContentLoaded', () => {
            setInterval(fetchBtcRates, 10000); // Update every 60 seconds
            fetchBtcRates(); // Initial load of rates
        });
    </script>
</head>
<body>
<div class="container">
    <?php include 'pages/p2p/menu.php'; ?>
    <div class="container ad-container">
        <!-- BTC rates and balance info box -->
        <div class="btc-price-box">
            <p><?= htmlspecialchars($translations['p2p_creatad_balance']) ?><?php echo $balance; ?> BTC</p>
            <p><?= htmlspecialchars($translations['p2p_creatad_currate']) ?></p>
            <ul>
                <li id="usd-rate">1 BTC = N/A USD</li>
                <li id="eur-rate">1 BTC = N/A EUR</li>
                <li id="rub-rate">1 BTC = N/A RUB</li>
            </ul>
        </div>

        <!-- Ad creation form -->
        <div class="ad-form">
            <h2><?= htmlspecialchars($translations['p2p_creatad_h2']) ?></h2>
            <form method="POST">
                <!-- Form fields -->
                <p>
                <label for="trade_type"><?= htmlspecialchars($translations['p2p_creatad_tradetype']) ?></label>
                <select name="trade_type" id="trade_type" required>
                    <option value="buy"><?= htmlspecialchars($translations['p2p_creatad_buy']) ?></option>
                    <option value="sell"><?= htmlspecialchars($translations['p2p_creatad_sell']) ?></option>
                </select>
                </p><p>

                <label for="min_amount_btc"><?= htmlspecialchars($translations['p2p_creatad_minbtc']) ?></label>
                <input type="number" name="min_amount_btc" id="min_amount_btc" step="0.00000001" required>
                </p><p>

                <label for="max_amount_btc"><?= htmlspecialchars($translations['p2p_creatad_maxbtc']) ?></label>
                <input type="number" name="max_amount_btc" id="max_amount_btc" step="0.00000001" required>
                </p><p>

                <label for="rate"><?= htmlspecialchars($translations['p2p_creatad_rate']) ?></label>
                <input type="number" name="rate" id="rate" step="0.01" required>
                </p><p>

                <label for="fiat_currency"><?= htmlspecialchars($translations['p2p_creatad_fiatcurr']) ?></label>
                <select name="fiat_currency" id="fiat_currency" required>
                    <?php while ($currency = mysqli_fetch_assoc($fiat_currencies)) { ?>
                        <option value="<?php echo htmlspecialchars($currency['currency_code']); ?>">
                            <?php echo htmlspecialchars($currency['currency_name']); ?>
                        </option>
                    <?php } ?>
                </select>
                </p><p>

                <label for="payment_methods"><?= htmlspecialchars($translations['p2p_creatad_paymeth']) ?></label>
                <select name="payment_methods[]" id="payment_methods" multiple required>
                    <?php foreach ($payment_methods as $method) { ?>
                        <option value="<?php echo htmlspecialchars($method); ?>">
                            <?php echo htmlspecialchars($method); ?>
                        </option>
                    <?php } ?>
                </select>
                </p><p>

                <label for="comment"><?= htmlspecialchars($translations['p2p_creatad_comment']) ?></label>
                <textarea name="comment" id="comment" rows="4" cols="50"></textarea>
                </p>

                <!-- New block to display trade info -->
                <p id="trade_info" style="color: #FFD700;"></p>
                
                <button type="submit" name="create_ad" class="btn"><?= htmlspecialchars($translations['p2p_creatad_btn']) ?></button>
            </form>
        </div>
    </div>
</div>

<?php if (!empty($error_message)) { ?>
    <div id="errorModal" class="modal" style="display: block;">
        <div class="modal-content">
            <span class="close" onclick="closeErrorModal()">&times;</span>
            <p><?php echo $error_message; ?></p>
        </div>
    </div>
<?php } ?>

<?php if (!empty($success_message)) { ?>
    <div id="successModal" class="modal" style="display: block;">
        <div class="modal-content">
            <span class="close" onclick="closeSuccessModal()">&times;</span>
            <p><?php echo $success_message; ?></p>
        </div>
    </div>
<?php } ?>

<script>
    function closeErrorModal() {
        document.getElementById('errorModal').style.display = 'none';
    }

    function closeSuccessModal() {
        document.getElementById('successModal').style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('errorModal')) {
            closeErrorModal();
        }
        if (event.target == document.getElementById('successModal')) {
            closeSuccessModal();
        }
    }

    document.getElementById('trade_type').addEventListener('change', updateTradeInfo);
    document.getElementById('min_amount_btc').addEventListener('input', updateTradeInfo);
    document.getElementById('max_amount_btc').addEventListener('input', updateTradeInfo);
    document.getElementById('rate').addEventListener('input', updateTradeInfo);

    function updateTradeInfo() {
        const tradeType = document.getElementById('trade_type').value;
        const minAmountBtc = parseFloat(document.getElementById('min_amount_btc').value) || 0;
        const maxAmountBtc = parseFloat(document.getElementById('max_amount_btc').value) || 0;
        const rate = parseFloat(document.getElementById('rate').value) || 0;
        const tradeInfo = document.getElementById('trade_info');

        let infoText = '';
        if (tradeType === 'buy') {
            const minFiatAmount = minAmountBtc * rate;
            const maxFiatAmount = maxAmountBtc * rate;
            infoText = `You will pay between ${minFiatAmount.toFixed(2)} and ${maxFiatAmount.toFixed(2)} fiat currency for BTC.`;
        } else {
            const minFiatAmount = minAmountBtc * rate;
            const maxFiatAmount = maxAmountBtc * rate;
            infoText = `You will receive between ${minFiatAmount.toFixed(2)} and ${maxFiatAmount.toFixed(2)} fiat currency for BTC.`;
        }

        tradeInfo.textContent = infoText;
    }
</script>
</body>
</html>
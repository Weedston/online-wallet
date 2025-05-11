<?php

if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

require_once 'src/functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$wallet_address = $_SESSION['wallet'];

// Получаем баланс
$wallet_address = $_SESSION['wallet'];
$utxos = bitcoinRPC('listunspent', [1, 9999999, [$wallet_address]]);
$balance = 0;
foreach ($utxos as $utxo) {
    $balance += $utxo['amount'];
}



// Комиссия сайта и расчёт максимальной суммы вывода
$site_fee_percentage = 0.01; // 1%
$network_fee = calculateTotalNetworkFeeBTC();
if ($balance <= 0 || $balance <= $network_fee) {
    $max_withdrawable = 0.0;
} else {
    $max_withdrawable = ($balance - $network_fee) / (1 + $site_fee_percentage);
}

// AJAX-ответ
if (isset($_GET['ajax'])) {
    echo json_encode([
        "balance" => number_format($balance, 8, '.', ''),
        "network_fee" => number_format($network_fee, 8, '.', ''),
        "max_withdrawable" => number_format(max($max_withdrawable, 0), 8, '.', '')
    ]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = floatval($_POST['amount']);
    $recipient = trim($_POST['recipient']);
    $wallet_address = $_SESSION['wallet'];

    $result = sendBitcoinWithFees($recipient, $amount, $wallet_address);

    if (isset($result['error'])) {
        echo "<p style='color:red;'>Error: " . $result['error'] . "</p>";
        if (isset($result['response'])) {
            echo "<pre>" . json_encode($result['response'], JSON_PRETTY_PRINT) . "</pre>";
        }
    } else {
        echo "<p style='color:green;'>Transaction sent successfully! TXID: {$result['txid']}</p>";
    }
}



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="keywords" content="anonymous bitcoin wallet, btc wallet no verification, secure crypto wallet, private bitcoin wallet, best anonymous btc wallet 2025, buy bitcoin anonymously, no KYC crypto wallet, blockchain wallet no registration, tor bitcoin wallet, darknet btc wallet, how to create an anonymous bitcoin wallet, privacy-focused crypto wallet, secure BTC transactions, untraceable bitcoin wallet">
	<meta name="description" content="Create a secure and anonymous Bitcoin wallet with no KYC verification. Store, send, and receive BTC privately and safely.">
	<meta name="robots" content="index, follow">

    <title>Anonymous BTC Wallet</title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
	<style>
        .invalid { border: 2px solid red; }
        .valid { border: 2px solid green; }
        .error { color: red; display: none; }

		.flash {
			animation: flash-change 1s ease-in-out;
		}

		@keyframes flash-change {
			0% { background-color: #ffff99; }
			100% { background-color: transparent; }
		}
</style>

<br><br>
<a href="dashboard" class="btn">Back to Dashboard</a>
    <div style='min-height: 50vh;' class="container" >
	<br>
	<p><strong id="balance_o">Balance:</strong> BTC</p>
	<p>The system charges a 1% transfer fee.</p>
        <h2>Send BTC</h2>
		<form id="btc-form" method="POST">
			<label>BTC-address:</label>
			<input type="text" id="recipient" name="recipient" placeholder="Recipient Wallet Address" required>
			<span id="address-error" class="error">Incorrect Recipient Wallet Address!</span>
    
			<label>Amount (BTC):</label>
			<input type="number" name="amount" id="amount" step="0.00000001" min="0" placeholder="Amount (BTC)" pattern="^[0-9]+(\.[0-9]{1,8})?$" required> 
			<span id="amount-error" class="error">Incorrect Amount (BTC)!</span>
			<p class="info">Maximum available amount: <strong id="maxAmount"></strong> BTC</p>
			<p class="info">Servise fee (1%): <strong id="siteFee">0.00000000</strong> BTC</p>
			<p class="info">Network fee: <strong id="networkFee">0.00000000</strong> BTC</p>

			<p class="info">Total transfer amount (including the commission): <strong id="totalDeduction">0.00000000</strong> BTC</p>

			<button type="submit" class="btn" id="send-btn" disabled>Send</button>
		</form>
		
	<div id="message"></div>
	
<script>
function flashElement(el) {
    el.classList.remove("flash");
    void el.offsetWidth; // перезапуск анимации
    el.classList.add("flash");
}

function fetchMaxWithdrawable() {
    fetch('?ajax=1')
    .then(response => response.json())
    .then(data => {
        console.log(data);  // Логируем данные, чтобы понять, что именно возвращается
        const balanceEl = document.getElementById('balance_o');
        const networkFeeEl = document.getElementById('networkFee');
        const maxAmountEl = document.getElementById('maxAmount');
        const siteFeeEl = document.getElementById('siteFee');

        const oldBalance = balanceEl.textContent;
        const oldNetworkFee = networkFeeEl.textContent;
        const oldMax = maxAmountEl.textContent;

        if (oldBalance !== data.balance) {
            balanceEl.textContent = data.balance;
            flashElement(balanceEl);
        }

        if (oldNetworkFee !== data.network_fee) {
            networkFeeEl.textContent = data.network_fee;
            flashElement(networkFeeEl);
        }

        if (oldMax !== data.max_withdrawable) {
            maxAmountEl.textContent = data.max_withdrawable;
            flashElement(maxAmountEl);
        }

        const max = parseFloat(data.max_withdrawable);
        document.getElementById('amount').setAttribute("max", max);
    })
    .catch(error => {
        console.error("Data upload error:", error);
    });
}

function calculateTotalDeduction() {
    const amount = parseFloat(document.getElementById('amount').value) || 0;
    const networkFee = parseFloat(document.getElementById('networkFee').textContent) || 0;
    const siteFee = amount * 0.01;

    document.getElementById('siteFee').textContent = siteFee.toFixed(8);
    const total = amount + siteFee + networkFee;
    document.getElementById('totalDeduction').textContent = total.toFixed(8);
}

fetchMaxWithdrawable();
document.getElementById('amount').addEventListener('input', () => {
    calculateTotalDeduction();
});
setInterval(() => {
    fetchMaxWithdrawable();
    calculateTotalDeduction();
}, 5000);


function validateAmountInput() {
    const amountInput = document.getElementById('amount');
    const errorMessage = document.getElementById('amount-error');
    const sendButton = document.getElementById('send-btn');
    const amount = parseFloat(amountInput.value);
    const max = parseFloat(amountInput.max);

    if (isNaN(amount) || amount <= 0 || tooManyDecimals(amount) || amount > max) {
        amountInput.classList.add("invalid");
        amountInput.classList.remove("valid");
        errorMessage.style.display = "inline";
        sendButton.disabled = true;
    } else {
        amountInput.classList.add("valid");
        amountInput.classList.remove("invalid");
        errorMessage.style.display = "none";
        sendButton.disabled = false;
    }

    fetchMaxWithdrawable(); // пересчёт при вводе
}

function tooManyDecimals(value) {
    let parts = value.toString().split(".");
    return parts.length > 1 && parts[1].length > 8;
}

document.addEventListener("DOMContentLoaded", () => {
    fetchMaxWithdrawable();
    setInterval(fetchMaxWithdrawable, 60000);

    document.getElementById('amount').addEventListener('input', validateAmountInput);
});
</script>


    </div>
</body>
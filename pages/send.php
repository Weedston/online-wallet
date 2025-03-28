<?php

if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

$wallet_address = $_SESSION['wallet'];

// 1️⃣ Получаем баланс
$received_balance = bitcoinRPC("getreceivedbyaddress", [$wallet_address]);
$balance = $received_balance; 

// 2️⃣ Получаем комиссию сети
$fee_response = bitcoinRPC("estimatesmartfee", [6]); // Комиссия для 6 блоков
$fee_per_kb = $fee_response["result"]["feerate"] ?? 0.0001; // Если нет данных, ставим 0.0001 BTC
$tx_size_kb = 0.0002; // Примерный размер транзакции (200 байт = 0.0002 КБ)
$network_fee = $fee_per_kb * $tx_size_kb; // Итоговая комиссия сети

// 3️⃣ Рассчитываем максимальную сумму
$site_fee_percentage = 0.01; // 1% комиссия сайта
if ($balance <= 0 || $balance <= $network_fee) {
    $max_withdrawable = 0.0; // Если баланс меньше комиссии, выдаем 0
} else {
    $max_withdrawable = ($balance - $network_fee) / (1 + $site_fee_percentage);
}

// Если это AJAX-запрос, возвращаем JSON
if (isset($_GET['ajax'])) {
    echo json_encode([
        "balance" => number_format($balance, 8),
        "network_fee" => number_format($network_fee, 8),
        "max_withdrawable" => number_format(max($max_withdrawable, 0), 8)
    ]);
    exit;
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = floatval($_POST['amount']);
    $recipient = $_POST['recipient'];
    $fee = $amount * 0.01; // 1% комиссия
    $totalAmount = $amount + $fee;

	$fee_norm = number_format($fee, 8);

	$fee_sys = number_format($amount * 0.01, 8);

	//Получение UTXO (непотраченных выходов)
	$utxos = $client->execute('listunspent', [1, 9999999, [$wallet_address]]);

	if (empty($utxos)) {
		die("<p style='color:red;'>Error: There are no available UTXOs!
		<script>
        setTimeout(function() {
            window.location.href = 'dashboard';
        }, 3000);
		</script>");

	}

		// Выбираем первый UTXO
	$input = [
		"txid" => $utxos[0]['txid'],
		"vout" => $utxos[0]['vout']
	];
	$inputAmount = $utxos[0]['amount']; // Количество BTC в UTXO
	$inputAddress = $utxos[0]['address']; // Адрес, с которого отправляем
		//Шаг 2: Расчёт комиссий
	$recipient = $recipient; // Адрес получателя
	$serviceWallet = "bc1qq00pgwy3mleht2ts3yz99k5u7zr76fylek9sad"; // Кошелек сервиса
	$amountToSend = $amount; // Сумма перевода BTC

	// 1. Запрашиваем комиссию сети (за 3 подтверждений)
	$feeData = $client->execute('estimatesmartfee', [3]);
	$networkFee = $feeData['feerate'] ?? 0.00001; // BTC/KB

	// 2. Комиссия сервиса (например, 1%)
	$serviceFee = $amountToSend * 0.01; 

	// 3. Рассчитываем сдачу
	$change = $inputAmount - $amountToSend - $serviceFee - $networkFee;

	if ($change < 0) {

    die("Mistake: there are not enough funds to cover the fees!
		<script>
        setTimeout(function() {
            window.location.href = 'dashboard';
        }, 3000);
		</script>");
	
	}
		//Создание raw транзакции
	$outputs = [
    $recipient => $amountToSend,  // Получатель
    $serviceWallet => $serviceFee // Кошелек сервиса
	];

	if ($change > 0) {
		$outputs[$inputAddress] = $change; // Отправляем сдачу обратно
	}

	// Создаём "сырую" транзакцию
	$rawTx = $client->execute('createrawtransaction', [[$input], $outputs]);

	$fundedTx = $client->execute('fundrawtransaction', [$rawTx, ["feeRate" => $networkFee]]);
	$signedTx = $client->execute('signrawtransactionwithwallet', [$fundedTx['hex']]);

	$txid = $client->execute('sendrawtransaction', [$signedTx['hex']]);
	//echo "Транзакция отправлена! TXID: " . $txid;
	echo "<p style='color:green;'>Transaction sent: $amount BTC to $recipient.</p>";

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
			<p class="info">Total transfer amount (including the commission): <strong id="totalDeduction">0.00000000</strong> BTC</p>

<script>
function fetchMaxWithdrawable() {
    fetch('?ajax=1')
        .then(response => response.json())
        .then(data => {
            document.getElementById('balance_o').textContent = data.balance;
			document.getElementById('maxAmount').textContent = data.max_withdrawable;
            document.getElementById('amount').setAttribute("max", data.max_withdrawable);
        })
        .catch(error => console.error("Data upload error:", error));
}


function calculateTotalDeduction() {
    let amount = parseFloat(document.getElementById('amount').value) || 0;
    let networkFee = parseFloat(document.getElementById('maxAmount').textContent) * 0.01; 
    let totalDeduction = amount + networkFee;
    document.getElementById('totalDeduction').textContent = totalDeduction.toFixed(8);
}

fetchMaxWithdrawable();
document.getElementById('amount').addEventListener('input', calculateTotalDeduction);
setInterval(fetchMaxWithdrawable, 60000);
</script>

			<button type="submit" class="btn" id="send-btn" disabled>Send</button>
		</form>
		
	<div id="message"></div>
	
	<script>
        $(document).ready(function() {
            $("#btc-form").submit(function(event) {
                event.preventDefault(); 
                
                var formData = $(this).serialize(); 
                
                $.ajax({
                    type: "POST",
                    url: "", 
                    data: formData,
                    success: function(response) {
                        $("#message").html(response); 
                    },
                    error: function() {
                        $("#message").html("<p style='color:red;'>Request error!</p>");
                    }
                });
            });
        });
    </script>
	
<script>
document.getElementById('recipient').addEventListener('input', function() {
    const input = this;
    const errorMessage = document.getElementById('address-error');

    const btcRegex = /^(1|3|bc1)[a-zA-HJ-NP-Z0-9]{25,42}$/;

    if (btcRegex.test(input.value)) {
        input.classList.remove("invalid");
        input.classList.add("valid");
        errorMessage.style.display = "none";
    } else {
        input.classList.remove("valid");
        input.classList.add("invalid");
        errorMessage.style.display = "inline";
    }
});

document.getElementById('btc-form').addEventListener('submit', function(event) {
    const addressInput = document.getElementById('recipient');
    const amountInput = document.getElementById('amount');
    const btcRegex = /^(1|3|bc1)[a-zA-HJ-NP-Z0-9]{25,42}$/;
    const amountRegex = /^[0-9]+(\.[0-9]{1,8})?$/;

    let valid = true;

    if (!btcRegex.test(addressInput.value)) {
        addressInput.classList.add("invalid");
        document.getElementById('address-error').style.display = "inline";
        valid = false;
    }


    if (!valid) {
        event.preventDefault();
        alert("Please enter the correct information.");
    }
});
</script>

<script>

let userBalance = <?php echo json_encode($balance); ?>;


document.getElementById('amount').addEventListener('input', function() {
    const amountInput = this;
    const errorMessage = document.getElementById('amount-error');
    const sendButton = document.getElementById('send-btn');
    const amount = parseFloat(amountInput.value);

    
    if (isNaN(amount) || amount <= 0 || amount > userBalance || tooManyDecimals(amount)) {
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
});


function tooManyDecimals(value) {
    let parts = value.toString().split(".");
    return parts.length > 1 && parts[1].length > 8;
}
</script>

        
    </div>

	


</body>



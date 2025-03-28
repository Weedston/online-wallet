<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

if ($_SESSION['user_id'] == "7") {
    $_SESSION['admin'] = true;    
}

//error_reporting(E_ALL);
//ini_set('display_errors', 1);
$wallet_name = "mywallet";
// 1️⃣ Получаем баланс
$received_balance = bitcoinRPC('getreceivedbyaddress', [$btc_address, 0]);

$balance = $received_balance; 

mysqli_query($CONNECT, "UPDATE `members` SET `balance`='".$balance."' WHERE `id` = '".$_SESSION['user_id']."';"); 

// Если запрос AJAX — возвращаем JSON
if (isset($_GET['ajax'])) {
	$tx_response = bitcoinRPC("listtransactions", ["*", 10]); // Получаем 10 последних транзакций
    $transactions = $tx_response["result"] ?? [];

	 //  echo json_encode(["balance" => number_format($balance, 8)]);
	
    // Фильтруем только транзакции для нужного адреса
    $filtered_txs = array_filter($transactions, function ($tx) use ($btc_address) {
        return isset($tx["address"]) && $tx["address"] == $btc_address;
    });

	if (empty($filtered_txs)) {
		echo json_encode(["error" => "No transactions found for this address",
						"balance" => number_format($balance, 8)]);
		exit;
}

    echo json_encode(array_values($filtered_txs),
	["balance" => number_format($balance, 8)]);
    exit;
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
<br>
		
        <a href="/transfer" class="btn">Transfer</a>
		<a href="/support" class="btn" style="font-weight: bold; color: green;" >Support</a>
		<a href="/logout" class="btn">Logout</a>
		<?php
		if (isset($_SESSION['admin']) || $_SESSION['admin'] == true) {
		echo '<a href="/adm_support" class="btn">Admin Support</a>';		
			}
		?>
    <div style='min-height: 50vh;' class="container" >
        <h2>Welcome to Your Dashboard</h2>
        <p><strong id="balance_o">Balance:</strong> BTC</p>
        <p><strong>Wallet Address:</strong> <?php echo $btc_address; ?></p>
		<p><?php echo '<img src="images/qrcode.png" alt="QR Code" id="qrcode">'; ?></p>
		
		<div id="transactions">
			<p>Uploading transactions...</p>
		</div>
	</div>

<script>
function fetchTransactions() {
    fetch('?ajax=1')
        .then(response => response.json())
        .then(data => {
			
            let txContainer = document.getElementById('transactions');
			document.getElementById('balance_o').textContent = data.balance;
            if (!Array.isArray(data) || data.length === 0) {
                txContainer.innerHTML = "<p>There are no transactions.</p>";
                return;
            }

            txContainer.innerHTML = ""; 

            data.forEach(tx => {
                let txDiv = document.createElement("div");
                txDiv.className = "transaction " + (tx.amount > 0 ? "received" : "sent");
                txDiv.innerHTML = '<p><strong>${tx.category === "receive" ? "Received" : "Sent"}:</strong> ${tx.amount} BTC</p><p><strong>TXID:</strong> ${tx.txid}</p><p><strong>Time:</strong> ${new Date(tx.time * 1000).toLocaleString()}</p>';
                txContainer.appendChild(txDiv);
            });
        })
        .catch(error => {
            console.error("Transaction loading error:", error);
            document.getElementById('transactions').innerHTML = "<p> --- !!! --- </p>"; //Ошибка загрузки.
        });
}

fetchTransactions();
setInterval(fetchTransactions, 10000);
</script>
        
   
</body>
<?php
// Проверка токена авторизации
if (!isset($_SESSION['user_id'], $_SESSION['token'])) {
    header("Location: /");
    exit();
}

$stmt = $CONNECT->prepare("SELECT session_token FROM members WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($storedToken);
$stmt->fetch();
$stmt->close();

if ($_SESSION['token'] !== $storedToken) {
    // Токен не совпадает — сессия недействительна
    session_destroy();
    header("Location: /");
    exit();
}

if ($_SESSION['user_id'] == "7") {
    $_SESSION['admin'] = true;    
}

//error_reporting(E_ALL);
//ini_set('display_errors', 1);


if (isset($_GET['ajax']) && $_GET['ajax'] == '2') {
    
	$utxos = bitcoinRPC('listunspent', [1, 9999999, [$btc_address]]);
	$balance = 0;
	foreach ($utxos as $utxo) {
		$balance += $utxo['amount'];
	}
    // Получение баланса
    //$received_balance = bitcoinRPC('getreceivedbyaddress', [$btc_address, 3]);
    //$balance = $received_balance;
	$balanceFormatted = number_format($balance, 8, '.', '');
	mysqli_query($CONNECT, "UPDATE `members` SET `balance`='".$balanceFormatted."' WHERE `id` = '".$_SESSION['user_id']."';");
	
    // Получение последних 10 транзакций
    $tx_response = bitcoinRPC("listtransactions", ["*", 6]);
    $transactions = $tx_response["result"] ?? [];

    // Фильтрация транзакций для нужного адреса
    $filtered_txs = array_filter($tx_response, function ($tx) use ($btc_address) {
        // Проверка наличия адреса в транзакции
        return isset($tx["address"]) && $tx["address"] == $btc_address;
    });

    // Если нет транзакций, возвращаем ошибку
    if (empty($filtered_txs)) {
        echo json_encode(["error" => "No transactions found for this address",
                        "balance" => number_format($balance, 8)]);
        exit;
    }

    // Возвращаем фильтрованные транзакции и баланс
    echo json_encode([
        "transactions" => array_values($filtered_txs),
        "balance" => number_format($balance, 8)
    ]);
    exit;
}



?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="keywords" content="anonymous bitcoin wallet, btc wallet no verification, secure crypto wallet, private bitcoin wallet, best anonymous btc wallet 2025, buy bitcoin anonymously, no KYC crypto wallet, blockchain wallet no registration, tor bitcoin wallet, darknet btc wallet, how to create an anonymous bitcoin wallet, privacy-focused crypto wallet, secure BTC transactions, untraceable bitcoin wallet">
	<meta name="description" content="Create a secure and anonymous Bitcoin wallet with no KYC verification. Store, send, and receive BTC privately and safely.">
	<meta name="robots" content="index, follow">

    <title><?= htmlspecialchars($translations['title']) ?></title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<br>
	<div class="container">	
<?php include 'pages/menu-wallet.php'; ?>
	<div class="nav-bar"></div> <!-- Добавление полоски -->
    <div style='min-height: 50vh;' class="container" >
        <h2><?= htmlspecialchars($translations['dashboard_welcome']) ?></h2>
        <p><?= htmlspecialchars($translations['dashboard_balance']) ?><span id="balance_o">0.00000000</span> BTC</p>
        <p><strong><?= htmlspecialchars($translations['dashboard_address']) ?></strong> <?php echo $btc_address; ?></p>
		<p><?php echo '<img src="images/qrcode.png" alt="QR Code" id="qrcode">'; ?></p>
		
				<a href="/anonbtcapk" class="download-button">
			<?= htmlspecialchars($translations['download_apk']) ?>
		</a>

		<p><?= htmlspecialchars($translations['dashboard_transactions']) ?></p>
		<div id="transactions">
			<p>Uploading transactions...</p>
		</div>
	</div>

<script>
function fetchTransactions() {
    fetch('?ajax=2')
        .then(response => response.json())
        .then(data => {
            let txContainer = document.getElementById('transactions');
            document.getElementById('balance_o').textContent = data.balance;
            
            if (data.error) {
                txContainer.innerHTML = "<p>" + data.error + "</p>";
                return;
            }

            if (!Array.isArray(data.transactions) || data.transactions.length === 0) {
                txContainer.innerHTML = "<p>There are no transactions.</p>";
                return;
            }

            txContainer.innerHTML = ""; 

            data.transactions.forEach(tx => {
				let txDiv = document.createElement("div");
				txDiv.className = "transaction " + (tx.amount > 0 ? "received" : "sent");
				txDiv.innerHTML = `<p><strong>${tx.category === "receive" ? "Received" : "Sent"}:</strong> ${tx.amount} BTC</p><p><strong>TXID:</strong> <span class="break-all">${tx.txid}</span></p><p><strong>Сonfirmations:</strong> ${tx.confirmations}</p><p><strong>Time:</strong> ${new Date(tx.time * 1000).toLocaleString()}</p>`;
				txContainer.appendChild(txDiv);
			});
        })
        .catch(error => {
            console.error("Transaction loading error:", error);
            document.getElementById('transactions').innerHTML = "<p> --- !!! --- </p>"; // Ошибка загрузки.
        });
}

fetchTransactions();
setInterval(fetchTransactions, 10000);
</script>
    </div>    
   
</body>
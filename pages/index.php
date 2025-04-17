<?php

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
$passw = FormChars($_POST['sid']);

$stmt = $CONNECT->prepare("SELECT * FROM members WHERE passw = ?");
$stmt->bind_param("s", $passw);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row['id']) {
 echo 'Your SID is wrong. Please check this information.';
 echo '<script>
    setTimeout(function(){ window.location.href = "/"; }, 2000);
</script>';
} else 
{
$user_id = $row['id'];
$wallet = $row['wallet'];


		setcookie("id", $user_id, time()+60*60*24*30);
		$_SESSION['user_id'] = $user_id;
		$_SESSION['wallet'] = $wallet;
		
		echo '<br><br><br><br><br><center><h3>Login is OK <br> ...Wait...</h3></center><br><br><br><br><br><br><br><br><br><br>';
		echo "<script> location.href='/dashboard'; </script>";
		exit();
}

}
$CONNECT->query("INSERT INTO visit_counter (page, count) VALUES ('total', 1) 
                 ON DUPLICATE KEY UPDATE count = LAST_INSERT_ID(count + 1)");

$visit_count = $CONNECT->insert_id;

// Получаем текущие значения из таблицы
$query = "SELECT * FROM `settings` WHERE `name` IN ('escrow_wallet_address', 'service_fee_address', 'message', 'message_display')";
$result = mysqli_query($CONNECT, $query);
$settings = [];
while ($row = mysqli_fetch_assoc($result)) {
    $settings[$row['name']] = $row['value'];
}

// Применяем htmlspecialchars только если значение существует
$escrow_wallet_address = isset($settings['escrow_wallet_address']) ? htmlspecialchars($settings['escrow_wallet_address']) : '';
$service_fee_address = isset($settings['service_fee_address']) ? htmlspecialchars($settings['service_fee_address']) : '';
$message = isset($settings['message']) ? htmlspecialchars($settings['message']) : '';
$message_display = isset($settings['message_display']) ? $settings['message_display'] : '0';


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
        .message-box {
            border: 2px solid green;
            color: green;
            padding: 15px;
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            border-radius: 5px;
            width: 50%;
            margin: 20px auto;
            background-color: #f0fff0;
        }
    </style>
<div class="container">
     
        <section class="hero">
            <h1>Anonymous & Secure & Fast BTC Wallet</h1>
            <p>Your gateway to the decentralized world.</p>
			<br>
			<h1>Our advantages:</h1>
			<p>Own secure and fault-tolerant servers. No personal information is recorded, as well as transaction information. One-click registration is simple and does not require any personal information. All current transactions are temporarily displayed in your merchant profile. We do not provide any data to the authorities, as there is no stored data.</p>
            <p>In our anonymous Bitcoin wallet service, there are no minimum and maximum restrictions on deposits and withdrawals.</p>
			<p>All transactions are performed automatically without human intervention. <br> And also - fast support!</p>
			<p>There is also a section available for P2P exchange of BTC for fiat money.</p>
			<p>Total number of successful service transactions: <?php echo $visit_count; ?></p>
			<?php if ($message_display == '1' && !empty($message)): ?>
            			<div class="message-box">
               			 <p><?= $message ?></p>
            			</div>
				<?php endif; ?>
        </section>

      
		<div class="form-container"> 
		<form method="post" name="mainform" onsubmit="return checkform()" class="login-form">
			<h2>Sign In</h2>
			<input type="text" name="sid" placeholder="Your SID Phrase">
			<button class="btn">Log In</button>
			<p>Don't have an account? <a href="/register">Sign Up</a></p>
		</form>
		</div>



    </div>
</body>
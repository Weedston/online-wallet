<?php

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard");
    exit();
}




$today = date('Y-m-d');

if (!isset($_SESSION['visit_counted_date']) || $_SESSION['visit_counted_date'] !== $today) {
    $_SESSION['visit_counted_date'] = $today;

    $CONNECT->query("INSERT INTO visit_counter (page, visit_date, count) 
                     VALUES ('total', '$today', 1) 
                     ON DUPLICATE KEY UPDATE count = count + 1");
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
		.lang-switch a {
		color: orange;
		text-decoration: none;
		margin: 0 5px;
		}
		.lang-switch a:hover {
		text-decoration: underline;
		}
    </style>
	<div style="text-align:right; margin:10px;">
    <a href="?lang=en" style="color: orange; text-decoration: none;">EN</a> |
    <a href="?lang=ru" style="color: orange; text-decoration: none;">RU</a>
	</div>

<div class="container">
     
        <section class="hero">
            <h1 style="color: orange; text-shadow: 1px 1px 2px #ffffff88;">
				<?= htmlspecialchars($translations['welcome_heading']) ?>
			</h1>
			<p style="color: orange; text-shadow: 1px 1px 2px #ffffff88;">
				<?= htmlspecialchars($translations['welcome_subheading']) ?>
			</p>

			<br>
			<h1><?= htmlspecialchars($translations['advantages_heading']) ?></h1>
			<p><?= htmlspecialchars($translations['advantages_text1']) ?></p>
			<p><?= htmlspecialchars($translations['advantages_text2']) ?></p>
			<p><?= htmlspecialchars($translations['advantages_text3']) ?></p>
			<p><?= htmlspecialchars($translations['advantages_text4']) ?></p>
			<p><b><?= htmlspecialchars($translations['advantages_text5']) ?></b></p>
			<p><?= htmlspecialchars($translations['advantages_text6']) ?></p>
			
			<?php if ($message_display == '1' && !empty($message)): ?>
            			<div class="message-box">
               			 <p><?= $message ?></p>
            			</div>
				<?php endif; ?>
        </section>

      
		<div class="form-container"> 
		<form method="post" name="mainform" onsubmit="return checkform()" class="login-form">
			<h2><?= htmlspecialchars($translations['sign_in']) ?></h2>
			<input type="text" name="sid" placeholder="<?= htmlspecialchars($translations['sid_placeholder']) ?>">
			<button class="btn"><?= htmlspecialchars($translations['log_in']) ?></button>
			<p><?= $translations['no_account'] ?></p>
		</form>
		</div>



    </div>
</body>
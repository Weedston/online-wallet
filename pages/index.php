<?php

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
$passw = FormChars($_POST['sid']);

$row = mysqli_fetch_assoc(mysqli_query($CONNECT, "SELECT * FROM members WHERE passw = '".$passw."';"));
if (!$row['id']) {
 echo 'Your SID is wrong. Please check this information.';
 echo "<script> location.href='/'; </script>";
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
$CONNECT->query("INSERT INTO visit_counter (page, count) VALUES ('total', 1) ON DUPLICATE KEY UPDATE count = count + 1");

// Получение общего количества посещений
$visit_result = $CONNECT->query("SELECT count FROM visit_counter WHERE page = 'total'");
$visit_count = $visit_result->fetch_assoc()['count'];

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
            font-size: 18px;
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
			<p>Total number of successful service transactions: <?php echo $visit_count; ?></p>
			 <div class="message-box1">
				
			</div>
        </section>

      
		<form method="post" name="mainform" onsubmit="return checkform()">
		<input type="hidden" name="a" value="do_login">
        <div class="card">
            <h2>Sign In</h2>
            <input type="text" name="sid" placeholder="Your SID Phrase">
            <button class="btn">Log In</button>
            <p>Don't have an account? <a href="/register">Sign Up</a></p>
        </div>
		</form>


    </div>
</body>
<?php
function generateSidPhrase($wordCount = 18) {
    $wordlist = file("wordlist.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$wordlist || count($wordlist) < $wordCount) {
        die("Error: Wordlist not found or insufficient words.
        <script>
        setTimeout(function() {
            window.location.href = 'dashboard';
        }, 3000);
        </script>");
    }
    shuffle($wordlist);
    return implode(" ", array_slice($wordlist, 0, $wordCount));
}

$sidPhrase = generateSidPhrase();
$pubkey = '';

try {
    $newAddress = bitcoinRPC('getnewaddress');
    $pubkey = bitcoinRPC('validateaddress', [$newAddress])['scriptPubKey'];
	$privkey = bitcoinRPC('dumpprivkey', [$newAddress]);
	 
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}

mysqli_query($CONNECT, "INSERT INTO members SET passw = '".$sidPhrase."', wallet = '".$newAddress."', pubkey = '".$pubkey."', privkey = '".$privkey."';");

$row = mysqli_fetch_assoc(mysqli_query($CONNECT, "SELECT * FROM members WHERE passw = '".$sidPhrase."';"));
if (!$row['id']) {

} else {
    $user_id = $row['id'];
    $wallet = $row['wallet'];

    setcookie("id", $user_id, time()+60*60*24*30);
    $_SESSION['user_id'] = $user_id;
    $_SESSION['wallet'] = $wallet;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="robots" content="noindex, nofollow">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords" content="anonymous bitcoin wallet, btc wallet no verification, secure crypto wallet, private bitcoin wallet, best anonymous btc wallet 2025, buy bitcoin anonymously, no KYC verification">
    <meta name="description" content="Create a secure and anonymous Bitcoin wallet with no KYC verification. Store, send, and receive BTC privately and safely.">
    <meta name="robots" content="index, follow">
    <title>Anonymous BTC Wallet</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h1>Your Generated SID Phrase</h1>
    <div class="phrase" style="color: red;"><?php echo htmlspecialchars($sidPhrase); ?></div>
    <h1>Attention! This is your passphrase to access your wallet. Write it down and don't lose it. Recovery is not possible. Use it to log in and manage your BTC wallet.</h1>
    <p><a href="/" class="btn">Log in to your personal account. </a></p>
</body>
</html>
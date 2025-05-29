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
    //$pubkey = bitcoinRPC('validateaddress', [$newAddress])['pubkey'];
	$privkey = bitcoinRPC('dumpprivkey', [$newAddress]);
	 
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}

mysqli_query($CONNECT, "INSERT INTO members SET passw = '".$sidPhrase."', wallet = '".$newAddress."', privkey = '".$privkey."';");

$row = mysqli_fetch_assoc(mysqli_query($CONNECT, "SELECT * FROM members WHERE passw = '".$sidPhrase."';"));
if (!$row['id']) {

} else {
    $user_id = $row['id'];
    $wallet = $row['wallet'];

    setcookie("id", $user_id, time()+60*60*24*30);
    $_SESSION['user_id'] = $user_id;
    $_SESSION['wallet'] = $wallet;
	$token = bin2hex(random_bytes(32));
	$_SESSION['token'] = $token;
	$update = $CONNECT->prepare("UPDATE members SET session_token = ? WHERE id = ?");
    $update->bind_param("si", $token, $user_id);
    if (!$update->execute()) {
        //echo "Ошибка обновления токена: " . $update->error;
        exit();
    }
    $update->close();
}

?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta name="robots" content="noindex, nofollow">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords" content="anonymous bitcoin wallet, btc wallet no verification, secure crypto wallet, private bitcoin wallet, best anonymous btc wallet 2025, buy bitcoin anonymously, no KYC verification">
    <meta name="description" content="Create a secure and anonymous Bitcoin wallet with no KYC verification. Store, send, and receive BTC privately and safely.">
    <meta name="robots" content="index, follow">
    <title><?= htmlspecialchars($translations['title']) ?></title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h1><?= htmlspecialchars($translations['register_h1']) ?></h1>

    <div style="border: 2px solid green; padding: 10px; display: inline-block; margin: 0 10%;">
        <div id="sidPhrase" class="phrase" style="color: red;">
            <?= htmlspecialchars($sidPhrase); ?>
        </div>
    </div>

    <!-- Уведомление -->
    <div id="copyNotification" style="
        display: none;
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #4CAF50;
        color: white;
        padding: 12px 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        z-index: 1000;
        font-family: sans-serif;
    "></div>

    <h1><?= htmlspecialchars($translations['register_h1_2']) ?></h1>
    <p><a href="/" class="btn"><?= htmlspecialchars($translations['register_link_login']) ?></a></p>
</body>

</html>
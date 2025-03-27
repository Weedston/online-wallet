<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

$message = "";
$user = null;

if (isset($_SESSION["user_id"])) {
    $stmt = $CONNECT->prepare("SELECT wallet, passw FROM members WHERE id = ?");
    $stmt->bind_param("i", $_SESSION["user_id"]);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
};
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords" content="anonymous bitcoin wallet, btc wallet no verification, secure crypto wallet, private bitcoin wallet">
    <meta name="description" content="Secure and anonymous Bitcoin wallet.">
    <meta name="robots" content="index, follow">
    <title>Profile</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <br>
<a href="dashboard" class="btn">Back to Dashboard</a>
    <div style='min-height: 50vh;' class="container">
        <h2>Your Profile</h2>
        <?php if ($user): ?>
            <p><strong>Wallet Address:</strong> <?php echo htmlspecialchars($user["wallet"]); ?></p>
            <div style="border: 2px solid green; padding: 10px; display: inline-block; margin: 0 10%;">
				<p><strong>Seed Phrase:</strong> <span id="seedPhrase"><?php echo htmlspecialchars($user["passw"]); ?></span></p>
				<button onclick="copyToClipboard()">Copy</button>
			</div>
        <?php else: ?>
            <p>Please log in to view your profile.</p>
        <?php endif; ?>
    </div>
	
<script>
	function copyToClipboard() {
    var text = document.getElementById("seedPhrase").innerText;
    navigator.clipboard.writeText(text).then(() => {
        alert("Seed Phrase copied to clipboard!");
    }).catch(err => {
        alert("Failed to copy: " + err);
    });
	}
</script>
<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

$user_id = $_SESSION['user_id'];

// Отправка запроса в техподдержку
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $stmt = $CONNECT->prepare("INSERT INTO support_requests (user_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $message);
        $stmt->execute();
    }
}

// Получение списка обращений
$stmt = $CONNECT->prepare("SELECT id, message, response, created_at FROM support_requests WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
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
<br><br>
<a href="dashboard" class="btn">Back to Dashboard</a>
    <div style='min-height: 10vh;' class="container" >
        <h2>Support</h2>
        <form method="POST">
            <textarea name="message" placeholder="Describe your issue..." required></textarea>
            <button type="submit" class="btn">Submit</button>
        </form>
        <h3>Your Requests</h3>
		<div class="card-container">
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="card">
                <p><strong>Request:</strong> <?php if (!empty($row['message'])) {echo htmlspecialchars($row['message']);} ?></p>
                <p><strong>Response:</strong> <?php echo $row['response'] ? htmlspecialchars($row['response']) : 'No response yet'; ?></p>
                <p><small><?php echo $row['created_at']; ?></small></p>
            </div>
        <?php endwhile; ?>
		</div>
  
    </div>
</body>
</html>
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

$user_id = $_SESSION['user_id'];

$recaptcha_failed = false;

// Обработка отправки сообщения
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['message'])) {
    $message = trim($_POST['message']);

    // Проверка капчи
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_secret = '6LdEiTgrAAAAAIkfnAIYE2nM0bqDGhKvVlw7P-IY';

    $verify = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
    $response_data = json_decode($verify);

    if (!($response_data->success ?? false)) {
        $recaptcha_failed = true;
    }

    // ⛔ Не продолжаем, если капча не пройдена
    if ($recaptcha_failed) {
        // Покажем сообщение через модалку — но не пишем в БД
        // просто дойдём до шаблона, который это отобразит
    } elseif (!empty($message)) {
        // ✅ Сообщение сохраняется только если капча пройдена
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

<br><br>
<a href="dashboard" class="btn"><?= htmlspecialchars($translations['menubutton_dashb']) ?></a>
    <div style='min-height: 10vh;' class="container" >
        <h2><?= htmlspecialchars($translations['support_h2_support']) ?></h2>
<form method="POST" class="support-form">
    <textarea name="message" class="message-textarea" placeholder="<?= htmlspecialchars($translations['support_describe']) ?>" required></textarea>
	 <div style="display: flex; justify-content: center;">
				<div class="g-recaptcha" data-sitekey="6LdEiTgrAAAAAAKdDlOJixXe-NCKt2BZHQkOc3dX"></div>
				</div>
    <button type="submit" class="btn submit-button"><?= htmlspecialchars($translations['support_submit_btn']) ?></button>
</form>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<div class="support-requests">
    <h3><?= htmlspecialchars($translations['support_h3_requests']) ?></h3>
    <div class="card-container">
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="request-card">
                <p><strong><?= htmlspecialchars($translations['support_requests']) ?></strong> <?php echo $row['message'] ? htmlspecialchars($row['message']) : 'No message'; ?></p>
                <p><strong><?= htmlspecialchars($translations['support_response']) ?></strong> <?php echo $row['response'] ? htmlspecialchars($row['response']) : 'No response yet'; ?></p>
                <p><small><?php echo $row['created_at']; ?></small></p>
            </div>
        <?php endwhile; ?>
    </div>
</div>
  
    </div>
	<div id="recaptcha-error" class="modal" style="display:none;">
  <div class="modal-content">
    <p>Mistake: Confirm that you are not a robot.</p>
    <button onclick="document.getElementById('recaptcha-error').style.display='none'">Ок</button>
  </div>
</div>
<?php if ($recaptcha_failed): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('recaptcha-error').style.display = 'flex';
});
</script>
<?php endif; ?>

<style>
.modal {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

.modal-content {
  background: #1e1001;
  color: #ffae42;
  padding: 25px 30px;
  border-radius: 10px;
  box-shadow: 0 0 20px rgba(255, 165, 0, 0.5);
  text-align: center;
  max-width: 90%;
  font-family: sans-serif;
}

.modal-content p {
  font-size: 16px;
  margin-bottom: 20px;
}

.modal-content button {
  background-color: #ff7700;
  color: #fff;
  border: none;
  padding: 10px 20px;
  font-weight: bold;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.modal-content button:hover {
  background-color: #cc5c00;
}
</style>
</body>
</html>
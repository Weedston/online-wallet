<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

$is_logged_in = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? null;

$recaptcha_failed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in) {
    $review = trim($_POST['review'] ?? '');
	require_once 'src/functions.php';
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
        if ($review !== '') {
        $stmt = $CONNECT->prepare("INSERT INTO reviews (user_id, review_text) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $review);
        $stmt->execute();
	$msg = "📝 <b>Новый отзыв от пользователя</b>\n\n" .
    "🆔 ID пользователя: <code>{$_SESSION['user_id']}</code>\n" .
    "💬 Отзыв: <b>" . htmlspecialchars($review) . "</b>\n";
	sendTelegram($msg);
    }
    }
	
	
	
}


$result = $CONNECT->query("SELECT * FROM reviews ORDER BY created_at DESC");
$reviews = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отзывы пользователей</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="css/styles.css">
    <style>
        body {
            background-color: #121212;
            color: #f5f5f5;
            font-family: "Segoe UI", sans-serif;
            margin: 0;
            padding: 2rem;
        }
        h1 {
            color: #ff9800;
            text-align: center;
            margin-bottom: 2rem;
        }
        .container {
            max-width: 800px;
            margin: auto;
        }
        form {
            background-color: #1f1f1f;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 0 10px rgba(255, 140, 0, 0.2);
        }
        textarea {
            width: 100%;
            height: 100px;
            background-color: #2a2a2a;
            border: 1px solid #444;
            color: #f0f0f0;
            border-radius: 8px;
            padding: 10px;
            font-size: 1rem;
            resize: vertical;
        }
        button {
            background-color: #cc5500;
            color: #fff;
            padding: 10px 20px;
            margin-top: 10px;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #ff6600;
        }
        .review {
            background-color: #1e1e1e;
            border-left: 5px solid #ff9800;
            margin-bottom: 1.5rem;
            padding: 1rem;
            border-radius: 10px;
        }
        .meta {
            font-size: 0.85rem;
            color: #aaa;
            margin-bottom: 0.5rem;
        }
        .text {
            font-size: 1rem;
            white-space: pre-line;
        }
        .login-prompt {
            background: #1f1f1f;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            color: #ccc;
        }
        .login-prompt a {
            color: #ff9800;
            text-decoration: underline;
        }
    </style>
	
</head>
<body>
    <div class="container">
	<?php if ($is_logged_in): ?>
    <?php include 'pages/menu-wallet.php'; ?>
	<div class="nav-bar"></div>
	<?php endif; ?>

        <h1><?= htmlspecialchars($translations['reviews_h1']) ?></h1>

        <?php foreach ($reviews as $r): ?>
            <div class="review">
                <div class="meta">
                    User ID: <?= htmlspecialchars($r['user_id']) ?> • <?= date('d.m.Y H:i', strtotime($r['created_at'])) ?>
                </div>
                <div class="text">
                    <?= htmlspecialchars($r['review_text']) ?>
                </div>
            </div>
        <?php endforeach; ?>
		
        <?php if ($is_logged_in): ?>
            <form method="POST">
                <label for="review"><?= htmlspecialchars($translations['reviews_label']) ?></label><br>
                <textarea name="review" required placeholder="<?= htmlspecialchars($translations['reviews_text']) ?>"></textarea><br>
				<div style="display: flex; justify-content: center;">
				<div class="g-recaptcha" data-sitekey="6LdEiTgrAAAAAAKdDlOJixXe-NCKt2BZHQkOc3dX"></div>
				</div>
                <button type="submit"><?= htmlspecialchars($translations['reviews_button']) ?></button>
            </form>
			<script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <?php else: ?>
            <div class="login-prompt">
                <?= htmlspecialchars($translations['reviews_login_pr1']) ?><a href="/"><?= htmlspecialchars($translations['reviews_login_pr2']) ?></a>.
            </div>
        <?php endif; ?>
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
    </div>
</body>
</html>

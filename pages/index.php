<?php

if (isset($_SESSION['user_id'], $_SESSION['token'])) {
    $userId = $_SESSION['user_id'];
    $token = $_SESSION['token'];

    // Проверяем токен в БД
    $stmt = $CONNECT->prepare("SELECT session_token FROM members WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($dbToken);
    $stmt->fetch();
    $stmt->close();

    if ($token === $dbToken) {
        header("Location: dashboard");
        exit();
    } else {
        // Если токен не совпадает — удаляем сессию
        session_destroy();
        header("Location: /");
        exit();
    }
}





$today = date('Y-m-d');

if (!isset($_SESSION['visit_counted_date']) || $_SESSION['visit_counted_date'] !== $today) {
    $_SESSION['visit_counted_date'] = $today;

    $CONNECT->query("INSERT INTO visit_counter (page, visit_date, count) 
                     VALUES ('total', '$today', 1) 
                     ON DUPLICATE KEY UPDATE count = count + 1");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $passw = FormChars($_POST['sid']);

    // Подготовленный запрос на выборку пользователя
    $stmt = $CONNECT->prepare("SELECT id, wallet FROM members WHERE passw = ?");
    $stmt->bind_param("s", $passw);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo 'Your SID is wrong. Please check this information.';
        echo '<script> setTimeout(function(){ window.location.href = "/"; }, 2000); </script>';
        exit();
    }

    $user_id = (int)$row['id'];
    $wallet = $row['wallet'];

    // Генерируем токен
    $token = bin2hex(random_bytes(32));

    // Сохраняем токен в сессию и куки
    $_SESSION['user_id'] = $user_id;
    $_SESSION['wallet'] = $wallet;
    $_SESSION['token'] = $token;

    setcookie("id", $user_id, time() + 60 * 60 * 24 * 30, "/");
    setcookie("token", $token, time() + 60 * 60 * 24 * 30, "/");

    // Обновляем токен в БД
    $update = $CONNECT->prepare("UPDATE members SET session_token = ? WHERE id = ?");
    $update->bind_param("si", $token, $user_id);
    if (!$update->execute()) {
        //echo "Ошибка обновления токена: " . $update->error;
        exit();
    }
    $update->close();

    echo '<br><br><br><br><br><center><h3>Login is OK <br> ...Wait...</h3></center><br><br><br><br><br><br><br><br><br><br>';
    echo "<script> location.href='/dashboard'; </script>";
    exit();
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

// Получаем сообщения из базы
$stmt = $CONNECT->prepare("SELECT name, value FROM settings WHERE name IN ('message', 'message_ru', 'message_display')");
$stmt->execute();
$result = $stmt->get_result();

$settings = [];
while ($row = $result->fetch_assoc()) {
    $settings[$row['name']] = $row['value'];
}
$stmt->close();

// Определяем, какое сообщение использовать
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : $default_language;
$message = ($lang === 'ru' && !empty($settings['message_ru'])) ? $settings['message_ru'] : $settings['message'];
$message_display = $settings['message_display'] ?? '0';



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
			<p><?= htmlspecialchars_decode($translations['advantages_text6']) ?></p>
			<?php if ($message_display == '1' && !empty($message)): ?>
				<div class="message-box">
					<p><?= htmlspecialchars_decode($message) ?></p>
				</div>
			<?php endif; ?>

        </section>

      
		<?php

$settingStmt = $CONNECT->prepare("SELECT value FROM settings WHERE name = 'maintenance_mode'");
$settingStmt->execute();
$settingStmt->bind_result($maintenance);
$settingStmt->fetch();
$settingStmt->close();


if ($maintenance === 'on'): ?>
    <div style="background-color: #1b1b1b; color: #ffdda8; padding: 25px; text-align: center; font-size: 18px; border-radius: 8px; margin: 20px auto; max-width: 600px; box-shadow: 0 0 12px rgba(255, 140, 0, 0.3); font-family: Arial, sans-serif;">
        <strong style="color: #ffa94d;">⚙ <?= htmlspecialchars($translations['maintenance_h1']) ?></strong><br><br>
        <?= htmlspecialchars($translations['maintenance_text']) ?>
    </div>
<?php else: ?>
    <div class="form-container">
        <form method="post" name="mainform" onsubmit="return checkform()" class="login-form">
            <h2 style="color: #ffa94d; text-align: center;"><?= htmlspecialchars($translations['sign_in']) ?></h2>
            <input type="text" name="sid" placeholder="<?= htmlspecialchars($translations['sid_placeholder']) ?>">
            <button class="btn" style="width: 100%; padding: 10px; background-color: #ff8c00; border: none; border-radius: 4px; color: white; cursor: pointer;"><?= htmlspecialchars($translations['log_in']) ?></button>
            <p style="text-align: center; margin-top: 15px;"><?= $translations['no_account'] ?></p>
			<p style="text-align: center; margin-top: 15px;"><?= $translations['view_rewiew'] ?></p>
        </form>
    </div>
			<?php endif; ?>
				<a href="/anonbtcapk" class="download-button">
			<?= htmlspecialchars($translations['download_apk']) ?>
		</a>






    </div>
</body>
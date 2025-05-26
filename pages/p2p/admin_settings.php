<?php

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: dashboard");
    exit();
}

// Обработчик формы для сохранения изменений
if (isset($_POST['set_settings'])) {
    $escrow_wallet_address = mysqli_real_escape_string($CONNECT, $_POST['escrow_wallet_address']);
    $service_fee_address = mysqli_real_escape_string($CONNECT, $_POST['service_fee_address']);
    $message = mysqli_real_escape_string($CONNECT, $_POST['message']);
    $message_ru = mysqli_real_escape_string($CONNECT, $_POST['message_ru']);
    $message_display = $_POST['message_display'] == '1' ? 1 : 0;

    mysqli_query($CONNECT, "UPDATE `settings` SET `value` = '$escrow_wallet_address' WHERE `name` = 'escrow_wallet_address'");
    mysqli_query($CONNECT, "UPDATE `settings` SET `value` = '$service_fee_address' WHERE `name` = 'service_fee_address'");
    mysqli_query($CONNECT, "UPDATE `settings` SET `value` = '$message' WHERE `name` = 'message'");
    mysqli_query($CONNECT, "UPDATE `settings` SET `value` = '$message_ru' WHERE `name` = 'message_ru'");
    mysqli_query($CONNECT, "UPDATE `settings` SET `value` = '$message_display' WHERE `name` = 'message_display'");

    echo "<div class='success-message'>Настройки успешно обновлены!</div>";
}

// Получаем текущие значения из таблицы
$query = "SELECT * FROM `settings` WHERE `name` IN ('escrow_wallet_address', 'service_fee_address', 'message', 'message_ru', 'message_display')";
$result = mysqli_query($CONNECT, $query);
$settings = [];
while ($row = mysqli_fetch_assoc($result)) {
    $settings[$row['name']] = $row['value'];
}

// Применяем htmlspecialchars только если значение существует
$escrow_wallet_address = isset($settings['escrow_wallet_address']) ? htmlspecialchars($settings['escrow_wallet_address']) : '';
$service_fee_address = isset($settings['service_fee_address']) ? htmlspecialchars($settings['service_fee_address']) : '';
$message = isset($settings['message']) ? htmlspecialchars($settings['message']) : '';
$message_ru = isset($settings['message_ru']) ? htmlspecialchars($settings['message_ru']) : '';
$message_display = isset($settings['message_display']) ? $settings['message_display'] : '0';


if (isset($_POST['set_maintenance'])) {
    $new_mode = ($_POST['maintenance_mode'] === 'on') ? 'on' : 'off';

    // Обновляем режим обслуживания
    $stmt = $CONNECT->prepare("UPDATE settings SET value = ? WHERE name = 'maintenance_mode'");
    $stmt->bind_param("s", $new_mode);
    $stmt->execute();

    // Если включаем режим обслуживания, разлогиниваем всех кроме пользователя с id=7
    if ($new_mode === 'on') {
        $stmt = $CONNECT->prepare("UPDATE members SET session_token = NULL WHERE id != ?");
        $exclude_id = 7;
        $stmt->bind_param("i", $exclude_id);
        $stmt->execute();
    }

    echo "<div style='color: green;'>Настройка обновлена</div>";
}


// Получение текущего значения
$current = 'off';
$stmt1 = $CONNECT->prepare("SELECT value FROM settings WHERE name = 'maintenance_mode'");
$stmt1->execute();
$stmt1->bind_result($current);
$stmt1->fetch();
$stmt1->close();
?>

<!DOCTYPE html>
<html lang="ru">
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
    <?php include 'pages/p2p/menu_adm.php'; ?>

    <div class="container">
        <h2>Admin Settings Panel</h2>
        <h1>Редактирование настроек</h1>

        <form method="POST">
            <label for="escrow_wallet_address">Escrow Wallet Address:</label>
            <input type="text" name="escrow_wallet_address" id="escrow_wallet_address" value="<?= $escrow_wallet_address ?>" required><br>

            <label for="service_fee_address">Service Fee Address:</label>
            <input type="text" name="service_fee_address" id="service_fee_address" value="<?= $service_fee_address ?>" required><br>

            <label for="message">Сообщение (EN):</label><br>
			<textarea name="message" id="message" rows="4" cols="30"><?= $message ?></textarea><br><br>

			<label for="message_ru">Сообщение (RU):</label><br>
			<textarea name="message_ru" id="message_ru" rows="4" cols="30"><?= $message_ru ?></textarea><br><br>


            <label for="message_display">Отображать сообщение:</label>
            <select name="message_display" id="message_display">
                <option value="1" <?= $message_display == '1' ? 'selected' : '' ?>>Да</option>
                <option value="0" <?= $message_display == '0' ? 'selected' : '' ?>>Нет</option>
            </select><br>

            <button type="submit" name="set_settings">Сохранить изменения</button>
        </form>
			<div style="background-color: #1b1b1b; color: #ffdda8; padding: 20px; border-radius: 8px; max-width: 400px; margin: 20px auto; box-shadow: 0 0 10px rgba(255, 140, 0, 0.2); font-family: Arial, sans-serif;">
				<h3 style="margin-top: 0; color: #ffa94d;">⚙ Управление режимом обслуживания</h3>
				<form method="POST">
					<label for="maintenance_mode" style="display: block; margin-bottom: 10px;">Режим обслуживания:</label>
					<select name="maintenance_mode" id="maintenance_mode" style="width: 100%; padding: 8px; border: 1px solid #ff8c00; border-radius: 4px; margin-bottom: 15px; background-color: #2a2a2a; color: #ffd7a0;">
						<option value="off" <?= $current === 'off' ? 'selected' : '' ?>>Выключен</option>
						<option value="on" <?= $current === 'on' ? 'selected' : '' ?>>Включен</option>
					</select>
					<button type="submit" name="set_maintenance" style="background-color: #ff8c00; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer;">
						Сохранить
					</button>
				</form>
			</div>

    </div>
</body>
</html>
<?php

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: dashboard");
    exit();
}

// Обработчик формы для сохранения изменений
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $escrow_wallet_address = mysqli_real_escape_string($CONNECT, $_POST['escrow_wallet_address']);
    $service_fee_address = mysqli_real_escape_string($CONNECT, $_POST['service_fee_address']);
    $message = mysqli_real_escape_string($CONNECT, $_POST['message']);
    $message_display = $_POST['message_display'] == '1' ? 1 : 0;  // Преобразуем в булевое значение

    // Обновляем значения в таблице
    $query = "UPDATE `settings` SET `value` = '$escrow_wallet_address' WHERE `name` = 'escrow_wallet_address'";
    mysqli_query($CONNECT, $query);

    $query = "UPDATE `settings` SET `value` = '$service_fee_address' WHERE `name` = 'service_fee_address'";
    mysqli_query($CONNECT, $query);

    $query = "UPDATE `settings` SET `value` = '$message' WHERE `name` = 'message'";
    mysqli_query($CONNECT, $query);

    $query = "UPDATE `settings` SET `value` = '$message_display' WHERE `name` = 'message_display'";
    mysqli_query($CONNECT, $query);
    
    echo "<div class='success-message'>Настройки успешно обновлены!</div>";
}

// Получаем текущие значения из таблицы
$query = "SELECT * FROM `settings` WHERE `name` IN ('escrow_wallet_address', 'service_fee_address', 'message', 'message_display')";
$result = mysqli_query($CONNECT, $query);
$settings = [];
while ($row = mysqli_fetch_assoc($result)) {
    $settings[$row['name']] = $row['value'];
}

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
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fc;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            color: #333;
            text-align: center;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        label {
            font-size: 16px;
            color: #555;
        }
        input[type="text"], textarea, select {
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 100%;
        }
        button {
            padding: 10px 20px;
            font-size: 16px;
            color: #fff;
            background-color: #007bff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .success-message {
            padding: 15px;
            background-color: #28a745;
            color: white;
            border-radius: 4px;
            text-align: center;
            margin-top: 20px;
        }
        .container form > button {
            align-self: center;
        }
    </style>
</head>

<body>
    <?php include 'pages/p2p/menu_adm.php'; ?>

    <div class="container">
        <h2>Admin Settings Panel</h2>
        <h1>Редактирование настроек</h1>

        <form method="POST">
            <label for="escrow_wallet_address">Escrow Wallet Address:</label>
            <input type="text" name="escrow_wallet_address" id="escrow_wallet_address" value="<?= htmlspecialchars($settings['escrow_wallet_address']) ?>" required><br>

            <label for="service_fee_address">Service Fee Address:</label>
            <input type="text" name="service_fee_address" id="service_fee_address" value="<?= htmlspecialchars($settings['service_fee_address']) ?>" required><br>

            <label for="message">Сообщение:</label>
            <textarea name="message" id="message" rows="4" cols="50"><?= htmlspecialchars($settings['message']) ?></textarea><br>

            <label for="message_display">Отображать сообщение:</label>
            <select name="message_display" id="message_display">
                <option value="1" <?= $settings['message_display'] == '1' ? 'selected' : '' ?>>Да</option>
                <option value="0" <?= $settings['message_display'] == '0' ? 'selected' : '' ?>>Нет</option>
            </select><br>

            <button type="submit">Сохранить изменения</button>
        </form>
    </div>
</body>
</html>

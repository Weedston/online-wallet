<?php
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: dashboard");
    exit();
}


// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $escrow_wallet_address = $_POST['escrow_wallet_address'];
    $service_fee_address = $_POST['service_fee_address'];
    $message = $_POST['message'];
    $message_display = $_POST['message_display'] ? 1 : 0;

    // Обновление значений в базе данных
    $stmt = $pdo->prepare("UPDATE settings SET value = :value WHERE name = :name");
    
    $stmt->execute([':value' => $escrow_wallet_address, ':name' => 'escrow_wallet_address']);
    $stmt->execute([':value' => $service_fee_address, ':name' => 'service_fee_address']);
    $stmt->execute([':value' => $message, ':name' => 'message']);
    $stmt->execute([':value' => $message_display, ':name' => 'message_display']);
    
    // Сообщение об успешном обновлении
    echo "<p>Настройки успешно обновлены!</p>";
}

// Получение текущих значений из базы данных
$stmt = $pdo->query("SELECT name, value FROM settings");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['name']] = $row['value'];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админка: Редактирование настроек</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Редактирование настроек</h1>
    
    <form action="" method="POST">
        <div>
            <label for="escrow_wallet_address">Escrow Wallet Address:</label>
            <input type="text" name="escrow_wallet_address" value="<?= htmlspecialchars($settings['escrow_wallet_address']) ?>" required>
        </div>
        
        <div>
            <label for="service_fee_address">Service Fee Address:</label>
            <input type="text" name="service_fee_address" value="<?= htmlspecialchars($settings['service_fee_address']) ?>" required>
        </div>
        
        <div>
            <label for="message">Сообщение:</label>
            <textarea name="message" rows="4"><?= htmlspecialchars($settings['message']) ?></textarea>
        </div>
        
        <div>
            <label for="message_display">Отображать сообщение:</label>
            <input type="checkbox" name="message_display" value="1" <?= $settings['message_display'] == '1' ? 'checked' : '' ?>>
        </div>
        
        <button type="submit">Сохранить изменения</button>
    </form>
</body>
</html>

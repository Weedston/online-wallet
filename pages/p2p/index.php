<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}
require_once 'src/functions.php';

require_once __DIR__ . '/../../config.php';  // Убедитесь, что путь к файлу конфигурации правильный

$CONNECT = mysqli_connect(HOST, USER, PASS, DB);

if (!$CONNECT) {
    die("Connection failed: " . mysqli_connect_error());
}

// Проверяем, была ли нажата кнопка "Accept"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_ad'])) {
    $ad_id = intval($_POST['ad_id']);
    $buyer_id = $_SESSION['user_id'];
    $btc_amount = floatval($_POST['btc_amount']);

    // Получаем информацию об объявлении
    $ad_query = "SELECT * FROM ads WHERE id = '$ad_id'";
    $ad_result = mysqli_query($CONNECT, $ad_query);
    $ad = mysqli_fetch_assoc($ad_result);

    if ($btc_amount < $ad['min_amount_btc'] || $btc_amount > $ad['max_amount_btc']) {
        echo "Ошибка: сумма BTC должна быть в пределах минимальной и максимальной.";
    } else {
        // Обновляем статус объявления на "ожидание" и сохраняем информацию о покупателе
        $update_query = "UPDATE ads SET status = 'pending', buyer_id = '$buyer_id', amount_btc = '$btc_amount' WHERE id = '$ad_id'";
        if (mysqli_query($CONNECT, $update_query)) {
            // Добавляем уведомление для создателя объявления
            add_notification($ad['user_id'], "Your ad #$ad_id has been accepted and is in the pending status. Go to the \"Trade history\" section and continue the transaction.");

            // Перенаправляем пользователя на страницу деталей сделки
            header("Location: p2p-trade_details.php?ad_id=$ad_id");
            exit();
        } else {
            echo "Ошибка при обновлении статуса объявления: " . mysqli_error($CONNECT);
        }
    }
}

// Получение всех активных объявлений
$ads = mysqli_query($CONNECT, "SELECT ads.*, members.username FROM ads JOIN members ON ads.user_id = members.id WHERE ads.status = 'active'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>P2P Exchange BTC to Fiat</title>
    <link rel="stylesheet" href="../../css/styles.css">
    <style>
        /* Модальное окно */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.8);
            padding-top: 60px;
        }
        .modal-content {
            background-color: #1e1e1e;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 30%;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(255, 165, 0, 0.5);
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover, .close:focus {
            color: white;
            text-decoration: none;
            cursor: pointer;
        }
        /* Курсор при наведении на строку таблицы */
        tr.clickable-row {
            cursor: pointer;
        }
        /* Стили для кнопок в модальном окне */
        .modal-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .modal-buttons .btn {
            padding: 10px 20px;
            background-color: #ff9800;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .modal-buttons .btn:hover {
            background-color: #ff5722;
        }
        .modal-buttons .btn.cancel {
            background-color: #888;
        }
        .modal-buttons .btn.cancel:hover {
            background-color: #555;
        }
        .error-message {
            color: red;
            display: none;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'pages/p2p/menu.php'; ?>
        <h2>Active P2P Exchange Ads</h2>
        <table>
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Min BTC Amount</th>
                    <th>Max BTC Amount</th>
                    <th>Rate</th>
                    <th>Payment Methods</th>
                    <th>Fiat Amount</th>
                    <th>Trade Type</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($ad = mysqli_fetch_assoc($ads)) { 
                    $ad_id = $ad['id'];
                    $fiat_amount = $ad['max_amount_btc'] * $ad['rate'];

                    // Получение методов оплаты для этого объявления
                    $payment_methods_result = mysqli_query($CONNECT, "SELECT payment_method FROM ad_payment_methods WHERE ad_id = '$ad_id'");
                    $payment_methods = [];
                    while ($row = mysqli_fetch_assoc($payment_methods_result)) {
                        $payment_methods[] = $row['payment_method'];
                    }
                    $payment_methods_display = implode(', ', $payment_methods);
                ?>
                    <tr class="clickable-row" onclick="openModal('<?php echo $ad_id; ?>', '<?php echo htmlspecialchars($ad['user_id']); ?>', '<?php echo htmlspecialchars($ad['min_amount_btc']); ?>', '<?php echo htmlspecialchars($ad['max_amount_btc']); ?>', '<?php echo htmlspecialchars($ad['rate']); ?>', '<?php echo htmlspecialchars($payment_methods_display); ?>', '<?php echo number_format($fiat_amount, 2, '.', ' '); ?>', '<?php echo htmlspecialchars($ad['fiat_currency']); ?>', '<?php echo htmlspecialchars($ad['trade_type']); ?>', '<?php echo htmlspecialchars($ad['comment']); ?>')">
                        <td><?php echo htmlspecialchars($ad['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($ad['min_amount_btc']); ?></td>
                        <td><?php echo htmlspecialchars($ad['max_amount_btc']); ?></td>
                        <td><?php echo htmlspecialchars($ad['rate']); ?></td>
                        <td><?php echo htmlspecialchars($payment_methods_display); ?></td>
                        <td><?php echo number_format($fiat_amount, 2, '.', ' '); ?> <?php echo htmlspecialchars($ad['fiat_currency']); ?></td>
                        <td><?php echo htmlspecialchars($ad['trade_type'] == 'buy' ? 'Buy' : 'Sell'); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <div id="adModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Ad Details</h2>
            <p><strong>User ID:</strong> <span id="modal-user-id"></span></p>
            <p><strong>Min BTC Amount:</strong> <span id="modal-min-amount-btc"></span></p>
            <p><strong>Max BTC Amount:</strong> <span id="modal-max-amount-btc"></span></p>
            <p><strong>Rate:</strong> <span id="modal-rate"></span></p>
            <p><strong>Payment Methods:</strong> <span id="modal-payment-methods"></span></p>
            <p><strong>Fiat Amount:</strong> <span id="modal-fiat-amount"></span></p>
            <p><strong>Fiat Currency:</strong> <span id="modal-fiat-currency"></span></p>
            <p><strong>Trade Type:</strong> <span id="modal-trade-type"></span></p>
            <p><strong>Comment:</strong> <span id="modal-comment"></span></p>
            <form method="POST" action="" style="display:inline;">
                <input type="hidden" id="modal-ad-id" name="ad_id" value="">
                <label for="btc-amount">BTC Amount:</label>
                <input type="number" id="btc-amount" name="btc_amount" step="0.00000001" required oninput="validateBtcAmount()">
                <div class="error-message" id="btc-amount-error">BTC amount must be within the specified range.</div>
                <div class="modal-buttons" id="modal-buttons">
                    <button class="btn cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="accept_ad" class="btn" id="modal-accept-btn">Accept</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(adId, userId, minAmountBtc, maxAmountBtc, rate, paymentMethods, fiatAmount, fiatCurrency, tradeType, comment) {
            document.getElementById('modal-ad-id').value = adId;
            document.getElementById('modal-user-id').innerText = userId;
            document.getElementById('modal-min-amount-btc').innerText = minAmountBtc;
            document.getElementById('modal-max-amount-btc').innerText = maxAmountBtc;
            document.getElementById('modal-rate').innerText = rate;
            document.getElementById('modal-payment-methods').innerText = paymentMethods;
            document.getElementById('modal-fiat-amount').innerText = fiatAmount;
            document.getElementById('modal-fiat-currency').innerText = fiatCurrency;
            document.getElementById('modal-trade-type').innerText = tradeType;
            document.getElementById('modal-comment').innerText = comment;
            document.getElementById('adModal').style.display = 'block';

            const btcAmountInput = document.getElementById('btc-amount');
            btcAmountInput.min = minAmountBtc;
            btcAmountInput.max = maxAmountBtc;

            const currentUser = '<?php echo $_SESSION['user_id']; ?>';
            if (userId === currentUser) {
                document.getElementById('modal-accept-btn').style.display = 'none';
                btcAmountInput.disabled = true;
            } else {
                document.getElementById('modal-accept-btn').style.display = 'inline-block';
                btcAmountInput.disabled = false;
            }
        }

        function closeModal() {
            document.getElementById('adModal').style.display = 'none';
        }

        function validateBtcAmount() {
            const btcAmountInput = document.getElementById('btc-amount');
            const minAmountBtc = parseFloat(btcAmountInput.min);
            const maxAmountBtc = parseFloat(btcAmountInput.max);
            const btcAmount = parseFloat(btcAmountInput.value);
            const errorMessage = document.getElementById('btc-amount-error');

            if (btcAmount < minAmountBtc || btcAmount > maxAmountBtc) {
                errorMessage.style.display = 'block';
                document.getElementById('modal-accept-btn').disabled = true;
            } else {
                errorMessage.style.display = 'none';
                document.getElementById('modal-accept-btn').disabled = false;
            }
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('adModal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>
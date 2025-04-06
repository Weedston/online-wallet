<?php

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$CONNECT = mysqli_connect(HOST, USER, PASS, DB);

$user_id = $_SESSION['user_id'];

// Получение методов оплаты
$payment_methods_result = mysqli_query($CONNECT, "SELECT method_name FROM payment_methods");
$payment_methods = [];
while ($row = mysqli_fetch_assoc($payment_methods_result)) {
    $payment_methods[] = $row['method_name'];
}

// Обработка удаления и редактирования объявления через JSON-RPC
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = file_get_contents('php://input');
    $request = json_decode($input, true);

    if (isset($request['jsonrpc']) && $request['jsonrpc'] == '2.0') {
        $response = [];
        if ($request['method'] == 'deleteAd') {
            $ad_id = intval($request['params']['ad_id']);
            $delete_query = "DELETE FROM ads WHERE id = '$ad_id' AND user_id = '$user_id'";
            $delete_payment_methods_query = "DELETE FROM ad_payment_methods WHERE ad_id = '$ad_id'";

            if (mysqli_query($CONNECT, $delete_query) && mysqli_query($CONNECT, $delete_payment_methods_query)) {
                $response = [
                    'jsonrpc' => '2.0',
                    'result' => 'Ad successfully deleted!',
                    'id' => $request['id']
                ];
            } else {
                $response = [
                    'jsonrpc' => '2.0',
                    'error' => [
                        'code' => -32000,
                        'message' => 'Error deleting ad: ' . mysqli_error($CONNECT)
                    ],
                    'id' => $request['id']
                ];
            }
        } elseif ($request['method'] == 'editAd') {
            $ad_id = intval($request['params']['ad_id']);
            $min_amount_btc = floatval($request['params']['min_amount_btc']);
            $max_amount_btc = floatval($request['params']['max_amount_btc']);
            $rate = floatval($request['params']['rate']);
            $payment_methods = $request['params']['payment_methods'];
            $trade_type = mysqli_real_escape_string($CONNECT, $request['params']['trade_type']);
            $comment = mysqli_real_escape_string($CONNECT, $request['params']['comment']);
            $update_query = "UPDATE ads SET min_amount_btc = '$min_amount_btc', max_amount_btc = '$max_amount_btc', rate = '$rate', trade_type = '$trade_type', comment = '$comment' WHERE id = '$ad_id' AND user_id = '$user_id'";

            if (mysqli_query($CONNECT, $update_query)) {
                // Удаляем старые методы оплаты
                mysqli_query($CONNECT, "DELETE FROM ad_payment_methods WHERE ad_id = '$ad_id'");
                // Вставляем новые методы оплаты
                foreach ($payment_methods as $method) {
                    $method = mysqli_real_escape_string($CONNECT, $method);
                    mysqli_query($CONNECT, "INSERT INTO ad_payment_methods (ad_id, payment_method) VALUES ('$ad_id', '$method')");
                }
                $response = [
                    'jsonrpc' => '2.0',
                    'result' => 'Ad successfully updated!',
                    'id' => $request['id']
                ];
            } else {
                $response = [
                    'jsonrpc' => '2.0',
                    'error' => [
                        'code' => -32000,
                        'message' => 'Error updating ad: ' . mysqli_error($CONNECT)
                    ],
                    'id' => $request['id']
                ];
            }
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    // If the request is not a valid JSON-RPC request, return an error response
    header('Content-Type: application/json');
    $error_response = [
        'jsonrpc' => '2.0',
        'error' => [
            'code' => -32600,
            'message' => 'Invalid Request'
        ]
    ];
    echo json_encode($error_response);
    exit();
}

// Получение информации о пользователе
$user = mysqli_fetch_assoc(mysqli_query($CONNECT, "SELECT * FROM members WHERE id = '$user_id'"));

// Получение объявлений пользователя
$ads = mysqli_query($CONNECT, "SELECT * FROM ads WHERE user_id = '$user_id'");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="../../css/styles.css">
    <script>
        async function deleteAd(adId) {
            if (!confirm('Are you sure you want to delete this ad?')) return;

            const response = await fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    jsonrpc: '2.0',
                    method: 'deleteAd',
                    params: { ad_id: adId },
                    id: Date.now()
                })
            });
            const result = await response.json();
            if (result.error) {
                alert(result.error.message);
            } else {
                alert(result.result);
                document.getElementById('ad-row-' + adId).remove();
            }
        }

        function openEditModal(adId, currentData) {
            document.getElementById('edit-ad-id').value = adId;
            document.getElementById('edit-date').value = currentData.date;
            document.getElementById('edit-min-amount').value = currentData.minAmountBtc;
            document.getElementById('edit-max-amount').value = currentData.maxAmountBtc;
            document.getElementById('edit-rate').value = currentData.rate;
            const paymentMethodsSelect = document.getElementById('edit-payment-methods');
            paymentMethodsSelect.innerHTML = ''; // Clear existing options
            const allPaymentMethods = <?php echo json_encode($payment_methods); ?>;
            allPaymentMethods.forEach(method => {
                let optionElement = document.createElement('option');
                optionElement.value = method;
                optionElement.text = method;
                if (currentData.paymentMethods.includes(method)) {
                    optionElement.selected = true;
                }
                paymentMethodsSelect.appendChild(optionElement);
            });
            document.getElementById('edit-trade-type').value = currentData.tradeType;
            document.getElementById('edit-comment').value = currentData.comment;
            document.getElementById('edit-id').innerText = adId;
            document.getElementById('editModal').style.display = 'block';
        }

        async function editAd() {
            const adId = document.getElementById('edit-ad-id').value;
            const date = document.getElementById('edit-date').value;
            const min_amount_btc = document.getElementById('edit-min-amount').value;
            const max_amount_btc = document.getElementById('edit-max-amount').value;
            const rate = document.getElementById('edit-rate').value;
            const payment_methods = Array.from(document.getElementById('edit-payment-methods').selectedOptions).map(option => option.value);
            const trade_type = document.getElementById('edit-trade-type').value;
            const comment = document.getElementById('edit-comment').value;

            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        jsonrpc: '2.0',
                        method: 'editAd',
                        params: { ad_id: adId, date: date, min_amount_btc: min_amount_btc, max_amount_btc: max_amount_btc, rate: rate, payment_methods: payment_methods, trade_type: trade_type, comment: comment },
                        id: Date.now()
                    })
                });

                const text = await response.text();

                if (!text) {
                    throw new Error('Empty response text');
                }

                const result = JSON.parse(text);
                if (result.error) {
                    alert(result.error.message);
                } else {
                    alert(result.result);
                    // Обновить данные объявления на странице без перезагрузки
                    document.getElementById('date-' + adId).innerText = date;
                    document.getElementById('min-amount-' + adId).innerText = min_amount_btc;
                    document.getElementById('max-amount-' + adId).innerText = max_amount_btc;
                    document.getElementById('rate-' + adId).innerText = rate;
                    document.getElementById('payment-methods-' + adId).innerText = payment_methods.join(', ');
                    document.getElementById('trade-type-' + adId).innerText = trade_type;
                    document.getElementById('comment-' + adId).innerText = comment;
                    document.getElementById('editModal').style.display = 'none';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while saving the ad. Please try again.');
            }
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function calculateFiatAmount() {
            const min_amount_btc = document.getElementById('edit-min-amount').value;
            const max_amount_btc = document.getElementById('edit-max-amount').value;
            const rate = document.getElementById('edit-rate').value;
            const min_fiat_amount = Math.round(min_amount_btc * rate);
            const max_fiat_amount = Math.round(max_amount_btc * rate);
            document.getElementById('edit-min-fiat-amount').value = min_fiat_amount;
            document.getElementById('edit-max-fiat-amount').value = max_fiat_amount;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const paymentMethodsSelect = document.getElementById('edit-payment-methods');
            <?php foreach ($payment_methods as $index => $method) { ?>
                let optionElement<?php echo $index; ?> = document.createElement('option');
                optionElement<?php echo $index; ?>.value = '<?php echo htmlspecialchars($method); ?>';
                optionElement<?php echo $index; ?>.text = '<?php echo htmlspecialchars($method); ?>';
                paymentMethodsSelect.appendChild(optionElement<?php echo $index; ?>);
            <?php } ?>
        });
    </script>
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
        .modal-content input, .modal-content textarea, .modal-content select {
            width: calc(100% - 20px);
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #FF9900;
            border-radius: 5px;
            background: #2a2a2a;
            color: white;
        }
        .modal-content button {
            background-color: #FF9900;
            color: black;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .modal-content button:hover {
            background-color: darkorange;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'pages/p2p/menu.php'; ?>
        <h2>User Profile</h2>
        <p><strong>Your ID:</strong> <?php echo htmlspecialchars($user_id); ?></p>
        <p><strong>Wallet:</strong> <?php echo htmlspecialchars($user['wallet']); ?></p>
        <p><strong>Balance:</strong> <?php echo htmlspecialchars($user['balance']); ?></p>

        <h2>Your Ads</h2>
        <table>
            <thead>
                <tr>
                    <th>Ad ID</th>
                    <th>Date</th>
                    <th>Min BTC Amount</th>
                    <th>Max BTC Amount</th>
                    <th>Rate</th>
                    <th>Payment Methods</th>
                    <th>Fiat Amount</th>
                    <th>Trade Type</th>
                    <th>Comment</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($ad = mysqli_fetch_assoc($ads)) { 
                    $min_fiat_amount = round($ad['min_amount_btc'] * $ad['rate']);
                    $max_fiat_amount = round($ad['max_amount_btc'] * $ad['rate']);
                    // Получение методов оплаты для этого объявления
                    $ad_payment_methods_result = mysqli_query($CONNECT, "SELECT payment_method FROM ad_payment_methods WHERE ad_id = '{$ad['id']}'");
                    $ad_payment_methods = [];
                    while ($row = mysqli_fetch_assoc($ad_payment_methods_result)) {
                        $ad_payment_methods[] = $row['payment_method'];
                    }
                    // Если методов оплаты нет, получаем весь список
                    if (empty($ad_payment_methods)) {
                        $ad_payment_methods = $payment_methods;
                    }
                ?>
                    <tr id="ad-row-<?php echo $ad['id']; ?>">
                        <td id="id-<?php echo $ad['id']; ?>"><?php echo htmlspecialchars($ad['id']) ?? ''; ?></td>
                        <td id="date-<?php echo $ad['id']; ?>"><?php echo htmlspecialchars($ad['created_at']) ?? ''; ?></td>
                        <td id="min-amount-<?php echo $ad['id']; ?>"><?php echo htmlspecialchars($ad['min_amount_btc']) ?? ''; ?></td>
                        <td id="max-amount-<?php echo $ad['id']; ?>"><?php echo htmlspecialchars($ad['max_amount_btc']) ?? ''; ?></td>
                        <td id="rate-<?php echo $ad['id']; ?>"><?php echo htmlspecialchars($ad['rate']) ?? ''; ?></td>
                        <td id="payment-methods-<?php echo $ad['id']; ?>"><?php echo htmlspecialchars(implode(', ', $ad_payment_methods)) ?? ''; ?></td>
                        <td id="fiat-amount-<?php echo $ad['id']; ?>"><?php echo htmlspecialchars($min_fiat_amount . ' - ' . $max_fiat_amount) ?? ''; ?></td>
                        <td id="trade-type-<?php echo $ad['id']; ?>"><?php echo htmlspecialchars($ad['trade_type']) ?? ''; ?></td>
                        <td id="comment-<?php echo $ad['id']; ?>"><?php echo htmlspecialchars($ad['comment']) ?? ''; ?></td>
                        <td>
                            <button onclick="openEditModal(<?php echo $ad['id']; ?>, {
                                date: '<?php echo htmlspecialchars($ad['created_at']) ?? ''; ?>',
                                minAmountBtc: '<?php echo htmlspecialchars($ad['min_amount_btc']) ?? ''; ?>',
                                maxAmountBtc: '<?php echo htmlspecialchars($ad['max_amount_btc']) ?? ''; ?>',
                                rate: '<?php echo htmlspecialchars($ad['rate']) ?? ''; ?>',
                                paymentMethods: '<?php echo htmlspecialchars(implode(',', $ad_payment_methods)) ?? ''; ?>',
                                tradeType: '<?php echo htmlspecialchars($ad['trade_type']) ?? ''; ?>',
                                comment: '<?php echo htmlspecialchars($ad['comment']) ?? ''; ?>'
                            })" class="btn">Edit</button>
                            <button onclick="deleteAd(<?php echo $ad['id']; ?>)" class="btn">Delete</button>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <!-- Модальное окно для редактирования объявления -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Edit Ad</h2>
            <p><strong>Ad ID:</strong> <span id="edit-id"></span></p>
            <form onsubmit="event.preventDefault(); editAd();">
                <input type="hidden" id="edit-ad-id">
                <label for="edit-date">Date:</label>
                <input type="text" id="edit-date" readonly>
                <label for="edit-min-amount">Min BTC Amount:</label>
                <input type="number" id="edit-min-amount" step="0.00000001" required oninput="calculateFiatAmount()">
                <label for="edit-max-amount">Max BTC Amount:</label>
                <input type="number" id="edit-max-amount" step="0.00000001" required oninput="calculateFiatAmount()">
                <label for="edit-rate">Rate:</label>
                <input type="number" id="edit-rate" step="0.01" required oninput="calculateFiatAmount()">
                <label for="edit-payment-methods">Payment Methods:</label>
                <select id="edit-payment-methods" multiple required>
                    <?php foreach ($payment_methods as $method) { ?>
                        <option value="<?php echo htmlspecialchars($method); ?>"><?php echo htmlspecialchars($method); ?></option>
                    <?php } ?>
                </select>
                <label for="edit-min-fiat-amount">Min Fiat Amount:</label>
                <input type="number" id="edit-min-fiat-amount" readonly>
                <label for="edit-max-fiat-amount">Max Fiat Amount:</label>
                <input type="number" id="edit
<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}
require_once 'src/functions.php';

$CONNECT = mysqli_connect(HOST, USER, PASS, DB);

if (!$CONNECT) {
    die("Connection failed: " . mysqli_connect_error());
}

$error_message = '';

// Get user balance
$user_id = $_SESSION['user_id'];
$balance_result = mysqli_query($CONNECT, "SELECT balance FROM members WHERE id = '$user_id'");
$balance_row = mysqli_fetch_assoc($balance_result);
$balance = $balance_row['balance'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_ad'])) {
    // Защита от повторной отправки
    if (!isset($_POST['form_token']) || $_POST['form_token'] !== $_SESSION['form_token']) {
       // header('Location: /p2p');;
    }
    unset($_SESSION['form_token']); // Предотвращаем повторную отправку

    $ad_id = intval($_POST['ad_id']);
	error_log("@@@@@@@Zdes est ad: $ad_id");
    $user_id = $_SESSION['user_id'];
    $btc_amount = $_POST['btc_amount']; 
	error_log("BBBBBBBBBBBBBBBBBBBB: ad_id.: $ad_id");
	error_log("EEEEEEEEEEEEEEEEEEEE: btc_amount.: $btc_amount");
    // Получаем объявление
    $ad_query = mysqli_query($CONNECT, "SELECT * FROM ads WHERE id = '$ad_id'");
    $ad = mysqli_fetch_assoc($ad_query);
    if (!$ad) {
        die('Ad not found.');
    }


	$user_role = getUserRole($ad, $user_id);

if ($user_role === 'buyer') {
    $buyer_id = $user_id;
    $seller_id = $ad['user_id'];
} elseif ($user_role === 'seller') {
    $buyer_id = $ad['user_id'];
    $seller_id = $user_id;
} else {
    // На случай, если пользователь не связан с объявлением (например, злоумышленник)
    die("Access denied: unauthorized user.");
}
$wallet_query = mysqli_query($CONNECT, "SELECT wallet FROM members WHERE id = '$seller_id'");
    $seller_wallet = mysqli_fetch_assoc($wallet_query);
	error_log("DDDDDDDDDDDDDDDDDD: seller_wallet: " . $seller_wallet['wallet']);
    if (!$seller_wallet) {
        die('seller wallet not found.');
    }


if (empty($error_message)) {
            // Отправляем BTC в эскроу с учетом комиссии
            $tx_result = sendToEscrow($ad_id, $seller_wallet['wallet'], $btc_amount, $CONNECT);

            if ($tx_result['success']) {
                $txid = $tx_result['txid'];
                //$seller_pubkey = $ad['seller_pubkey'];
                //$arbiter_pubkey = $ad['arbiter_pubkey'];

                
                // Запись в escrow_deposits
                $insert_query = "INSERT INTO escrow_deposits (
                    ad_id, escrow_address, buyer_pubkey, seller_pubkey, arbiter_pubkey, txid, btc_amount, status
                ) VALUES (
                    '$ad_id', '$escrow_address', '$buyer_pubkey', '$seller_pubkey', '$arbiter_pubkey', '$txid', '$btc_amount', 'btc_deposited'
                )";
                mysqli_query($CONNECT, $insert_query);
				addServiceComment($ad_id, "BTC deposited to escrow wallet. TXID: $txid, Amount: $btc_amount BTC <p id='confirmationsResult'>Confirmations: ...</p> We are waiting for payment from the buyer", 'deposit');

                // Обновляем объявление
    $update_query = "
        UPDATE ads SET 
            status = 'pending', 
            buyer_id = '$buyer_id', 
            seller_id = '$seller_id',
            amount_btc = '$btc_amount' 
        WHERE id = '$ad_id'
    ";

    if (mysqli_query($CONNECT, $update_query)) {
        add_notification(
            $ad['user_id'],
            "Your ad #$ad_id has been accepted and is in the pending status. 
            Go to the <a href=\"p2p-trade_history\">Trade history</a> section and continue the transaction."
        );
        header("Location: p2p-trade_details?ad_id=$ad_id");
        exit();
    } else {
        echo "Error updating ad: " . mysqli_error($CONNECT);
    }
            } else {
                $error_message = $tx_result['error'] ?? 'Error: Failed to complete escrow transfer.';
            }
        }
    $btc_amount = $ad['amount_btc'];

    }


// Get all active ads
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
        /* Modal window */
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
        /* Cursor on table row hover */
        tr.clickable-row {
            cursor: pointer;
        }
        /* Styles for buttons in modal */
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
        .balance-highlight {
            color: orange;
            font-weight: bold;
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

                    // Get payment methods for this ad
                    $payment_methods_result = mysqli_query($CONNECT, "SELECT payment_method FROM ad_payment_methods WHERE ad_id = '$ad_id'");
                    $payment_methods = [];
                    while ($row = mysqli_fetch_assoc($payment_methods_result)) {
                        $payment_methods[] = $row['payment_method'];
                    }
                    $payment_methods_display = implode(', ', $payment_methods);
					
                ?>
                    <tr class="clickable-row" data-ad-id="<?php echo $ad_id; ?>" data-user-id="<?php echo htmlspecialchars($ad['user_id'] ?? ''); ?>" data-min-amount-btc="<?php echo htmlspecialchars($ad['min_amount_btc'] ?? ''); ?>" data-max-amount-btc="<?php echo htmlspecialchars($ad['max_amount_btc'] ?? ''); ?>" data-rate="<?php echo htmlspecialchars($ad['rate'] ?? ''); ?>" data-payment-methods="<?php echo htmlspecialchars($payment_methods_display ?? ''); ?>" data-fiat-amount="<?php echo number_format($fiat_amount, 2, '.', ' '); ?>" data-fiat-currency="<?php echo htmlspecialchars($ad['fiat_currency'] ?? ''); ?>" data-trade-type="<?php echo htmlspecialchars($ad['trade_type'] ?? ''); ?>" data-comment="<?php echo htmlspecialchars($ad['comment'] ?? ''); ?>">
                        
						<td><?php echo htmlspecialchars($ad['user_id'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($ad['min_amount_btc'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($ad['max_amount_btc'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($ad['rate'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($payment_methods_display ?? ''); ?></td>
                        <td><?php echo number_format($fiat_amount, 2, '.', ' '); ?> <?php echo htmlspecialchars($ad['fiat_currency'] ?? ''); ?></td>
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
            <p><strong>Your Balance:</strong> <span id="modal-user-balance" class="balance-highlight"><?php echo $balance; ?> BTC</span></p>
            <form method="POST" action="" style="display:inline;">
                <input type="hidden" id="modal-ad-id" name="ad_id" value="">
                <label for="btc-amount">BTC Amount:</label>
                <input type="number" id="btc-amount" name="btc_amount" step="0.00000001" required oninput="validateBtcAmount()">
                <div class="error-message" id="btc-amount-error">BTC amount must be within the specified range.</div>
                <div class="modal-buttons" id="modal-buttons">
                    <button class="btn cancel" type="button" onclick="closeModal()">Cancel</button>
					<?php
						if (empty($_SESSION['form_token'])) {
						$_SESSION['form_token'] = bin2hex(random_bytes(32));
						}
					?>
					<input type="hidden" name="form_token" value="<?echo $_SESSION['form_token'] ?>">
                    <button type="submit" name="accept_ad" class="btn" id="modal-accept-btn">Accept</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($error_message)) { ?>
        <div id="errorModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeErrorModal()">&times;</span>
                <p><?php echo $error_message; ?></p>
            </div>
        </div>
        <script>
            document.getElementById('errorModal').style.display = 'block';

            function closeErrorModal() {
                document.getElementById('errorModal').style.display = 'none';
            }

            window.onclick = function(event) {
                if (event.target == document.getElementById('errorModal')) {
                    closeErrorModal();
                }
            }
        </script>
    <?php } ?>

    <script>
        document.querySelectorAll('.clickable-row').forEach(row => {
            row.addEventListener('click', function() {
                const adId = this.dataset.adId;
				
                const userId = this.dataset.userId;
                const minAmountBtc = this.dataset.minAmountBtc;
                const maxAmountBtc = this.dataset.maxAmountBtc;
                const rate = this.dataset.rate;
                const paymentMethods = this.dataset.paymentMethods;
                const fiatAmount = this.dataset.fiatAmount;
                const fiatCurrency = this.dataset.fiatCurrency;
                const tradeType = this.dataset.tradeType;
                const comment = this.dataset.comment;

                openModal(adId, userId, minAmountBtc, maxAmountBtc, rate, paymentMethods, fiatAmount, fiatCurrency, tradeType, comment);
            });
        });

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

            if (comment.trim() === '') {
                document.getElementById('modal-comment').innerText = 'No comment.';
            } else {
                document.getElementById('modal-comment').innerText = comment;
            }

            document.getElementById('adModal').style.display = 'block';

            const btcAmountInput = document.getElementById('btc-amount');
            btcAmountInput.min = minAmountBtc;
            btcAmountInput.max = maxAmountBtc;
            btcAmountInput.setAttribute('data-rate', rate);
            btcAmountInput.setAttribute('data-trade-type', tradeType);

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
            const rate = parseFloat(btcAmountInput.getAttribute('data-rate'));
            const tradeType = btcAmountInput.getAttribute('data-trade-type');
            const fiatAmountElement = document.getElementById('modal-fiat-amount');

            if (btcAmount < minAmountBtc || btcAmount > maxAmountBtc) {
                errorMessage.style.display = 'block';
                document.getElementById('modal-accept-btn').disabled = true;
            } else {
                errorMessage.style.display = 'none';
                document.getElementById('modal-accept-btn').disabled = false;
            }

            let fiatAmount = btcAmount * rate;
            if (tradeType === 'sell') {
                fiatAmount = btcAmount / rate;
            }
            fiatAmountElement.innerText = fiatAmount.toFixed(2);
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('adModal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>
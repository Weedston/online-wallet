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

// Check if "Accept" button was pressed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_ad'])) {
    $ad_id = intval($_POST['ad_id']);
    $buyer_id = $_SESSION['user_id'];
    $btc_amount = floatval($_POST['btc_amount']);

    // Get ad info
    $ad_query = "SELECT * FROM ads WHERE id = '$ad_id'";
    $ad_result = mysqli_query($CONNECT, $ad_query);
    $ad = mysqli_fetch_assoc($ad_result);

    // Balance check for "buy" trade type
    if ($ad['trade_type'] == 'buy') {
        $user_query = "SELECT balance FROM members WHERE id = '$buyer_id'";
        $user_result = mysqli_query($CONNECT, $user_query);
        $user = mysqli_fetch_assoc($user_result);
        
        if ($btc_amount > $user['balance']) {
            $error_message = "Error: Insufficient BTC balance for the transaction.";
        }
    }

    if (empty($error_message) && ($btc_amount < $ad['min_amount_btc'] || $btc_amount > $ad['max_amount_btc'])) {
        $error_message = "Error: The BTC amount must be within the specified range.";
    }

    if (empty($error_message)) {
        // Get wallet addresses and public keys of participants from the database
        $buyer_wallet_result = mysqli_query($CONNECT, "SELECT wallet, pubkey FROM members WHERE id = '$buyer_id'");
        if ($buyer_wallet_row = mysqli_fetch_assoc($buyer_wallet_result)) {
            $buyer_wallet = $buyer_wallet_row['wallet'];
            $buyer_pubkey = $buyer_wallet_row['pubkey'];
        } else {
            $error_message = "Error: Buyer wallet not found.";
        }

        $seller_id = $ad['user_id'];
        $seller_pubkey_result = mysqli_query($CONNECT, "SELECT pubkey FROM members WHERE id = '$seller_id'");
        if ($seller_pubkey_row = mysqli_fetch_assoc($seller_pubkey_result)) {
            $seller_pubkey = $seller_pubkey_row['pubkey'];
        } else {
            $error_message = "Error: Seller public key not found.";
        }

        $arbiter_id = 182; // Set arbiter_id to 182
        $arbiter_pubkey_result = mysqli_query($CONNECT, "SELECT pubkey FROM members WHERE id = '$arbiter_id'");
        if ($arbiter_pubkey_row = mysqli_fetch_assoc($arbiter_pubkey_result)) {
            $arbiter_pubkey = $arbiter_pubkey_row['pubkey'];
        } else {
            $error_message = "Error: Arbiter public key not found.";
        }

        if (empty($error_message)) {
            // Log the public keys for debugging
            error_log("Buyer Public Key: $buyer_pubkey");
            error_log("Seller Public Key: $seller_pubkey");
            error_log("Arbiter Public Key: $arbiter_pubkey");

            // Validate public keys
            if (!ctype_xdigit($buyer_pubkey) || !ctype_xdigit($seller_pubkey) || !ctype_xdigit($arbiter_pubkey)) {
                $error_message = "Error: One or more public keys are not valid hex strings.";
                error_log("Invalid Public Key: Buyer: $buyer_pubkey, Seller: $seller_pubkey, Arbiter: $arbiter_pubkey");
            }

            if (empty($error_message)) {
                // Create multisig address
                $multisig_result = bitcoinRPC('createmultisig', [2, [$buyer_pubkey, $seller_pubkey, $arbiter_pubkey]]);
                error_log("Multisig Result: " . json_encode($multisig_result)); // Log the multisig result
                if (isset($multisig_result['address'])) {
                    $multisig_address = $multisig_result['address'];
                    $redeemScript = $multisig_result['redeemScript'];
                } else {
                    $error_message = "Error: Failed to create multisig address.";
                    error_log("Multisig creation error: " . json_encode($multisig_result));
                }

                if (empty($error_message)) {
                    // Get unspent transaction outputs (UTXOs) for the buyer
                    $unspent_outputs = bitcoinRPC('listunspent', [1, 9999999, [$buyer_wallet]]);
                    if (empty($unspent_outputs)) {
                        $error_message = "Error: No unspent outputs found for the buyer.";
                    } else {
                        $txid = $unspent_outputs[0]['txid'];
                        $vout = $unspent_outputs[0]['vout'];
                        $amount = $unspent_outputs[0]['amount']; // Get the amount of the UTXO

                        if ($amount < $btc_amount) {
                            $error_message = "Error: Insufficient UTXO amount.";
                        }

                        if (empty($error_message)) {
                            // Create escrow transaction
                            $inputs = [["txid" => $txid, "vout" => $vout]];
                            $escrow_amount = $btc_amount * 0.99; // Subtract 1% service fee
                            $service_fee = $btc_amount * 0.01; // 1% service fee

                            // Ensure the amounts are correctly formatted
                            $escrow_amount = number_format($escrow_amount, 8, '.', '');
                            $service_fee = number_format($service_fee, 8, '.', '');

                            // Sum of outputs must be equal to the input amount
                            $change_amount = $amount - ($escrow_amount + $service_fee);
                            if ($change_amount < 0) {
                                $error_message = "Error: The sum of outputs is greater than the input amount.";
                            }

                            $outputs = [
                                $multisig_address => (float)$escrow_amount,
                                "tb1qtdxq5dzdv29tkw7t3d07qqeuz80y9k80ynu5tn" => (float)$service_fee
                            ]; // Replace <service_address> with actual service address

                            if ($change_amount > 0) {
                                $outputs[$buyer_wallet] = (float)number_format($change_amount, 8, '.', '');
                            }

                            $raw_tx_result = bitcoinRPC('createrawtransaction', [$inputs, $outputs]);
                            error_log("Raw TX Result: " . json_encode($raw_tx_result)); // Log the raw transaction result
                            if (isset($raw_tx_result)) {
                                // Sign the transaction using the wallet
                                $signed_tx_result = bitcoinRPC('signrawtransactionwithwallet', [$raw_tx_result]);
                                error_log("Signed TX Result: " . json_encode($signed_tx_result)); // Log the signed transaction result
                                if (isset($signed_tx_result['hex'])) {
                                    $signed_tx = $signed_tx_result['hex'];
                                    $txid_result = bitcoinRPC('sendrawtransaction', [$signed_tx]);
                                    if (isset($txid_result)) {
                                        $txid = $txid_result;
                                    } else {
                                        $error_message = "Error: Failed to send raw transaction.";
                                    }
                                } else {
                                    $error_message = "Error: Failed to sign raw transaction.";
                                }
                            } else {
                                $error_message = "Error: Failed to create raw transaction.";
                            }
                        }
                    }

                    if (empty($error_message)) {
                        // Insert escrow deposit information into database
                        $insert_query = "INSERT INTO escrow_deposits (ad_id, escrow_address, buyer_pubkey, seller_pubkey, arbiter_pubkey, txid, btc_amount, status) 
                                         VALUES ('$ad_id', '$multisig_address', '$buyer_pubkey', '$seller_pubkey', '$arbiter_pubkey', '$txid', '$btc_amount', 'btc_deposited')";
                        mysqli_query($CONNECT, $insert_query);

                        // Update ad status to "pending" and save buyer info
                        $update_query = "UPDATE ads SET status = 'pending', buyer_id = '$buyer_id', amount_btc = '$btc_amount' WHERE id = '$ad_id'";
                        if (mysqli_query($CONNECT, $update_query)) {
                            // Add notification for the ad creator
                            add_notification($ad['user_id'], "Your ad #$ad_id has been accepted and is in the pending status. Go to the <a href=\"p2p-trade_history\">Trade history</a> section and continue the transaction.");

                            // Redirect user to trade details page
                            header("Location: p2p-trade_details?ad_id=$ad_id");
                            exit();
                        } else {
                            $error_message = "Error updating ad status: " . mysqli_error($CONNECT);
                        }
                    }
                }
            }
        }
    }
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
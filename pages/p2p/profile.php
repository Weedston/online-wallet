<?php

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$CONNECT = mysqli_connect(HOST, USER, PASS, DB);

$user_id = $_SESSION['user_id'];

// Обработка удаления объявления через JSON-RPC
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = file_get_contents('php://input');
    $request = json_decode($input, true);

    if (isset($request['jsonrpc']) && $request['jsonrpc'] == '2.0' && isset($request['method']) && $request['method'] == 'deleteAd') {
        $ad_id = intval($request['params']['ad_id']);
        $delete_query = "DELETE FROM ads WHERE id = '$ad_id' AND user_id = '$user_id'";

        $response = [];

        if (mysqli_query($CONNECT, $delete_query)) {
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

        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
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
    </script>
</head>
<body>
    <?php include 'pages/p2p/menu.php'; ?>
    <div class="container">
        <h2>User Profile</h2>
        <p><strong>Your ID:</strong> <?php echo htmlspecialchars($user_id); ?></p>
        <p><strong>Wallet:</strong> <?php echo htmlspecialchars($user['wallet']); ?></p>
        <p><strong>Balance:</strong> <?php echo htmlspecialchars($user['balance']); ?></p>

        <h2>Your Ads</h2>
        <table>
            <thead>
                <tr>
                    <th>BTC Amount</th>
                    <th>Rate</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($ad = mysqli_fetch_assoc($ads)) { ?>
                    <tr id="ad-row-<?php echo $ad['id']; ?>">
                        <td><?php echo htmlspecialchars($ad['amount_btc']); ?></td>
                        <td><?php echo htmlspecialchars($ad['rate']); ?></td>
                        <td><?php echo htmlspecialchars($ad['payment_method']); ?></td>
                        <td><?php echo htmlspecialchars($ad['status']); ?></td>
                        <td><?php echo htmlspecialchars($ad['created_at']); ?></td>
                        <td>
                            <form method="POST" action="edit_ad.php" style="display:inline;">
                                <input type="hidden" name="ad_id" value="<?php echo $ad['id']; ?>">
                                <button type="submit" name="edit_ad" class="btn">Edit</button>
                            </form>
                            <button onclick="deleteAd(<?php echo $ad['id']; ?>)" class="btn">Delete</button>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</body>
</html>
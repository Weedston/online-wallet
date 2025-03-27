<?php

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$CONNECT = mysqli_connect(HOST, USER, PASS, DB);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accept_ad"])) {
    $user_id = $_SESSION['user_id'];
    $ad_id = intval($_POST['ad_id']);

    // Обновляем статус объявления на "completed"
    $query = "UPDATE ads SET status = 'completed' WHERE id = '$ad_id'";

    if (mysqli_query($CONNECT, $query)) {
        echo "<p style='color:green;'>Ad accepted successfully!</p>";
    } else {
        echo "<p style='color:red;'>Error: " . mysqli_error($CONNECT) . "</p>";
    }

    // Создаем запись транзакции
    $ad = mysqli_fetch_assoc(mysqli_query($CONNECT, "SELECT * FROM ads WHERE id = '$ad_id'"));
    $seller_id = $ad['user_id'];
    $btc_amount = $ad['amount_btc'];
    $fiat_amount = $ad['rate'] * $btc_amount;

    $query = "INSERT INTO transactions (ad_id, buyer_id, seller_id, btc_amount, fiat_amount, status) VALUES ('$ad_id', '$user_id', '$seller_id', '$btc_amount', '$fiat_amount', 'pending')";

    if (mysqli_query($CONNECT, $query)) {
        echo "<p style='color:green;'>Transaction created successfully!</p>";
    } else {
        echo "<p style='color:red;'>Error: " . mysqli_error($CONNECT) . "</p>";
    }

    header("Location: index.php");
    exit();
}
?>
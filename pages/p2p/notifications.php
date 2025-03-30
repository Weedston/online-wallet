<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'];

$CONNECT = mysqli_connect(HOST, USER, PASS, DB);

function fetchAllNotifications($user_id, $CONNECT) {
    $query = "SELECT message, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = mysqli_prepare($CONNECT, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $notifications = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $notifications;
}

$notifications = fetchAllNotifications($user_id, $CONNECT);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Notifications</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
<div class="container">
    <?php include 'menu.php'; ?>
    <div class="container notifications-container">
        <h2>All Notifications</h2>
        <ul>
            <?php foreach ($notifications as $notification) { ?>
                <li>
                    <span><?php echo htmlspecialchars($notification['message']); ?></span>
                    <span class="notification-date"><?php echo htmlspecialchars($notification['created_at']); ?></span>
                </li>
            <?php } ?>
        </ul>
    </div>
</div>
</body>
</html>
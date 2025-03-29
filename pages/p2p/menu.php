<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$user_id = $_SESSION['user_id'];
$unread_notifications_result = mysqli_query($CONNECT, "SELECT COUNT(*) as count FROM notifications WHERE user_id = '$user_id' AND is_read = 0");
$unread_notifications = mysqli_fetch_assoc($unread_notifications_result)['count'];
?>

<nav>
    <ul>
        <li><a href="dashboard">Dashboard</a></li>
        <li><a href="transfer">Transfer</a></li>
        <li><a href="support">Support</a></li>
        <li><a href="p2p">P2P Exchange</a></li>
        <li><a href="p2p-create">Create Ad</a></li>
        <li><a href="p2p-history">Exchange History</a></li>
        <li><a href="p2p-profile">Profile</a></li>
        <li><a href="logout">Logout</a></li>
        <li>
            <a href="#" id="notification-bell">
                Уведомления
                <?php if ($unread_notifications > 0): ?>
                    <span class="badge"><?php echo $unread_notifications; ?></span>
                <?php endif; ?>
            </a>
            <div id="notification-popup" class="notification-popup" style="display: none;">
                <h4>Уведомления</h4>
                <ul id="notification-list">
                    <!-- Уведомления будут загружены тут -->
                </ul>
            </div>
        </li>
    </ul>
</nav>

<style>
/* Стиль для колокольчика уведомлений */
#notification-bell {
    position: relative;
    display: inline-block;
}
#notification-bell .badge {
    position: absolute;
    top: -10px;
    right: -10px;
</style>
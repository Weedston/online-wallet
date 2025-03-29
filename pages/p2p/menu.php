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
        <li><a href="p2p-trade_history">Trade History</a></li>
        <li><a href="p2p-profile">Profile</a></li>
        <li><a href="logout">Logout</a></li>
        <li>
            <a href="#" id="notification-bell">
                <img src="../../images/notyf.png" alt="Notifications" width="24" height="24">
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
    background-color: red;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
}
.notification-popup {
    position: absolute;
    top: 40px;
    right: 0;
    background-color: white;
    border: 1px solid #ccc;
    padding: 10px;
    width: 300px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    z-index: 1000;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function fetchNotifications() {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'jsonrpc.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);
                if (response.result) {
                    var unreadCount = response.result.unread_count;
                    var badge = document.querySelector('#notification-bell .badge');
                    if (unreadCount > 0) {
                        if (!badge) {
                            badge = document.createElement('span');
                            badge.className = 'badge';
                            document.getElementById('notification-bell').appendChild(badge);
                        }
                        badge.textContent = unreadCount;
                    } else {
                        if (badge) {
                            badge.remove();
                        }
                    }
                }
            }
        };
        xhr.send(JSON.stringify({
            jsonrpc: "2.0",
            method: "getUnreadNotificationsCount",
            params: { user_id: <?php echo $user_id; ?> },
            id: 1
        }));
    }

    setInterval(fetchNotifications, 5000);
});
</script>
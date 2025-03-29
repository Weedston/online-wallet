<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$user_id = $_SESSION['user_id'];
$notification_unread_count_result = mysqli_query($CONNECT, "SELECT COUNT(*) as count FROM notifications WHERE user_id = '$user_id' AND is_read = 0");
$notification_unread_count = mysqli_fetch_assoc($notification_unread_count_result)['count'];
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
                <?php if ($notification_unread_count > 0): ?>
                    <span class="notification-badge"><?php echo $notification_unread_count; ?></span>
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
#notification-bell .notification-badge {
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
    display: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработчик для значка уведомлений
    document.getElementById('notification-bell').addEventListener('click', function() {
        var popup = document.getElementById('notification-popup');
        if (popup.style.display === 'none' || popup.style.display === '') {
            fetchNotifications();
            popup.style.display = 'block';
        } else {
            popup.style.display = 'none';
        }
    });

    // Функция для загрузки уведомлений
    function fetchNotifications() {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/src/jsonrpc.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.result) {
                            var notifications = response.result.notifications;
                            var notificationList = document.getElementById('notification-list');
                            notificationList.innerHTML = '';
                            notifications.forEach(function(notification) {
                                var listItem = document.createElement('li');
                                listItem.textContent = notification.message;
                                notificationList.appendChild(listItem);
                            });
                        } else if (response.error) {
                            console.error("Error: " + response.error);
                        }
                    } catch (e) {
                        console.error("Parsing error:", e);
                        console.error("Response:", xhr.responseText);
                    }
                } else {
                    console.error("Request failed with status:", xhr.status);
                }
            }
        };
        xhr.onerror = function() {
            console.error("Request failed");
        };
        xhr.send(JSON.stringify({
            jsonrpc: "2.0",
            method: "getNotifications",
            params: { user_id: <?php echo $user_id; ?> },
            id: 1
        }));
    }

    setInterval(fetchNotifications, 5000);
});
</script>
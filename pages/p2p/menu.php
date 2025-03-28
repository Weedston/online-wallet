<?php
// Обработка AJAX-запросов из JavaScript
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    if ($_GET['action'] == 'check_notifications') {
        $user_id = $_SESSION['user_id'];
        $unread_notifications_result = mysqli_query($CONNECT, "SELECT COUNT(*) as count FROM notifications WHERE user_id = '$user_id' AND is_read = 0");
        $unread_notifications = mysqli_fetch_assoc($unread_notifications_result)['count'];
        echo json_encode(['new_notifications' => $unread_notifications > 0]);
        exit();
    }

    if ($_GET['action'] == 'load_notifications') {
        $user_id = $_SESSION['user_id'];
        $notifications_result = mysqli_query($CONNECT, "SELECT * FROM notifications WHERE user_id = '$user_id' AND is_read = 0 ORDER BY created_at DESC");
        $notifications = [];
        while ($notification = mysqli_fetch_assoc($notifications_result)) {
            $notifications[] = $notification;
        }
        // Отметим уведомления как прочитанные
        mysqli_query($CONNECT, "UPDATE notifications SET is_read = 1 WHERE user_id = '$user_id' AND is_read = 0");
        echo json_encode(['notifications' => $notifications]);
        exit();
    }
}

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
    background-color: red;
    color: white;
    padding: 5px;
    border-radius: 50%;
}
/* Стиль для всплывающего окна уведомлений */
.notification-popup {
    position: absolute;
    right: 0;
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    padding: 10px;
    width: 300px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    border-radius: 5px;
}
.notification-popup h4 {
    margin-top: 0;
    font-size: 16px;
    color: #333;
}
.notification-popup ul {
    list-style: none;
    padding: 0;
    margin: 0;
}
.notification-popup ul li {
    border-bottom: 1px solid #eee;
    padding: 5px 0;
    font-size: 14px;
    color: #555;
}
.notification-popup ul li:last-child {
    border-bottom: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bell = document.getElementById('notification-bell');
    const popup = document.getElementById('notification-popup');

    bell.addEventListener('click', function(e) {
        e.preventDefault();
        popup.style.display = popup.style.display === 'none' ? 'block' : 'none';
        if (popup.style.display === 'block') {
            loadNotifications();
        }
    });

    function loadNotifications() {
        fetch('menu.php?action=load_notifications')
            .then(response => response.json())
            .then(data => {
                const notificationList = document.getElementById('notification-list');
                notificationList.innerHTML = '';
                data.notifications.forEach(notification => {
                    const li = document.createElement('li');
                    li.textContent = notification.message;
                    notificationList.appendChild(li);
                });
            })
            .catch(error => console.error('Error loading notifications:', error));
    }

    setInterval(checkNotifications, 5000);

    function checkNotifications() {
        fetch('menu.php?action=check_notifications')
            .then(response => response.json())
            .then(data => {
                if (data.new_notifications) {
                    bell.classList.add('blink');
                    const audio = new Audio('notification_sound.mp3');
                    audio.play();
                } else {
                    bell.classList.remove('blink');
                }
            })
            .catch(error => console.error('Error checking notifications:', error));
    }
});
</script>

<style>
@keyframes blink {
    50% {
        opacity: 0;
    }
}
.blink {
    animation: blink 1s infinite;
}
</style>

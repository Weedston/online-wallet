<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

?>

<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="notification-popup show <?= $_SESSION['flash_message']['type'] ?>">
        <?= htmlspecialchars($_SESSION['flash_message']['text']) ?>
    </div>
    <script>
        setTimeout(function() {
            const popup = document.querySelector('.notification-popup');
            if (popup) popup.classList.remove('show');
        }, 5000);
    </script>
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>


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
                <span class="notification-badge" id="notification-count">0</span>
            </a>
            <div id="notification-popup" class="notification-popup">
                <h4>Notifications</h4>
                <ul id="notification-list">
                    <!-- Уведомления будут загружены тут -->
                </ul>
                <a href="notifications" class="view-all-link">View All Notifications</a>
            </div>
        </li>
    </ul>
</nav>
<div class="nav-bar"></div> <!-- Добавление полоски -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработчик для значка уведомлений
    document.getElementById('notification-bell').addEventListener('click', function() {
        var popup = document.getElementById('notification-popup');
        if (popup.style.display === 'none' || popup.style.display === '') {
            fetchNotifications();
            markNotificationsAsRead(); // Добавлено для отметки уведомлений как прочитанных
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
                        
                        if (response) {
                            var notifications = response;
							console.log("Received notifications!!!!!!:", notifications);
                            var notificationList = document.getElementById('notification-list');
                            notificationList.innerHTML = '';
                            notifications.forEach(function(notification) {
                                var listItem = document.createElement('li');
                                listItem.innerHTML = notification.message;
                                notificationList.appendChild(listItem);
                            });
                        } else if (response.error) {
                            console.error("Error: " + response.error.message);
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
            params: { user_id: <?php echo json_encode($_SESSION['user_id']); ?> },
            id: 1
        }));
    }

    // Функция для отметки уведомлений как прочитанных
    function markNotificationsAsRead() {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/src/jsonrpc.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        console.log("Notifications marked as read response:", response); // Добавляем лог
                        if (response.result) {
                            console.log("Notifications marked as read");
                            fetchUnreadNotificationCount(); // Обновить количество непрочитанных уведомлений
                        } else if (response.error) {
                            console.error("Error: " + response.error.message);
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
            method: "markNotificationsAsRead",
            params: { user_id: <?php echo json_encode($_SESSION['user_id']); ?> },
            id: 1
        }));
    }

    // Функция для получения количества непрочитанных уведомлений
    function fetchUnreadNotificationCount() {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/src/jsonrpc.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                console.log("Ответ сервера при получении количества непрочитанных уведомлений:", xhr.responseText);
                if (xhr.status === 200) {
                    try {
                        if (xhr.responseText) {
                            var response = JSON.parse(xhr.responseText);
                            console.log("Parsed response+++:", response);
                            if (response) {
                                var count = response;
                                console.log("Unread notification count parsed-----:", count);
                                document.getElementById('notification-count').textContent = count;
                            } else if (response !== undefined) {
                                var count = response;
                                console.log("Unread notification count (fallback)!!!!!!:", count);
                                document.getElementById('notification-count').textContent = count;
                            } else if (response.error) {
                                console.error("Error: " + response.error.message);
                                document.getElementById('notification-count').textContent = "0";
                            }
                        } else {
                            console.error("Пустой ответ сервера");
                            document.getElementById('notification-count').textContent = "0";
                        }
                    } catch (e) {
                        console.error("Ошибка парсинга JSON:", e);
                        console.error("Response:", xhr.responseText);
                        document.getElementById('notification-count').textContent = "0";
                    }
                } else {
                    console.error("Request failed with status:", xhr.statusText);
                    document.getElementById('notification-count').textContent = "0";
                }
            }
        };
        xhr.onerror = function() {
            console.error("Request failed");
            document.getElementById('notification-count').textContent = "0";
        };
        var requestData = JSON.stringify({
            jsonrpc: "2.0",
            method: "getUnreadNotificationCount",
            params: { user_id: <?php echo json_encode($_SESSION['user_id']); ?> },
            id: 1
        });
        console.log("Request data:", requestData);
        xhr.send(requestData);
    }

    fetchUnreadNotificationCount();
    setInterval(fetchUnreadNotificationCount, 5000);
});
</script>
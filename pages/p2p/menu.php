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
  <button class="menu-toggle" aria-label="Открыть меню">&#9776;</button>
  <ul>
    <li><a href="dashboard"><?= htmlspecialchars($translations['p2p_menu_dash']) ?></a></li>
    <li><a href="transfer"><?= htmlspecialchars($translations['p2p_menu_transfer']) ?></a></li>
    <li><a href="support"><?= htmlspecialchars($translations['p2p_menu_support']) ?></a></li>
    <li><a href="p2p"><?= htmlspecialchars($translations['p2p_menu_p2p_exch']) ?></a></li>
    <li><a href="p2p-create"><?= htmlspecialchars($translations['p2p_menu_createad']) ?></a></li>
    <li><a href="p2p-trade_history"><?= htmlspecialchars($translations['p2p_menu_tradehist']) ?></a></li>
    <li><a href="p2p-profile"><?= htmlspecialchars($translations['p2p_menu_profile']) ?></a></li>
    <li><a href="logout"><?= htmlspecialchars($translations['p2p_menu_logout']) ?></a></li>
    <li>
      <a href="#" id="notification-bell">
        <img src="../../images/notyf.png" alt="Notifications" width="24" height="24">
        <span class="notification-badge" id="notification-count">0</span>
      </a>
      <div id="notification-popup" class="notification-popup">
        <h4><?= htmlspecialchars($translations['p2p_menu_notifications']) ?></h4>
        <ul id="notification-list">
          <!-- Уведомления будут загружены тут -->
        </ul>
        <a href="notifications" class="view-all-link"><?= htmlspecialchars($translations['p2p_menu_allnotif']) ?></a>
      </div>
    </li>
  </ul>
</nav>
<script src="/js/menu.js"></script>
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
                            var notificationList = document.getElementById('notification-list');
                            notificationList.innerHTML = '';
                            notifications.forEach(function(notification) {
                                var listItem = document.createElement('li');
                                listItem.innerHTML = notification.message;
                                notificationList.appendChild(listItem);
                            });
                        } else if (response.error) {
                        }
                    } catch (e) {
                    }
                } else {
                }
            }
        };
        xhr.onerror = function() {
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
                        if (response.result) {
                            fetchUnreadNotificationCount(); // Обновить количество непрочитанных уведомлений
                        } else if (response.error) {
                        }
                    } catch (e) {
                    }
                } else {
                }
            }
        };
        xhr.onerror = function() {
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
                if (xhr.status === 200) {
                    try {
                        if (xhr.responseText) {
                            var response = JSON.parse(xhr.responseText);
                            if (response) {
                                var count = response;
                                document.getElementById('notification-count').textContent = count;
                            } else if (response !== undefined) {
                                var count = response;
                                document.getElementById('notification-count').textContent = count;
                            } else if (response.error) {
                                document.getElementById('notification-count').textContent = "0";
                            }
                        } else {
                            document.getElementById('notification-count').textContent = "0";
                        }
                    } catch (e) {
                        document.getElementById('notification-count').textContent = "0";
                    }
                } else {
                    document.getElementById('notification-count').textContent = "0";
                }
            }
        };
        xhr.onerror = function() {
            document.getElementById('notification-count').textContent = "0";
        };
        var requestData = JSON.stringify({
            jsonrpc: "2.0",
            method: "getUnreadNotificationCount",
            params: { user_id: <?php echo json_encode($_SESSION['user_id']); ?> },
            id: 1
        });
        xhr.send(requestData);
    }

    fetchUnreadNotificationCount();
    setInterval(fetchUnreadNotificationCount, 5000);
});
</script>
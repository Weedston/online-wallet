<?php

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: dashboard");
    exit();
}


//error_reporting(E_ALL);
//ini_set('display_errors', 1);


//
// Получение списка обращений, сгруппированных по user_id
$query = "SELECT 
    m.id as user_id, 
    m.wallet, 
    s.id as request_id, 
    s.message, 
    s.response, 
    s.created_at
FROM support_requests s
JOIN members m ON s.user_id = m.id
ORDER BY 
    (SELECT MAX(sr.created_at) FROM support_requests sr WHERE sr.user_id = m.id) DESC, 
    m.id, 
    s.created_at DESC;";
$result = $CONNECT->query($query);

$requests = [];

while ($row = $result->fetch_assoc()) {
    $requests[$row['user_id']]['wallet'] = $row['wallet'];
    $requests[$row['user_id']]['requests'][] = $row;
}
///

// Обработка ответа на запрос
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["request_id"], $_POST["response"])) {
// Предполагаем, что $mysqli уже подключен
$request_id = intval($_POST["request_id"]); 
$response = trim($_POST["response"]); 

if (!empty($response) && $request_id > 0) {
    // Создаем подготовленный запрос
    $stmt = $CONNECT->prepare("UPDATE support_requests SET response = ? WHERE id = ?");
    // Привязываем параметры (s - строка, i - число)
    $stmt->bind_param("si", $response, $request_id);
    // Выполняем запрос
    $stmt->execute();
    // Закрываем запрос
    $stmt->close();
	header("Location: adm_support");
}
}

// Обработка отправки сообщения пользователю
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['message'], $_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $stmt = $CONNECT->prepare("INSERT INTO support_requests (user_id, response) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $message);
        $stmt->execute();
    }
}

// Получение общего количества посещений
$visit_result = $CONNECT->query("SELECT count FROM visit_counter WHERE page = 'total'");
$visit_count = $visit_result->fetch_assoc()['count'];


// Получение всех обращений
//$result = $CONNECT->query("SELECT id, user_id, message, response, created_at FROM support_requests ORDER BY created_at DESC");
$query = "SELECT m.id as user_id, m.wallet, s.id as request_id, s.message, s.response, s.created_at
          FROM support_requests s
          JOIN members m ON s.user_id = m.id
          ORDER BY s.created_at DESC";

$result = $CONNECT->query($query);

$users = $CONNECT->query("SELECT id, passw, wallet, balance FROM members ORDER BY id DESC");
$users_mess = $CONNECT->query("SELECT id, passw, wallet, balance FROM members ORDER BY id DESC");

    
	
// Если запрос AJAX — возвращаем JSON
if (isset($_GET['ajax']) && $_GET['ajax'] == '2') {
    // Выполняем запрос к базе данных
    $visit_result = $CONNECT->query("SELECT count FROM visit_counter WHERE page = 'total'");
        // Запрашиваем баланс
	$balance_data = bitcoinRPC('getbalance');
	
    // Подготавливаем ответ
    $response = [];
    // Проверяем, получены ли данные из базы
    if ($visit_result && $visit_result->num_rows > 0) {
        $visit_count = $visit_result->fetch_assoc()['count'];
        $response['count'] = $visit_count;
    } else {
        $response['error'] = 'ERROR';
    }
	
   if (is_numeric($balance_data)) {
    // Преобразуем в число с плавающей точкой
    $result = floatval($balance_data);
    
    // Форматируем до 8 знаков после запятой
    $formatted_balance = number_format($result, 8, '.', '');

    // Логируем отформатированный баланс
    error_log('Formatted balance: ' . $formatted_balance);

    $response['balance'] = $formatted_balance;
} else {
    // Если ответ не числовой, возвращаем ошибку
    $response['balance_error'] = 'Полученное значение не является числом: ' . var_export($balance_data, true);
}
    // Отправляем JSON-ответ
    echo json_encode($response);
    exit;
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="keywords" content="anonymous bitcoin wallet, btc wallet no verification, secure crypto wallet, private bitcoin wallet, best anonymous btc wallet 2025, buy bitcoin anonymously, no KYC crypto wallet, blockchain wallet no registration, tor bitcoin wallet, darknet btc wallet, how to create an anonymous bitcoin wallet, privacy-focused crypto wallet, secure BTC transactions, untraceable bitcoin wallet">
	<meta name="description" content="Create a secure and anonymous Bitcoin wallet with no KYC verification. Store, send, and receive BTC privately and safely.">
	<meta name="robots" content="index, follow">

    <title>Anonymous BTC Wallet</title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body><br><br>
	<?php include 'pages/p2p/menu_adm.php'; ?>
    <div style='min-height: 50vh;' class="container">
        <h2>Admin Support Panel</h2>
		<h3>Total Site Visits: <span id="visitCount">0</span></h3>
		<h3>Total Balance Wallet: <span id="totalBalance">0</span></h3>
        <?php foreach ($requests as $user_id => $data): ?>
            <div class="user-group">
                <h3 class="spoiler-header" onclick="toggleSpoiler('group_<?php echo $user_id; ?>')" style="cursor: pointer;">User ID: <?php echo $user_id; ?>, Wallet: <?php echo htmlspecialchars($data['wallet']); ?> &#9660;</h3>
                <div id="group_<?php echo $user_id; ?>" style="display: none;">
                    <div class="card-container">
					<?php foreach ($data['requests'] as $req): ?>
                        <div class="card">
                            <p><strong>Request:</strong> <?php echo $req['message'] ? htmlspecialchars($req['message']) : 'No Message yet'; ?></p>
                            <p><strong>Response:</strong> <?php echo $req['response'] ? htmlspecialchars($req['response']) : 'No response yet'; ?></p>
                            <p><small><?php echo $req['created_at']; ?></small></p>
                            <form method="POST" >
                                <input type="hidden" name="request_id" value="<?php echo $req['request_id']; ?>">
                                <textarea name="response" placeholder="Write a response..." required></textarea>
                                <button type="submit" class="btn">Send Response</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
					</div>
                </div>
            </div>
        <?php endforeach; ?>
		
		<script>
    document.addEventListener("DOMContentLoaded", function () {
        let headers = document.querySelectorAll(".spoiler-header");

        headers.forEach(header => {
            header.addEventListener("click", function () {
                let content = this.nextElementSibling; 
                if (content.style.display === "none" || content.style.display === "") {
                    content.style.display = "block";
                } else {
                    content.style.display = "none";
                }
            });
        });
    });
</script>

<script>
function fetchVisitCount() {
    fetch("?ajax=2", { method: "GET" })
        .then(response => {
            if (!response.ok) {
                throw new Error("Network response was not ok");
            }
            return response.json();
        })
        .then(data => {
            if (data.count !== undefined) {
                console.log("Visit Count: ", data.count);
                document.getElementById("visitCount").innerText = data.count;
            } else {
                console.error("Error in server response (count):", data);
            }

            if (data.balance !== undefined) {
                console.log("Balance: ", data.balance);
                document.getElementById("totalBalance").innerText = data.balance;
            } else {
                console.error("Error in server response (balance):", data);
            }
        })
        .catch(error => console.error("Fetch error:", error));
}


fetchVisitCount();

setInterval(fetchVisitCount, 10000);
</script>


		<h3>Send Message to User</h3>
        <form method="POST">
            <label for="user_id">Select User:</label>
            <select name="user_id" required>
                <?php while ($user = $users_mess->fetch_assoc()): ?>
                    <option value="<?php echo $user['id']; ?>"> <?php echo htmlspecialchars($user['id']); ?> </option>
                <?php endwhile; ?>
            </select>
            <textarea name="message" placeholder="Enter your message..." required></textarea>
            <button type="submit" class="btn">Send Message</button>
        </form>
		
		<h3>All Users</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Password</th>
                <th>Wallet</th>
                <th>Balance</th>
            </tr>
            <?php while ($user = $users->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['passw']); ?></td>
                    <td><?php echo htmlspecialchars($user['wallet']); ?></td>
                    <td><?php echo $user['balance']; ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
        
    </div>
</body>
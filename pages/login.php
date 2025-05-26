<?php
if ($_POST['a'] === 'do_login') {
    $passw = FormChars($_POST['username']);

    // Используем подготовленный запрос
    $stmt = $CONNECT->prepare("SELECT id, wallet FROM members WHERE passw = ?");
    $stmt->bind_param("s", $passw);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        echo 'Your login or password is wrong. Please check this information.';
        //echo "<script> location.href='/'; </script>";
        exit();
    } else {
        $user_id = $row['id'];
        $wallet = $row['wallet'];

        // Генерируем токен сессии
        $newToken = bin2hex(random_bytes(32));

        // Устанавливаем сессионные данные
        $_SESSION['user_id'] = $user_id;
        $_SESSION['wallet'] = $wallet;
        $_SESSION['token'] = $newToken;

		echo "$newToken";

		$stmt->close();
		echo "UPDATE members SET session_token = $newToken WHERE id = $user_id";

        // Обновляем токен в БД
        $update = $CONNECT->prepare("UPDATE members SET session_token = ? WHERE id = ?");
        $update->bind_param("si", $newToken, $user_id);
        $update->execute();
exit();
if ($update->execute()) {
    echo "Токен успешно обновлён";
	exit();
} else {
    echo "Ошибка при обновлении токена: " . $update->error;
	exit();
}


        // Устанавливаем куки (по желанию можешь убрать или добавить безопасные флаги)
        setcookie("id", $user_id, time() + 60*60*24*30, "/");
        setcookie("token", $newToken, time() + 60*60*24*30, "/");

        echo '<br><br><br><br><br><center><h3>Login is OK</h3></center><br><br><br><br><br><br><br><br><br><br>';
        //echo "<script> location.href='/dashboard'; </script>";
        exit();
    }
}
?>

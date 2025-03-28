<?php

if ($_POST['a'] == 'do_login')
{
$passw = FormChars($_POST['sid']);

$row = mysqli_fetch_assoc(mysqli_query($CONNECT, "SELECT * FROM members WHERE passw = '".$passw."';"));
if (!$row['id']) {
 echo 'Your SID is wrong. Please check this information.';
 echo "<script> location.href='/'; </script>";
} else 
{
$user_id = $row['id'];
$wallet = $row['wallet'];


		setcookie("id", $user_id, time()+60*60*24*30);
        setcookie("hash", $hash, time()+60*60*24*30);
		$_SESSION['user_id'] = $user_id;
		$_SESSION['wallet'] = $wallet;
		
		echo '<br><br><br><br><br><center><h3>Login is OK</h3></center><br><br><br><br><br><br><br><br><br><br>';
		echo "<script> location.href='/dashboard'; </script>";
		exit();
}

}

?>
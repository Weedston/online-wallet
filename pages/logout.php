<?php
  if ( isset( $_COOKIE['id'] ) ) setcookie( 'id', '', time() - 1);
  		$_SESSION['admin'] = null;
		$_SESSION['user_id'] = null;
		$_SESSION['wallet'] = '';
		
  echo '<br><br><br><br><br><center><h3>Log OUT is ok</h3></center>';
  echo "<script> location.href='/'; </script>";
  header('Refresh: 1; URL=/');
  exit();
?>
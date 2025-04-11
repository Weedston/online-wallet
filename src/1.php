<?php
require_once 'functions.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$ad_id = 6;
$txid = "dbf9a27464b6c014c2c85e9dfe652032bc9c0327cc6f9e883b4935b8840d162a";
$btc_amount = 0.00020000;

addServiceComment($ad_id, "BTC deposited to escrow wallet. TXID: $txid, Amount: $btc_amount BTC", 'deposit');


?>
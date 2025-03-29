<?php
require_once '../../config.php';

function getBtcRates() {
    $apiUrl = 'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=usd,eur,rub';
    $response = file_get_contents($apiUrl);
    if ($response === FALSE) {
        return null;
    }

    $data = json_decode($response, true);
    if (isset($data['bitcoin'])) {
        return $data['bitcoin'];
    }

    return null;
}

$btcRates = getBtcRates();
header('Content-Type: application/json');
echo json_encode($btcRates);
?>
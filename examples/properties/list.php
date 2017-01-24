<?php

require_once('IProClient.php');

$client = new IProClient('1000', '0bd58c64591b407e969c72ffaffeaf99', 'http://localhost:83/');

$resp = $client->getAccessToken();

if ($resp['code'] == 200) {
    
    $result = $resp['result'];
    $client->setAccessToken($result['access_token']);
    
    $resp = $client->propertyList();

    echo json_encode($resp['result']);
    
} else {
    var_dump($resp['result']);
}
?>
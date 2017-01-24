<?php

require_once('IProClient.php');

$client = new IProClient('1000', '0bd58c64591b407e969c72ffaffeaf99', 'http://localhost:83/');

$resp = $client->getAccessToken();

if ($resp['code'] == 200) {

    $result = $resp['result'];
    $client->setAccessToken($result['access_token']);

    //Get the property list
    //Under normal circumstances this wouldn't be necessary
    //But demonstrates the functionality
    $resp = $client->propertyList();

    if (count($resp['result']) > 0) {

        //Choose a property at random for the demonstration
        $property_index = array_rand($resp['result']);
        $property_id = $resp['result'][$property_index]['Id'];
        
        //Choose sensible dates for the demonstration
        $start_date = new DateTime('next saturday');
        $end_date = new DateTime();
        $end_date->setTimestamp($start_date->getTimestamp());
        $end_date->add(new DateInterval('P7D'));

        $calculated_booking = $client->calculateBooking($property_id, $start_date->format('Y-m-d'), $end_date->format('Y-m-d'), 2, 1, 0);

        echo json_encode($calculated_booking);
    }
} else {
    var_dump($resp['result']);
}
?>
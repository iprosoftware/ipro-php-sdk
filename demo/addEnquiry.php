<?php
/**
 * Created by Dayi Chen.
 * Date: 2016/1/5
 * Time: 16:20
 */

require_once('src/IProClient.php');

$client = new IProClient('1000','0bd58c64591b407e969c72ffaffeaf99','http://www.azores-staging.cn');

$resp = $client->getAccessToken();
if($resp['code'] == 200) {
    $result = $resp['result'];
    $client->setAccessToken($result['access_token']);
    $resp = $client->addEnquiry('Dayi','Chen','dayi@ipro-software.com','12484','2016-02-27','2016-03-02',"3","100",'13800138000','0592','1','1',"","","2016-01-05T07:26:40.7595426Z");

    var_dump( $resp['result']);

}else{
    var_dump( $resp['result']);
}


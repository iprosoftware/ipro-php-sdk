# IPro API Client

This client makes it simple to integrate your application with I-Pro api platform by [OAuth 2.0](http://oauth.net/2/).

[![Build Status](https://travis-ci.org/iprosoftware/ipro-php-sdk.svg)](https://travis-ci.org/iprosoftware/ipro-php-sdk)

---


## Requirements

The following versions of PHP are supported.

* PHP 5.x
* PHP 7.0


## Usage



### Add Enquiry

```php
require_once('IProClient.php');

$client = new IProClient('1000','0bd58c64591b407e969c72ffaffeaf99','http://www.azores-staging.cn');

$resp = $client->getAccessToken();
if($resp['code'] == 200) {
    $result = $resp['result'];
    $client->setAccessToken($result['access_token']);
    $resp = $client->addEnquiry('Dayi','Chen','dayi@ipro-software.com', '12484','2016-02-27','2016-03-02',"3","100",'13800138000','0592','1','1',"","","2016-01-05T07:26:40.7595426Z");

    var_dump( $resp['result']);

}else{
    var_dump( $resp['result']);
}
```
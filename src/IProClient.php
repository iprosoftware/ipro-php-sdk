<?php

/**
 * Created by Dayi Chen.
 * Date: 2016/1/5
 * Time: 15:56
 */
class IProClient {

    /**
     * Different Grant types
     */
    const GRANT_TYPE_CLIENT_CREDENTIALS = 'client_credentials';

    /**
     * HTTP Methods
     */
    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';
    const HTTP_METHOD_PUT = 'PUT';
    const HTTP_METHOD_DELETE = 'DELETE';
    const HTTP_METHOD_HEAD = 'HEAD';
    const HTTP_METHOD_PATCH = 'PATCH';

    /**
     * HTTP Form content types
     */
    const HTTP_FORM_CONTENT_TYPE_APPLICATION = 0;
    const HTTP_FORM_CONTENT_TYPE_MULTIPART = 1;

    /**
     * Client ID
     *
     * @var string
     */
    protected $client_id = null;

    /**
     * Client Secret
     *
     * @var string
     */
    protected $client_secret = null;

    /**
     * IPro OAuth2 server host
     *
     * @var int
     */
    protected $host = null;

    /**
     * OAuth2 token endpoint
     *
     * @var int
     */
    protected $token_endpoint = null;

    /**
     * Access Token
     *
     * @var string
     */
    protected $access_token = null;

    /**
     * The path to the certificate file to use for https connections
     *
     * @var string  Defaults to .
     */
    protected $certificate_file = null;

    /**
     * Construct
     *
     * @param string $client_id Client ID
     * @param string $client_secret Client Secret
     * @param string $host  I-Pro OAuth2 server
     * @param string $access_token Access Token
     * @return void
     */
    public function __construct($client_id, $client_secret, $host) {
        if (!extension_loaded('curl')) {
            throw new Exception('The PHP exention curl must be installed to use this library.');
        }

        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->host = $host;
        $this->token_endpoint = $host . '/oauth/2.0/token';
    }

    /**
     * Get the client Id
     *
     * @return string Client ID
     */
    public function getClientId() {
        return $this->client_id;
    }

    /**
     * Get the client Secret
     *
     * @return string Client Secret
     */
    public function getClientSecret() {
        return $this->client_secret;
    }

    /**
     * setToken
     *
     * @param string $token Set the access token
     * @return void
     */
    public function setAccessToken($token) {
        $this->access_token = $token;
    }

    /**
     * Check if there is an access token present
     *
     * @return bool Whether the access token is present
     */
    public function hasAccessToken() {
        return !!$this->access_token;
    }

    /**
     * getAccessToken
     *
     * @return array Array of parameters required by the grant_type (CF SPEC)
     */
    public function getAccessToken() {
        $parameters = array(
            'grant_type' => self::GRANT_TYPE_CLIENT_CREDENTIALS
        );

        $http_headers = array(
            'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret)
        );


        return $this->executeRequest($this->token_endpoint, $parameters, self::HTTP_METHOD_POST, $http_headers);
    }

    /**
     * Execute a request (with curl)
     *
     * @param string $url URL
     * @param mixed  $parameters Array of parameters
     * @param string $http_method HTTP Method
     * @param array  $http_headers HTTP Headers
     * @param int    $form_content_type HTTP form content type to use
     * @return array
     */
    private function executeRequest($url, $parameters = array(), $http_method = self::HTTP_METHOD_GET, array $http_headers = null, $form_content_type = self::HTTP_FORM_CONTENT_TYPE_APPLICATION) {
        $curl_options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CUSTOMREQUEST => $http_method
        );

        if ($this->hasAccessToken()) {
            if (!is_array($http_headers)) {
                $http_headers = array();
            }
            $http_headers['Authorization'] = 'Bearer ' . $this->access_token;
        }


        switch ($http_method) {
            case self::HTTP_METHOD_POST:
                $curl_options[CURLOPT_POST] = true;
            /* No break */
            case self::HTTP_METHOD_PUT:
            case self::HTTP_METHOD_PATCH:

                /**
                 * Passing an array to CURLOPT_POSTFIELDS will encode the data as multipart/form-data,
                 * while passing a URL-encoded string will encode the data as application/x-www-form-urlencoded.
                 * http://php.net/manual/en/function.curl-setopt.php
                 */
                if (is_array($parameters) && self::HTTP_FORM_CONTENT_TYPE_APPLICATION === $form_content_type) {
                    $parameters = http_build_query($parameters, null, '&');
                }
                $curl_options[CURLOPT_POSTFIELDS] = $parameters;
                break;
            case self::HTTP_METHOD_HEAD:
                $curl_options[CURLOPT_NOBODY] = true;
            /* No break */
            case self::HTTP_METHOD_DELETE:
            case self::HTTP_METHOD_GET:
                if (is_array($parameters) && count($parameters) > 0) {
                    $url .= '?' . http_build_query($parameters, null, '&');
                } elseif ($parameters) {
                    $url .= '?' . $parameters;
                }
                break;
            default:
                break;
        }

        $curl_options[CURLOPT_URL] = $url;

        if (is_array($http_headers)) {
            $header = array();
            foreach ($http_headers as $key => $parsed_urlvalue) {
                $header[] = "$key: $parsed_urlvalue";
            }
            $curl_options[CURLOPT_HTTPHEADER] = $header;
        }

        $ch = curl_init();
        curl_setopt_array($ch, $curl_options);
        // https handling
        if (!empty($this->certificate_file)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_CAINFO, $this->certificate_file);
        } else {
            // bypass ssl verification
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }
        if (!empty($this->curl_options)) {
            curl_setopt_array($ch, $this->curl_options);
        }
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        if ($curl_error = curl_error($ch)) {
            throw new Exception($curl_error);
        } else {
            $json_decode = json_decode($result, true);
        }
        curl_close($ch);

        return array(
            'result' => (null === $json_decode) ? $result : $json_decode,
            'code' => $http_code,
            'content_type' => $content_type
        );
    }

    /**
     *  Add an enquiry
     *
     * @param string    $firstName First Name
     * @param string    $lastName Last Name
     * @param string    $propertyIds umbracoId in IPro system, eg. 1234,1235
     * @param string    $startDate  Start date, format is yyyy-MM-dd
     * @param string    $endDate  End date, format is yyyy-MM-dd
     * @param string    $days  days
     * @param string    $budget  budget
     * @param string    $mobile  mobile
     * @param string    $phone  phone
     * @param string    $adults  adults
     * @param string    $children  children
     * @param string    $comment  comment
     * @param string    $createdTime  created time, format is yyyy-MM-ddTHH:mm:ssZ. e.g 2016-01-05T07:26:40.7595426Z
     * @return array
     */
    public function addEnquiry($firstName, $lastName, $email, $propertyIds, $startDate, $endDate, $days, $budget, $mobile, $phone, $adults, $children, $source, $comment, $createdTime) {
        if (!$this->hasAccessToken()) {
            throw new Exception('access_token is missing.');
        }
        $params = array(
            'firstname' => $firstName,
            'lastname' => $lastName,
            'email' => $email,
            'propertyids' => $propertyIds,
            'startdate' => $startDate,
            'enddate' => $endDate,
            'days' => $days,
            'budget' => $budget,
            'mobile' => $mobile,
            'phone' => $phone,
            'adults' => $adults,
            'children' => $children,
            'source' => $source,
            'comments' => $comment,
            'createdate' => $createdTime
        );


        return $this->executeRequest($this->host . '/apis/enquiry', $params, self::HTTP_METHOD_POST, array(), self::HTTP_FORM_CONTENT_TYPE_APPLICATION);
    }

    /**
     *  Add a booking
     *
     * @param string    $firstName First Name
     * @param string    $lastName Last Name
     * @param string    $propertyIds umbracoId in IPro system, eg. 1234,1235
     * @param string    $startDate  Start date, format is yyyy-MM-dd
     * @param string    $endDate  End date, format is yyyy-MM-dd
     * @param string    $days  days
     * @param string    $budget  budget
     * @param string    $mobile  mobile
     * @param string    $phone  phone
     * @param string    $adults  adults
     * @param string    $children  children
     * @param string    $comment  comment
     * @param string    $createdTime  created time, format is yyyy-MM-ddTHH:mm:ssZ. e.g 2016-01-05T07:26:40.7595426Z
     * @return array
     */
    public function addBooking($firstName, $lastName, $email, $propertyIds, $startDate, $endDate, $days, $budget, $mobile, $phone, $adults, $children, $source, $comment, $createdTime) {
        if (!$this->hasAccessToken()) {
            throw new Exception('access_token is missing.');
        }
        $params = array(
            'firstname' => $firstName,
            'lastname' => $lastName,
            'email' => $email,
            'propertyids' => $propertyIds,
            'startdate' => $startDate,
            'enddate' => $endDate,
            'days' => $days,
            'budget' => $budget,
            'mobile' => $mobile,
            'phone' => $phone,
            'adults' => $adults,
            'children' => $children,
            'source' => $source,
            'comment' => $comment,
            'createdate' => $createdTime
        );


        return $this->executeRequest($this->host . '/apis/enquiry', $params, self::HTTP_METHOD_POST, array(), self::HTTP_FORM_CONTENT_TYPE_APPLICATION);
    }

    /**
     * Calculate a booking based on the given parameters
     * 
     * @param int $propertyid
     * @param string $checkin (Y-m-d)
     * @param string $checkout (Y-m-d)
     * @param int $adults
     * @param int $children
     * @param int $infants
     * @param int $pets
     * @return json array
     */
    public function calculateBooking($propertyid, $checkin, $checkout, $adults = 1, $children = 0, $infants = 0, $pets = 0, $petsextraid = 9999) {
        $params = [];
        $params["Properties[0].Id"] = $propertyid;
        $params["Properties[0].Checkin"] = $checkin;
        $params["Properties[0].Checkout"] = $checkout;
        $params["Properties[0].Adults"] = $adults;
        $params["Properties[0].Children"] = $children;
        $params["Properties[0].Infants"] = $infants;

        if ($pets > 0) {
            $params["Properties[0].Extras[0].Id"] = $petsextraid;
            $params["Properties[0].Extras[0].Qty"] = $pets;
        }

        return $this->executeRequest($this->host . '/apis/booking/calc', $params, self::HTTP_METHOD_POST, array(), self::HTTP_FORM_CONTENT_TYPE_APPLICATION);
    }

    /**
     * Returns the complete list of live properties
     */
    public function propertyList($params = []) {
        return $this->executeRequest($this->host . '/apis/properties', $params, self::HTTP_METHOD_GET, array(), self::HTTP_FORM_CONTENT_TYPE_APPLICATION);
    }

}

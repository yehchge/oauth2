<?php

$code = isset($_GET['code'])?trim($_GET['code']):'';

if (!$code) {
    $error = isset($_GET['error'])?$_GET['error']:'';
    $errorDescription = isset($_GET['error_description'])?$_GET['error_description']:'';

    $str = "error: $error, <BR>error_description: $errorDescription<BR>";
    $str .= "Go <a href='./'>Home</home>";
    echo $str;exit;
}

$url = 'http://localhost/oauth2/token.php';
$responseBody = fetch($url, 'POST', array(
    'client_id'     => 'testclient',
    'client_secret' => 'testpass',
    'grant_type'    => 'authorization_code',
    'code'          => $code
));

$responseArr = json_decode($responseBody, true);

// if there is no access_token, we have a problem!!!
if (!isset($responseArr['access_token'])) {
    $str = $responseArr ? $responseArr : $responseBody;
    echo "<pre>";print_r($str);echo "</pre>";
    exit;
}

$accessToken = $responseArr['access_token'];
$expiresIn = $responseArr['expires_in'];

$url = 'http://localhost/oauth2/me.php';
$responseBody = fetch($url, 'GET', array(
    'client_id'     => 'testclient',
    'client_secret' => 'testpass',
    'grant_type'    => 'authorization_code',
    'code'          => $code
), array('Authorization: Bearer '.$accessToken));

echo $responseBody;
exit;

/**
 *
 * @param string $url
 *
 * @return html content
 */
function fetch($url, $type = "GET", $post_data = array(), $custom_header = array()) {
    switch($type) {

        case "POST":
            $ch = curl_init();


            curl_setopt($ch, CURLOPT_URL, $url);

            curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp');
            curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp');

            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            if(is_array($custom_header) && count($custom_header)>0){
                $header = [];
                foreach($custom_header as $val){
                    $header[] = $val;
                }
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            }

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query( $post_data ) );

            // if($this->ssl) {
            //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            //     curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            // }

            curl_setopt($ch, CURLOPT_ENCODING, ''); // 要求 curl 自動解壓縮 gzip
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $res = curl_exec($ch);

            if (!$res) {
                trigger_error(curl_error($ch));
                exit;
            }

            curl_setopt($ch, CURLOPT_POST, true);
        break;

        case "GET":
        default:
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);

            curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp');
            curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp');

            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            if(is_array($custom_header) && count($custom_header)>0){
                $header = [];
                foreach($custom_header as $val){
                    $header[] = $val;
                }
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            }

            // if($this->ssl) {
            //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            //     curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            // }

            // if ($this->proxy) {
            //     curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
            // }

            curl_setopt($ch, CURLOPT_ENCODING, ''); // 要求 curl 自動解壓縮 gzip
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $res = curl_exec($ch);

            if ($res === FALSE) {
                echo $url.PHP_EOL;
                trigger_error(curl_error($ch));
                if(curl_errno($ch)){
                    echo 'Curl error: ' . curl_error($ch);
                }
                return '';
            }
        break;
    }

    if(0) {
        $res = file_get_contents($url);
    }

    if (gettype($ch) == "resource")
        curl_close($ch);

    return $res;
}
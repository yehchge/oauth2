<?php

$redirectUrl = 'http://localhost/oauth2/receive_authorize.php';

$url = 'http://localhost/oauth2/authorize.php?'.http_build_query(array(
    'response_type' => 'code',
    'client_id' => 'testclient',
    // 'redirect_uri' => $redirectUrl,
    'scope' => 'profile',
    'state' => 'iLoveFish'
));

header("location: $url");
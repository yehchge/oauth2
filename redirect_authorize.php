<?php

require __DIR__.'/config.php';

$redirectUrl = _SITE_URL.'/receive_authorize.php';

$url = _SITE_URL.'/authorize.php?'.http_build_query(array(
    'response_type' => 'code',
    'client_id' => 'testclient',
    // 'redirect_uri' => $redirectUrl,
    'scope' => 'profile',
    'state' => 'iLoveFish'
));

header("location: $url");
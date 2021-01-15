<?php

// include our OAuth2 Server object
require_once __DIR__.'/../server.php';

// Handle a request to a resource and authenticate the access token
if (!$server->verifyResourceRequest(OAuth2\Request::createFromGlobals())) {
    $server->getResponse()->send();

    $result = array(
        'error' => 'access_denied',
        'error_description' => 'an access token is required'
    );

    echo json_encode($result);
    die;
}
echo json_encode(array('success' => true, 'message' => 'You accessed my APIs!'));


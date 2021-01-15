<?php

session_start();

require __DIR__.'/config.php';
require __DIR__."/DB.php";

// include our OAuth2 Server object
require __DIR__.'/server.php';

$db = new DB(array(
    'type' => 'mysql',
    'host' => _DBHOST,
    'name' => _DBNAME,
    'user' => _DBUSER,
    'pass' => _DBPASS
));

$request = OAuth2\Request::createFromGlobals();
$response = new OAuth2\Response();

$scopeRequired = 'profile'; // this resource requires "profile" scope

// Handle a request to a resource and authenticate the access token
if (!$server->verifyResourceRequest($request, $response, $scopeRequired)) {
    $server->getResponse()->send();
    die;
}

$token = $server->getAccessTokenData(OAuth2\Request::createFromGlobals());

$email = trim($token['user_id']);
// echo "User Email associated with this token is {$token['user_id']}";

$aUserRow = $db->row_array("SELECT email,firstName,lastName FROM users WHERE email = :email", array('email' => $email));

header('Content-Type: application/json; charset=utf-8');
$str = json_encode($aUserRow, JSON_PRETTY_PRINT);
$str .= "Go <a href='./'>Home</a>";

echo $str;

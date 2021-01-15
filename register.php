<?php

session_start();

// Check if the user is already logged in, if yes then redirect him to welcome page
if(isset($_SESSION["loggedin"])){
    header("location: index.php");
    exit;
}

require __DIR__.'/config.php';
require __DIR__."/DB.php";

$db = new DB(array(
    'type' => 'mysql',
    'host' => _DBHOST,
    'name' => _DBNAME,
    'user' => _DBUSER,
    'pass' => _DBPASS
));

if($_POST){
    $aRow =& $_POST;

    $aData = array(
        'email' => $aRow['email'],
        'firstName' => $aRow['firstName'],
        'lastName' => $aRow['lastName'],
        'password' => MD5($aRow['password'])
    );

    $aUserRow = $db->row_array("SELECT * FROM users WHERE email = :email", array('email' => $aRow['email']));
    if(!$aUserRow)
        $result = $db->exec_insert('users', $aData);

    if(isset($result) && $result){
        echo "Add Success.".PHP_EOL;
    }else{
        echo "Add Fail.".PHP_EOL;
    }
}

?>
<!doctype html>
<html>
    <head>
        <meta charset="utf-8"/>
        <title>OAuth2 Register</title>
    </head>
    <body>
        <header>
            <a href="./">Home</a> |
            <a href="./register.php">Register</a> |
            <a href="./login.php">Login</a> |
            <a href="./logout.php">Logout</a>
        </header>
        <h1>Put your chickens to the test!</h1>
        <hr>
        <form action="./register.php" method="post">
            <div>
                <label>Email</label>
                <input type="text" name="email" id="email">
            </div>
            <div>
                <label>Password</label>
                <input type="password" name="password" id="password">
            </div>
            <div>
                <label>First Name</label>
                <input type="text" name="firstName" id="firstName">
            </div>
            <div>
                <label>Last Name</label>
                <input type="text" name="lastName" id="lastName">
            </div>
            <div>
                <a href="./">Cancel</a>
                <button type="submit">Do it!</button>
            </div>
        </form>
    </body>
</html>
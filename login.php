<?php

session_start();

require __DIR__.'/config.php';
require __DIR__."/DB.php";

$db = new DB(array(
    'type' => 'mysql',
    'host' => _DBHOST,
    'name' => _DBNAME,
    'user' => _DBUSER,
    'pass' => _DBPASS
));

// Check if the user is already logged in, if yes then redirect him to welcome page
if(isset($_SESSION["loggedin"])){
    header("location: index.php");
    exit;
}

if($_POST){
    $aRow =& $_POST;
    
    $aUserRow = $db->row_array("SELECT * FROM users WHERE email = :email", array('email' => trim($aRow['email'])));

    if($aUserRow && isset($aUserRow['password'])){
        if($aUserRow['password']==MD5(trim($aRow['password']))){
            $_SESSION['loggedin'] = true;
            $_SESSION['email'] = $aUserRow['email'];
            header("location: index.php");
            exit;
        }
    }
    echo "<font color=red>Bad credentials</font>";
}
?>
<!doctype html>
<html>
    <head>
        <meta charset="utf-8"/>
        <title>OAuth2 Login</title>
    </head>
    <body>
        <header>
            <a href="./">Home</a> |
            <a href="./register.php">Register</a> |
            <a href="./login.php">Login</a> |
            <a href="./logout.php">Logout</a>
        </header>
        <h1>Login!</h1>
        <hr>
        <form action="./login.php" method="post">
            <div>
                <label>Email</label>
                <input type="text" name="email" id="email">
            </div>
            <div>
                <label>Password</label>
                <input type="password" name="password" id="password">
            </div>
            <div>
                <button type="submit" name="sb" id="sb">Login!</button>
            </div>
        </form>
    </body>
</html>
<?php
session_start();
?>
<!doctype html>
<html>
    <head>
        <meta charset="utf-8"/>
        <title>OAuth2 Home</title>
    </head>
    <body>
        <header>
            <a href="./">Home</a> |
            <a href="./register.php">Register</a> |
            <a href="./login.php">Login</a> |
            <a href="./logout.php">Logout</a>
        </header>
        <b>OAuth2 Home</b>
<?php
if(isset($_SESSION["loggedin"])):
?>
        <p><a href="redirect_authorize.php">Authorize</p>
        <p><a href="logout.php">Logout</a></p>
<?php
else:    
?>
        <p><a href="login.php">Login</a></p>
        <p><a href="register.php">Register</a></p>
<?php    
endif;
?>

    </body>
</html>

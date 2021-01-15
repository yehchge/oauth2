<?php

require __DIR__.'/config.php';


$db = new PDO('mysql:host='._DBHOST.';port=3306;dbname='._DBNAME.';charset=utf8mb4',_DBUSER,_DBPASS,array(PDO::ATTR_PERSISTENT => false));

$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$result = $db->query("SELECT * FROM users");
while($row=$result->fetch(PDO::FETCH_OBJ)){
    echo $row->email.PHP_EOL;
}
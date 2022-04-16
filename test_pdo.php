<?php

require __DIR__.'/config.php';
require __DIR__.'/DB.php';

$db = new PDO('mysql:host='._DBHOST.';port=3306;dbname='._DBNAME.';charset=utf8mb4',_DBUSER,_DBPASS,array(PDO::ATTR_PERSISTENT => false));

$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$result = $db->query("SELECT * FROM users");
while($row=$result->fetch(PDO::FETCH_OBJ)){
    echo $row->email.PHP_EOL;
}

$oDB = new DB(array(
    'type' => 'mysql',
    'host' => _DBHOST,
    'name' => _OAUTH2_NAME,
    'user' => _DBUSER,
    'pass' => _DBPASS
));

// Add
$data = array();
$data['one'] = sRandomString('',7);
$data['two'] = rand(1,100);
$id = $oDB->insert('tb1', $data);
if ($id){
    echo 'Insert ID is : ', $id."\r\n";
}

// Update
$aWhere = array();
$aData = array();
$aWhere['id'] = 1;
$aData['one'] = 'iLoveFish';
$iChangeRows = $oDB->update('tb1', $aData, "id=:id", array('id'=>1));

if ($iChangeRows) {
    echo 'Number of rows modified: ', $iChangeRows."\r\n";
}


// Delete
$iDbq2 = $oDB->delete('tb1', 'id=:id', array('id'=>1));
if ($iDbq2) {
    echo 'Number of rows deleted: ', $iDbq2."\r\n";
}

// List
$results = $oDB->select("SELECT * FROM tb1 ORDER BY id DESC LIMIT 0,10");
foreach($results as $key => $val){
    echo $val['one']." , ".$val['two'].PHP_EOL;
}

function sRandomString($sString,$sNum){ //(字元,回傳幾位)
    if(strlen($sString)==0){
        $s="ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $s.="abcdefghijklmnopqrstuvwxyz";
        $s.="0123456789";
    } else {
        $s=$sString;
    }
    $rs = '';
    for($i=0;$i<$sNum;$i++){
        $rs.=$s[rand(0,strlen($s)-1)];
    }
    return $rs;
}
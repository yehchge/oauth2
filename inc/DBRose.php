<?php

class Database extends PDO {

    // Variables 
    var $m_character = "utf8";

    public function __construct($sDb='',$sHost='',$sUser='',$sPass='') {
        $this->m_sDb=$sDb;
        $this->m_sHost=$sHost;
        $this->m_sUser=$sUser;
        $this->m_sPass=$sPass;

        if(!$this->m_iDbh) $this->vConnect();
        
        # reset executed query count
        $this->count_queries = 0;       
        $this->query_log = array();
    }
    
    public function __destruct() {
        $this->bFreeAllRows();
        $this->vClose();
    }

    /**
    * @desc  連線資料庫
    */
    private function vConnect() {
        try {
            $this->m_iDbh = new PDO('mysql:host='.$this->m_sHost.';dbname='.$this->m_sDb.';charset=utf8', $this->m_sUser, $this->m_sPass, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8';"));
            $this->m_iDbh->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $err) {
            $this->_reportError( $err );
        }
    }

    /**
    * @param $sSql SQL語法
    * @return returns value of variable $m_iRs
    * @desc query db
    */
    public function iQuery($sSql) {
        $sSql = trim($sSql);
        $this->_query_string = $sSql;
        if( $this->m_iDebug ) { $this->query_log[] = $sSql; }
        $this->count_queries++;
        
        $this->m_iRs = $this->m_iDbh->prepare($sSql);

        try {
            $this->m_iRs->execute();
        } catch(PDOException $err){
            $this->_reportError( $err );
        }
        return $this->m_iRs;
    }
    
    /**
    * @param $iRs resource result 
    * @return Fetch a result row as an associative array, a numeric array, or both. 
    * @desc 取得sql結果
    */
    public function aFetchArray($iRs=0) {
        if(!$this->m_iRs && !$iRs) return 0;
           
        if($iRs) $iTmpRs=$iRs;
        else $iTmpRs=$this->m_iRs;
        return $iTmpRs->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
    * @param $iRs resource result 
    * @return Fetch a result row as an associative array, a numeric array, or both. 
    * @desc 取得sql結果
    */
    public function aFetchAssoc($iRs=0) {
        if(!$this->m_iRs && !$iRs) return 0;
        if($iRs) $iTmpRs=$iRs;
        else $iTmpRs=$this->m_iRs;
    
        try {
            $iTmpRs->setFetchMode(PDO::FETCH_ASSOC);
            return $iTmpRs->fetch();
        } catch(PDOException $err){
            $this->_reportError( $err );
        }
    }
    
    /**
    * @param $iRs resource result 
    * @return Fetch a result row as an associative array, a numeric array, or both. 
    * @desc 取得sql結果
    */
    function aFetchRow($iRs=0) {
        if(!$this->m_iRs && !$iRs) return 0;
        if($iRs) $iTmpRs=$iRs;
        else $iTmpRs=$this->m_iRs;
    
        try {
            $iTmpRs->setFetchMode(PDO::FETCH_ASSOC);
            return $iTmpRs->fetch();
        } catch(PDOException $err){
            $this->_reportError( $err );
        }
    }
    
    /**
    * @return Get the ID generated from the previous INSERT operation 
    * @desc 取得insert後的自動流水號
    */
    public function iGetInsertId() {
        if(!$this->m_iDbh) return 0;
        return $this->m_iDbh->lastInsertId();
    }
    
    /**
    * @param $sTable db table $aField field array $aValue value array
    * @return if return sql is ok  "" is failure
    * @desc insert into table
    */
    public function sInsert($sTable,$aField,$aValue) {
        if(!is_array($aField)) return 0;
        if(!is_array($aValue)) return 0;
        
        count($aField)==count($aValue) or die(count($aField) .":". count($aValue) );
        
        $fieldNames = implode('`, `', $aField);
        $fieldValues = ':'. implode(', :', $aField);
        
        $sSql = "INSERT INTO `$sTable` (`$fieldNames`) VALUES ($fieldValues)";
        $this->_query_string = $sSql;
        if( $this->m_iDebug ) { $this->query_log[] = $sSql; }
        $this->count_queries++;
        
        if(!$this->m_iDbh) $this->vConnect();
        
        $this->m_iRs =  $this->m_iDbh->prepare($sSql);
        
        foreach ($aValue as $key => $value) {
            $this->m_iRs->bindValue(":".$aField[$key], $value);
        }

        try {
            $this->m_iRs->execute();
        } catch (PDOException $err) {
            $this->_reportError( $err );
        }
        
        if(!$this->m_iRs) {
            return NULL;
        } else return $sSql;
    }
    
    /**
    * @param $sTable db table $aField field array $aValue value array $sWhere trem
    * @return if return sql is ok  "" is failure
    * @desc update  table
    */
    public function sUpdate($sTable,$aField,$aValue,$sWhere) {
        if(!is_array($aField)) return 0;
        if(!is_array($aValue)) return 0;
        if(count($aField)!=count($aValue)) return 0;
        
        $fieldDetails = NULL;
        foreach($aField as $key => $value) {
            $fieldDetails .= "`$value`=:$value,";
        }
        $fieldDetails = rtrim($fieldDetails, ',');
        
        $sSql = "UPDATE $sTable SET $fieldDetails WHERE $sWhere";
        $this->_query_string = $sSql;
        if( $this->m_iDebug ) { $this->query_log[] = $sSql; }
        $this->count_queries++;
        
        $this->m_iRs =  $this->m_iDbh->prepare($sSql);
        
        foreach ($aValue as $key => $value) {
            $this->m_iRs->bindValue(":".$aField[$key], $value);
        }
        
        try {
            $this->m_iRs->execute();
        } catch (Exception $err) {
            $this->_reportError( $err );
        }
        
        if(!$this->m_iRs) return NULL;
        else return $sSql;
    }
    
    /**
     * insert 
     * @param string $table A name of table to insert into
     * @param string $data An associative array
     */
    public function bInsert($table, $data) {
        ksort($data);
        
        $fieldNames = implode('`, `', array_keys($data));
        $fieldValues = ':'. implode(', :', array_keys($data));
    
        $sSql = "INSERT INTO $table (`$fieldNames`) VALUES ($fieldValues)";
        $this->_query_string = $sSql;
        if( $this->m_iDebug ) { $this->query_log[] = $sSql; }
        $this->count_queries++;
        
        $this->m_iRs = $this->m_iDbh->prepare($sSql);
        
        foreach ($data as $key => $value) {
            $this->m_iRs->bindValue(":$key", $value);
        }
        
        try {
            $this->m_iRs->execute();
        } catch (Exception $err) {
            $this->_reportError( $err );
        }
        
        if(!$this->m_iRs) {
            return NULL;
        } else return $sSql;
    }

    /**
     * update 
     * @param string $table A name of table to insert into
     * @param string $data An associative array
     * @param string $where the WHERE query part
     */
    public function bUpdate($table, $where, $data) {
        ksort($data);
        
        $fieldDetails = NULL;
        foreach($data as $key => $value) {
            $fieldDetails .= "`$key`=:$key,";
        }
        $fieldDetails = rtrim($fieldDetails, ',');
        
        $aWhere = array();
        foreach( $where AS $key => $value ) {
            $aWhere[] = "$key = '$value'";
        }
        $sWhere = count( $aWhere ) > 0 ? implode( " AND " , $aWhere ) : "1";
        
        $sSql = "UPDATE $table SET $fieldDetails WHERE $sWhere";
        $this->_query_string = $sSql;
        if( $this->m_iDebug ) { $this->query_log[] = $sSql; }
        $this->count_queries++;
        
        $this->m_iRs = $this->m_iDbh->prepare($sSql);
        
        foreach ($data as $key => $value) {
            $this->m_iRs->bindValue(":$key", $value);
        }
        
        try {
            $this->m_iRs->execute();
        } catch (Exception $err) {
            $this->_reportError( $err );
        }
        
        if(!$this->m_iRs) return NULL;
        else return $sSql;
    }
    
    /**
     * delete
     * @param string $table
     * @param string $where
     * @param integer $limit
     * @return integer Affected Rows
     */
    public function vDelete($table, $where, $limit = 1) {
        if (!$where OR $where=='1') return false;
        return $this->iQuery("DELETE FROM $table WHERE $where LIMIT $limit");
    }
    
    public function vAllDelete($table, $where){
        if (!$where OR !$table OR $where=='1') return false;
        return $this->iQuery("DELETE FROM $table WHERE $where");
    }
    
    /**
    * @param $iRs resource result 
    * @return Get number of rows in result
    * @desc 取得sql結果比數
    */
    function iNumRows($iRs=0) {
        if ($iRs) $iTmpRs=$iRs;
        else $iTmpRs=$this->m_iRs;
        if (!$iTmpRs) return 0;
        //$this->m_aRs = $iTmpRs->fetchAll(PDO::FETCH_ASSOC);
        $iTotal = $iTmpRs->rowCount();
        return $iTotal;
    }
    
    // transactions function
    public function vBegin() {
        $this->m_iDbh->beginTransaction();
    }
    
    public function vCommit() {
        $this->m_iDbh->commit();
    }
    
    public function vRollback() {
        $this->m_iDbh->rollBack();
    }

    /**
    * @param $iRs resource result 
    * @return Free result memory
    * @desc Free result memory
    */
    function bFreeRows($iRs=0) {
        $this->m_iRs = null;
        $iRs = null;
    }
    
    function bFreeAllRows() {
        if(count($this->m_aRs) == 0) return false;
        for($i=0;$i<count($this->m_aRs);$i++){
            $this->m_aRs[$i] = null;
        }   
    }
    
    /**
    * @desc  關閉資料庫
    */
    function vClose() {
        $this->m_iDbh = null;
    }

    /**
    * @param $sTable db table
    * @return array
    * @desc 得到table所有欄位資訊
    */
    function aGetAllFieldsInfo($sTable){
        $this->iQuery("SHOW FULL FIELDS FROM $sTable"); 
        while($aRow=$this->aFetchArray()){
            $aFields[]=$aRow;           
        }   
        return $aFields;        
    }
    
    /**
    * @param $sTable db table
    * @return array
    * @desc 得到table create sql info
    */
    function aGetCreateTableInfo($sTable){
        $this->iQuery("SET SQL_QUOTE_SHOW_CREATE = 1"); 
        $this->iQuery("SHOW CREATE TABLE $sTable"); 
        $aRow=$this->aFetchArray();
        return $aRow;       
    }
    
    function aGetTableStatus($sTable){
        $this->iQuery("SHOW TABLE STATUS LIKE '$sTable'"); 
        $aRows = array();
        while($aRow=$this->aFetchArray()){
            $aRows[] = $aRow;
        }
        return $aRows;      
    }
    
    /**
    * @param $sTable db table
    * @return array
    * @desc 得到table create sql info
    */
    function bIsTableExist($sTable){
        $iDbq = $this->iQuery("SHOW TABLES LIKE '%$sTable%'"); 
        if($this->iNumRows($iDbq)) return true;
        return false;
    }
    
    /**
    * @param $sTable db table
    * @return array
    * @desc 得到table create sql info
    */
    function bIsDatabaseExist($sDatabase){
        $iDbq = $this->iQuery("show databases like '$sDatabase'"); 
        if($this->iNumRows($iDbq)) return true;
        return false;
    }

    function _reportError( $err ) {
        echo $this->_query_string ."<br>";
        echo $err->getMessage();
        return;
        //die();
        
        // Catch Expcetions from the above code for our Exception Handling
        $trace = '<table border="0">';
        foreach ($err->getTrace() as $a => $b) {
            foreach ($b as $c => $d) {
                if ($c == 'args') {
                    foreach ($d as $e => $f) {
                        $trace .= '<tr><td><b>' . strval($a) . '#</b></td><td align="right"><u>args:</u></td> <td><u>' . $e . '</u>:</td><td><i>' . $f . '</i></td></tr>';
                    }
                } else {
                    $trace .= '<tr><td><b>' . strval($a) . '#</b></td><td align="right"><u>' . $c . '</u>:</td><td></td><td><i>' . $d . '</i></td>';
                }
            }
        }
        $trace .= '</table>';
        
        $echo_str = '<html><head><title>PHP PDO ERROR</title></head><body>';
        $echo_str.= '<br /><br /><br /><font face="Verdana"><center><fieldset style="width: 66%; border: 4px solid white; background: white;"><legend><b>[</b>PHP PDO Error ' . strval($err->getCode()) . '<b>]</b></legend> 
                    <table border="1">
                      <tr>
                        <td align="right"><b><u>Message:</u></b></td>
                        <td><i>' . $err->getMessage() . '</i></td>
                      </tr>
                      <tr>
                        <td align="right"><b><u>Code:</u></b></td>
                        <td><i>' . strval($err->getCode()) . '</i></td>
                      </tr>
                      <tr>
                        <td align="right"><b><u>File:</u></b></td>
                        <td><i>' . $err->getFile() . '</i></td>
                      </tr>
                      <tr>
                        <td align="right"><b><u>Line:</u></b></td>
                        <td><i>' . strval($err->getLine()) . '</i></td>
                      </tr>
                      <tr>
                        <td align="right"><b><u>Trace:</u></b></td>
                        <td><br /><br />' . $trace . '</td>
                      </tr>';
        if( $this->_query_string )  {           # 如果有執行 mysql_query 時顯示 coding 內容
            $echo_str.= '
              <tr>
                <td align="right"><b><u>Query String:</u></b></td>
                <td><br /><br />' .$this->myHSC($this->_query_string). '</td>
              </tr>';
        }
        if( $this->query_log)   {
            $echo_str.= '
              <tr>
                <td align="right"><b><u>Query Log:</u></b></td>
                <td><br /><br />' .implode("<br />",$this->query_log). '</td>
              </tr>';
        }
        
        if($_GET)   {
            $echo_str.= '
              <tr>
                <td align="right"><b><u>_GET:</u></b></td>
                <td><br /><br /><pre>' .var_export( $_GET, true ). '</pre></td>
              </tr>';
        }
        if($_POST)  {
            $echo_str.= '
              <tr>
                <td align="right"><b><u>_POST:</u></b></td>
                <td><br /><br /><pre>' .var_export( $_POST, true ). '</pre></td>
              </tr>';
        }
        if( $_SESSION ) {
            $echo_str.= '
              <tr>
                <td align="right"><b><u>_SESSION:</u></b></td>
                <td style="max-width:400px;"><br /><br /><pre>' .var_export( $_SESSION, true ). '</pre></td>
              </tr>';
        }             
        $echo_str.= '</table></fieldset></center></font>';
        $echo_str.= '</body></html>';
            
        $msgs = array();
        if( !empty( $trace ) )  {       # 顯示系統內定錯誤訊息
           $msgs[] = '<p>' .$echo_str. '</p>';
        }
       
        # display or send? 如果有DEBUG則顯示錯誤內容
        if( $this->m_iDebug ) {   # development environment - display error immediately
            die( implode( "\n", $msgs ) );
        } else {   # live server - notify developer via email and then stop script execution
            ini_set("smtp_port","25");
            ini_set("SMTP","zimbra.iwant-in.net");
            $headers =  "Content-Type: text/html; charset=".CHARSET."\r\n".
                        "From: "._SYS_MAIL_FROM."\t\n".
                        "X-Mailer: PHP/".phpversion();
            // mail( "service@gmail.com", $_SERVER['SERVER_NAME'].' ERROR: DB_Error', implode( "\n", $msgs ), $headers );
            die( 'A database error occurred.' );
        }
    }
    
    function myHSC( $str ) {
        if( !is_string($str) ) return $str; 
        return preg_replace( "(copy;|reg;|trade;|[a-z]{3,8};|#1[0-9]{2};|#[0-9]{4,5};)", "&\\1", htmlspecialchars( $str, ENT_COMPAT, CHARSET ) );
    }

    /**
    * @param $sTableName db table 
    * @return 數字 
    * @desc 得到table primary key
    */
    function sGetTablePrimaryKey($sTableName=""){
        return md5($sTableName+uniqid(mt_rand(), true));
    }

}

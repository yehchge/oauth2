<?php 

/**
 * @author      Jesse Boyer <contact@jream.com>
 * @copyright   Copyright (C), 2011-12 Jesse Boyer
 * @license     GNU General Public License 3 (http://www.gnu.org/licenses/)
 *              Refer to the LICENSE file distributed within the package.
 *
 * @link        http://jream.com
 * 
 * @category    Database
 * @example
 * try {
 *    $db = new jream\Database($db);
 *    $db->select("SELECT * FROM user WHERE id = :id", array('id', 25));
 *    $db->insert("user", array('name' => 'jesse'));
 *    $db->update("user", array('name' => 'juicy), "id = '25'");
 *    $db->delete("user", "id = '25'");
 * } catch (Exception $e) {
 *    echo $e->getMessage();
 * }
 */
class DB extends PDO {

    // Variables
    // var $m_sDb      =   "";
    // var $m_sHost    =   '';
    // var $m_sUser    =   '';
    // var $m_sPass    =   '';
    // var $m_iDbh     =   0;
    // var $m_iRs      =   0;
    // var $m_character    = "utf8";
    // var $m_sPort    =   "3306";
    // var $m_connect  =   true; // 是否長連
    // private $mode;
    
    /** @var boolean $activeTransaction Whether a transaction is going on */
    public $activeTransaction;
    
    /** @var string $_sql Stores the last SQL command */
    private $_sql;
    
    /** @var constant $_fetchMode The select statement fetch mode */
    private $_fetchMode = \PDO::FETCH_ASSOC;


    /**
     * __construct - Initializes a PDO connection (Two ways of connecting)
     * 
     * @param array $db An associative array containing the connection settings,
     * @param string $type Optional if using arugments to connect
     * @param string $host Optional if using arugments to connect
     * @param string $name Optional if using arugments to connect
     * @param string $user Optional if using arugments to connect
     * @param string $pass Optional if using arugments to connect
     *
     *  // First Way:
     *    $db = array(
     *        'type' => 'mysql'
     *        ,'host' => 'localhost'
     *        ,'name' => 'test'
     *        ,'user' => 'root'
     *        ,'pass' => ''
     *    );
     *  $db = new jream\Database($db);
     */
    public function __construct($db, $type = null, $host = null, $name = null, $user = null, $pass = null, $persistent = false)
    {
        try {
            $persistent = isset($db['persistent']) ? $db['persistent'] : false;
            parent::__construct("{$db['type']}:host={$db['host']};dbname={$db['name']}", $db['user'], $db['pass'], array(\PDO::ATTR_PERSISTENT => $persistent));
            parent::exec('SET names utf8');
        } catch (\PDOException $e) {
            die($e->getMessage());
        }
    }
    
    /**
     * setFetchMode - Change the default mode for fetching a query
     *
     * @param constant $fetchMode Use the PDO fetch constants, eg: \PDO::FETCH_CLASS
     */
    public function setFetchMode($fetchMode)
    {
        $this->_fetchMode = $fetchMode;
    }

    public function setCharset($encode = "utf8") {
        parent::exec("SET names {$encode}");
    }
    
    /**
     * select - Run & Return a Select Query
     * 
     * @param string $query Build a query with ? marks in the proper order,
     *    eg: SELECT :email, :password FROM tablename WHERE userid = :userid
     * 
     * @param array $bindParams Fields The fields to select to replace the :colin marks,
     *    eg: array('email' => 'email', 'password' => 'password', 'userid' => 200);
     *
     * @param constant $overrideFetchMode Pass in a PDO::FETCH_MODE to override the default or the setFetchMode setting
     *
     * @return array
     */
    public function select($query, $bindParams = array(), $overrideFetchMode = null)
    {
        /** Store the SQL for use with fetching it when desired */
        $this->_sql = $query;
        
        /** Make sure bindParams is an array, I mess this up a lot when overriding fetch! */
        if (!is_array($bindParams))
        throw new \Exception("$bindParams must be an array");
        
        /** Run Query and Bind the Values */
        $sth = $this->_prepareAndBind($bindParams);
    
        $result = $sth->execute();
        
        /** Throw an exception for an error */
        $this->_handleError($result, __FUNCTION__);
        
        /** Automatically return all the goods */
        if ($overrideFetchMode != null)
        return $sth->fetchAll($overrideFetchMode);
        else
        return $sth->fetchAll($this->_fetchMode);
    }

    /**
     * Returns a single result row - array version
     *
     * @access  public
     * @return  array
     */
    public function row_array($query, $bindParams = array(), $overrideFetchMode = null)
    {
        /** Store the SQL for use with fetching it when desired */
        $this->_sql = $query;
        
        /** Make sure bindParams is an array, I mess this up a lot when overriding fetch! */
        if (!is_array($bindParams))
        throw new \Exception("$bindParams must be an array");
        
        /** Run Query and Bind the Values */
        $sth = $this->_prepareAndBind($bindParams);
    
        $result = $sth->execute();
        
        /** Throw an exception for an error */
        $this->_handleError($result, __FUNCTION__);

        /** Automatically return all the goods */
        if ($overrideFetchMode != null)
        $result = $sth->fetchAll($overrideFetchMode);
        else
        $result = $sth->fetchAll($this->_fetchMode);


        if (!isset($result[0]))
        {
            return [];
        }

        return $result[0];
    }

    /**
     * insert - Convenience method to insert data
     *
     * @param string $table    The table to insert into
     * @param array $data    An associative array of data: field => value
     */
    public function insert($table, $data)
    {    
        /** Prepare SQL Code */
        $insertString = $this->_prepareInsertString($data);

        /** Store the SQL for use with fetching it when desired */
        $this->_sql = "INSERT INTO `{$table}` (`{$insertString['names']}`) VALUES({$insertString['values']})";

        /** Bind Values */
        $sth = $this->_prepareAndBind($data);

        /** Execute Query */
        $result = $sth->execute();
        
        /** Throw an exception for an error */
        $this->_handleError($result, __FUNCTION__);
        
        /** Return the insert id */
        return $this->lastInsertId();
    }

    /**
     * exec - Convenience method to insert data
     *
     * @param string $table    The table to insert into
     * @param array $data    An associative array of data: field => value
     */
    public function exec_insert($table, $data)
    {    
        /** Prepare SQL Code */
        $insertString = array(
            'names' => implode("`, `",array_keys($data)),
            'values' => implode("', '",array_values($data))
        );

        /** Store the SQL for use with fetching it when desired */
        $this->_sql = "INSERT INTO `{$table}` (`{$insertString['names']}`) VALUES('{$insertString['values']}')";

        /** Execute Query */
        $result = $this->exec($this->_sql);
        
        /** Throw an exception for an error */
        $this->_handleError($result, __FUNCTION__);
        
        /** Return the insert id */
        return $result;
    }


    
    /**
     * update - Convenience method to update the database
     * 
     * @param string $table The table to update
     * @param array $data An associative array of fields to change: field => value
     * @param string $where A condition on where to apply this update
     * @param array $bindWhereParams If $where has parameters, apply them here
     *
     * @return boolean Successful or not
     */
    public function update($table, $data, $where, $bindWhereParams = array())
    {
        /** Build the Update String */
        $updateString = $this->_prepareUpdateString($data);

        /** Store the SQL for use with fetching it when desired */
        $this->_sql = "UPDATE `{$table}` SET $updateString WHERE $where";
        
        /** Bind Values */
        $sth = $this->_prepareAndBind($data);

        /** Bind Where Params */
        $sth = $this->_prepareAndBind($bindWhereParams, $sth);
        
        /** Execute Query */
        $result = $sth->execute();
        
        /** Throw an exception for an error */
        $this->_handleError($result, __FUNCTION__);
        
        /** Return Result */
        return $result;
    }
    
    /**
     * replace - Convenience method to replace into the database
     *              Note: Replace does a Delete and Insert 
     *
     * @param string $table The table to update
     * @param array $data An associative array of fields to change: field => value
     *
     * @return boolean Successful or not
     */
    public function replace($table, $data)
    {
        /** Build the Update String */
        $updateString = $this->_prepareUpdateString($data);

        /** Prepare SQL Code */
        $this->_sql = "REPLACE INTO `{$table}` SET $updateString";
        
        /** Bind Values */
        $sth = $this->_prepareAndBind($data);
        
        /** Execute Query */
        $result = $sth->execute();

        /** Throw an exception for an error */
        $this->_handleError($result, __FUNCTION__);
        
        /** Return Result */
        return $result;
    }
    
    /**
    * delete - Convenience method to delete rows
    *
    * @param string $table The table to delete from
    * @param string $where A condition on where to apply this call
    * @param array $bindWhereParams If $where has parameters, apply them here
    * 
    * @return integer Total affected rows
    */
    public function delete($table, $where, $bindWhereParams = array())
    {
        /** Prepare SQL Code */
        $this->_sql = "DELETE FROM `{$table}` WHERE $where";
        
        /** Bind Values */
        $sth = $this->_prepareAndBind($bindWhereParams);        
        
        /** Execute Query */
        $result = $sth->execute();

        /** Throw an exception for an error */
        $this->_handleError($result, __FUNCTION__);
        
        /** Return Result */
        return $sth->rowCount();
    }
    
    /**
     * insertUpdate - Convenience method to insert/if key exists update.
     *
     * @param string $table    The table to insert into
     * @param array $data    An associative array of data: field => value
     */
    public function insertUpdate($table, $data)
    {
        /** Prepare SQL Code */
        $insertString = $this->_prepareInsertString($data);
        $updateString = $this->_prepareUpdateString($data);

        /** Store the SQL for use with fetching it when desired */
        $this->_sql = "INSERT INTO `{$table}` (`{$insertString['names']}`) VALUES({$insertString['values']}) ON DUPLICATE KEY UPDATE {$updateString}";
        
        /** Bind Values */
        $sth = $this->_prepareAndBind($data);

        /** Execute Query */
        $result = $sth->execute();
        
        /** Throw an exception for an error */
        $this->_handleError($result, __FUNCTION__);
        
        /** Return the insert id */
        return $this->lastInsertId();    
    }
    
    /**
     * getQuery - Return the last sql Query called
     * 
     * @return string
     */
    public function showQuery()
    {
        return $this->_sql;
    }
        
    /**
    * id - Gets the last inserted ID
     * 
     * @return integer
     */
    public function id()
    {
        return $this->lastInsertId();
    }
    
    /**
     * beginTransaction - Overloading default method 
     */
    public function beginTransaction()
    {
        parent::beginTransaction();
        $this->activeTransaction = true;
    }
    
    /**
     * commit - Overloading default method 
     */
    public function commit()
    {
        parent::commit();
        $this->activeTransaction = false;
    }
    
    /**
     * rollback - Overloading default method 
     */
    public function rollback()
    {
        parent::rollback();
        $this->activeTransaction = false;
    }
    
    /**
     * showColumns - Display the columns for a table (MySQL)
     *
     * @param string $table Name of a MySQL table
     */
    public function showColumns($table)
    {
        $result = $this->select("SHOW COLUMNS FROM `$table`", array(), \PDO::FETCH_ASSOC);
        
        $output = array();
        foreach ($result as $key => $value)
        {
        
            if ($value['Key'] == 'PRI')
            $output['primary'] = $value['Field'];
            
            $output['column'][$value['Field']] = $value['Type'];
        }
        
        return $output;
    }

    /**
     * _prepareAndBind - Binds values to the Statement Handler
     *
     * @param array $data
     * @param object $reuseStatement If you need to reuse the statement to apply another bind
     *
     * @return object
     */
    private function _prepareAndBind($data, $reuseStatement = false)
    {
        if ($reuseStatement == false) {
            $sth = $this->prepare($this->_sql);
        } else {
            $sth = $reuseStatement;
        }
        
        foreach ($data as $key => $value)
        {
            if (is_int($value)) {
                $sth->bindValue(":$key", $value, \PDO::PARAM_INT);
            } else {
                $sth->bindValue(":$key", $value, \PDO::PARAM_STR);
            }
        }
        
        return $sth;
    }
    
    /**
     * _prepareInsertString - Handles an array and turns it into SQL code
     * 
     * @param array $data The data to turn into an SQL friendly string
     * @return array
     */
    private function _prepareInsertString($data) 
    {
        /** 
        * @ Incoming $data looks like:
        * $data = array('field' => 'value', 'field2'=> 'value2');
        */
        return array(
            'names' => implode("`, `",array_keys($data)),
            'values' => ':'.implode(', :',array_keys($data))
        );
    }
    
    /**
     * _prepareUpdateString - Handles an array and turn it into SQL code
     * 
     * @param array $data
     * @return string
     */
    private function _prepareUpdateString($data) 
    {
        /**
        * @ Incoming $data looks like:
        * $data = array('field' => 'value', 'field2'=> 'value2');
        */
        $fieldDetails = NULL;
        foreach($data as $key => $value)
        {
            $fieldDetails .= "`$key`=:$key, "; /** Notice the space after the comma */
        }
        $fieldDetails = rtrim($fieldDetails, ', '); /** Notice the space after the comma */
        return $fieldDetails;
    }
    
    /**
    * _handleError - Handles errors with PDO and throws an exception.
    */
    private function _handleError($result, $method)
    {
        /** If it's an SQL error */
        if ($this->errorCode() != '00000')
        throw new \Exception("Error: " . implode(',', $this->errorInfo()));
        
        if ($result == false) 
        {
            $error =  $method . " did not execute properly";
            throw new \Exception($error);
        }
    }

    // public function __destruct() {
    //     $this->close();
    // }

    // /**
    //  *  @desc 設定 MySQL 連結為 UTF-8
    //  *  @created 2014/11/14
    //  */
    // function set_encode($encode = "utf8") {
    //     $this->query("SET character_set_client = $encode");
    //     $this->query("SET character_set_results = $encode");
    //     $this->query("SET character_set_connection = $encode");
    // }
    // 
    // /**
    //  *  @desc 連線資料庫
    //  */
    // public function conn() {

    //      try {
    //         $this->m_iDbh = new PDO('mysql:host='.$this->m_sHost.';port='.$this->m_sPort
    //             .';dbname='.$this->m_sDb,$this->m_sUser,$this->m_sPass, array(PDO::
    //             ATTR_PERSISTENT => $this->m_connect));
    //     }
    //     catch (PDOException $e) {
    //         die("Connect Error Infomation:" . $e->getMessage());
    //     }
    // }

    // /**
    //  *  @desc 關閉資料庫
    //  */
    // function close() {
    //     $this->m_iDbh = NULL;
    // }

    // /**
    //  *  @desc query db
    //  *  @param $sSql SQL語法
    //  *  @return value of variable $rs
    //  */
    // function query($sSql){
    //     $this->m_iRs = $this->m_iDbh->query($sSql);
    //     return $this->m_iRs;
    // }

    // /**
    //  * @desc 執行Insert, Update, Delete 語法
    //  * @created 2017/04/18
    //  */
    // function vExec($sSql){
    //     $this->m_iRs = $this->m_iDbh->exec($sSql);
    //     return $this->m_iRs;
    // }

    // /**
    //  *  @desc 取得sql結果
    //  *  @param $iRs resource result
    //  *  @param result_type: MYSQLI_BOTH, MYSQLI_ASSOC, MYSQLI_NUM
    //  *  @return Fetch a result row as an associative array, a numeric array, or both.
    //  */
    // function aFetchArray($iRs) {
    //     $iRs->setFetchMode(PDO::FETCH_NUM);
    //     return $iRs->fetch();
    // }

    // /**
    // * @param $iRs resource result
    // * @return Fetch a result row as an associative array, a numeric array, or both.
    // * @desc 取得sql結果
    // */
    // function aFetchAssoc($iRs=0) {
    //     $iRs->setFetchMode(PDO::FETCH_ASSOC);
    //     return $iRs->fetch();
    // }

    // function aFetchAllAssoc($iRs=0) {
    //     $iRs->setFetchMode(PDO::FETCH_ASSOC);
    //     return $iRs->fetchAll();
    // }

    // /**
    // * @return Get the ID generated from the previous INSERT operation
    // * @desc
    // */
    // function iGetInsertId() {
    //     return $this->m_iDbh->lastInsertId();
    // }

    // /**
    //  * delete
    //  *
    //  * @param string $table
    //  * @param string $where
    //  * @param integer $limit
    //  * @return integer Affected Rows
    //  */
    // function vDelete($sTable,$sWhere){
    //     if (!$sWhere) throw new Exception("CSQLite3->vDelete: fail no where. table: $sTable");
    //     $iUpdateRows = $this->vExec("DELETE FROM $sTable WHERE $sWhere");
    //     if(!$this->m_iRs){
    //         throw new Exception("CSQLite3->vDelete: fail to delete data in $sTable");
    //     }
    //     return $iUpdateRows;
    // }

    // /**
    // * @param $sTable db table $aField field array $aValue value array
    // * @return if return sql is ok  "" is failure
    // * @desc insert into table
    // */
    // function sInsert($sTable,$aField,$aValue) {
    //     if(!is_array($aField)) return 0;
    //     if(!is_array($aValue)) return 0;

    //     count($aField)==count($aValue) or die(count($aField) .":". count($aValue) );

    //     $sSql="INSERT INTO $sTable ( ";
    //     for($i=1;$i<=count($aField);$i++) {
    //         $sSql.="`".$aField[$i-1]."`";
    //         if($i!=count($aField)) $sSql.=",";
    //     }

    //     $sSql.=") values(";

    //     for($i=1;$i<=count($aValue);$i++) {
    //         $sSql.="'".$this->escapeString($aValue[$i-1])."'";
    //         if($i!=count($aValue)) $sSql.=",";
    //     }
    //     $sSql.=")";

    //     $this->iQuery($sSql);

    //     //if(!$this->m_iRs) return NULL;
    //     if(!$this->m_iRs) throw new Exception("CSQLite3->sInsert: fail to insert data into $sTable");
    //     else return $sSql;
    // }

    // /**
    // * @param $sTable db table $aField field array $aValue value array $sWhere trem
    // * @return if return sql is ok  "" is failure
    // * @desc update  table
    // */
    // function sUpdate($sTable,$aField,$aValue,$sWhere) {
    //     if(!is_array($aField)) return 0;
    //     if(!is_array($aValue)) return 0;

    //     if(count($aField)!=count($aValue)) return 0;

    //     $sSql="update $sTable set ";
    //     for($i=0;$i<count($aField);$i++) {
    //         $sSql.="`".$aField[$i]."`='".$this->escapeString($aValue[$i])."'";
    //         if(($i+1)!=count($aField)) $sSql.=",";
    //     }

    //     $sSql.=" where ".$sWhere;
    //     $this->sSql = $sSql;
    //     $this->iQuery($sSql);
    //     if(!$this->m_iRs) throw new Exception("CSQLite3->sUpdate: fail to update data in $sTable");
    //     else return $sSql;
    // }

    // /**
    // * @param string $sTable The table name, array $aAdd The add data array
    // * @return boolean
    // * @desc insert into table
    // */
    // function bInsert( $sTable , $aAdd ) {
    //     $sSql="INSERT INTO $sTable (";
    //     foreach( $aAdd AS $key => $value ) {
    //         $sSql.="`".$key."`,";
    //     }
    //     $sSql = substr($sSql,0,-1);
    //     $sSql.=") values (";
    //     foreach( $aAdd AS $key => $value ) {
    //         $sSql.="'".$value."',";
    //     }
    //     $sSql = substr($sSql,0,-1);
    //     $sSql.=")";

    //     $this->sSql = $sSql;
    //     $this->vExec( $sSql );
    //     if(!$this->m_iRs) throw new Exception("CSQLite3->bInsert: fail to insert data in $sTable");
    //     return $this->iGetInsertId();
    // }

    // /**
    // * @param string $sTable The table name, array $aSrc The source data array, array $aTar The target data array
    // * @return boolean
    // * @desc update table
    // */
    // function bUpdate( $sTable , $aSrc , $aTar ) {
    //     $aWhere = array();
    //     foreach( $aSrc AS $key => $value ) {
    //         $aWhere[] = "$key = '".$this->escapeString($value)."'";
    //     }
    //     $aSrc = array();
    //     foreach( $aTar AS $key => $value ) {
    //         $aSet[] = "$key = '".$this->escapeString($value)."'";
    //     }
    //     $sSQL = "UPDATE $sTable SET " . implode( "," , $aSet ) . " WHERE " . ( count( $aWhere ) > 0 ? implode( " AND " , $aWhere ) : "1" );

    //     $this->sSql = $sSQL;
    //     $iUpdateRows = $this->vExec( $sSQL );
    //     if(!$this->m_iRs) throw new Exception("CSQLite3->bUpdate: fail to update data in $sTable");
    //     return $iUpdateRows;
    // }

    // public function escapeString($tar) {
    //     if( !is_array($tar) )
    //         return ini_set("magic_quotes_runtime",0) ?  trim($tar) : addslashes(trim($tar));

    //     return array_map($this->escapeString, $tar); //pass ref to function
    // }

}

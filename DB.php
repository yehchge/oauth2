<?php 

/**
 * @category Database
 * @example
 * try {
 *    $db = new DB(['type'=>'mysql', 'host'=>'dbhost','name'=>'dbname','user'=>'dbuser','pass'=>'dbpass']);
 *    $db->select("SELECT * FROM user WHERE id = :id", array('id', 25));
 *    $db->insert("user", array('name' => 'mary'));
 *    $db->update("user", array('name' => 'jackie), "id = '25'");
 *    $db->delete("user", "id = '25'");
 * } catch (Exception $e) {
 *    echo $e->getMessage();
 * }
 */
class DB extends PDO {
    
    /** @var string $_sql Stores the last SQL command */
    private $_sql;
    
    /** @var constant $_fetchMode The select statement fetch mode */
    private $_fetchMode = PDO::FETCH_ASSOC;

    /**
     * Initializes a PDO connection
     * @param array $db An associative array containing the connection settings,

     *    $db = array(
     *        'type' => 'mysql',
     *        'host' => 'localhost',
     *        'name' => 'test',
     *        'user' => 'root',
     *        'pass' => ''
     *    );
     *  $db = new DB($db);
     */
    public function __construct($db, $persistent = false)
    {
        try {
            $dsn = $db['type'].':host='.$db['host'].';dbname='.$db['name'];
            parent::__construct($dsn, $db['user'], $db['pass'], array(
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ));
            self::setCharset();
            self::setFetchMode();
        } catch (PDOException $e) {
            die($e->getMessage().PHP_EOL);
        }
    }
    
    /**
     * @param constant $fetchMode Use the PDO fetch constants, eg: PDO::FETCH_CLASS
     */
    public function setFetchMode($fetchMode = PDO::FETCH_ASSOC)
    {
        $this->_fetchMode = $fetchMode;
    }

    public function setCharset($encode = "utf8") {
        try {
            parent::exec("SET names {$encode}");
        } catch (PDOException $e) {
            die($e->getMessage().PHP_EOL);
        }
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
     * @return array
     */
    public function select($query, $bindParams = array())
    {
        try {
            /** Store the SQL for use with fetching it when desired */
            $this->_sql = $query;
            
            /** Make sure bindParams is an array, I mess this up a lot when overriding fetch! */
            if (!is_array($bindParams))
                throw new Exception("$bindParams must be an array");
            
            /** Run Query and Bind the Values */
            $sth = $this->_prepareAndBind($bindParams);
        
            $result = $sth->execute();
            
            /** Throw an exception for an error */
            $this->_handleError($result, __FUNCTION__);
            
            return $sth->fetchAll($this->_fetchMode);
        } catch (PDOException $e) {
            die($e->getMessage().PHP_EOL);
        }    
    }

    /**
     * Returns a single result row - array version
     *
     * @access  public
     * @return  array
     */
    public function row_array($query, $bindParams = array())
    {
        try {

            /** Store the SQL for use with fetching it when desired */
            $this->_sql = $query;
            
            /** Make sure bindParams is an array, I mess this up a lot when overriding fetch! */
            if (!is_array($bindParams))
                throw new Exception("$bindParams must be an array");
            
            /** Run Query and Bind the Values */
            $sth = $this->_prepareAndBind($bindParams);
        
            $result = $sth->execute();
            
            /** Throw an exception for an error */
            $this->_handleError($result, __FUNCTION__);

            $result = $sth->fetchAll($this->_fetchMode);

            if (!isset($result[0]))
            {
                return [];
            }

            return $result[0];
        } catch (PDOException $e) {
            die($e->getMessage().PHP_EOL);
        }            
    }

    /**
     * insert - Convenience method to insert data
     *
     * @param string $table  The table to insert into
     * @param array $data    An associative array of data: field => value
     */
    public function insert($table, $data)
    {  
        try {
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
        } catch (PDOException $e) {
            die($e->getMessage().PHP_EOL);
        }
    }

    /**
     * exec - Convenience method to insert data
     *
     * @param string $table  The table to insert into
     * @param array $data    An associative array of data: field => value
     */
    public function exec_insert($table, $data)
    {   
        try {
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
        } catch (PDOException $e) {
            die($e->getMessage().PHP_EOL);
        }

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
        try {
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
            
            return $result;
        } catch (PDOException $e) {
            die($e->getMessage().PHP_EOL);
        }
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
        try {
            /** Prepare SQL Code */
            $this->_sql = "DELETE FROM `{$table}` WHERE $where";
            
            /** Bind Values */
            $sth = $this->_prepareAndBind($bindWhereParams);        
            
            /** Execute Query */
            $result = $sth->execute();

            /** Throw an exception for an error */
            $this->_handleError($result, __FUNCTION__);
            
            return $sth->rowCount();
        } catch (PDOException $e) {
            die($e->getMessage().PHP_EOL);
        }
    }
    
    /**
     * insertUpdate - Convenience method to insert/if key exists update.
     *
     * @param string $table    The table to insert into
     * @param array $data    An associative array of data: field => value
     */
    public function insertUpdate($table, $data)
    {
        try {
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
        } catch (PDOException $e) {
            die($e->getMessage().PHP_EOL);
        }
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
     * @return integer
     */
    public function id()
    {
        return $this->lastInsertId();
    }
    
    public function beginTransaction()
    {
        parent::beginTransaction();
    }

    public function commit()
    {
        parent::commit();
    }

    public function rollback()
    {
        parent::rollback();
    }
    
    /**
     * showColumns - Display the columns for a table (MySQL)
     *
     * @param string $table Name of a MySQL table
     */
    public function showColumns($table)
    {
        $result = $this->select("SHOW COLUMNS FROM `$table`", array(), PDO::FETCH_ASSOC);
        
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
                $sth->bindValue(":$key", $value, PDO::PARAM_INT);
            } else {
                $sth->bindValue(":$key", $value, PDO::PARAM_STR);
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
        throw new Exception("Error: " . implode(',', $this->errorInfo()));
        
        if ($result == false) 
        {
            $error =  $method . " did not execute properly";
            throw new Exception($error);
        }
    }

}

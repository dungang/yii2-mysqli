<?php
/**
 * Created by PhpStorm.
 * User: dungang
 * Date: 2017/3/1
 * Time: 9:29
 */

namespace dungang\mysqli;

use PDO;
use PDOException;

class PDO_Mysql_Statement
{

    /**
     * @var null| PDO_Mysql
     */
    private $_connection = NULL;

    /**
     * @var \mysqli_stmt|null
     */
    private $_statement = NULL;

    /**
     * @var \mysqli_result|bool
     */
    private $_result = NULL;

    /**
     * @var string
     */
    private $_pql = 'unknown';

    private $_typeMap = array(
        PDO::PARAM_INT => 'i',
        PDO::PARAM_STR => 's',
        PDO::PARAM_INT => 'd',
        PDO::PARAM_NULL => 's'
    );


    private $prepareParams = array();

    private $readyTypes = array();

    private $readyValues = array();



    private $_mode = MYSQL_BOTH;

    /**
     * PDO_Mysql_Statement constructor.
     * @param $_statement \mysqli_stmt
     * @param $connection PDO_Mysql
     */
    public function __construct($_statement, $connection)
    {
        $this->_statement = $_statement;
        $this->_connection = $connection;
    }

    public function getPdoType($type)
    {
        static $map = array(
            'boolean' => PDO::PARAM_BOOL,
            'integer' => PDO::PARAM_INT,
            'string' => PDO::PARAM_STR,
            'NULL' => PDO::PARAM_NULL,
        );
        return isset($map[$type]) ? $map[$type] : PDO::PARAM_STR;
    }

    public function bindParam($parameter, $value, $type = PDO::PARAM_STR)
    {
        $type = isset($this->_typeMap[$type]) ? $this->_typeMap[$type] : false;
        $key = array_search($parameter, $this->prepareParams);
        if ($key !== false and $type !== false) {
            $this->readyTypes[$key] = $type;
            $this->readyValues[$key] = $value;
            return true;
        } else {
            return false;
        }
    }

    //这里bindValue已经失去了本应该有的特性
    public function bindValue($parameter, $value, $type = PDO::PARAM_STR)
    {
        return $this->bindParam($parameter, $value, $type);
    }

    public function setStateSql($sql)
    {
        $this->_pql = $sql;
    }

    /**
     * @param array $params
     */
    public function execute($params = [])
    {
        if (!empty($params)) {
            foreach ($params as $_k => $_v) {
                $this->bindParam($_k, $_v, $this->getPdoType(gettype($_v)));
            }
        }
        if (!empty($this->readyTypes)) {
            $params = $this->readyValues;
            //ksort($params);
            array_unshift($params, implode($this->readyTypes));
            $statement = $this->_statement;
            call_user_func_array(array($statement, 'bind_param'), $this->refValues($params));

        }
        $this->_statement->execute();

        if ($this->_statement->errno) {
            throw new PDOException($this->_statement->error);
        }
    }

    /**
     * @return int
     */
    public function rowCount()
    {
        return $this->_statement->affected_rows;
    }

    /**
     * @param $mode
     * @return bool
     */
    public function setFetchMode($mode)
    {
        $mode = $this->transformFetchMode($mode);
        if ($mode === false) {
            return false;
        }
        $this->_mode = $mode;
        return true;
    }

    /**
     * @return bool
     */
    public function closeCursor()
    {
        $this->prepareParams = array();
        $this->readyTypes = array();
        $this->readyValues = array();
        $this->_pql = 'unknown';
        $this->_mode = MYSQL_BOTH;

        if (!empty($this->_result)) {
            $this->_result->free();
        }
        $this->_result = NULL;
        return $this->_statement->reset();
    }

    /**
     * @return int
     */
    public function columnCount()
    {
        return $this->_statement->field_count;
    }

    public function debugDumpParams()
    {
        echo $this->_pql;
    }

    public function errorCode()
    {
        return $this->_statement->errno;
    }

    public function errorInfo()
    {
        return array_values($this->_statement->error_list);
    }

    public function setPrepareParams($params)
    {
        $this->prepareParams = $params;
    }

    public function fetch($mode = NULL)
    {
        if ($this->_result == NULL) {
            $this->_result = $this->_statement->get_result();
        }
        if (empty($this->_result)) {
            throw new PDOException($this->_statement->error);
        }

        $_mode = $this->_mode;
        if (!empty($mode) and ($mode = $this->transformFetchMode($mode)) != false) {
            $_mode = $mode;
        }
        $result = $this->_result->fetch_array($_mode);
        return $result === NULL ? false : $result;
    }

    public function fetchColumn($column_number = 0)
    {
        $column = $this->fetch(PDO::FETCH_NUM);
        return $column[$column_number];
    }

    public function fetchAll($mode = NULL)
    {
        if ($this->_result == NULL) {
            $this->_result = $this->_statement->get_result();
        }
        if (empty($this->_result)) {
            throw new PDOException($this->_statement->error);
        }
        $_mode = $this->_mode;
        if (!empty($mode) and ($mode = $this->transformFetchMode($mode)) != false) {
            $_mode = $mode;
        }
        $result = $this->_result->fetch_all($_mode);
        return $result === NULL ? false : $result;
    }

    public function fetchObject()
    {
        throw new PDOException('Not supported yet');
    }

    private function transformFetchMode($mode)
    {
        switch ($mode) {
            case PDO::FETCH_ASSOC :
                return MYSQLI_ASSOC;
            case PDO::FETCH_BOTH  :
                return MYSQLI_BOTH;
            case PDO::FETCH_NUM   :
                return MYSQLI_NUM;
            default :
                return false;
        }
    }

    private function refValues($arr)
    {
        $refs = array();
        foreach ($arr as $key => $value) {
            if ($key != 0) {
                $refs[$key] = &$arr[$key];
            } else {
                $refs[$key] = $value;
            }
        }
        return $refs;
    }

    public function __destruct()
    {
        if (!empty($this->_result)) {
            $this->_result->free();
        }
        if (!empty($this->_statement)) {
            $this->_statement->close();
        }
    }
}
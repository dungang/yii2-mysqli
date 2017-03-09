<?php
/**
 * Created by PhpStorm.
 * User: dungang
 * Date: 2017/3/1
 * Time: 9:11
 */

namespace dungang\mysqli;

use PDO;
use PDOException;
use mysqli;

class PDO_Mysql
{
    const MYSQL_ATTR_USE_BUFFERED_QUERY = 1000;

    const MYSQL_ATTR_LOCAL_INFILE = 1001;

    const MYSQL_ATTR_INIT_COMMAND = 1002;

    const MYSQL_ATTR_READ_DEFAULT_FILE = 1003;

    const MYSQL_ATTR_READ_DEFAULT_GROUP = 1004;

    const MYSQL_ATTR_MAX_BUFFER_SIZE = 1005;

    const MYSQL_ATTR_DIRECT_QUERY = 1006;

    private $handle = NULL;

    private $tmpParams = array();

    public function __construct($connectionString, $username, $password, $options = array())
    {
        //简单解析
        if (preg_match('/mysql:(.*)/i', $connectionString, $matches)) {
            $query = str_replace(';','&',$matches[1]);
            $map['port']=3306;
            parse_str($query,$map);
            if (isset($map['host']) && isset($map['dbname'])) {
                $this->handle = new mysqli($map['host'], $username, $password, $map['dbname'],$map['port']);
                if ($this->handle->connect_errno) {
                    throw new PDOException($this->handle->connect_error,$this->handle->connect_errno);
                }
                return;
            }
        }
        throw new PDOException('connectionString is invalid');
    }

    public function beginTransaction()
    {
        return $this->handle->autocommit(FALSE);
    }

    public function commit()
    {
        return $this->handle->commit();
    }

    public function rollBack()
    {
        return $this->handle->rollback();
    }

    public function errorCode()
    {
        return $this->handle->errno;
    }

    public function errorInfo()
    {
        return array_values($this->handle->error_list);
    }

    public function setAttribute($attribute, $value, &$source = null)
    {
        switch ($attribute) {
            case PDO::ATTR_AUTOCOMMIT:
                $value = $value ? 1 : 0;
                if (!$this->handle->autocommit($value)) {
                    throw  new PDOException('set autocommit faild');
                }

                return true;
            case PDO::ATTR_TIMEOUT:
                $value = intval($value);
                if ($value > 1 && $this->handle->options(MYSQLI_OPT_CONNECT_TIMEOUT, $value)) {
                    $source[PDO::ATTR_TIMEOUT] = $value;
                    return true;
                }
                break;

            case self::MYSQL_ATTR_LOCAL_INFILE:
                $value = $value ? true : false;
                if ($this->handle->options(MYSQLI_OPT_LOCAL_INFILE, $value)) {
                    $source[self::MYSQL_ATTR_LOCAL_INFILE] = $value;
                    return true;
                }
                break;

            case self::MYSQL_ATTR_INIT_COMMAND:
                if ($value && $this->handle->options(MYSQLI_INIT_COMMAND, $value)) {
                    $source[self::MYSQL_ATTR_INIT_COMMAND] = $value;
                    return true;
                }
                break;

            case self::MYSQL_ATTR_READ_DEFAULT_FILE:
                $value = $value ? true : false;
                if ($this->handle->options(MYSQLI_READ_DEFAULT_FILE, $value)) {
                    $source[self::MYSQL_ATTR_READ_DEFAULT_FILE] = $value;
                    return true;
                }
                break;

            case self::MYSQL_ATTR_READ_DEFAULT_GROUP:
                $value = $value ? true : false;
                if ($this->handle->options(MYSQLI_READ_DEFAULT_GROUP, $value)) {
                    $source[self::MYSQL_ATTR_READ_DEFAULT_GROUP] = $value;
                    return true;
                }
                break;
        }

        return false;
    }

    /**
     * @param $attribute
     * @return mixed
     */
    public function getAttribute($attribute)
    {
        if (PDO::ATTR_DRIVER_NAME == $attribute) {
            return 'mysql';
        }
        return '';
    }

    public function exec($statement)
    {
        if ($result = $this->handle->query($statement)){
            if (is_object($result)) {
                mysqli_free_result($result);
                return 0;
            }
            return $this->handle->affected_rows;
        }
        throw new PDOException($this->handle->error,$this->handle->errno);
    }


    public static function getAvailableDrivers()
    {
        return array('mysql');
    }

    /**
     * @param $statement string
     * @return PDO_Mysql_Statement
     */
    public function prepare($statement)
    {
        $this->tmpParams = array();
        $newStatement = preg_replace_callback('/(:\w+)/i', function ($matches) {
            $this->tmpParams[] = $matches[1];
            return '?';
        }, $statement);
        $s = $this->handle->prepare($newStatement);
        if ($s == false) {
            throw new PDOException($this->handle->error);
        }
        $oStatement = new PDO_Mysql_Statement($s, $this);
        $oStatement->setPrepareParams($this->tmpParams);
        $oStatement->setStateSql($statement);
        return $oStatement;
    }

    public function lastInsertId()
    {
        return $this->handle->insert_id;
    }

    public function quote($param, $parameter_type = -1)
    {
        switch ($parameter_type) {
            case PDO::PARAM_BOOL:
                return $param ? 1 : 0;
            case PDO::PARAM_NULL:
                return 'NULL';
            case PDO::PARAM_INT:
                return is_null($param) ? 'NULL' : (is_int($param) ? $param : (float)$param);
            default:
                return '\'' . $this->handle->real_escape_string($param) . '\'';
        }
    }

    public function close()
    {
        $this->handle->close();
    }

    public function disconnect()
    {
        $this->close();
    }

    public function __destruct()
    {
        $this->close();
    }
}

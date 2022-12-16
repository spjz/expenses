<?php
/**
* @category   D3R
* @package    D3R_Db
* @copyright  Copyright (c) 2006 D3R Ltd (http://d3r.com)
* @license    http://d3r.com/license.txt
*/

namespace D3R;

class Db
{

    protected static $_instance = null;

    public static function get()
    {
        if (false === (static::$_instance instanceof static)) {
            if (!defined('DB_USERNAME') || !defined('DB_PASSWORD') || !defined('DB_HOST') || !defined('DB_NAME')) {
                throw new Exception('Database configuration not set');
            }
            static::$_instance = new static(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
        }

        return static::$_instance;
    }

    protected $_host        = false;
    protected $_username    = false;
    protected $_password    = false;
    protected $_name        = false;
    protected $_mysqli      = false;
    protected $_query       = null;
    protected $_numRows     = false;
    protected $_numThisPage = false;
    protected $_insertId    = false;
    protected $_affectRows  = false;
    protected $_error       = 0;

    protected function __construct($host, $username, $password, $name)
    {
        $this->_host     = $host;
        $this->_username = $username;
        $this->_password = $password;
        $this->_name     = $name;
    }

    protected function __clone()
    {

    }

    /**
    * Open the database connection using parameters from the constructor
    *
    * Returns false if mysql returns an error
    *
    * @access       protected
    * @return       bool
    *
    */
    protected function connect()
    {
        $this->init();

        if (1049 == $this->_mysqli->errno) {
            // initialise the mysqli object
            $this->_mysqli = mysqli_init();

            // open connection
            $this->_mysqli->real_connect(
                $this->_host,
                $this->_username,
                $this->_password
            );

            $this->_mysqli->query('CREATE DATABASE IF NOT EXISTS ' . $this->_name);
            $this->_mysqli->close();
            $this->init();
        }

        if ($this->_mysqli->errno > 0) {
            throw new Exception('MySQL Connect Failed (' . $this->_mysqli->errno . '): ' . $this->_mysqli->error, $this->_mysqli->errno);
        }

        $this->_mysqli->set_charset('utf8');

    }

    public function disconnect()
    {
        if (!$this->isConnected()) {
            return true;
        }

        $result = $this->_mysqli->close();
        $this->_mysqli = false;
    }

    /**
    * tries to establish the db connection
    *
    * @access       protected
    *
    */
    protected function init()
    {
        // initialise the mysqli object
        $this->_mysqli = mysqli_init();

        // open connection
        $this->_mysqli->real_connect(
            $this->_host,
            $this->_username,
            $this->_password,
            $this->_name
        );

        // check for major error
        if (false === is_object($this->_mysqli)) {
            throw new Exception('MySQL Connect Failed');
        }
    }

    /**
    * takes a string and mysql escapes it
    *
    * @access       public
    * @param       string       $value
    * @return       string
    *
    */
    public function escape($value)
    {
        if (false == $this->isConnected()) {
            $this->connect();
        }

        return $this->_mysqli->real_escape_string($value);
    }

    /**
    * takes a string and mysql escapes it and adds quotes unless it looks like an sql function or os numeric
    *
    * @access       public
    * @param       string       $value
    * @return       string
    *
    */
    public function escapeValue($value)
    {

        if (is_numeric($value)) {
            // Added quotes around number to stop numbers in
            // text fields being inserted as INT
            $value = '\'' . $this->escape($value) . '\'';
        } elseif (is_bool($value)) {
            $value = 0;
            if ($value) {
                $value = 1;
            }
        } elseif (is_null($value)) {
            $value = 'NULL';
        } elseif (is_string($value)) {
            // @todo add code to avoid sql functions such as NOW()
            $value = "'" . $this->escape($value) . "'";
        } elseif (is_array($value)) {
            $newvalue   = array();
            foreach ($value as $subvalue) {
                $newvalue[]   = $this->escapeValue($subvalue);
            }
            $value  = implode(',', $newvalue);
        } else {
            $value = "''";
        }

        return $value;

    }

    /**
    * takes a string and escapes variables into it from an indexed array
    *
    * @access       public
    * @param       string       $sql
    * @param       array       $params
    * @return       string
    *
    */
    public function escapeInto($sql, $params)
    {
        if (!is_array($params)) {
            return $sql;
        }

        $keys = array();
        $values = array();

        foreach ($params as $key => $value) {
            $keys[] = ':' . $key;
            $values[] = $this->escapeValue($value);
        }

        return str_replace($keys, $values, $sql);
    }

    /**
    * Check the database connection was made succesfully
    *
    * @access       public
    * @return       bool
    *
    */
    public function isConnected()
    {
        if (is_object($this->_mysqli)) {
            return $this->_mysqli->ping();
        } else {
            return false;
        }
    }

    /**
    * Run a select query on the database
    * Returns false if mysql returns an error
    *
    * @access       public
    * @param        string      $query
    * @return       array|boolean
    *
    */
    public function select($sql, $params = false)
    {
        $output = false;

        $this->_queryType = 'select';

        $this->_query = $this->escapeInto($sql, $params);

        $this->startQuery();

        $this->_mysqli->real_query($this->_query);
        $result = $this->_mysqli->use_result();

        if ($result) {
            $rows = array();
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $result->free();

            $output = $rows;
            $this->_numRows = count($output);
        }

        $this->endQuery();

        return $output;
    }

    /**
    * Run a select query on the database
    * Returns false if mysql returns an error
    *
    * @access       public
    * @param        string      $query
    * @return       mixed
    *
    */
    public function selectPage($page, $sql, $params = false, $numPerPage = 20)
    {

        $output = false;

        if (!is_numeric($page) || $page < 1) {
            $page = 1;
        }

        if (false === stripos($sql, 'SQL_CALC_FOUND_ROWS')) {
            //check for select
            $selectPosition = stripos($sql, 'SELECT');

            // check if we have select and remove
            if (false !== $selectPosition) {
                $sql = substr($sql, $selectPosition + 6);
            }

            // add the select to the front of the query
            $sql = 'SELECT SQL_CALC_FOUND_ROWS' . $sql;
        }

        // add the limit to the end of the query
        $sql .= ' LIMIT ' . (($page - 1) * $numPerPage) . ', ' . $numPerPage;

        $this->_queryType = 'select';

        $this->_query = $this->escapeInto($sql, $params);

        $this->startQuery();

        $this->_mysqli->real_query($this->_query);
        $result = $this->_mysqli->use_result();

        if ($result) {
            $rows = array();
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $result->free();

            // get the total number o rows
            $this->_mysqli->real_query('SELECT FOUND_ROWS() AS total');
            $rowResult = $this->_mysqli->use_result();
            $row = $rowResult->fetch_assoc();
            $this->_numRows = $row['total'];
            $rowResult->free();

            $output = $rows;
            $this->_numThisPage = count($output);
        }

        $this->endQuery();

        return $output;

    }

    /**
    * Run a select query on the database and return first result as and associative array
    *
    * @access       public
    * @param        string      $query
    * @return       mixed
    *
    */
    public function selectFirst($sql, $params = false)
    {
        $output = false;

        $this->_queryType = 'select';

        $this->_query = $this->escapeInto($sql, $params);

        $this->startQuery();

        $this->_mysqli->real_query($this->_query);
        $result = $this->_mysqli->use_result();

        if ($result) {
            $output = $result->fetch_assoc();
            $result->free();
            if ($output) {
                $this->_numRows = 1;
            }
        }

        $this->endQuery();

        return $output;
    }

    /**
    * takes a table name and an associative array and inserts an array of key value pairs
    * from the record array into the table of the given name in the database.
    * The ignore parameter uses insert ignore rather than straight insert.
    *
    * @access       public
    * @param        string      $table
    * @param       array       $records
    * @param       bool        $ignore
    * @return       mixed
    *
    */
    public function insert($table, $records, $ignore = false)
    {
        return $this->_insertOrReplace($table, $records, $ignore);
    }

    public function replace($table, $records, $ignore = false)
    {
        return $this->_insertOrReplace($table, $records, $ignore, true);
    }

    /**
    * takes a table name and an associative array and inserts an array of key value pairs
    * from the record array into the table of the given name in the database.
    * The ignore parameter uses insert ignore rather than straight insert.
    *
    * @access       protected
    * @param        string      $table
    * @param        array       $records
    * @param        bool        $ignore
    * @param        bool        $replace    Execute an INSERT or a REPLACE statement
    * @return       mixed
    *
    */
    protected function _insertOrReplace($table, $records, $ignore = false, $replace = false)
    {

        $this->_queryType = 'insert_multiple';
        if ($replace) {
            $this->_queryType = 'replace_multiple';
        }

        // handle ignore sql
        $ignoreSql = '';
        if ($ignore && !$replace) {
            $ignoreSql = 'IGNORE';
        }

        // escape table name
        $table = $this->escape($table);

        // check for single record and correct array
        if (!isset($records[0])) {
            $record = $records;
            $records = array();
            $records[0] = $record;
            $this->_queryType = ($replace) ? 'replace' : 'insert';
        }

        //get field names
        $fields = array_keys($records[0]);

        // get field sql
        $first = true;
        $fieldSql = '(';

        foreach ($fields as $field) {
            if (!$first) {
                $fieldSql .= ', ';
            }

            $fieldSql .= '`' . $this->escape($field) . '`';

            $first = false;
        }

        $fieldSql .= ')';

        // get value sql

        $valueSql = '';
        $firstRow = true;

        foreach ($records as $record) {
            if (!$firstRow) {
                $valueSql .= ",\n";
            }

            $valueSql .= '(';
            $first = true;

            foreach ($fields as $field) {
                if (!$first) {
                    $valueSql .= ', ';
                }

                $valueSql .= $this->escapeValue($record[$field]);

                $first = false;
            }

            $valueSql .= ')';

            $firstRow = false;
        }

        // col names come from the array keys
        $cols = array_keys($record);

        // build the statement
        $verb           = ($replace) ? 'REPLACE' : 'INSERT' ;
        $this->_query   = "$verb $ignoreSql INTO `$table` $fieldSql \nVALUES $valueSql";
        $this->startQuery();
        $result = $this->_mysqli->real_query($this->_query);
        $this->_insertId = $this->_mysqli->insert_id;
        $this->endQuery();

        if ($this->_insertId > 0) {
            return $this->_insertId;
        } else {
            if ($ignore) {
                return $result;
            } else {
                return false;
            }
        }
    }

    /**
    * returns last insert id
    *
    * @access       public
    * @return       mixed
    *
    */
    public function getInsertId()
    {

        if (is_numeric($this->_insertId) && $this->_insertId > 0) {
            return $this->_insertId;
        } else {
            return false;
        }
    }

    /**
    * Get the current schema name for this connection
    *
    * @access public
    * @return string
    */
    public function getSchema()
    {
        return $this->_name;
    }

    /**
    * update the records in the table to have the values from the associative
    * record array using the condition from the where parameter with substitution of the parameters from the params array
    *
    * @access       public
    * @param        string      $table
    * @param       array       $record
    * @param       string      $where
    * @param       array       $params
    * @return       mixed
    *
    */
    public function update($table, $record, $where, $params = array())
    {
        $this->_queryType = 'update';

        // escape table name
        $table = $this->escape($table);

        // build the statement
        $this->_query = "UPDATE `$table` SET  " . $this->recordToSql($record) . ' WHERE ' . $this->escapeInto($where, $params);

        $this->startQuery();
        $result = $this->_mysqli->real_query($this->_query);
        $this->endQuery();

        return $result;
    }

    /**
    * delete records mathing the where clause from the table
    *
    * @access       public
    * @param        string      $table
    * @param       string      $where
    * @param       array       $params
    * @return       mixed
    *
    */
    public function delete($table, $where, $params = array())
    {
        $this->_queryType = 'delete';

        // escape table name
        $table = $this->escape($table);

        // build the statement
        $this->_query = "DELETE FROM `$table` WHERE " . $this->escapeInto($where, $params);

        $this->startQuery();
        $result = $this->_mysqli->real_query($this->_query);
        $this->endQuery();

        return $result;
    }

    public function query($sql)
    {
        $this->startQuery();
        $this->_query = $sql;
        $result = $this->_mysqli->query($this->_query);
        $this->endQuery();

        return $result;
    }

    /**
    * takes a record as an associative array and returns a string for an insert or
    * update statement.
    *
    * @access       public
    * @param       array       $Record
    * @return       mixed
    *
    */
    public function recordToSql($record)
    {
        // get field sql
        $first = true;
        $sql = '';

        foreach ($record as $field => $value) {
            if (!$first) {
                $sql .= ', ';
            }

            $sql .= '`' . $this->escape($field) . '` = ' . $this->escapeValue($value);

            $first = false;
        }

        return $sql;
    }

    /**
    * takes a string and mysql escapes it
    *
    * @access       public
    * @return       int
    *
    */
    public function affectedRows()
    {
        return $this->_affectedRows;
    }

    public function numRows()
    {
        return $this->_numRows;
    }

    public function error()
    {
        return $this->_error;
    }

    /**
    * run everytime we start a new query, starts the timer and sets the query type
    *
    * @access          public
    * @param  string  $QueryType
    *
    */
    public function startQuery()
    {
        if (false == $this->isConnected()) {
            $this->connect();
        }

        // set the query running variable
        $this->_queryRunning = true;

        // set the query type
        if (false === $this->_queryType) {
            $this->_queryType = 'run';
        }

        // reset number of rows
        $this->_numRows = 0;

        // reset number of rows this page
        $this->_numThisPage = 0;

        // reset insert id
        $this->_insertId = 0;

        //reset affected rows
        $this->_affectedRows = 0;

        //reset error
        $this->_error = 0;
    }

    /**
    * run at the end of every query
    *
    * @access       public
    *
    */
    public function endQuery()
    {
        // set the query running variable
        $this->_queryRunning = false;

        // init the Error and debug vars
        $error = false;

        // Clear out last query
        $this->_query = null;

        // get affected rows
        $this->_affectedRows = $this->_mysqli->affected_rows;
    }
}

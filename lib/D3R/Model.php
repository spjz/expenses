<?php
/**
* D3R Ltd
*
* LICENSE
*
* This source file is subject to version 1.0 of the D3R Software
* license, that is bundled with this package in the file LICENSE, and
* is available through the world-wide-web at the following URL:
* http://d3r.com/license.txt. If you did not receive
* a copy of the D3R license and are unable to obtain it
* through the world-wide-web, please send an email to license@d3r.com
* so we can mail you a copy immediately.
*
* @package    D3R_Model
* @copyright  Copyright (c) 2006 D3R Ltd (http://d3r.com)
* @license    http://d3r.com/license.txt
*/

namespace D3R;

abstract class Model
{
    protected static $_tableName  = false;
    protected static $_itemName   = false;

    public static function tableName()
    {
        return static::$_tableName;
    }

    public static function itemName()
    {
        return static::$_itemName;
    }

    /**
     * Transform a property name into a valid database column name for this model
     *
     * Column names are simply itemname_name, eg: person_name, file_created, etc
     *
     * @param string $fieldName
     */
    public static function fullFieldName($fieldName)
    {
        if (false !== stripos($fieldName, static::itemName() . '_')) {
            return $fieldName;
        }

        return static::itemName() . '_' . $fieldName;
    }

    public static function idColName()
    {
        return static::fullFieldName('id');
    }

    public static function find($idOrWhere = false, $params = array(), $limit = false)
    {
        $sql    = "SELECT * FROM " . static::tableName();

        if (is_numeric($idOrWhere)) {
            $sql 	.= " WHERE " . static::idColName() . " = :id";
            $params = array('id' => $idOrWhere);
        } elseif (!empty($idOrWhere)) {
            $sql .= " WHERE {$idOrWhere}";
        }

        if (false !== $limit) {
            $sql .= " LIMIT {$limit}";
        }

        if (is_numeric($idOrWhere)) {
            return static::findFirstBySql($sql, $params);
        } else {
            return static::findBySql($sql, $params);
        }

        return false;
    }

    public static function findPage($page, $numPerPage = 20, $where = 1, $params = array())
    {
        $sql    = "SELECT * FROM " . static::tableName();
        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }

        if (false === ($result = Db::get()->selectPage($page, $sql, $params, $numPerPage))) {
            return false;
        }

        $models = array();
        if (0 < count($result)) {
            foreach ($result as $row) {
                $models[] = static::createFromArray($row);
            }
        }

        return $models;
    }

    public static function findBySql($sql, $params)
    {
        if (false == ($result = Db::get()->select($sql, $params))) {
            return false;
        }

        $models = array();
        if (0 < count($result)) {
            foreach ($result as $row) {
                $models[] = static::createFromArray($row);
            }
        }

        return $models;
    }

    public static function findFirstBySql($sql, $params)
    {
        if (false == ($result = Db::get()->selectFirst($sql, $params))) {
            return false;
        }

        return static::createFromArray($result);
    }

    public static function createFromArray(array $array)
    {
        $model = new static();

        $idColName = static::idColName();
        if (!isset($array[$idColName])) {
            throw new Exception('Unable to create model from array with missing id in D3R_Model::CreateFromArray()');
        }

        $id = $array[$idColName];
        unset($array[$idColName]);

        $model->setId($id);
        $model->setFromArray($array);

        return $model;
    }

    protected $_id   = false;
    protected $_data = array();

    public function __get($name)
    {
        return $this->field($name);
    }

    public function __set($name, $value)
    {
        $this->setField($name, $value);
    }

    public function setId($id)
    {
        if (!is_numeric($id)) {
            throw new Exception('ID value is not numeric in D3R_Model::setId()');
        }

        $this->_id = $id;
    }

    public function setFromArray(array $array)
    {
        $idColName = static::IdColName();
        if (isset($array[$idColName])) {
            unset($array[$idColName]);
        }

        if (empty($array)) {
            throw new Exception('Illegal empty array in D3R_Model::setFromArray()');
        }
        $this->_data = $array;
    }

    public function field($name)
    {
        $fullFieldName = static::fullFieldName($name);
        if ($fullFieldName == static::idColName()) {
            return $this->_id;
        }

        return $this->_data[$fullFieldName];
    }

    public function setField($name, $value)
    {
        $fullFieldName = static::fullFieldName($name);
        $this->_data[$fullFieldName] = $value;

        return $this;
    }

    public function getDataArray()
    {
        return $this->_data;
    }

    public function save()
    {
        $record = $this->getDataArray();

        if (false == $this->_id) {
            // Creating a new model
            if (false === ($result = Db::get()->insert(static::tableName(), $record))) {
                throw new Exception('Unable to save model (insert)');
            }

            $this->_id = $result;

            return true;
        } else {
            // Updating an existing one
            $where  = static::IdColName() . ' = :id';
            $params = array('id' => $this->_id);

            if (false === ($result = Db::get()->update(static::tableName(), $record, $where, $params))) {
                throw new Exception('Unable to save model (update)');
            }

            return true;
        }
    }
}

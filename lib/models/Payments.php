<?php

namespace models;

use D3R\Db;
use D3R\Model;

class Payments extends Model
{
    protected static $_tableName = 'payments';

    protected static $_itemName = 'payment';

    public static function count($where = false, $params = array())
    {
        $sql    = "SELECT COUNT(*) FROM " . static::tableName();

        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }

        if (false == ($result = Db::get()->selectFirst($sql, $params))) {
            return false;
        }

        return $result;
    }
}
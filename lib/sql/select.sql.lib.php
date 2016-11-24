<?php
/**
 * Created by PhpStorm.
 * User: seven
 * Date: 14-5-28
 * Time: 上午7:35
 */
class DWLibSqlSelect extends DWLibSqlAbstract
{
    protected $selectField = NULL;
    protected $from = NULL;
    protected $joinFields = [];
    protected $whereField = NULL;
    protected $orderByField = NULL;
    protected $order = 'ASC';
    protected $limitField = NULL;

    public function __construct($table, $columns = '*')
    {
        //table
        $this->from = $this->parseTableAs($table);

        //columns
        $this->selectField = $this->parseColumns($columns);
    }

    public function leftJoin($table, $on)
    {
        $joinField = ' LEFT JOIN ';
        $joinField .= $this->parseTableAs($table);
        $joinField .= ' ON ' . $this->parseConditionField($on);
        $this->joinFields[] = $joinField;

        return $this;
    }

    public function where($condition)
    {
        $this->whereField = $this->parseConditionField($condition);

        return $this;
    }

    public function orderBy($by, $order = 'ASC')
    {
        if (is_array($by))
        {
            $this->orderByField = $this->parseColumns($by);
        }
        else
        {
            $this->orderByField = $by;
        }
        $this->order = $order;

        return $this;
    }

    public function limit($count, $offset = 0)
    {
        $this->limitField = "$offset, $count";

        return $this;
    }

    public function getSQL()
    {
        $SQL = 'SELECT ' . $this->selectField . ' FROM ' . $this->from;
        if ($this->joinFields)
        {
            $SQL .= ' ' . implode(' ', $this->joinFields);
        }
        if ($this->whereField)
        {
            $SQL .= ' WHERE ' . $this->whereField;
        }
        if ($this->orderByField)
        {
            $SQL .= ' ORDER BY ' . $this->orderByField . ' ' . $this->order;
        }
        if ($this->limitField)
        {
            $SQL .= ' LIMIT ' . $this->limitField;
        }
        return $SQL;
    }
}

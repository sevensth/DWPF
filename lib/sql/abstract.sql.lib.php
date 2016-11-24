<?php
/**
 * Created by PhpStorm.
 * User: seven
 * Date: 14-5-28
 * Time: 上午7:35
 */
abstract class DWLibSqlAbstract
{
    public static function select($table, $columns = '*')
    {
        return new DWLibSqlSelect($table, $columns);
    }

    protected function parseColPair(array $pair)
    {
        $preprocessedArgs = [];
        foreach ($pair as $col)
        {
            if (is_array($col))
            {
                $preprocessedArgs[] = "$col[0].`$col[1]`";
            }
            else
            {
                $preprocessedArgs[] = $col;
            }
        }
        return $preprocessedArgs;
    }

    public abstract  function getSQL();

    //= != <> < > <= >= IS LIKE
    //BETWEEN
    //IN COALESCE GREATEST INTERVAL LEAST STRCMP
    //[ '>' => [col1, col2]]
    //[ '=' => [[table1, col1], [table2, col2]]
    private static $binaryConditionOps = ['=', '!=', '<>', '<', '>', '<=', '>=', 'IS', 'LIKE', 'IN'];
    private static $ternaryConditionOps = ['BETWEEN AND'];
    private static $binaryRightFunctionConditionOps = ['IN'];
    private static $functionConditionOps = ['COALESCE', 'GREATEST', 'INTERVAL', 'LEAST', 'STRCMP'];
    private static function allConditionOps()
    {
        static $allOps = NULL;
        if (!$allOps)
        {
            $allOps = array_merge(self::$binaryConditionOps, self::$ternaryConditionOps, self::$binaryRightFunctionConditionOps, self::$functionConditionOps);
        }
        return $allOps;
    }

    protected function parseConditionFieldConditionOp($op, array $arg)
    {
        $result = NULL;

        if (in_array($arg[0], self::allConditionOps()))
        {
            $op = $arg[0];
            array_shift($arg);
        }
        $op = strtoupper($op);

        $arg = $this->parseColPair($arg);

        if (in_array($op, self::$binaryConditionOps))
        {
            $leftArg = $arg[0];
            $rightArg = $arg[1];
            if (count($arg) > 2)
            {
                $rightArg = '(' . implode(', ', array_slice($arg, 1)) . ')';
            }
            $result = "($leftArg $op $rightArg)";
        }
        if (in_array($op, self::$ternaryConditionOps))
        {
            $ops = explode(' ', $op);
            $result = "($arg[0] $ops[0] $arg[1] $ops[1] $arg[2])";
        }
        if (in_array($op, self::$binaryRightFunctionConditionOps))
        {
            $leftArg = $arg[0];
            $rightArg = '(' . implode(', ', array_slice($arg, 1)) . ')';
            $result = "($leftArg $op $rightArg)";
        }
        else if (in_array($op, self::$functionConditionOps))
        {
            $argImplode = implode(', ', $arg);
            $result = "$op($argImplode)";
        }
        return $result;
    }


    private static $unitaryLogicOps = ['NOT', '!'];
    private static $binaryLogicOps = ['AND', '&&', 'OR', '||', 'XOR'];
    private static function allLogicOps()
    {
        static $allOps = NULL;
        if (!$allOps)
        {
            $allOps = array_merge(self::$unitaryLogicOps, self::$binaryLogicOps);
        }
        return $allOps;
    }

    protected function parseConditionFieldLogicOp($op, array $arg)
    {
        $result = NULL;

        $preprocessedArgs = $this->dispatchConditionFieldOps($arg);
        $op = strtoupper($op);
        if (in_array($op, self::$unitaryLogicOps))
        {
            $result = "($op($preprocessedArgs[0]))";
        }
        else if (in_array($op, self::$binaryLogicOps))
        {
            $result = implode(" $op ", $preprocessedArgs);
            $result = "($result)";
        }

        return $result;
    }

    protected function dispatchConditionFieldOps(array $arg)
    {
        $preprocessedArgs = [];
        foreach ($arg as $key => $val)
        {
            $key = strtoupper($key);
            $valFirstElement = is_array($val) ? $val[0] : NULL;
            if (in_array($key, self::allConditionOps()) || in_array($valFirstElement, self::allConditionOps()))
            {
                $preprocessedArgs[] = $this->parseConditionFieldConditionOp($key, $val);
            }
            else if (in_array($key, self::allLogicOps()))
            {
                $preprocessedArgs[] = $this->parseConditionFieldLogicOp($key, $val);
            }
            else if (is_numeric($key))
            {
                $tmpResult = $this->dispatchConditionFieldOps($val);
                $preprocessedArgs[] = is_array($tmpResult) ? implode(' ', $tmpResult) : $tmpResult;
            }
            else
            {
                $preprocessedArgs[] = $val;
            }
        }
        return $preprocessedArgs;
    }

    protected function parseConditionField($arg)
    {
        if (is_array($arg))
        {
            $preprocessedArgs = $this->dispatchConditionFieldOps($arg);
            return implode(' ', $preprocessedArgs);
        }
        else
        {
            return $arg;
        }
    }

    //table
    //[table, as]
    //[[table, as], [table]]
    protected function parseTableAs($table)
    {
        if (is_array($table))
        {
            if (is_array($table[0]))
            {
                $results = [];
                foreach ($table as $val)
                {
                    $results[] = $this->parseTableAs($val);
                }
                return implode(', ', $results);
            }
            else
            {
                if (count($table) > 1)
                {
                    return "`$table[0]` AS $table[1]";
                }
                else
                {
                    return "`$table[0]`";
                }
            }
        }
        else
        {
            return "`$table`";
        }
    }

    protected function parseColumns($columns)
    {
        if (!is_array($columns))
        {
            return $columns;
        }

        $selectCols = [];
        foreach ($columns as $key => $value)
        {
            if (is_numeric($key))//[col1, col2, ...]
            {
                $selectCols[] = $value == '*' ? $value : '`'.$value.'`';
            }
            else
            {
                if (is_array($value))//[table1 => [col1, col2], table2 => [col1, col3], ...]
                {
                    foreach ($value as $c)
                    {
                        $rightPart = $c == '*' ? $c : '`'. $c .'`';
                        $selectCols[] = $key . '.' . $rightPart;
                    }
                }
                else//[table1 => col1, table2 => col1, ...]
                {
                    $rightPart = $value == '*' ? $value : '`'.$value.'`';
                    $selectCols[] = $key . '.' . $rightPart;
                }
            }
        }
        return implode(', ', $selectCols);
    }
}

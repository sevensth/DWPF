<?php
/**
 * @author seven
 * Initialization function are just for mysql, for sqlite supporting, we should add more functions.
 */
abstract class DWModelAbstract
{
	const DBInfoHost = 'host';
	const DBInfoPort = 'port';
	const DBInfoDBName = 'dbname';
	const DBInfoUser = 'user';
	const DBInfoPassword = 'pw';
	const DBInfoDBType = 'db';
	const DBInfoCharset = 'charset';
	const DBInfoPresistent = 'presistent';
	const DBInfoEmulatePrepare = 'emuPrepare';
	
	private static $dbInfo = array();
	private static $dbConnPool = array();
	private static $models = array();
	private static $tableNamePrefix = '';
	
	private static $dbSelectTimes = 0;
	private static $dbUpdateTimes = 0;
	private static $dbDeleteTimes = 0;
	private static $dbAddTimes = 0;
	protected static function increaseDBSelectTimes($times = 1)
	{
		self::$dbSelectTimes += $times;
	}

    public static function getDBSelectTimes()
    {
        return self::$dbSelectTimes;
    }
	
	protected static function normalizeDBInfo($dbInfo)
	{
		if (is_array($dbInfo))
		{
			if (!array_key_exists(self::DBInfoHost, $dbInfo))
			{
				$dbInfo[self::DBInfoHost] = 'localhost';
			}
			if (!array_key_exists(self::DBInfoPort, $dbInfo))
			{
				$dbInfo[self::DBInfoPort] = '3306';
			}
			if (!array_key_exists(self::DBInfoDBType, $dbInfo))
			{
				$dbInfo[self::DBInfoDBType] = 'mysql';
			}
			if (!array_key_exists(self::DBInfoCharset, $dbInfo))
			{
				$dbInfo[self::DBInfoCharset] = 'utf8';
			}
			if (!array_key_exists(self::DBInfoPresistent, $dbInfo))
			{
				$dbInfo[self::DBInfoPresistent] = false;
			}
			if (!array_key_exists(self::DBInfoEmulatePrepare, $dbInfo))
			{
				$dbInfo[self::DBInfoEmulatePrepare] = false;
			}
			return $dbInfo;
		}
		return NULL;
	}
	
	public static function setDataBaseInfo($dbInfo)
	{
		if (is_array($dbInfo))
		{
			self::$dbInfo = self::normalizeDBInfo($dbInfo);
		}
	}
	
	public static function setTableNamePrefix($prefix)
	{
		self::$tableNamePrefix = $prefix;
	}
	
	protected static function pdoConnection($dbInfo)
	{
		if (!is_array($dbInfo))
		{
			return NULL;
		}
		
		$dbInfo = self::normalizeDBInfo($dbInfo);
		$dbInfoString = serialize($dbInfo);
		if (array_key_exists($dbInfoString, self::$dbConnPool))
		{
			return self::$dbConnPool[$dbInfoString];
		}
		
		//new connection
		$host = $dbInfo[self::DBInfoHost];
		$port = $dbInfo[self::DBInfoPort];
		$db = $dbInfo[self::DBInfoDBName];
		$user = $dbInfo[self::DBInfoUser];
		$pw = $dbInfo[self::DBInfoPassword];
		$dbtype = $dbInfo[self::DBInfoDBType];
		$charset = $dbInfo[self::DBInfoCharset];
		$presistent = $dbInfo[self::DBInfoPresistent];
		$emuPrepare = $dbInfo[self::DBInfoEmulatePrepare];

        //prior to PHP 5.3.6, the charset option was ignored
		$pdoInitString = "$dbtype:host=$host;dbname=$db;port=$port;charset=$charset";
		$pdoAttr = array();
		$pdoAttr[PDO::ATTR_PERSISTENT] = $presistent;
		$pdoAttr[PDO::ATTR_EMULATE_PREPARES] = $emuPrepare;
        $pdoAttr[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES $charset";
// 		if (count($pdoAttr) == 0)
// 		{
// 			$pdoAttr = '';
// 		}
		
		$connect = new PDO($pdoInitString, $user, $pw, $pdoAttr);
		if ($connect)
		{
			self::$dbConnPool[$dbInfoString] = $connect;
		}
		
		return $connect;
	}
	
    public static function sharedModel()
    {
    	$className = get_called_class();
    	if (array_key_exists($className, self::$models))
    	{
    		return self::$models[$className];
    	}
    	
    	//create new model
    	$model = new $className(self::$dbInfo, self::$tableNamePrefix);
    	self::$models[$className] = $model;
    	
    	return $model;
    }
    
    
    //-------------------- instance functions ---------------------
    protected $dbConnection = NULL;
    protected $tablePrefix = '';
    public function __construct($dbInfo, $tablePrefix = '')
    {
    	$connect = self::pdoConnection($dbInfo);
    	if (!$connect)
    	{
    		throw new Exception("cannot get db connection");
    	}
    	
    	$this->dbConnection = $connect;
    	$this->tablePrefix = $tablePrefix;
    }
    
//     public function __set($name,$value){
//     	if(strtolower($name) === $this->pk) {
//     		$this->variables[$this->pk] = $value;
//     	}
//     	else {
//     		$this->variables[$name] = $value;
//     	}
//     }
    
//     public function __get($name)
//     {
//     	if(is_array($this->variables)) {
//     		if(array_key_exists($name,$this->variables)) {
//     			return $this->variables[$name];
//     		}
//     	}
    
//     	$trace = debug_backtrace();
//     	trigger_error(
//     	'Undefined property via __get(): ' . $name .
//     	' in ' . $trace[0]['file'] .
//     	' on line ' . $trace[0]['line'],
//     	E_USER_NOTICE);
//     	return null;
//     }



    protected function fetchAllAsAsscociationArray(PDOStatement $statement)
    {
    	if ($statement)
    	{
    		$resultArry = $statement->fetchAll(PDO::FETCH_ASSOC);
    		if (is_array($resultArry))
    		{
    			return $resultArry;
    		}
    	}
    	return NULL;
    }
    
    protected function fetchOneAsAsscociationArray(PDOStatement $statement, $restrict = false)
    {
    	if ($statement)
    	{
    		if ($restrict && $statement->rowCount() > 1)
    		{
				//on restrict, multiple result is treated as fail.
       		}
    		else
    		{
    			$resultArry = $statement->fetch(PDO::FETCH_ASSOC);
    			if (is_array($resultArry))
    			{
    				return $resultArry;
    			}
    		}
    	}
    	return NULL;
    }
}


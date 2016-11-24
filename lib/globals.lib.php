<?php
final class DWLibGlobals
{
	const GlobalKeyDefaultRouter = 'dftrtr';
    const GlobalKeyBeginTime = 'bgntm';//< Record the begin time of index.php
	
	private static $globalMap = array();
	
	public static function setGlobal($key, $value)
	{
		if ($key && $value)
		{
			self::$globalMap[$key] = $value;
			return true;
		}
		return false;
	}
	
	public static function getGlobal($key)
	{
		if (array_key_exists($key, self::$globalMap))
		{
			return self::$globalMap[$key];
		}
		return NULL;
	}
}
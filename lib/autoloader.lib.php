<?php
class DWLibAutoloader
{
	protected $classMapCacheFile = '';
	protected $classMapCache = array();
	protected $classPrefix = '';
	public function __construct($classPrefix)
	{
		$this->classPrefix = $classPrefix;
		$this->classMapCacheFile = CACHE_DIR . "/DWAutoloaderClassFileMap.cache";
		if (file_exists($this->classMapCacheFile))
		{
			$this->classMapCache = unserialize(file_get_contents($this->classMapCacheFile));
			if (!is_array($this->classMapCache))
			{
				$this->classFileMapCache = array();
			}
		}
	}
	
	public function autoload($className)
	{
		if (array_key_exists($className, $this->classMapCache))
		{
			/* Load class using path from cache file (if the file still exists) */
			if (file_exists($this->classMapCache[$className]))
			{
				require_once $this->classMapCache[$className];
				return;
			}
		}
	
		$fileFullPath = $this->filePathForClass($className, $this->classPrefix);
		if (file_exists($fileFullPath))
		{
			$this->classMapCache[$className] = $fileFullPath;
			file_put_contents($this->classMapCacheFile, serialize($this->classMapCache));
			require_once $fileFullPath;
			return;
		}
		throw new Exception("Cannot load class $className", -1);
	}
	
	protected function filePathForClass($className, $classPrefix)
	{
		//DWLibUtility->LibUtility
		$className = preg_replace("/^$classPrefix/", '', $className);// ltrim($className, $classPrefix);
		
		//LibUtility->array('Lib', 'Utility')
		$components = preg_split('/(?=[A-Z])/', $className, -1, PREG_SPLIT_NO_EMPTY);
		//array('Lib', 'Utility') -> array('Utility', 'Lib')
		$componentsReversed = array_reverse($components);
		//array('Utility', 'Lib') -> array('Utility', 'Lib', 'php')
		array_push($componentsReversed, SCRIPT_EXTENTION);
		//array('Utility', 'Lib', 'php') -> Utility.Lib.php
		$fileName = implode('.', $componentsReversed);
		//Utility.Lib.php -> utility.lib.php
		$fileName = strtolower($fileName);
		
		//array('Lib', 'Utility') -> array('Lib')
		array_pop($components);
		//array('Lib') -> Lib
		$fileDir = implode(DIRECTORY_SEPARATOR, $components);
		//Lib -> lib
		$fileDir = strtolower($fileDir);
		
		//full path
		$fileFullPath = ROOT_PATH . DIRECTORY_SEPARATOR . $fileDir . DIRECTORY_SEPARATOR . $fileName;
		return $fileFullPath;
	}
	
	public function register()
	{
		spl_autoload_register(array($this, 'autoload'));
	}
}
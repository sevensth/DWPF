<?php
define('ENABLE_CACHE', false);

abstract class DWLibCacheAbstract
{
    abstract public function setCache($key, $value, $expire);
    abstract public function getCache($key);
    abstract public function clearCache();

    //Config cache
    private $settings = array();

    //Predefined options
    protected static $cacheOptionBeginning = 0;
    protected static $cacheOptionStorageType = 1;
    protected static $cacheOptionTotalCount = 2;

    //Predefined options value
    protected static $cacheOptionValueStorageTypeFile = 0x1;
    protected static $cacheOptionValueStorageTypeMemory = 0x2;

    private function isOptionValid($option)
    {
        if (is_numeric($option))
        {
            if ($option > self::$cacheOptionBeginning && $option < self::$cacheOptionTotalCount)
            {
                return true;
            }
        }
        return false;
    }

    public function setCacheOption($option, $value)
    {
        if ($this->isOptionValid($option))
        {
            $this->settings[$option] = $value;
        }
    }

    public function getCacheOption($option)
    {
    	return $this->settings[$option];
    }

    private static $metaKeyExpire = 1;
    protected function makeMeta($key, $value, $expire)
    {
        $meta = array();
        if (is_numeric($expire) && $expire > 0)
        {
            $meta[self::$metaKeyExpire] = $expire + time();
        }
        return $meta;
    }

    //invalid metainfo is treated as cache be valid
    protected function checkCacheExpire($meta)
    {
    	if (!ENABLE_CACHE)
    	{
    		return false;
    	}
    	
    	if (is_array($meta) && array_key_exists(self::$metaKeyExpire, $meta))
    	{
    		$expire = $meta[self::$metaKeyExpire];
        	if (is_numeric($expire) && $expire > 0 && $expire < time())
        	{
				return false;
        	}
    	}
        return true;
    }
}
<?php
Class DWLibCacheModule extends DWLibCacheSubdirectory
{
	public static function sharedInstance()
	{
		static $instance = null;
		if ($instance === null)
		{
			$baseDir = CACHE_DIR . DIRECTORY_SEPARATOR . 'modulecaches';
			$instance = new self($baseDir);
		}
		return $instance;
	}

    public function setCache($moduleName, $condition, $data, $expire = 86400)
    {
        return parent::setCache(urldecode($moduleName), $condition, $data, $expire);
    }

    public function getCache($moduleName, $condition)
    {
        return parent::getCache(urldecode($moduleName), $condition);
    }
}
<?php
abstract Class DWLibCacheSubdirectory
{
    private $baseDir;
    private $subDirCacheList = array();

    protected function __construct($baseDir)
    {
        $this->baseDir = $baseDir;
    }

    protected function getFileCacheFromCacheList($subDirName)
    {
        $modelCache = '';
        if (array_key_exists($subDirName, $this->subDirCacheList))
        {
        	$modelCache = $this->subDirCacheList[$subDirName];
        }
        else
        {
            $modelCacheDir = DWLibUtility::appendPathComponent($this->baseDir, $subDirName);
            try
            {
                $modelCache = new DWLibCacheFile($modelCacheDir);
            } catch (Exception $e) {
                $modelCache = null;
            }
            if ($modelCache)
            {
                $this->subDirCacheList[$subDirName] = $modelCache;
            }
        }
        return $modelCache;
    }

    public function setCache($subDirName, $key, $data, $expire = 3600)
    {
        if (is_string($subDirName) && strlen($subDirName) > 0 &&
            is_string($key) && strlen($key) > 0
            )
        {
            $cache = $this->getFileCacheFromCacheList($subDirName);
            $cache->setCache($key, $data, $expire);
        }
    }

    public function getCache($subDirName, $key)
    {
        if (is_string($subDirName) && strlen($subDirName) > 0 &&
            is_string($key) && strlen($key) > 0
            )
        {
            $cache = $this->getFileCacheFromCacheList($subDirName);
            return $cache->getCache($key);
        }
    }
}
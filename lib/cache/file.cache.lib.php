<?php
class DWLibCacheFile extends DWLibCacheAbstract
{
    protected $cacheDir;
    public function __construct($cacheDir, $createDir = true)
    {
        if (!DWLibUtility::validateDirectory($cacheDir))
        {
            if ($createDir)
            {
                if (!DWLibUtility::createDirectory($cacheDir))
                {
                    throw new Exception('Cache directory `' . $cacheDir . '` can NOT be created.');
                }
            }
            else
            {
                throw new Exception('Cache directory `' . $cacheDir . '` is NOT valid.');
            }
        }

        $this->setCacheOption(self::$cacheOptionStorageType, self::$cacheOptionValueStorageTypeFile);
        $this->cacheDir = $cacheDir;
    }

    private function getCacheFilePath($key)
    {
        if (is_string($key) && strlen($key) > 0)
        {
            $filename = urlencode($key);
            return DWLibUtility::appendPathComponent($this->cacheDir, $key);
        }
    }

    private function getCacheFileMetaPath($filePath)
    {
        return $filePath . '.meta';
    }

    private function putMetaToFile($metaFile, $meta)
    {
        return file_put_contents($metaFile, $meta);
    }

    private function getMetaFromFile($metaFile)
    {
    	$meta = '';
    	if (file_exists($metaFile))
    	{
			$meta = file_get_contents($metaFile);
    	}
    	return $meta;
    }

    private function putCacheToFile($cacheFile, $value)
    {
        $data = serialize($value);
        return file_put_contents($cacheFile, $data);
    }

    private function getCacheFromFile($cacheFile)
    {
    	$value = '';
    	if (file_exists($cacheFile))
    	{
        	$fileContent = file_get_contents($cacheFile);
        	$value = unserialize($fileContent);
    	}
        if ($value === false)
        {
            return '';
        }
        else
        {
            return $value;
        }
    }

    public function setCache($key, $value, $expire = 3600)
    {
        if (is_string($key) && strlen($key) > 0)
        {
            $cacheFile = $this->getCacheFilePath($key);
            $cacheMetaFile = $this->getCacheFileMetaPath($cacheFile);

            if ($this->putCacheToFile($cacheFile, $value) !== false)
            {
                $meta = $this->makeMeta($key, $value, $expire);
                $this->putMetaToFile($cacheMetaFile, $meta);
            }
        }
    }

    public function getCache($key)
    {
        if (is_string($key) && strlen($key) > 0)
        {
            $cacheFile = $this->getCacheFilePath($key);
            $cacheMetaFile = $this->getCacheFileMetaPath($cacheFile);

            $meta = $this->getMetaFromFile($cacheMetaFile);
            if ($this->checkCacheExpire($meta))
            {
                $value = $this->getCacheFromFile($cacheFile);
                return $value;
            }
        }
    }

    public function clearCache()
    {
        DWLibUtility::removeItem($this->cacheDir);
        DWLibUtility::createDirectory($this->cacheDir, true);
    }
}
<?php
class DWLibCacheMem extends DWLibCacheAbstract
{
    protected $maxMemUsage;

    protected $cache;

    public function __construct($maxMemUsage = 1024000)
    {
        parent::__construct();
        $this->$maxMemUsage = $maxMemUsage;
        $this->setCacheOption(self::CacheOptionStorageType, self::CacheOptionValueStorageTypeMemory);
        $this->clearCache();
    }

    const CacheWrapKeyMeta = 'm';
    const CacheWrapKeyCache = 'c';

    public function setCache($key, $value, $expire = 3600)
    {
        if (is_string($key) && strlen($key) > 0)
        {
            $meta = $this->makeCacheMate($key, $value, $expire);
            $this->$cache[$key] = array(self::CacheWrapKeyMeta => $meta, self::CacheWrapKeyCache => $value);
        }
    }

    public function getCache($key)
    {
        if (is_string($key) && strlen($key) > 0)
        {
            $cacheWrap = $this->cache[$key];
            if ($cacheWrap)
            {
                $meta = $cacheWrap[self::CacheWrapKeyMeta];
                if ($this->cacheCacheExpire($meta))
                {
                    return $cacheWrap[self::CacheWrapKeyCache];
                }
            }
        }
    }

    public function clearCache()
    {
        $cache = array();
    }
}
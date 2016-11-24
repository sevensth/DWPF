<?php
Class DWLibCacheModel extends DWLibCacheSubdirectory
{
    protected function baseDirectory()
    {
        return ROOT_PATH . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'model';
    }

    public function setCache($modelName, $condition, $data, $expire = 3600)
    {
        return parent::setCache(urldecode($modelName), $condition, $data, $expire);
    }

    public function getCache($modelName, $condition)
    {
        return parent::getCache(urldecode($modelName), $condition);
    }
}
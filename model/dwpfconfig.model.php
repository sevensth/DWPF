<?php
/**
 * Created by PhpStorm.
 * User: seven
 * Date: 15-5-22
 * Time: 下午3:31
 */
class DWModelDwpfconfig extends DWModelAbstract
{
    const ConfigNameNoADCate = 'DWPF_GCOF_NOAD_CATE';
    const ConfigNameNoADTag = 'DWPF_GCOF_NOAD_TAG';

    public function __construct($dbInfo, $tablePrefix = '')
    {
    }

    protected static $GlobalConfigPostTitle = 'DWPF:GLOBAL_CONFIG';
    public function getAllConfigs()
    {
        static $configs = NULL;
        if ($configs) return $configs;

        //load
        $articleModel = DWModelArticle::sharedModel();
        $article = $articleModel->getFullInfoByTitle(self::$GlobalConfigPostTitle);
        $metas = $articleModel->getMetaByPostId($article[DWModelArticle::TablePostColumnId]);

        $configs = [];
        if ($metas)
        {
            $configs = $metas;
        }
        return $configs;
    }

    public function getConfig($name, $explodeString = true)
    {
        $configs = $this->getAllConfigs();
        $config = $configs[$name];
        if ($config && is_string($config) && $explodeString)
        {
            return explode(',', $config);
        }
        else
        {
            return $config;
        }
    }
}

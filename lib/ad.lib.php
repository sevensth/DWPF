<?php
class DWLibAd
{
    const BaiduUnionAdHomepage = 'homepage';
    const BaiduUnionAdArticle = 'article';
    public static function baiduUnionAd($name)
    {
        static $ad = [
            'homepage' => '<script type="text/javascript">var cpro_id = "u1591559";</script><script src="http://cpro.baidustatic.com/cpro/ui/c.js" type="text/javascript"></script>',
            'article' => '<script type="text/javascript">var cpro_id = "u1592415";</script><script src="http://cpro.baidustatic.com/cpro/ui/c.js" type="text/javascript"></script>',
        ];
        static $adFake = [
            'homepage' => '<div style="width:250px; height:250px;"></div>',
            'article' => '<div style="width:300px; height:250px;"></div>',
        ];

        $disableAd = DEBUG || DWLibUtility::isHTTPS();
        if ($disableAd)
        {
            return $adFake[$name];
        }
        else
        {
            return $ad[$name];
        }
    }
}

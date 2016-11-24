<?php
class DWLibI18n
{
    private static $i18nFileExt = 'i18n.json';
    private static $defaultI18nModuleName = 'i18ncommon';
    const DefaultLocalization = 'en_US';
    private static $currentLocalization = self::DefaultLocalization;
    public static function setLocalization($local)
    {
        self::$currentLocalization = $local;
    }

    public static function sharedCommonI18n()
    {
        static $sharedCommonI18n = NULL;
        if (!$sharedCommonI18n)
        {
            $sharedCommonI18n = new self(self::$defaultI18nModuleName);
        }
        return $sharedCommonI18n;
    }

    private $moduleName;
    private $i18nFile;
    private $stringMap = array();
    public function __construct($moduleName)
    {
        $this->moduleName = $moduleName;
        $i18nFileName = $moduleName . '.' . self::$currentLocalization . '.' . self::$i18nFileExt;
        $this->i18nFile = I18N_DIR . DIRECTORY_SEPARATOR . self::$currentLocalization . DIRECTORY_SEPARATOR . $i18nFileName;
        if (file_exists($this->i18nFile))
        {
            $fileContent = file_get_contents($this->i18nFile);
            $stringMap = json_decode($fileContent, true);
            if (is_array($stringMap))
            {
                $this->stringMap = $stringMap;
            }
        }
    }

    private function getI18nStringFromMap($string, $map)
    {
        if (is_array($map) && array_key_exists($string, $map))
        {
            return $map[$string];
        }
        return NULL;
    }

    public function i18n($string)
    {
    	if (self::$currentLocalization === self::DefaultLocalization)
    	{
    		return $string;
    	}
    	
        $localizedString = $this->getI18nStringFromMap($string, $this->stringMap);
        if (!$localizedString)
        {
            if ($this->moduleName !== self::$defaultI18nModuleName)
            {
                $sharedCommonI18n = self::sharedCommonI18n();
                $localizedString = $sharedCommonI18n->i18n($string);
            }
        }
        
        if ($localizedString)
        {
            return $localizedString;
        }
        else
        {
            return $string;
        }
    }
}
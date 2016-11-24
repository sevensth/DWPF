<?php

require_once 'savant/Savant3.php';

class DWLibTemplate extends Savant3
{
	const ConfigKeyTemplateDir = 'template_path';
	const ConfigKeyI18n = '_dw_i18n';
	
    const varKeySuper = '_dw_super';

    const EnvKeyModuleGroup = '_dw_mdg';
    const EnvKeyModule = '_dw_md';
    const EnvKeyAction = '_dw_act';

    protected static $templateVarName = '_dw_var';
    protected static $templateEnvName = '_dw_env';

    protected $i18n = NULL;

    public function __construct($config = array())
    {
    	parent::__construct($config);
    	if(is_array($config) && array_key_exists(self::ConfigKeyI18n, $config))
    	{
    		$this->i18n = $config[self::ConfigKeyI18n];
    		unset($config[self::ConfigKeyI18n]);
    	}
    }

    public function setVar($var)
    {
        $this->assignRef(self::$templateVarName, $var);
    }

    protected function getVarFromHierarchy(&$root, &$key)
    {
        $var = NULL;
        if (array_key_exists($key, $root))
        {
            $var = &$root[$key];
        }
        else
        {
            if (array_key_exists(self::varKeySuper, $root))
            {
                $var = $this->getVarFromHierarchy($root[self::varKeySuper], $key);
            }
        }
        return $var;
    }

    public function getVar($key = NULL)
    {
    	$varName = self::$templateVarName;
        $var = &$this->$varName;
        if ($key)
        {
            if (is_array($var))
            {
                return $this->getVarFromHierarchy($var, $key);
            }
        }
        else
        {
            return $var;
        }
        return NULL;
    }

    public function setEnv($env)
    {
        $this->assignRef(self::$templateEnvName, $env);
    }

    public function getEnv($key = NULL)
    {
    	$envName = self::$templateEnvName;
        $env = &$this->$envName;
        if ($key)
        {
            if (is_array($env) && array_key_exists($key, $env))
            {
                return $env[$key];
            }
        }
        else
        {
            return $env;
        }
        return NULL;
    }
    
    public function sEchoI18n($str)
    {
    	$this->eprint($this->i18n->i18n($str));
    }

    public function getI18n($str)
    {
        return $this->i18n->i18n($str);
    }

    public function sEcho($str)
    {
    	$this->eprint($str);
    }


    //
    //<script type="text/javascript" src="/dw/resource/frontui/js/jquery/jquery.min.js?ver=1.10.2"></script>
    public function showJavascriptTag($scriptName, $id = '', $class = '')
    {
        $src = $this->getResourceUrl(DWLibRouter::ResourceTypeJavascript, $scriptName);
        echo "<script type=\"text/javascript\" src=\"$src\" id=\"$id\" class=\"$class\"></script>";
    }

    //<link rel="stylesheet" href="/dw/resource/frontui/css/style.css?ver=3.8.1" type="text/css" media="all">
    public function showCSSTag($cssName, $id = '', $class = '', $media = 'all')
    {
        $href = $this->getResourceUrl(DWLibRouter::ResourceTypeCss, $cssName);
        echo "<link rel=\"stylesheet\" type=\"text/css\" media=\"$media\" href=\"$href\" id=\"$id\" class=\"$class\" />";
    }

    public function showImageUrl($imageName, $version = true)
    {
        echo $this->getResourceUrl(DWLibRouter::ResourceTypeImage, $imageName, $version);
    }

    public function getResourceUrl($type, $name, $version = true)
    {
        $router = DWLibGlobals::getGlobal(DWLibGlobals::GlobalKeyDefaultRouter);
        $url = $router->makeResourceURI($type, $name, $this->getEnv(self::EnvKeyModuleGroup), $version);
        return $url;
    }

    public function getHomePageUrl()
    {
        $router = DWLibGlobals::getGlobal(DWLibGlobals::GlobalKeyDefaultRouter);
        return $router->getRootURL();
    }
}



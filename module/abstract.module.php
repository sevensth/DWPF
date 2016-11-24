<?php
/*
 * @todo Returned html should be in an array, so there will be less data copy by PHP.
 */

abstract class DWModuleAbstract
{
	const DefaultAction = 'default';
    const ActionBody = 'body';
    const ActionContent = 'content';
    const ActionPrefix = 'prefix';
    const ActionSuffix = 'suffix';
    const ActionHead = 'head';
    const ActionFoot = 'foot';
	
	const ConfigKeyGET = 'G';
	const ConfigKeyPOST = 'P';
	const ConfigKeyFILE = 'F';
	const ConfigKeyCOOKIE = 'C';
	const ConfigKeySERVER = 'SVR';
	const ConfigKeySESSION = 'SSN';
	const ConfigKeyENV = 'E';
	const ConfigKeyUserInfo = 'UIF';
	
	const ConfigKeyUserInfoKeyLastAction = 'UIFKLA';
	
	const SubmoduleLayoutConfigKeyModule = 'kM';
	const SubmoduleLayoutConfigKeyAction = 'kA';
    const SubmoduleLayoutConfigKeyRef = 'kR';

	protected $configs;
	protected $i18n;
	protected $templateDir;
	protected $templateEngin;
    protected $acceptableActions;
		
	private $className;
	private $moduleGroup;
	private $moduleName;
	
	private function parseClassName()
	{
		$this->className = get_class($this);
		
		//DWModuleFrontuiArticle -> ModuleFrontuiArticle
		$PrefixTrimedClassName = preg_replace('/^'.CLASS_PREFIX.'/', '', $this->className);
		//ModuleFrontuiArticle -> array('Module', 'Frontui', 'Article')
		$components = preg_split('/(?=[A-Z])/', $PrefixTrimedClassName, -1, PREG_SPLIT_NO_EMPTY);
		$this->moduleGroup = strtolower($components[1]);
		array_shift($components);
		array_shift($components);
		$this->moduleName = strtolower(implode('', $components));
	}
	
	protected function className()
	{
		if (!$this->className)
		{
			$this->parseClassName();
		}
		return $this->className;
	}
	protected function moduleGroup()
	{
		if (!$this->className)
		{
			$this->parseClassName();
		}
		return $this->moduleGroup;
	}
	
	protected function moduleName()
	{
		if (!$this->className)
		{
		$this->parseClassName();
		}
		return $this->moduleName;
	}
	
	protected function submodulesConfigLayout()
	{
		return array();
	}
	
	protected function submodulesConfigArrayForAction($action)
	{
		$submodulesLayout = $this->submodulesConfigLayout();
		if (is_array($submodulesLayout) && array_key_exists($action, $submodulesLayout))
		{
			$subModuleConfig = $submodulesLayout[$action];
            if (is_array($subModuleConfig) && array_key_exists(self::SubmoduleLayoutConfigKeyRef, $subModuleConfig))
            {
                $refAction = $subModuleConfig[self::SubmoduleLayoutConfigKeyRef];
                return $this->submodulesConfigArrayForAction($refAction);
            }
            else
            {
                return $subModuleConfig;
            }
		}
		return array();
	}
	
	protected function validateSubmoduleConfigArray($configArr)
	{
		if (is_array($configArr) && !empty($configArr))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

    protected function getActionsOfModuleInConfigLayout($layout, $module)
    {
        if ($this->validateSubmoduleConfigArray($layout))
        {
            $actions = array();
            foreach ($layout as $key => $subLayout)
            {
                $actions[$key] = 1;
                if (is_array($subLayout) && array_key_exists(self::SubmoduleLayoutConfigKeyRef, $subLayout))
                {
                    continue;
                }
                foreach ($subLayout as $configPair)
                {
                    $submodule = $configPair[self::SubmoduleLayoutConfigKeyModule];
                    if ($submodule == $module)
                    {
                        $submoduleAction = $configPair[self::SubmoduleLayoutConfigKeyAction];
                        if (!$submoduleAction)
                        {
                            $submoduleAction = self::DefaultAction;
                        }
                        $actions[$submoduleAction] = 1;
                    }
                }
            }
            return array_keys($actions);
        }
        return NULL;
    }

	
	public static function defaultConfigs()
	{
		$configs = array(
				self::ConfigKeyGET => $_GET,
				self::ConfigKeyPOST => $_POST,
				self::ConfigKeyFILE => $_FILES,
				self::ConfigKeyCOOKIE => $_COOKIE,
				self::ConfigKeySERVER => $_SERVER,
				self::ConfigKeyENV => $_ENV,
				self::ConfigKeyUserInfo => array(),
		);
		
		if (isset($_SESSION))
		{
			$configs[self::ConfigKeySESSION] = $_SESSION;
		}
		return $configs;
	}
	
	public static function sharedInstance($configs = array())
	{
		static $cachedInstances = array();
		$className = get_called_class();
		$instance = NULL;
		if (array_key_exists($className, $cachedInstances))
		{
			$instance = $cachedInstances[$className];
			//may be should refresh configs here
		}
		else
		{
			$instance = new $className($configs);
			if ($instance)
			{
				$cachedInstances[$className] = $instance;
			}
		}
		return $instance;
	}
	
    public function __construct($configs = array())
    {
        $this->parseClassName();

        if (!is_array($configs))
    	{
    		$configs = array();
    	}
		$this->configs = $configs;

		$moduleGroup = $this->moduleGroup();
		$this->i18n = new DWLibI18n($moduleGroup);
		$this->templateDir = VIEW_DIR . DIRECTORY_SEPARATOR . $moduleGroup;
		$this->templateEngin = new DWLibTemplate(array(
				DWLibTemplate::ConfigKeyTemplateDir => $this->templateDir,
				DWLibTemplate::ConfigKeyI18n => $this->i18n
		));
        $optionModel = DWModelOption::sharedModel();
        $this->templateEngin->setEnv([
            'siteName' => $optionModel->getOption(DWModelOption::TableOptionColumnNameValueBlogName),
            'siteDescription' => $optionModel->getOption(DWModelOption::TableOptionColumnNameValueBlogDescription),
            'siteKeyword' => $optionModel->getOption(DWModelOption::TableOptionColumnNameValueBlogKeyword),
            'siteCharset' => $optionModel->getOption(DWModelOption::TableOptionColumnNameValueBlogCharset),
        ]);
    }
    
    public function run($action = self::DefaultAction, $directlyOutput = false, &$inheritTemplateVar = NULL)
    {
        $action = $this->actionRedirect($action);
    	if (!$this->actionAcceptable($action))
    	{
    		DWLibError::throwE404Exception();
    		return NULL;
    	}
    	
    	//action若取得缓存，则不再遍历子module
    	$html = $this->cacheForAction($action);
    	if ($html)
    	{
    		if ($directlyOutput)
    		{
    			echo $html;
    		}
    	}
    	else
    	{
    		$submodulesConfigArray = $this->submodulesConfigArrayForAction($action);
    		if ($this->validateSubmoduleConfigArray($submodulesConfigArray))
    		{
                $vars = $this->prepareVarsForTemplate($action);
                if ($inheritTemplateVar)
                {
                	$vars[DWLibTemplate::varKeySuper] = $inheritTemplateVar;
                }
    			$html = $this->runSubmodules($submodulesConfigArray, $this->configs, $directlyOutput, $vars);
    		}
    		else
    		{
    			$html = $this->parseTemplateForAction($action, $inheritTemplateVar);
    			if ($directlyOutput)
    			{
    				echo $html;
    			}
    		}    		
    	}
    	
        return $html;
    }

    protected function actionRedirect($action)
    {
        return $action;
    }

    protected function acceptableActions()
    {
        if (!$this->acceptableActions)
        {
            $this->acceptableActions = $this->getActionsOfModuleInConfigLayout($this->submodulesConfigLayout(), $this->className());
        }
        return $this->acceptableActions;
    }

    protected function actionAcceptable($action)
    {
        $acceptableActions = $this->acceptableActions();
        if ($acceptableActions && is_array($acceptableActions) && in_array($action, $acceptableActions))
        {
            return true;
        }
    	return false;
    }
    
    protected function templateFileForAction($action)
    {
    	$moduleGroup = $this->moduleGroup();
    	$tempFileName = $action . '.' . $this->moduleName() . '.' . $moduleGroup . '.' . TEMPLATE_FILE_EXTENSION;
    	return $tempFileName;
    }
    
    protected function runSubmodules($submodulesConfigArray, $constructConfigs = array(), $directlyOutput = false, &$inheritTemplateVar = NULL)
    {
    	$html = '';
    	for ($index = 0; $index < count($submodulesConfigArray); $index++)
    	{
    		$configPair = $submodulesConfigArray[$index];
    		$submodule = $configPair[self::SubmoduleLayoutConfigKeyModule];
    		$submoduleAction = $configPair[self::SubmoduleLayoutConfigKeyAction];
    		if (!$submoduleAction)
    		{
    			$submoduleAction = self::DefaultAction;
    		}
    		$module = $submodule::sharedInstance($constructConfigs);
    		$tmpHTML = $module->run($submoduleAction, $directlyOutput, $inheritTemplateVar);
    		$html .= $tmpHTML;
    	}
    	return $html;
    }
    
    protected function parseTemplateForAction($action, $inheritTemplateVar = NULL)
    {
    	$html = $this->cacheForAction($action);
    	if (!$html)
    	{
    		$template = $this->templateFileForAction($action);

    		$vars = $this->prepareVarsForTemplate($action);
    		if ($inheritTemplateVar)
    		{
    			$vars[DWLibTemplate::varKeySuper] = $inheritTemplateVar;
    		}

            //render
    		$this->templateEngin->setVar($vars);
            $env = $this->templateEngin->getEnv();
            if (!is_array($env)) $env = array();
            $env[DWLibTemplate::EnvKeyModuleGroup] = $this->moduleGroup();
            $env[DWLibTemplate::EnvKeyModule] = $this->moduleName();
            $env[DWLibTemplate::EnvKeyAction] = $action;
            $this->templateEngin->setEnv($env);
    		$html = $this->templateEngin->fetch($template);
    	
    		$this->setCacheForAction($action, $html);
    	}
    	return $html;
    }

    protected function cacheKeyForAction($action)
    {
    	return NULL;
    }
    
    protected function cacheForAction($action)
    {
    	$className = $this->className();
    	$cacheKey = $this->cacheKeyForAction($action);
    	$html = NULL;
    	if ($cacheKey)
    	{
    		$html = DWLibCacheModule::sharedInstance()->getCache($className, $cacheKey);
    	}
    	return $html;
    }
    
    protected function setCacheForAction($action, $html)
    {
    	if ($html)
    	{
    		$className = $this->className();
    		$cacheKey = $this->cacheKeyForAction($action);
    		if ($cacheKey)
    		{
    			DWLibCacheModule::sharedInstance()->setCache($className, $cacheKey, $html);
    		}
    	}
    }
    
    protected function prepareVarsForTemplate($action)
    {
    	return NULL;
    }
    
    abstract public function recordShownOnce($action);
}

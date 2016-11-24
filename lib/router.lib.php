<?php
class DWLibRouter
{
	const DefaultModuleGroup = 'default';
	const DefaultModule = 'default';
	const DefaultAction = 'default';
	
	const RouteKeyModuleGroup = 'mg';
	const RouteKeyModule = 'm';
	const RouteKeyAction = 'a';
	const RouteKeyFrom = 'f';
	const RouteKeyTo = 't';
	const RouteKeyToRef = 'tr';
	
	const GETKeyLastComponent = 'gklc';
	
    protected $moduleDir;
    protected $classPrefix;
    protected $subsiteDir;
    protected $customRoute = array();

    private $rootUrl;

    public function __construct($moduleDir, $classPrefix, $subsiteDir = NULL)
    {
        $this->$moduleDir = $moduleDir;
        $this->classPrefix = $classPrefix;
        $this->subsiteDir = $subsiteDir;
    }

    public function addCustomRoute($route)
    {
    	if (is_array($route) && array_key_exists(self::RouteKeyFrom, $route) && array_key_exists(self::RouteKeyTo, $route))
    	{
    		array_push($this->customRoute, $route);
    	}
    }
    
    public function route($URI, &$HTTPGETArg)
    {
    	$URI = str_replace('\\', '/', $URI);
    	if ($this->subsiteDir)
    	{
    		$URI = preg_replace('/^\/' . $this->subsiteDir . '/', '', $URI);
    	}
    	
    	$URIComponents = explode('?', $URI);
    	$URIPath = trim($URIComponents[0], '/');
    	$URIPathComponents = array();
    	if (strlen($URIPath) > 0)
    	{
    		$URIPath = preg_replace('/\/{2,}/', '/', $URIPath);
    		$URIPathComponents = explode('/', $URIPath);
       	}
        $module = self::DefaultModuleGroup;
        $controller = self::DefaultModule;
        $action = self::DefaultAction;
        
        $URIPathComponentsCount = count($URIPathComponents);
        
        for ($i = 0; $i < min($URIPathComponentsCount, 3); $i++)
        {
        	$component = &$URIPathComponents[$i];
        	if ($i == $URIPathComponentsCount-1)
        	{
        		if (strpos($component, '.') !== false)
        		{
        			break;
        		}
        	}
        	
        	switch ($i)
        	{
        		case 0:
        			$module = $component;
        			break;
        		case 1:
        			$controller = $component;
        			break;
        		case 2:
        			$action = $component;
        			break;
        		default:
        			;
        		break;
        	}
        }
        
        //convert to GET args
        for ($i = 3; $i < $URIPathComponentsCount; $i+=2)
        {
        	$key = $URIPathComponents[$i];
        	if (array_key_exists($i+1, $URIPathComponents))
        	{
        		$value = $URIPathComponents[$i+1];
        		$HTTPGETArg[$key] = $value;
        	}
        }
        
        if ($URIPathComponentsCount > 0)
        {
        	$HTTPGETArg[self::GETKeyLastComponent] = $URIPathComponents[$URIPathComponentsCount-1];
        }
        
        
        //apply custom route
        foreach ($this->customRoute as $route)
        {
        	$routeFrom = $route[self::RouteKeyFrom];
        	$moduleGroupMatch = !array_key_exists(self::RouteKeyModuleGroup, $routeFrom) || $routeFrom[self::RouteKeyModuleGroup] == $module;
        	$moduleMatch = !array_key_exists(self::RouteKeyModule, $routeFrom) || $routeFrom[self::RouteKeyModule] == $controller;
        	$actionMatch = !array_key_exists(self::RouteKeyAction, $routeFrom) || $routeFrom[self::RouteKeyAction] == $action;
        	if ($moduleGroupMatch && $moduleMatch && $actionMatch)
        	{
        		if (array_key_exists(self::RouteKeyToRef, $route))
        		{
        			$routeToRef = $route[self::RouteKeyToRef];
        			
        			$routeBaseMap = array(self::RouteKeyModuleGroup => $module, self::RouteKeyModule => $controller, self::RouteKeyAction => $action);
        			
        			if (array_key_exists(self::RouteKeyModuleGroup, $routeToRef) && array_key_exists($routeToRef[self::RouteKeyModuleGroup], $routeBaseMap))
        			{
        				$module = $routeBaseMap[$routeToRef[self::RouteKeyModuleGroup]];
        			}
        			if (array_key_exists(self::RouteKeyModule, $routeToRef) && array_key_exists($routeToRef[self::RouteKeyModule], $routeBaseMap))
        			{
        				$controller = $routeBaseMap[$routeToRef[self::RouteKeyModule]];
        			}
        			if (array_key_exists(self::RouteKeyAction, $routeToRef) && array_key_exists($routeToRef[self::RouteKeyAction], $routeBaseMap))
        			{
        				$action = $routeBaseMap[$routeToRef[self::RouteKeyAction]];
        			}
        		}
        		
        		if (array_key_exists(self::RouteKeyTo, $route))
        		{
        			$routeTo = $route[self::RouteKeyTo];
        			if (array_key_exists(self::RouteKeyModuleGroup, $routeTo))
        			{
        				$module = $routeTo[self::RouteKeyModuleGroup];
        			}
        			if (array_key_exists(self::RouteKeyModule, $routeTo))
        			{
        				$controller = $routeTo[self::RouteKeyModule];
        			}
        			if (array_key_exists(self::RouteKeyAction, $routeTo))
        			{
        				$action = $routeTo[self::RouteKeyAction];
        			}
        		}
        	}
        }

        $baseClassName = ucfirst($module) . ucfirst($controller);
        $className = $this->classPrefix . 'Module' . $baseClassName;
        $actionName = strtolower($action);

        //validate class name
//        if (!preg_match('/^[A-Z][_a-zA-Z0-9]+$/', $baseClassName))
//        {
//            $className = 'DWModuleFrontuiE404';
//        }

        return array($className, $actionName);
    }
    
    public function makeURI($moduleGroup = self::DefaultModuleGroup, $module = self::DefaultModule, $action = self::DefaultAction)
    {
    	//todo 应用custom路由
    	return $this->getRootURL() . "$moduleGroup/$module/$action";
    }

    const ResourceTypeCss = 'css';
    const ResourceTypeFont = 'font';
    const ResourceTypeImage = 'img';
    const ResourceTypeJavascript = 'js';
    const ResourceVersionArgName = 'v';
    public function makeResourceURI($type, $name, $moduleGroup = self::DefaultModuleGroup, $version = true)
    {
        $uri = $this->getRootURL() . "resource/$moduleGroup/$type/$name";
        if ($version)
        {
            $resourcePath = $this->makeResourceLocalPath($type, $name, $moduleGroup);
            $mtime = filemtime($resourcePath);
            if ($mtime)
            {
                $uri .= '?' . self::ResourceVersionArgName . '=' . $mtime;
            }
        }
        return $uri;
    }

    public function makeResourceLocalPath($type, $name, $moduleGroup = self::DefaultModuleGroup)
    {
        return ROOT_PATH . DIRECTORY_SEPARATOR . "resource/$moduleGroup/$type/$name";
    }

    public function getRootURL()
    {
        if (!$this->rootUrl)
        {
            $this->rootUrl = "/";
            if (!empty($this->subsiteDir))
            {
                $this->rootUrl .= "$this->subsiteDir/";
            }
        }
        return $this->rootUrl;
    }

    //http://www.dreamginwish.com/#ARTICLE:fwefsfew:ARTICLE => ROOT/article/fwefsfew.html
    //#ARTICLE::ARTICLE
    //#ROOT_URL
    //#IMAGE::IMAGE
    public function urlFromPattern($pattern, $moduleGroup = self::DefaultModuleGroup)
    {
        $components = explode('#', $pattern, 2);
        if (count($components) < 2)
        {
            return $pattern;
        }

        $markup = $components[1];

        $match = DWLibUtility::getContentBetweenMark('ARTICLE', ':', $markup);
        if ($match)
        {
            ///@TODO module name should not hard code.
            $articleModule = DWModuleFrontuiArticle::sharedInstance();
            $articleUrl = $articleModule->buildArticleUrl($match);
            return $articleUrl;
        }

        $match = DWLibUtility::getContentBetweenMark('IMAGE', ':', $markup);
        if ($match)
        {
            $imageUri = $this->makeResourceURI(self::ResourceTypeImage, $match, $moduleGroup);
            return $imageUri;
        }

        $match = DWLibUtility::getContentBetweenMark('JS', ':', $markup);
        if ($match)
        {
            $jsUri = $this->makeResourceURI(self::ResourceTypeJavascript, $match, $moduleGroup);
            return $jsUri;
        }

        $match = DWLibUtility::getContentBetweenMark('CSS', ':', $markup);
        if ($match)
        {
            $cssUri = $this->makeResourceURI(self::ResourceTypeCss, $match, $moduleGroup);
            return $cssUri;
        }

        if (preg_match('/ROOT_URL/', $markup, $matches))
        {
            return $this->getRootURL();
        }

        return $pattern;
    }
}
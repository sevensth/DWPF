<?php
$startTime = microtime(true);

//defines

//debug
define('DEBUG', false);
ini_set("error_reporting", E_ALL & ~E_NOTICE);
ini_set("display_errors", DEBUG ? true : false);

define('SUBSITE_DIR', '');
define('CLASS_PREFIX', 'DW');
define('SCRIPT_EXTENTION', 'php');
define('TEMPLATE_FILE_EXTENSION', 'phtml');

//the returned string is path with any trailing / component removed.
define('ROOT_PATH', dirname(__FILE__));
define('CACHE_DIR', ROOT_PATH . '/cache');
define('MODULE_DIR', ROOT_PATH . '/module');
define('LIB_DIR', ROOT_PATH . '/lib');
define('I18N_DIR', ROOT_PATH . '/i18n');
define('VIEW_DIR', ROOT_PATH . '/view');

define('DIRECTLY_OUTPUT', false);
define('LANG', 'zh_CN');
define('GLOBAL_SALT', '9feb5c7df7e25797ff68ecfa411c7e753338fb751');

//auto load classes
require_once LIB_DIR . '/autoloader.lib.php';
$autoloader = new DWLibAutoloader(CLASS_PREFIX);
$autoloader->register();

//record begin time
DWLibGlobals::setGlobal(DWLibGlobals::GlobalKeyBeginTime, $startTime);

//i18n
DWLibI18n::setLocalization(LANG);

//db
if (DEBUG)
{
    $dbInfo = [
        DWModelAbstract::DBInfoHost => '',
        DWModelAbstract::DBInfoDBName => '',
        DWModelAbstract::DBInfoUser => '',
        DWModelAbstract::DBInfoPassword => '',
    ];
}
else
{
    $dbInfo = [
        DWModelAbstract::DBInfoHost => 'localhost',
        DWModelAbstract::DBInfoDBName => '',
        DWModelAbstract::DBInfoUser => '',
        DWModelAbstract::DBInfoPassword => '',
    ];
}

DWModelAbstract::setDataBaseInfo($dbInfo);
DWModelAbstract::setTableNamePrefix('wp_');
//DWModelSession::sharedModel();

//tmp debug codes
//DWModelTerm::sharedModel()->getTagsByPostIds([920,922]);

//route
$requestURI = $_SERVER['REQUEST_URI'];
$router = new DWLibRouter(MODULE_DIR, CLASS_PREFIX, SUBSITE_DIR);
DWLibGlobals::setGlobal(DWLibGlobals::GlobalKeyDefaultRouter, $router);
//default module group is frontui
$router->addCustomRoute(array(
    DWLibRouter::RouteKeyFrom => array(DWLibRouter::RouteKeyModuleGroup => DWLibRouter::DefaultModuleGroup,),
    DWLibRouter::RouteKeyTo => array(DWLibRouter::RouteKeyModuleGroup => 'frontui',)
    ));
//frontui: default module is homepage
$router->addCustomRoute(array(
    DWLibRouter::RouteKeyFrom => array(DWLibRouter::RouteKeyModuleGroup => 'frontui', DWLibRouter::RouteKeyModule => DWLibRouter::DefaultModule,),
    DWLibRouter::RouteKeyTo => array(DWLibRouter::RouteKeyModule => 'homepage',)));
//frontui: search module is articlelist
//$router->addCustomRoute(array(
//    DWLibRouter::RouteKeyFrom => array(DWLibRouter::RouteKeyModuleGroup => 'frontui', DWLibRouter::RouteKeyModule => 'search',),
//    DWLibRouter::RouteKeyTo => array(DWLibRouter::RouteKeyModule => 'articlelist',)));
//frontui: category module is articlelist
$router->addCustomRoute(array(
    DWLibRouter::RouteKeyFrom => array(DWLibRouter::RouteKeyModuleGroup => 'frontui', DWLibRouter::RouteKeyModule => 'category',),
    DWLibRouter::RouteKeyTo => array(DWLibRouter::RouteKeyModule => 'articlelist',)));
//article as moduleGroup: append default as moduleGroup
$router->addCustomRoute(array(
    DWLibRouter::RouteKeyFrom => array(DWLibRouter::RouteKeyModuleGroup => 'article'),
    DWLibRouter::RouteKeyToRef => array(DWLibRouter::RouteKeyModule => DWLibRouter::RouteKeyModuleGroup, DWLibRouter::RouteKeyAction => DWLibRouter::RouteKeyModule),
    DWLibRouter::RouteKeyTo => array(DWLibRouter::RouteKeyModuleGroup => 'frontui',)));
//articlelist as moduleGroup: append default as moduleGroup
$router->addCustomRoute(array(DWLibRouter::RouteKeyFrom => array(DWLibRouter::RouteKeyModuleGroup => 'articlelist'), DWLibRouter::RouteKeyToRef => array(DWLibRouter::RouteKeyModule => DWLibRouter::RouteKeyModuleGroup, DWLibRouter::RouteKeyAction => DWLibRouter::RouteKeyModule), DWLibRouter::RouteKeyTo => array(DWLibRouter::RouteKeyModuleGroup => 'frontui',)));

$routeResult = $router->route($requestURI, $_GET);

//get module
$className = $routeResult[0];
$actionName = $routeResult[1];
$configs = DWModuleAbstract::defaultConfigs();

$classInstance = NULL;
try
{
    $autoloader->autoload($className);
	$classInstance = new $className($configs);
}
catch (Exception $e)
{
    //backward compatible
    if (DWLibBack::redirectOldURIIfNeeded($requestURI, $configs))
    {
        exit;
    }
    //got 404
	$httpProtocol = $configs[DWModuleAbstract::ConfigKeySERVER]['SERVER_PROTOCOL'];
	header("$httpProtocol 404 Not Found");
	$configs[DWModuleAbstract::ConfigKeyUserInfo][DWModuleAbstract::ConfigKeyUserInfoKeyLastAction] = $actionName;
	$actionName = DWModuleAbstract::DefaultAction;
	$classInstance = new DWModuleFrontuiE404($configs);
}

//run
$html = NULL;
try
{
	$html = $classInstance->run($actionName, DIRECTLY_OUTPUT);
}
catch (Exception $e)
{
	$httpProtocol = $configs[DWModuleAbstract::ConfigKeySERVER]['SERVER_PROTOCOL'];
	header("$httpProtocol 404 Not Found");
	$classInstance = new DWModuleFrontuiE404($configs);
	$configs[DWModuleAbstract::ConfigKeyUserInfo][DWModuleAbstract::ConfigKeyUserInfoKeyLastAction] = $actionName;
	$actionName = DWModuleAbstract::DefaultAction;
	$html = $classInstance->run($actionName, DIRECTLY_OUTPUT);
}

if (DIRECTLY_OUTPUT == false)
{
	echo $html;
}

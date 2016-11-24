<?php
/**
 * Created by PhpStorm.
 * User: seven
 * Date: 14-7-26
 * Time: 下午9:44
 */

class DWModuleFrontuiSearch extends DWModuleFrontuiAbstract
{
    protected function submodulesConfigLayout()
    {
        return array(self::DefaultAction => array());
    }

    public function recordShownOnce($action)
    {
    }

    protected function prepareVarsForTemplate($action)
    {
        $serverArgs = &$this->configs[self::ConfigKeySERVER];
        $urlParams = $serverArgs['QUERY_STRING'];
        $url = 'http://zhannei.baidu.com/cse/search?' . $urlParams;
        //using 303 or 307 is more accurate, but we should take compatibility into account.
        DWLibError::SendHTTPStatusCodeHeader(302, $url);
        return '';
    }
}
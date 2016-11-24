<?php
class DWModuleFrontuiE404 extends DWModuleFrontuiAbstract
{
    private $subModuleLayout = array(
    		self::DefaultAction => array(
    				array(
    						self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiHtml',
    						self::SubmoduleLayoutConfigKeyAction => self::ActionPrefix,
    				),
    				array(
    						self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiHtml',
    						self::SubmoduleLayoutConfigKeyAction => self::ActionHead,
    				),
    				array(
    						self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiE404',
    						self::SubmoduleLayoutConfigKeyAction => self::ActionBody,
    				),
    				array(
    						self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiHtml',
    						self::SubmoduleLayoutConfigKeyAction => self::ActionSuffix,
    				),
    		),
            self::ActionBody => array(
    				array(
    						self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiE404',
    						self::SubmoduleLayoutConfigKeyAction => self::ActionPrefix,
    				),
    				array(
    						self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiHead',
    				),
    				array(
    						self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiE404',
    						self::SubmoduleLayoutConfigKeyAction => self::ActionContent,
    				),
    				array(
    						self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiFoot',
    				),
    				array(
    						self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiE404',
    						self::SubmoduleLayoutConfigKeyAction => self::ActionSuffix,
    				),
    		),
    );

    /*
    public function run($action = self::DefaultAction, $directlyOutput = false, &$inheritTemplateVar = NULL)
    {
    	if ($action == self::DefaultAction)
    	{
    		$httpProtocol = $this->configs[self::ConfigKeySERVER]['SERVER_PROTOCOL'];
    		header("$httpProtocol 404 Not Found");
    	}
    	return parent::run($action, $directlyOutput, $inheritTemplateVar);
    }
    */
     
    protected function prepareVarsForTemplate($action)
    {
    	if ($action == 'body')
    	{
    	}
    }
    
    protected function submodulesConfigLayout()
    {
    	return $this->subModuleLayout;
    }

    protected function actionRedirect($action)
    {
        $acceptableActions = $this->acceptableActions();
        if ($acceptableActions && is_array($acceptableActions) && in_array($action, $acceptableActions))
        {
            return $action;
        }
        else
        {
            return self::DefaultAction;
        }
    }

    public function recordShownOnce($action)
    {
    }
}

<?php
class DWModuleFrontuiHtml extends DWModuleFrontuiAbstract
{
	private $subModuleLayout = array(
        self::ActionPrefix => array(
        ),
		self::ActionSuffix => array(
        ),
        self::ActionHead => array(
        ),
    );

	protected function submodulesConfigLayout()
	{
		return $this->subModuleLayout;
	}

	public function recordShownOnce($action)
	{
		$subModules = $this->subModuleLayout[$action];
		foreach ($subModules as $subModule => $subModuleAction)
		{
			$module = $subModule::sharedInstance();
			$module->recordShownOnce($subModuleAction);
		}
	}
}
<?php

class DWModuleFrontuiHead extends DWModuleFrontuiAbstract
{
    const MenuName = 'DWPF';

    private $subModuleLayout = array(
        self::DefaultAction => array(
        ),
    );

    protected function submodulesConfigLayout()
    {
        return $this->subModuleLayout;
    }

	protected function trimEmptyContentTreeNode(&$tree)
	{
		$trimmedTree = array();
		foreach ($tree as $node)
		{
			if ($node[DWModelTerm::TreeNodeKeyChildrenContentCount] > 0)
			{
				$node[DWModelTerm::TreeNodeKeyChildren] = $this->trimEmptyContentTreeNode($node[DWModelTerm::TreeNodeKeyChildren]);
			}
			
			if ($node[DWModelTerm::TableTermTaxonomyColumnContentCount] + $node[DWModelTerm::TreeNodeKeyChildrenContentCount] > 0)
			{
				array_push($trimmedTree, $node);
			}
		}
		return $trimmedTree;
	}
	
	protected function prepareVarsForTemplate($action)
	{
		if ($action == 'default')
		{
			$menuModel = DWModelMenu::sharedModel();
			$menus = $menuModel->getMenuTreeByName(self::MenuName);
            $menuModel->refactorMenuList($menus);

			$router = DWLibGlobals::getGlobal(DWLibGlobals::GlobalKeyDefaultRouter);
			$searchAction = $router->makeURI($this->moduleGroup(), 'search');

			return array('menu' => $menus, 'searchAction' => $searchAction);
		}
	}
	
	public function recordShownOnce($action)
	{
		
	}
}
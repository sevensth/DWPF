<?php
class DWModelMenu extends DWModelAbstract
{
	protected $termTableName;
	protected $texonomyTableName;
	protected $postTableName;
	protected $postMetaTableName;
	protected $termRelationTableName;
	
	public function __construct($dbInfo, $tablePrefix = '')
	{
		parent::__construct($dbInfo, $tablePrefix);
		$this->termTableName = $this->tablePrefix . DWModelTerm::TableTerm;
		$this->texonomyTableName = $this->tablePrefix . DWModelTerm::TableTermTaxonomy;
		$this->postTableName = $this->tablePrefix . DWModelArticle::TablePost;
		$this->postMetaTableName = $this->tablePrefix . DWModelArticle::TablePostMeta;
		$this->termRelationTableName =  $this->tablePrefix . DWModelTerm::TableTermRelation;
	}
	
	protected function getTermTaxonomyIdOfMenu($name)
	{
		$prepareSQL = 'SELECT `' . $this->texonomyTableName . '`.`' . DWModelTerm::TableTermTaxonomyColumnTaxonomyId .
		 '` FROM `' . $this->texonomyTableName .
		 '` LEFT JOIN `' . $this->termTableName .
		 '` ON `' . $this->texonomyTableName . '`.`' . DWModelTerm::TableTermTaxonomyColumnTermId . '` = `' . $this->termTableName . '`.`' . DWModelTerm::TableTermColumnId .
		 '` WHERE `' . DWModelTerm::TableTermTaxonomyColumnTaxonomy . '` = \'' . DWModelTerm::TableTermTaxonomyColumnTaxonomyValueNavMenu . '\' and name = ?';
		$statement = $this->dbConnection->prepare($prepareSQL);
		if ($statement)
		{
			$statement->execute(array($name));
			self::increaseDBSelectTimes();
			$resultArray = $this->fetchOneAsAsscociationArray($statement, true);
			return $resultArray[DWModelTerm::TableTermTaxonomyColumnTaxonomyId];
		}
	}
	
	protected function getMenuRawListByName($name)
	{
		$termTaxonomyId = $this->getTermTaxonomyIdOfMenu($name);
		/*
		 SELECT p.ID, tm.`name`, tm.slug, p.post_title, p.post_name, p.menu_order, n.post_name as n_post_name, n.post_title as n_post_title, m.meta_value as url, pp.meta_value as menu_parent, pc.`meta_value` as css_class, po.meta_value as meta_object
FROM wp_term_relationships as txr 
INNER JOIN wp_posts as p ON txr.object_id = p.ID 
LEFT JOIN wp_postmeta as m ON p.ID = m.post_id 
LEFT JOIN wp_postmeta as pl ON p.ID = pl.post_id AND pl.meta_key = '_menu_item_object_id' 
LEFT JOIN wp_postmeta as pp ON p.ID = pp.post_id AND pp.meta_key = '_menu_item_menu_item_parent'
LEFT JOIN wp_postmeta as pc ON p.ID = pc.post_id AND pc.meta_key = '_menu_item_classes' 
LEFT JOIN wp_postmeta as po ON p.ID = po.post_id AND po.meta_key = '_menu_item_object' 
LEFT JOIN wp_posts as n ON pl.meta_value = n.ID AND ( po.meta_value = 'page' OR po.meta_value = 'custom')
LEFT JOIN wp_terms as tm ON tm.`term_id` = pl.`meta_value` AND po.meta_value = 'category'
WHERE txr.term_taxonomy_id = 86 AND p.post_status='publish' 
    AND p.post_type = 'nav_menu_item' AND m.meta_key = '_menu_item_url' 
ORDER BY menu_parent, p.menu_order
		 */
		$selectField = 'p.`' . DWModelArticle::TablePostColumnId . '`, tm.`' . DWModelTerm::TableTermColumnName . '`, tm.`' .DWModelTerm::TableTermColumnSlug . '`, p.`' . DWModelArticle::TablePostColumnTitle . '`, p.`' . DWModelArticle::TablePostColumnName . '`, p.`' . DWModelArticle::TablePostColumnMenuOrder . '`, n.`' . DWModelArticle::TablePostColumnName . '` as n_post_name, n.`' . DWModelArticle::TablePostColumnTitle . '` as n_post_title, m.`' . DWModelArticle::TablePostMetaColumnValue . '` as url, pp.`' . DWModelArticle::TablePostMetaColumnValue . '` as menu_parent, pc.`' . DWModelArticle::TablePostMetaColumnValue . '` as css, po.`' . DWModelArticle::TablePostMetaColumnValue . '` as meta_object';
		$fromField = "`$this->termRelationTableName` as txr";
		$joinField = "INNER JOIN `$this->postTableName` as p ON txr.`" . DWModelTerm::TableTermRelationColumnObjId . '` = p.`' . DWModelArticle::TablePostColumnId .
		 "` LEFT JOIN `$this->postMetaTableName` as m ON p.`" . DWModelArticle::TablePostColumnId . '` = m.`' . DWModelArticle::TablePostMetaColumnPostId .
		 "` LEFT JOIN `$this->postMetaTableName` as pl ON p.`" . DWModelArticle::TablePostColumnId . '` = pl.`' . DWModelArticle::TablePostMetaColumnPostId . '` AND pl.`' . DWModelArticle::TablePostMetaColumnKey . '` = \'' . DWModelArticle::TablePostMetaColumnKeyValueMenuItemObjId .
		 "' LEFT JOIN `$this->postMetaTableName` as pp ON p.`" . DWModelArticle::TablePostColumnId . '` = pp.`' . DWModelArticle::TablePostMetaColumnPostId . '` AND pp.`' . DWModelArticle::TablePostMetaColumnKey . '` = \'' . DWModelArticle::TablePostMetaColumnKeyValueMenuItemParent .
		 "' LEFT JOIN `$this->postMetaTableName` as pc ON p.`" . DWModelArticle::TablePostColumnId . '` = pc.`' . DWModelArticle::TablePostMetaColumnPostId . '` AND pc.`' . DWModelArticle::TablePostMetaColumnKey . '` = \'' . DWModelArticle::TablePostMetaColumnKeyValueMenuItemCSSs .
		 "' LEFT JOIN `$this->postMetaTableName` as po ON p.`" . DWModelArticle::TablePostColumnId . '` = po.`' . DWModelArticle::TablePostMetaColumnPostId . '` AND po.`' . DWModelArticle::TablePostMetaColumnKey . '` = \'' . DWModelArticle::TablePostMetaColumnKeyValueMenuItemObj .
		 "' LEFT JOIN `$this->postTableName` as n ON pl.`" . DWModelArticle::TablePostMetaColumnValue . '` = n.`' . DWModelArticle::TablePostColumnId . '` AND (po.`' . DWModelArticle::TablePostMetaColumnValue . '` = \'' . DWModelArticle::TablePostMetaColumnValueValuePage . '\' OR po.`' . DWModelArticle::TablePostMetaColumnValue . '` = \'' . DWModelArticle::TablePostMetaColumnValueValueCustom . '\')' .
		" LEFT JOIN `$this->termTableName` as tm ON tm.`" . DWModelTerm::TableTermColumnId . '` = pl.`' . DWModelArticle::TablePostMetaColumnValue . '` AND po.`' . DWModelArticle::TablePostMetaColumnValue . '` = \'' . DWModelArticle::TablePostMetaColumnValueValueCategory . '\'';
		$whereField = 'txr.`' . DWModelTerm::TableTermRelationColumnTaxonomyId . '` = ? AND p.`' . DWModelArticle::TablePostColumnPostStatus . '` = \'' . DWModelArticle::TablePostColumnPostStatusValuePublish . '\' AND p.`' .DWModelArticle::TablePostColumnPostType . '` = \'' . DWModelArticle::TablePostColumnPostTypeValueNavMenuItem . '\' AND m.`' . DWModelArticle::TablePostMetaColumnKey . '` = \'' . DWModelArticle::TablePostMetaColumnKeyValueMenuItemUrl . '\'';
		$orderByField = 'menu_parent, p.`' . DWModelArticle::TablePostColumnMenuOrder . '`';
		
		$prepareSQL = "SELECT $selectField FROM $fromField $joinField WHERE $whereField ORDER BY $orderByField";
		$statement = $this->dbConnection->prepare($prepareSQL);
		if ($statement)
		{
			$statement->execute(array($termTaxonomyId));
			self::increaseDBSelectTimes();
			$resultArray = $this->fetchAllAsAsscociationArray($statement);
			return $resultArray;
		}
	}
	
	public function getMenuTreeByName($name)
	{
		$rawMenuList = $this->getMenuRawListByName($name);
		$this->refactorRawMenuList($rawMenuList);
		return $rawMenuList;
	}
	
	protected function refactorRawMenuList(&$menuList)
	{
		foreach ($menuList as &$menu)
		{
			//name & slug
			if ($menu['meta_object'] == DWModelArticle::TablePostMetaColumnValueValuePage || $menu['meta_object'] == DWModelArticle::TablePostMetaColumnValueValueCustom)
			{
				$menu['name'] = $menu['n_post_title'];
				$menu['slug'] = $menu['n_post_title'];
			}
			else if ($menu['meta_object'] == DWModelArticle::TablePostMetaColumnValueValueCategory)
			{
				if (!empty($menu['post_title']))
				{
					$menu['name'] = $menu['post_title'];
				}
			}
			unset($menu['n_post_title']);
			unset($menu['post_title']);
			
			//css
			$cssArray = unserialize($menu['css']);
			if (is_array($cssArray) || is_string($cssArray))
			{
				$menu['css'] = implode(' ', $cssArray);
			}
		}
	}

    public function refactorMenuList(&$menuList)
    {
        $articleModule = DWModuleFrontuiArticle::sharedInstance();
        $articleListModule = DWModuleFrontuiArticlelist::sharedInstance();
        $router = DWLibGlobals::getGlobal(DWLibGlobals::GlobalKeyDefaultRouter);
        foreach ($menuList as &$menu)
        {
            //url
            if ($menu['meta_object'] == DWModelArticle::TablePostMetaColumnValueValuePage)
            {
    			$url = $articleModule->buildArticleUrl($menu['slug']);
    			$menu['url'] = $url;
            }
            else if ($menu['meta_object'] == DWModelArticle::TablePostMetaColumnValueValueCategory)
            {
                $url = $articleListModule->urlForCategoryBySlug($menu['slug']);
                if (!$url)
                {
                    $url = $articleListModule->urlForCategoryById($menu['ID']);
                }
                $menu['url'] = $url;
            }
            else if ($menu['meta_object'] == DWModelArticle::TablePostMetaColumnValueValueCustom)
            {
                $menu['url'] = $router->urlFromPattern($menu['url']);
            }
        }
    }
}


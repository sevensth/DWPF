<?php
class DWModelTerm extends DWModelAbstract
{
	const TableTerm = 'terms';
	const TableTermTaxonomy = 'term_taxonomy';
	const TableTermRelation = 'term_relationships';
	
	const TableTermColumnId = 'term_id';
	const TableTermColumnName = 'name';
	const TableTermColumnSlug = 'slug';
	
	const TableTermTaxonomyColumnTermId = 'term_id';
	const TableTermTaxonomyColumnTaxonomy = 'taxonomy';
	const TableTermTaxonomyColumnTaxonomyId = 'term_taxonomy_id';
	const TableTermTaxonomyColumnParent = 'parent';
	const TableTermTaxonomyColumnContentCount = 'count';
	const TableTermTaxonomyColumnDescription = 'description';
	
	const TableTermTaxonomyColumnTaxonomyValueCategory = 'category';
	const TableTermTaxonomyColumnTaxonomyValueNavMenu = 'nav_menu';
	const TableTermTaxonomyColumnTaxonomyValueTag = 'post_tag';
	
	
	const TableTermRelationColumnObjId = 'object_id';
	const TableTermRelationColumnTaxonomyId = 'term_taxonomy_id';
	const TableTermRelationColumnOrder = 'term_order';
	
	
	protected $termTableName;
	protected $taxonomyTableName;
    protected $termRelationTableName;
    protected $postTableName;
	
	public function __construct($dbInfo, $tablePrefix = '')
	{
		parent::__construct($dbInfo, $tablePrefix);
		$this->termTableName = $this->tablePrefix . self::TableTerm;
		$this->taxonomyTableName = $this->tablePrefix . self::TableTermTaxonomy;
        $this->termRelationTableName = $this->tablePrefix . self::TableTermRelation;
        $this->postTableName = $this->tablePrefix . DWModelArticle::TablePost;
	}
	
	public function getById($id)
	{
		$queryPrepare = "SELECT * FROM `" . $this->termTableName . "` WHERE `" . self::TableTermColumnId . "` = ?";
		$statement = $this->dbConnection->prepare($queryPrepare);
		if ($statement)
		{
			$statement->execute(array($id));
			self::increaseDBSelectTimes();
			return $this->fetchOneAsAsscociationArray($statement, true);
		}
	}

    public function getOneBySlug($taxonomy, $slug)
    {
        /*
         SELECT * FROM `wp_term_taxonomy`
         LEFT JOIN `wp_terms` ON `wp_terms`.`term_id` = `wp_term_taxonomy`.`term_id`
         WHERE `wp_terms`.`slug` = 'api' and `wp_term_taxonomy`.`taxonomy` = 'post_tag'
         */
        $select = DWLibSqlSelect::select($this->taxonomyTableName, '*');
        $select->leftJoin($this->termTableName, [ '=' => [
            [$this->termTableName, self::TableTermColumnId],
            [$this->taxonomyTableName, self::TableTermTaxonomyColumnTermId]
        ]]);
        $select->where(['and' => [
            [
                '=',
                [$this->termTableName, self::TableTermColumnSlug],
                ':slug',
            ],
            [
                '=',
                [$this->taxonomyTableName, self::TableTermTaxonomyColumnTaxonomy],
                ':taxonomy'
            ]
        ]]);
        $queryPrepare = $select->getSQL();
        $statement = $this->dbConnection->prepare($queryPrepare);
        if ($statement)
        {
            $statement->execute([':slug' => $slug, ':taxonomy' => $taxonomy]);
            self::increaseDBSelectTimes();
            return $this->fetchOneAsAsscociationArray($statement, true);
        }
    }

    public function getOneTagBySlug($slug)
    {
        return $this->getOneBySlug(self::TableTermTaxonomyColumnTaxonomyValueTag, $slug);
    }
    
	public function getTermWithTaxonomyById($id)
	{
		"select * from wp_terms left join wp_term_taxonomy on wp_terms.term_id = wp_term_taxonomy.term_taxonomy_id where wp_terms.term_id = 3";
	}
	
	protected function getTermJoinTaxonomy($taxonomy, $orderBy, $asc = true)
	{
		//select * from wp_term_taxonomy left join wp_terms on wp_terms.term_id = wp_term_taxonomy.term_id where taxonomy = 'category'
		$queryPrepare = "SELECT * FROM `" . $this->taxonomyTableName . "` LEFT JOIN `" . $this->termTableName . "` ON `" . $this->taxonomyTableName . "`.`" . self::TableTermTaxonomyColumnTermId ."` = `" . $this->termTableName . "`.`" . self::TableTermColumnId . "` WHERE `" . self::TableTermTaxonomyColumnTaxonomy . "` = ?";
		if ($orderBy)
		{
			$queryPrepare .= " ORDER BY $orderBy ";
		}
		if (!$asc)
		{
			$queryPrepare .= ' DESC ';
		}
		$statement = $this->dbConnection->prepare($queryPrepare);
		if ($statement)
		{
			$statement->execute(array($taxonomy));
			self::increaseDBSelectTimes();
			return $this->fetchAllAsAsscociationArray($statement);
		}
	}
	
	const TreeNodeKeyChildren = 'chdr';
	const TreeNodeKeyChildrenContentCount = 'chdrctntct';
	const CategoryKeyUrl = 'url';
	const CategoryKeyThumbnailUrl = 'tmurl';
	public function getCategoryTree()
	{
        //cache
        static $tree = NULL;
        if ($tree) return $tree;

        //load
		$termJoinTaxonomy = $this->getTermJoinTaxonomy(self::TableTermTaxonomyColumnTaxonomyValueCategory, self::TableTermTaxonomyColumnParent);
		foreach ($termJoinTaxonomy as &$cate)
		{
			$cate[self::TreeNodeKeyChildrenContentCount] = $cate[self::TableTermTaxonomyColumnContentCount];
			if (array_key_exists(self::TableTermColumnSlug, $cate) && !empty($cate[self::TableTermColumnSlug]))
			{
				$cate[self::CategoryKeyUrl] = DWModuleFrontuiArticlelist::sharedInstance()->urlForCategoryBySlug($cate[self::TableTermColumnSlug]);
				$cate[self::CategoryKeyThumbnailUrl] = DWModuleFrontuiArticlelist::sharedInstance()->thumbnailUrlForCategory($cate[self::TableTermColumnSlug]);
			}
			else
			{
				$cate[self::CategoryKeyUrl] = DWModuleFrontuiArticlelist::sharedInstance()->urlForCategoryById($cate[self::TableTermColumnId]);
				$cate[self::CategoryKeyThumbnailUrl] = DWModuleFrontuiArticlelist::sharedInstance()->thumbnailUrlForCategory();
			}
		}

		$tree = DWLibUtility::makeTreeFromFlatList($termJoinTaxonomy, self::TableTermTaxonomyColumnTermId, self::TableTermTaxonomyColumnParent, self::TreeNodeKeyChildren, 0);
		DWLibUtility::accumulateSubNodeValueOfTree($termJoinTaxonomy, self::TreeNodeKeyChildrenContentCount, self::TreeNodeKeyChildren);
		
		return $tree;
	}
	
	public function getCategorySubTreeBySlug($slug)
	{
		$totalList = $this->getCategoryTree();
		$subTree = DWLibUtility::findNodeFromTree($totalList, self::TableTermColumnSlug, $slug, self::TreeNodeKeyChildren);
		return $subTree;
	}

	public function getCategorySubTreeById($id)
	{
		$totalList = $this->getCategoryTree();
		$subTree = DWLibUtility::findNodeFromTree($totalList, self::TableTermColumnId, $id, self::TreeNodeKeyChildren);
		return $subTree;
	}

	public function getCategorySubTreeByTaxonomyId($id)
	{
		$totalList = $this->getCategoryTree();
		$subTree = DWLibUtility::findNodeFromTree($totalList, self::TableTermTaxonomyColumnTaxonomyId, $id, self::TreeNodeKeyChildren);
		return $subTree;
	}

    public function getCategoriesBySlugs($slugs)
    {
        $totalList = $this->getCategoryTree();
        if (is_array($slugs))
        {
            $categories = [];
            foreach ($slugs as $slug)
            {
                $cate = DWLibUtility::findNodeFromTree($totalList, self::TableTermColumnSlug, $slug, self::TreeNodeKeyChildren);
                $categories[] = $cate;
            }
            return $categories;
        }
        else
        {
            $cate = DWLibUtility::findNodeFromTree($totalList, self::TableTermColumnSlug, $slugs, self::TreeNodeKeyChildren);
            return $cate;
        }
    }

	const CateKeyArticleList = 'atcls';
	public function fillCateTreeArticleLists(&$tree, $count = 10, $buildUrl = true, $topCateSpecCount = -1, $topCateSpecOffset = -1, $level = 0)
	{
		if (is_array($tree))
		{
			if (array_key_exists(DWModelTerm::TableTermRelationColumnTaxonomyId, $tree))
			{
				if (!array_key_exists(self::CateKeyArticleList, $tree))
				{
					$articleModel = DWModelArticle::sharedModel();
                    $getCount = ($level == 0 && $topCateSpecCount > 0) ? $topCateSpecCount : $count;
                    $getOffset = ($level == 0 && $topCateSpecOffset > 0) ? $topCateSpecOffset : 0;
					$articleList = $articleModel->getByTermTaxonomyId($tree[DWModelTerm::TableTermRelationColumnTaxonomyId], $getCount, -1, $getOffset);
					if (!$articleList)
					{
						$articleList = array();
					}
					if ($buildUrl)
					{
						foreach ($articleList as &$article)
						{
							$article[DWModuleFrontuiArticle::ArticleUrlKey] = DWModuleFrontuiArticle::sharedInstance()->buildArticleUrl($article[DWModelArticle::TablePostColumnName]);
						}
					}
					$tree[self::CateKeyArticleList] = $articleList;
				}
				if (array_key_exists(self::TreeNodeKeyChildren, $tree))
				{
					$this->fillCateTreeArticleLists($tree[self::TreeNodeKeyChildren], $count, $buildUrl, $topCateSpecCount, $topCateSpecOffset, $level+1);
				}
			}
			else
			{
				foreach ($tree as &$cate)
				{
					$this->fillCateTreeArticleLists($cate, $count, $buildUrl, $topCateSpecCount, $topCateSpecOffset, $level);
				}
			}
		}
	}
	
	public function getAllArticlesOfCateTree($tree, $count = 10)
	{
		$articleList = array();
		if (is_array($tree))
		{
			if (array_key_exists(DWModelTerm::TableTermRelationColumnTaxonomyId, $tree))
			{
				if (array_key_exists(self::CateKeyArticleList, $tree))
				{
					$articleList = array_merge($articleList, $tree[self::CateKeyArticleList]);
				}
				if (count($articleList) < $count && array_key_exists(self::TreeNodeKeyChildren, $tree))
				{
					$subCateArticleList = $this->getAllArticlesOfCateTree($tree[self::TreeNodeKeyChildren], $count);
					$articleList = array_merge($articleList, $subCateArticleList);
				}
			}
			else
			{
				foreach ($tree as &$cate)
				{
					$subCateArticleList = $this->getAllArticlesOfCateTree($cate, $count);
					$articleList = array_merge($articleList, $subCateArticleList);
					if (count($articleList) >= $count)
					{
						break;
					}
				}
			}
		}
		if (count($articleList) > $count)
		{
			$articleList = array_slice($articleList, 0, $count);
		}
		return $articleList;
	}
	
	public function getTags($orderBy = NULL, $order = 'ASC')
	{
		/*
		 SELECT `wp_term_taxonomy`.`term_taxonomy_id`, `wp_term_taxonomy`.`term_id`, `wp_term_taxonomy`.`count`, `wp_terms`.`name`, `wp_terms`.`slug`
		 FROM `wp_term_taxonomy`
		 LEFT JOIN `wp_terms` ON `wp_terms`.`term_id` = `wp_term_taxonomy`.`term_id`
		 WHERE `wp_term_taxonomy`.`taxonomy` = 'post_tag' ORDER BY `wp_term_taxonomy`.`term_taxonomy_id`
		 */
        $select = DWLibSqlSelect::select($this->taxonomyTableName, [
            $this->taxonomyTableName => [self::TableTermTaxonomyColumnTaxonomyId, self::TableTermTaxonomyColumnTermId, self::TableTermTaxonomyColumnContentCount],
            $this->termTableName => [self::TableTermColumnName, self::TableTermColumnSlug]
        ]);
        $select->leftJoin($this->termTableName, [ '=' => [
            [$this->termTableName, self::TableTermTaxonomyColumnTermId],
            [$this->taxonomyTableName, self::TableTermTaxonomyColumnTermId]
        ]]);
        $select->where(['=' => [
            [$this->taxonomyTableName, self::TableTermTaxonomyColumnTaxonomy],
            '?'
        ]]);
        if ($orderBy)
        {
            $select->orderBy($orderBy, $order);
        }
		$statement = $this->dbConnection->prepare($select->getSQL());
		if ($statement)
		{
			$statement->execute(array(self::TableTermTaxonomyColumnTaxonomyValueTag));
			self::increaseDBSelectTimes();
			return $this->fetchAllAsAsscociationArray($statement);
		}
	}

    /*
     SELECT `wp_posts`.`ID`, `wp_term_taxonomy`.*, `wp_terms`.* FROM `wp_term_taxonomy`
     LEFT JOIN `wp_terms` ON (wp_terms.`term_id` = wp_term_taxonomy.`term_id`)
     LEFT JOIN `wp_term_relationships` ON (wp_term_relationships.`term_taxonomy_id` = wp_term_taxonomy.`term_taxonomy_id`)
     LEFT JOIN `wp_posts` ON (wp_posts.`ID` = wp_term_relationships.`object_id`)
     WHERE (wp_posts.`ID` IN (1811)) AND `wp_term_taxonomy`.`taxonomy` = 'post_tag'
     */
    protected function getTaxonomyByPostIds($ids, $taxonomy)
    {
        if (!is_array($ids))
        {
            $ids = [$ids];
        }

        $inQuery = implode(',', array_fill(0, count($ids), '?'));

        $select = DWLibSqlSelect::select($this->taxonomyTableName, [
            $this->postTableName => DWModelArticle::TablePostColumnId,
            $this->taxonomyTableName => '*',
            $this->termTableName => '*',
        ]);
        $select->leftJoin($this->termTableName, [ '=' => [
            [$this->termTableName, self::TableTermColumnId],
            [$this->taxonomyTableName, self::TableTermTaxonomyColumnTermId]
        ]]);
        $select->leftJoin($this->termRelationTableName, [ '=' => [
            [$this->termRelationTableName, self::TableTermRelationColumnTaxonomyId],
            [$this->taxonomyTableName, self::TableTermTaxonomyColumnTaxonomyId]
        ]]);
        $select->leftJoin($this->postTableName, [ '=' => [
            [$this->postTableName, DWModelArticle::TablePostColumnId],
            [$this->termRelationTableName, self::TableTermRelationColumnObjId]
        ]]);
        $select->where(['and' => [
            ['IN' => [
                [$this->postTableName, DWModelArticle::TablePostColumnId],
                $inQuery
            ]],
            ['=' => [
                [$this->taxonomyTableName, self::TableTermTaxonomyColumnTaxonomy],
                '?'
            ]]
        ]]);

        $queryPrepare = $select->getSQL();
        $statement = $this->dbConnection->prepare($queryPrepare);
        if ($statement)
        {
            $ids[] = $taxonomy;
            $statement->execute($ids);
            self::increaseDBSelectTimes();
            return $this->fetchAllAsAsscociationArray($statement);
        }
    }

    public function getTagsByPostIds($ids)
    {
        return $this->getTaxonomyByPostIds($ids, self::TableTermTaxonomyColumnTaxonomyValueTag);
    }

    public function getCategoriesByPostIds($ids)
    {
        return $this->getTaxonomyByPostIds($ids, self::TableTermTaxonomyColumnTaxonomyValueCategory);
    }

    const TagKeyUrl = 'url';
    public function fillTagsWithCommonInfo(array &$tags)
    {
        $articleListModule = DWModuleFrontuiArticlelist::sharedInstance();
        foreach ($tags as $key => $tag)
        {
            $tags[$key][self::TagKeyUrl] = $articleListModule->urlForTagBySlug($tag[DWModelTerm::TableTermColumnSlug]);
        }
    }

    public function fillCategoriesWithCommonInfo(array &$categories)
    {
        $articleListModule = DWModuleFrontuiArticlelist::sharedInstance();
        foreach ($categories as $key => $category)
        {
            $categories[$key][self::CategoryKeyUrl] = $articleListModule->urlForCategoryBySlug($category[DWModelTerm::TableTermColumnSlug]);
        }
    }

    public function refactorTagsByPostsIds(array $tagFlatArray)
    {
        $tags = [];
        foreach ($tagFlatArray as $tag)
        {
            $tags[$tag[DWModelArticle::TablePostColumnId]][] = $tag;
        }
        return $tags;
    }

    public function refactorCategoriesByPostsIds(array $categoryFlatArray)
    {
        $categories = [];
        foreach ($categoryFlatArray as $category)
        {
            $categories[$category[DWModelArticle::TablePostColumnId]][] = $category;
        }
        return $categories;
    }

    const TagKeyWeight = 'wt';
    public function buildTagsWeightByContentCount(array &$tags)
    {
        $maxContentCount = 0;
        $minContentCount = PHP_INT_MAX;
        foreach ($tags as $tag)
        {
            $contentCount = $tag[DWModelTerm::TableTermTaxonomyColumnContentCount];
            if ($contentCount > $maxContentCount)
            {
                $maxContentCount = $contentCount;
            }
            if ($contentCount < $minContentCount)
            {
                $minContentCount = $contentCount;
            }
        }

        $contentCountDifference = $maxContentCount - $minContentCount;
        foreach ($tags as $key => $tag)
        {
            $contentCount = $tag[DWModelTerm::TableTermTaxonomyColumnContentCount];
            $tags[$key][self::TagKeyWeight] = $contentCountDifference == 0 ? 1 : ($contentCount-$minContentCount)/$contentCountDifference;
        }
    }
}


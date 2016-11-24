<?php
class DWModelArticle extends DWModelAbstract
{
	const TablePost = 'posts';
	const TablePostMeta = 'postmeta';
    
    const TablePostColumnId = 'ID';
    const TablePostColumnAuthor = 'post_author';
    const TablePostColumnDate = 'post_date';
    const TablePostColumnGmtDate = 'post_date_gmt';
    const TablePostColumnModifyDate = 'post_modified';
    const TablePostColumnContent = 'post_content';
    const TablePostColumnTitle = 'post_title';
    const TablePostColumnName = 'post_name';
    const TablePostColumnDigest = 'post_excerpt';
    const TablePostColumnPostStatus = 'post_status';
    const TablePostColumnCommentStatus = 'comment_status';
    const TablePostColumnPingStatus = 'ping_status';
    const TablePostColumnMenuOrder = 'menu_order';
    const TablePostColumnPostType = 'post_type';
    
    const TablePostMetaColumnId = 'meta_id';
    const TablePostMetaColumnPostId = 'post_id';
    const TablePostMetaColumnKey = 'meta_key';
    const TablePostMetaColumnValue = 'meta_value';
    
    const TablePostColumnCommentStatusValueOpen = 'open';
    const TablePostColumnCommentStatusValueClose = 'close';
    const TablePostColumnPingStatusValueOpen = 'open';
    const TablePostColumnPingStatusValueClose = 'close';
    const TablePostColumnPostStatusValuePublish = 'publish';
    const TablePostColumnPostStatusValuePrivate = 'private';
    const TablePostColumnPostTypeValueNavMenuItem = 'nav_menu_item';
    const TablePostColumnPostTypeValuePost = 'post';
    
    const TablePostMetaColumnKeyValueMenuItemObjId = '_menu_item_object_id';
    const TablePostMetaColumnKeyValueMenuItemParent = '_menu_item_menu_item_parent';
    const TablePostMetaColumnKeyValueMenuItemCSSs = '_menu_item_classes';
    const TablePostMetaColumnKeyValueMenuItemObj = '_menu_item_object';
    const TablePostMetaColumnKeyValueMenuItemUrl = '_menu_item_url';
    const TablePostMetaColumnKeyValueOldSlug = '_wp_old_slug';

    const TablePostMetaColumnValueValueCategory = 'category';
    const TablePostMetaColumnValueValuePage = 'page';
    const TablePostMetaColumnValueValueCustom = 'custom';
    
    protected $postTableName;
    protected $termRelationTableName;
    protected $termTableName;
    protected $termTaxonomyTableName;
    protected $postMetaTableName;
    protected $userTableName;
    
    protected $baseInfoCols = array(
    		self::TablePostColumnAuthor,
    		self::TablePostColumnDate,
    		self::TablePostColumnId,
    		self::TablePostColumnTitle,
    		self::TablePostColumnDigest,
    		self::TablePostColumnPostStatus,
    		self::TablePostColumnName,
    );
    
    public function __construct($dbInfo, $tablePrefix = '')
    {
    	parent::__construct($dbInfo, $tablePrefix);
    	$this->postTableName = $this->tablePrefix . self::TablePost;
    	$this->termRelationTableName = $this->tablePrefix . DWModelTerm::TableTermRelation;
    	$this->termTableName = $this->tablePrefix . DWModelTerm::TableTerm;
    	$this->termTaxonomyTableName = $this->tablePrefix . DWModelTerm::TableTermTaxonomy;
        $this->postMetaTableName = $this->tablePrefix . DWModelArticle::TablePostMeta;
        $this->userTableName = $this->tablePrefix . DWModelUser::TableUsers;
    }

    public function getPostMetaByPostId($id)
    {
        //SELECT ID, `wp_postmeta`.* FROM `wp_posts` LEFT JOIN `wp_postmeta` ON `wp_postmeta`.`post_id` = `wp_posts`.`ID` WHERE `wp_posts`.`ID` = 2130
        $select = DWLibSqlSelect::select(self::TablePost, [self::TablePost, '*']);
        $select->leftJoin(self::TablePostMeta, ['=' => [[self::TablePostMeta, self::TablePostMetaColumnPostId], [self::TablePost, self::TablePostColumnId]]]);
        $select->where(['=' => [[self::TablePost, self::TablePostColumnId], '?']]);
        $statement = $this->dbConnection->prepare($select->getSQL());
        if ($statement)
        {
            $statement->execute(array($id));
            self::increaseDBSelectTimes();
            return $this->fetchAllAsAsscociationArray($statement);
        }
    }

    /*
     SELECT * FROM `wp_posts` LEFT JOIN `wp_term_relationships` ON `wp_term_relationships`.`object_id` = `wp_posts`.`ID` LEFT JOIN `wp_term_taxonomy` ON `wp_term_taxonomy`.`term_taxonomy_id` = `wp_term_relationships`.`term_taxonomy_id` LEFT JOIN `wp_terms` ON `wp_terms`.`term_id` = `wp_term_taxonomy`.`term_id` WHERE `wp_posts`.`ID` = 2033
     SELECT ID, `wp_terms`.*, `wp_term_taxonomy`.* FROM `wp_posts` LEFT JOIN `wp_term_relationships` ON `wp_term_relationships`.`object_id` = `wp_posts`.`ID` LEFT JOIN `wp_term_taxonomy` ON `wp_term_taxonomy`.`term_taxonomy_id` = `wp_term_relationships`.`term_taxonomy_id` LEFT JOIN `wp_terms` ON `wp_terms`.`term_id` = `wp_term_taxonomy`.`term_id` WHERE `wp_posts`.`ID` = 2033
    */
    protected function getByOneColumnEqual($columnName, $value, $columnArray = NULL, $status = self::TablePostColumnPostStatusValuePublish)
    {
    	$column = "`$this->postTableName`.*";
    	if ($columnArray)
    	{
    		$column = implode(',', $columnArray);
    	}
        $column .= ', ' . DWModelUser::TableUsersColumnDisplayName . ', '. "`$this->userTableName`." . DWModelUser::TableUsersColumnId . ' as userId';

        $joinFiled = " LEFT JOIN `$this->userTableName` ON `$this->userTableName`.`" . DWModelUser::TableUsersColumnId . "` = `$this->postTableName`.`" . self::TablePostColumnAuthor . '`';
        $whereField = '`' . self::TablePostColumnPostStatus . '`=\'' . $status . '\' AND `' . self::TablePostColumnPostType . '` IN (\'' . self::TablePostColumnPostTypeValuePost . '\',\'' . self::TablePostMetaColumnValueValuePage . '\')';

        $executeArray = NULL;
        if ($columnName == self::TablePostColumnName)
        {
            $column .= ", `$this->postMetaTableName`.*";
            $joinFiled .= " LEFT JOIN `$this->postMetaTableName` ON `$this->postMetaTableName`.`" . self::TablePostMetaColumnPostId . "` = `$this->postTableName`.`" . self::TablePostColumnId . '`';
            $whereField .= " AND (`$columnName` = ? OR (`" . self::TablePostMetaColumnValue . "` = ? AND `$this->postMetaTableName`.`" . self::TablePostMetaColumnKey . '` = \'' . self::TablePostMetaColumnKeyValueOldSlug . '\'))';
            $executeArray = array($value, $value);
        }
        else
        {
            $whereField .= " AND `$columnName` = ?";
            $executeArray = array($value);
        }

    	$queryPrepare = "SELECT $column FROM `$this->postTableName` $joinFiled WHERE $whereField LIMIT 1";
    	$statement = $this->dbConnection->prepare($queryPrepare);
    	if ($statement)
    	{
            if ($statement->execute($executeArray))
            {
                self::increaseDBSelectTimes();
                return $this->fetchOneAsAsscociationArray($statement, true);
            }
            else
            {
//                $error = $statement->errorInfo();
//                var_dump($error);
            }
    	}
    }
    
    public function getFullInfoById($id)
    {
    	return $this->getByOneColumnEqual(self::TablePostColumnId, $id);
    }

    public function getBaseInfoById($id)
    {
    	return $this->getById(self::TablePostColumnId, $id, $this->baseInfoCols);
    }

    public function getFullInfoByTitle($title)
    {
        return $this->getByOneColumnEqual(self::TablePostColumnTitle, $title, NULL, self::TablePostColumnPostStatusValuePrivate);
    }
    
    public function getFullInfoByName($name)
    {
    	return $this->getByOneColumnEqual(self::TablePostColumnName, $name);
    }
    
    public function getBaseInfoByName($name)
    {
    	return $this->getById(self::TablePostColumnName, $name, $this->baseInfoCols);
    }
    
    public function getByTermTaxonomyId($taxonomyId, $count = 10, $page = 0, $offset = 0)
    {
        /*
         SELECT wp_posts.`post_author`, wp_posts.`post_date`, wp_posts.`ID`, wp_posts.`post_title`, wp_posts.`post_excerpt`, wp_posts.`post_status`, wp_posts.`post_name`, wp_users.`display_name`
         FROM `wp_posts`
         LEFT JOIN `wp_term_relationships` ON (wp_term_relationships.`object_id` = wp_posts.`ID`)
         LEFT JOIN `wp_users` ON (wp_users.`ID` = wp_posts.`post_author`)
         WHERE ((wp_term_relationships.`term_taxonomy_id` = 5) AND (wp_posts.`post_status` = 'publish'))
         ORDER BY `wp_posts`.`post_modified` DESC
         LIMIT 0, 20
        */
        $select = DWLibSqlAbstract::select($this->postTableName, [$this->postTableName => $this->baseInfoCols, $this->userTableName => DWModelUser::TableUsersColumnDisplayName]);
        $select->leftJoin($this->termRelationTableName, ['=' => [
            [$this->termRelationTableName, DWModelTerm::TableTermRelationColumnObjId],
            [$this->postTableName, self::TablePostColumnId]
        ]]);
        $select->leftJoin($this->userTableName, ['=' => [
            [$this->userTableName, DWModelUser::TableUsersColumnId],
            [$this->postTableName, self::TablePostColumnAuthor]
        ]]);
        $select->where(['and' => [
            [
                '=',
                [$this->termRelationTableName, DWModelTerm::TableTermRelationColumnTaxonomyId],
                '?'
            ],
            [
                '=',
                [$this->postTableName, self::TablePostColumnPostStatus],
                '?'
            ]
        ]]);
        $select->orderBy([$this->postTableName => self::TablePostColumnDate], 'DESC');
        $select->limit($count, ($page<0 ? $offset : $count*$page));
        $queryPrepare = $select->getSQL();
        $statement = $this->dbConnection->prepare($queryPrepare);
        if ($statement)
        {
            $statement->execute(array($taxonomyId, self::TablePostColumnPostStatusValuePublish));
            self::increaseDBSelectTimes();
            return $this->fetchAllAsAsscociationArray($statement);
        }
    }

    public function getRelatedBaseInfoByTermTaxonomyId($taxonomyId, $postId, $newer, $count = 2)
    {
        /*
         * get older:
SELECT
wp_posts.`post_author`, wp_posts.`post_date`, wp_posts.`ID`, wp_posts.`post_title`, wp_posts.`post_excerpt`, wp_posts.`post_status`, wp_posts.`post_name`, wp_users.`display_name`
FROM `wp_posts`
LEFT JOIN `wp_term_relationships` ON (wp_term_relationships.`object_id` = wp_posts.`ID`)
LEFT JOIN `wp_users` ON (wp_users.`ID` = wp_posts.`post_author`)
WHERE ((wp_term_relationships.`term_taxonomy_id` = 8) AND (wp_posts.`post_status` = 'publish') AND (wp_posts.`post_date` < (SELECT `post_date` FROM `wp_posts` WHERE (wp_posts.`ID` = 2479))))
ORDER BY wp_posts.`post_date` DESC
LIMIT 0, 2
         * Note the result is ORDER BY wp_posts.`post_date` DESC
         *
         * get newer:
         * SELECT
wp_posts.`post_author`, wp_posts.`post_date`, wp_posts.`ID`, wp_posts.`post_title`, wp_posts.`post_excerpt`, wp_posts.`post_status`, wp_posts.`post_name`, wp_users.`display_name`
FROM `wp_posts`
LEFT JOIN `wp_term_relationships` ON (wp_term_relationships.`object_id` = wp_posts.`ID`)
LEFT JOIN `wp_users` ON (wp_users.`ID` = wp_posts.`post_author`)
WHERE ((wp_term_relationships.`term_taxonomy_id` = 8) AND (wp_posts.`post_status` = 'publish') AND (wp_posts.`post_date` > (SELECT `post_date` FROM `wp_posts` WHERE (wp_posts.`ID` = 2479))))
ORDER BY wp_posts.`post_date` ASC
LIMIT 0, 2
         * Note the result is ORDER BY wp_posts.`post_date` ASC
         */

        $subSelect = DWLibSqlAbstract::select($this->postTableName, [self::TablePostColumnDate]);
        $subSelect->where(['=' => [[$this->postTableName, self::TablePostColumnId], '?']]);
        $usbSelectSql = $subSelect->getSQL();

        $select = DWLibSqlAbstract::select($this->postTableName, [$this->postTableName => $this->baseInfoCols, $this->userTableName => DWModelUser::TableUsersColumnDisplayName]);
        $select->leftJoin($this->termRelationTableName, ['=' => [
            [$this->termRelationTableName, DWModelTerm::TableTermRelationColumnObjId],
            [$this->postTableName, self::TablePostColumnId]
        ]]);
        $select->leftJoin($this->userTableName, ['=' => [
            [$this->userTableName, DWModelUser::TableUsersColumnId],
            [$this->postTableName, self::TablePostColumnAuthor]
        ]]);
        $select->where(['and' => [
            [
                '=',
                [$this->termRelationTableName, DWModelTerm::TableTermRelationColumnTaxonomyId],
                '?'
            ],
            [
                '=',
                [$this->postTableName, self::TablePostColumnPostStatus],
                '?'
            ],
            [
                $newer ? '>' : '<',
                [$this->postTableName, self::TablePostColumnDate],
                "($usbSelectSql)"
            ]
        ]]);
        $select->orderBy([$this->postTableName => self::TablePostColumnDate], $newer ? 'ASC' : 'DESC');
        $select->limit($count);

        $queryPrepare = $select->getSQL();
        $statement = $this->dbConnection->prepare($queryPrepare);
        if ($statement)
        {
            $statement->execute(array($taxonomyId, self::TablePostColumnPostStatusValuePublish, $postId));
            self::increaseDBSelectTimes();
            return $this->fetchAllAsAsscociationArray($statement);
        }
    }

    public function getByTagSlug($tag, $count = 10, $page = 0, $offset = 0)
    {
        /*
         SELECT post_author,post_date,ID,post_title,post_excerpt,post_status,post_name FROM `wp_posts`
         LEFT JOIN `wp_term_relationships` ON `wp_term_relationships`.`object_id` = `wp_posts`.`ID`
         LEFT JOIN `wp_term_taxonomy` ON `wp_term_taxonomy`.`term_taxonomy_id` = `wp_term_relationships`.`term_taxonomy_id`
         LEFT JOIN `wp_terms` ON `wp_terms`.`term_id` = `wp_term_taxonomy`.`term_id`
         WHERE `wp_terms`.`name` = 'CoreAnimation' ORDER BY `wp_posts`.`post_modified` DESC LIMIT 0, 10
         */
        $select = DWLibSqlAbstract::select($this->postTableName, [$this->postTableName => $this->baseInfoCols, $this->userTableName => DWModelUser::TableUsersColumnDisplayName]);
        $select->leftJoin($this->termRelationTableName, ['=' => [
            [$this->termRelationTableName, DWModelTerm::TableTermRelationColumnObjId],
            [$this->postTableName, self::TablePostColumnId]
        ]]);
        $select->leftJoin($this->termTaxonomyTableName, ['=' => [
            [$this->termTaxonomyTableName, DWModelTerm::TableTermTaxonomyColumnTaxonomyId],
            [$this->termRelationTableName, DWModelTerm::TableTermRelationColumnTaxonomyId]
        ]]);
        $select->leftJoin($this->termTableName, ['=' => [
            [$this->termTableName, DWModelTerm::TableTermColumnId],
            [$this->termTaxonomyTableName, DWModelTerm::TableTermTaxonomyColumnTermId]
        ]]);
        $select->leftJoin($this->userTableName, ['=' => [
            [$this->userTableName, DWModelUser::TableUsersColumnId],
            [$this->postTableName, self::TablePostColumnAuthor]
        ]]);
        $select->where(['and' => [
            [
                '=',
                [$this->termTableName, DWModelTerm::TableTermColumnSlug],
                '?'
            ],
            [
                '=',
                [$this->postTableName, self::TablePostColumnPostStatus],
                '?'
            ]
        ]]);
        $select->orderBy([$this->postTableName => self::TablePostColumnDate], 'DESC');
        $select->limit($count, ($page<0 ? $offset : $count*$page));
		$queryPrepare = $select->getSQL();
//        echo $queryPrepare;
//        var_dump(array($tag, self::TablePostColumnPostStatusValuePublish));
		$statement = $this->dbConnection->prepare($queryPrepare);
		if ($statement)
		{
			$statement->execute(array($tag, self::TablePostColumnPostStatusValuePublish));
			self::increaseDBSelectTimes();
			return $this->fetchAllAsAsscociationArray($statement);
		}
    }

    public function getByTitleSearch($keyword)
    {
        
    }
    
    protected function getRecentPostsWithColumn($count = 0, $columnArray = NULL)
    {
    	$column = '*';
    	if ($columnArray)
    	{
    		$column = implode(',', $columnArray);
    	}
    	$whereFiled = '`' . self::TablePostColumnPostStatus . '` = \'' . self::TablePostColumnPostStatusValuePublish . '\' AND `' . self::TablePostColumnPostType . '` = \'' . self::TablePostColumnPostTypeValuePost . '\'';
    	$orderByField = '`' . self::TablePostColumnDate . '`';
    	$limitField = $count > 0 ? "LIMIT $count" : '';
    	$queryPrepare = "SELECT $column FROM `$this->postTableName` WHERE $whereFiled ORDER BY $orderByField DESC $limitField";
    	$statement = $this->dbConnection->prepare($queryPrepare);
    	if ($statement)
    	{
    		$statement->execute(array());
    		self::increaseDBSelectTimes();
    		return $this->fetchAllAsAsscociationArray($statement);
    	}
    }
    
    public function getRecentPosts($count = '10', $detail = false)
    {
    	$columnArray = $detail ? NULL : $this->baseInfoCols;
    	return $this->getRecentPostsWithColumn($count, $columnArray);
    }

    const ArticleKeyTags = 'tags';
    public function fillArticleListWithTags(&$articleList)
    {
        $articleIds = DWLibPhpcompatibility::arrayColumn($articleList, self::TablePostColumnId);
        if ($articleIds)
        {
            $termModel = DWModelTerm::sharedModel();
            $tags = $termModel->getTagsByPostIds($articleIds);
            if ($tags)
            {
                $termModel->fillTagsWithCommonInfo($tags);
                $tags = $termModel->refactorTagsByPostsIds($tags);
                if ($tags)
                {
                    foreach ($articleList as $key => $article)
                    {
                        $articleList[$key][self::ArticleKeyTags] = $tags[$article[self::TablePostColumnId]];
                    }
                }
            }
        }
    }

    const ArticleKeyCategories = 'categories';
    public function fillArticleListWithCategories(&$articleList)
    {
        $articleIds = DWLibPhpcompatibility::arrayColumn($articleList, self::TablePostColumnId);
        if ($articleIds)
        {
            $termModel = DWModelTerm::sharedModel();
            $categories = $termModel->getCategoriesByPostIds($articleIds);
            if ($categories)
            {
                $termModel->fillCategoriesWithCommonInfo($categories);
                $categories = $termModel->refactorCategoriesByPostsIds($categories);
                if ($categories)
                {
                    foreach ($articleList as $key => $article)
                    {
                        $articleList[$key][self::ArticleKeyCategories] = $categories[$article[self::TablePostColumnId]];
                    }
                }
            }
        }
    }

    public function getMetaByPostId($id)
    {
        $select = DWLibSqlSelect::select($this->postMetaTableName);
        $select->where(['=' => [
            self::TablePostMetaColumnPostId,
            '?'
        ]]);
        $prepareSQL = $select->getSQL();
        $statement = $this->dbConnection->prepare($prepareSQL);
        if ($statement)
        {
            $statement->execute([$id]);
            self::increaseDBSelectTimes();
            $rawMetaList = $this->fetchAllAsAsscociationArray($statement);
            $metas = [];
            foreach ($rawMetaList as $rawMeta)
            {
                $metas[$rawMeta[self::TablePostMetaColumnKey]] = $rawMeta[self::TablePostMetaColumnValue];
            }
            return $metas;
        }
    }
}

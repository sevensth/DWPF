<?php
class DWModuleFrontuiArticlelist extends DWModuleFrontuiAbstract
{
	const ActionCategory = 'category';
	const ActionTag = 'tag';
    const ActionAjaxCategory = 'ajaxcategory';
    const ActionAjaxTag = 'ajaxtag';
	
	const GETArgId = 'id';
	const GETArgName = 'name';
	const GETArgPage = 'page';
    const GETArgCount = 'count';
    const GETArgOffset = 'offset';

    const GetCountSubCateDefault = 10;
    const GetCountDefault = 20;
    const GetCountLimit = 110;
    
    private $subModuleLayout = array(
            self::ActionAjaxCategory => array(

            ),
            self::ActionAjaxTag => array(

            ),
            self::ActionCategory => array(
                    self::SubmoduleLayoutConfigKeyRef => self::DefaultAction,
            ),
            self::ActionTag => array(
                    self::SubmoduleLayoutConfigKeyRef => self::DefaultAction,
            ),
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
    						self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiArticlelist',
    						self::SubmoduleLayoutConfigKeyAction => self::ActionBody,
    				),
    				array(
    						self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiHtml',
    						self::SubmoduleLayoutConfigKeyAction => self::ActionSuffix,
    				),
    		),
    		self::ActionBody => array(
    				array(
    						self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiHead',
    				),
    				array(
    						self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiArticlelist',
    						self::SubmoduleLayoutConfigKeyAction => self::ActionPrefix,
    				),
    				array(
    						self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiCommon',
    						self::SubmoduleLayoutConfigKeyAction => DWModuleFrontuiCommon::ActionBanner,
    				),
    				array(
    						self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiArticlelist',
    						self::SubmoduleLayoutConfigKeyAction => self::ActionContent,
    				),
    				array(
    						self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiArticlelist',
    						self::SubmoduleLayoutConfigKeyAction => self::ActionSuffix,
    				),
    				array(
    						self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiFoot',
    				),
    		),
    );
    
    protected function submodulesConfigLayout()
    {
    	return $this->subModuleLayout;
    }
    
    protected function submodulesConfigArrayForAction($action)
    {
    	if ($action == self::ActionCategory || $action == self::ActionTag)
    	{
    		$action = DWLibRouter::DefaultAction;
    	}
    	return parent::submodulesConfigArrayForAction($action);
    }
    
    protected function templateFileForAction($action)
    {
        if ($action == self::ActionAjaxCategory || $action == self::ActionAjaxTag)
        {
            $moduleGroup = $this->moduleGroup();
            $tempFileName = 'ajaxarticle.' . $this->moduleName() . '.' . $moduleGroup . '.' . TEMPLATE_FILE_EXTENSION;
            return $tempFileName;
        }
        return parent::templateFileForAction($action);
    }

    protected function prepareVarsForTemplate($action)
    {
    	$getArgs = &$this->configs[self::ConfigKeyGET];

        //parse HTTP GET arguments
        //id
        $anId = $getArgs[self::GETArgId];

        //name
        $aName = NULL;
        if (is_array($getArgs))
        {
            if (array_key_exists(self::GETArgName, $getArgs))
            {
                $aName = $getArgs[self::GETArgName];
            }
            else
            {
                $aName = $getArgs[DWLibRouter::GETKeyLastComponent];
            }
        }

        //count
        $count = self::GetCountDefault;
        if (is_array($getArgs))
        {
            if (array_key_exists(self::GETArgCount, $getArgs))
            {
                $count = $getArgs[self::GETArgCount];
            }
        }
        $safeCount = $count > self::GetCountLimit ? self::GetCountLimit : $count;

        //page
        $page = -1;
        if (is_array($getArgs))
        {
            if (array_key_exists(self::GETArgPage, $getArgs))
            {
                $page = $getArgs[self::GETArgPage];
            }
        }

        //offset
        $offset = 0;
        if (is_array($getArgs))
        {
            if (array_key_exists(self::GETArgOffset, $getArgs))
            {
                $offset = $getArgs[self::GETArgOffset];
            }
        }
        $nextLoadOffset = $safeCount+$offset;

        //handle actions
        if ($action == self::ActionCategory)
    	{
            $cateSlug = $aName;

            $termModel = DWModelTerm::sharedModel();
            $cate = $termModel->getCategorySubTreeBySlug($cateSlug);
            if (!$cate)
            {
                DWLibError::throwE404Exception("No article found in category $cateSlug");
                return NULL;
            }
            $termModel->fillCateTreeArticleLists($cate, self::GetCountSubCateDefault, true, $safeCount, $offset);
            $rootCateArticleList = &$cate[DWModelTerm::CateKeyArticleList];
            if ($rootCateArticleList)
            {
                $articleModel = DWModelArticle::sharedModel();
                $articleModel->fillArticleListWithTags($rootCateArticleList);
                $articleModel->fillArticleListWithCategories($rootCateArticleList);
            }

            $urlQueryId = '?' . self::GETArgId .'=' . $cate[DWModelTerm::TableTermTaxonomyColumnTaxonomyId];
            $urlQueryName = '?' . self::GETArgName . "=$cateSlug";
            $router = DWLibGlobals::getGlobal(DWLibGlobals::GlobalKeyDefaultRouter);
            $baseUrl = $router->makeURI($this->moduleGroup(), $this->moduleName(), self::ActionAjaxCategory) . $urlQueryId;
            $baseHRef = $router->makeURI($this->moduleGroup(), $this->moduleName(), self::ActionCategory) . $urlQueryName;
            $href = $baseHRef . '&' . self::GETArgCount . "=$safeCount&" . self::GETArgOffset . "=$nextLoadOffset";

            $showLoadMoreButton = false;
            if (is_array($cate) && array_key_exists(DWModelTerm::CateKeyArticleList, $cate))
            {
                if (count($cate[DWModelTerm::CateKeyArticleList]) >= $safeCount)
                {
                    $showLoadMoreButton = true;
                }
            }

            return array(
                'htmlTitle' => $cate[DWModelTerm::TableTermColumnName],
                'pageType' => $this->i18n->i18n('Category'),
                'bannerTitle' => $cate[DWModelTerm::TableTermColumnName],
                'bannerDigest' => $cate[DWModelTerm::TableTermTaxonomyColumnDescription],
                'cate' => $cate,
                'showLoadMoreButton' => $showLoadMoreButton,
                'href' => $href,
            	'loadMoreCount' => $safeCount,
            	'loadMoreOffset' => $nextLoadOffset,
            	'loadMoreBaseUrl' => $baseUrl,
                'loadMoreBaseHRef' => $baseHRef,
            );
    	}
        else if ($action == self::ActionAjaxCategory)
        {
            $cateTaxonomyId = $anId;
            $articleModel = DWModelArticle::sharedModel();
            $articleList = $articleModel->getByTermTaxonomyId($cateTaxonomyId, $safeCount, $page, $offset);
            if (!is_array($articleList))
            {
                DWLibError::throwE404Exception("No article found on category of id $cateTaxonomyId, page $page offset $offset");
                return NULL;
            }

            $articleModel = DWModelArticle::sharedModel();
            $articleModel->fillArticleListWithTags($articleList);
            $articleModel->fillArticleListWithCategories($articleList);
            foreach ($articleList as $key => $article)
            {
                $articleList[$key][DWModuleFrontuiArticle::ArticleUrlKey] = DWModuleFrontuiArticle::sharedInstance($this->configs)->buildArticleUrl($article[DWModelArticle::TablePostColumnName]);
            }
            $articleListCount = count($articleList);

            $status = 'success';
            if (!is_array($articleList))
            {
                $status = 'failed';
            }

            return array(
                'status' => $status,
                'requestCount' => $count,
                'returnCount' => $articleListCount,
                'noMoreData' => (($articleListCount < $safeCount || $articleListCount == 0) ? true : false),
                'articleList' => $articleList,
            );
        }
        else if ($action == self::ActionTag)
        {
            $tagSlug = $aName;

            $termModel = DWModelTerm::sharedModel();
            $tag = $termModel->getOneTagBySlug($tagSlug);
            if (!$tag)
            {
                DWLibError::throwE404Exception("Tag $tag not exists");
                return NULL;
            }

            $tagName = $tag[DWModelTerm::TableTermColumnName];
            $articleModel = DWModelArticle::sharedModel();
            $articleList = $articleModel->getByTagSlug($tagSlug, $safeCount);
            $articleModel->fillArticleListWithTags($articleList);
            $articleModel->fillArticleListWithCategories($articleList);
            if (!$articleList)
            {
            	DWLibError::throwE404Exception("Tag $tagSlug has no article");
            	return NULL;
            }
            foreach ($articleList as &$article)
            {
                $article[DWModuleFrontuiArticle::ArticleUrlKey] = DWModuleFrontuiArticle::sharedInstance()->buildArticleUrl($article[DWModelArticle::TablePostColumnName]);
            }

            $urlQueryName = '?' . self::GETArgName .'=' . $tagSlug;
            $router = DWLibGlobals::getGlobal(DWLibGlobals::GlobalKeyDefaultRouter);
            $baseUrl = $router->makeURI($this->moduleGroup(), $this->moduleName(), self::ActionAjaxTag) . $urlQueryName;
            $baseHRef = $router->makeURI($this->moduleGroup(), $this->moduleName(), self::ActionTag) . $urlQueryName;
            $href = $baseHRef . '&' . self::GETArgCount . "=$safeCount&" . self::GETArgOffset . "=$nextLoadOffset";

            $showLoadMoreButton = false;
            if (count($articleList) >= $safeCount)
            {
                $showLoadMoreButton = true;
            }

            return array(
                'htmlTitle' => $tagName,
                'pageType' => $this->i18n->i18n('Tags'),
            	'bannerTitle' => $this->i18n->i18n('Tag') . ":\"$tagName\"",
            	'articleList' => $articleList,
                'showLoadMoreButton' => $showLoadMoreButton,
                'href' => $href,
                'loadMoreCount' => $safeCount,
            	'loadMoreOffset' => $nextLoadOffset,
            	'loadMoreBaseUrl' => $baseUrl,
                'loadMoreBaseHRef' => $baseHRef,
            );
        }
        else if ($action == self::ActionAjaxTag)
        {
            $tagSlug = $aName;
        	$articleModel = DWModelArticle::sharedModel();
        	$articleList = $articleModel->getByTagSlug($tagSlug, $safeCount, $page, $offset);
            if (!is_array($articleList))
            {
                DWLibError::throwE404Exception("Tag $tagSlug has no article(page $page offset $offset)");
                return NULL;
            }
            $articleModel->fillArticleListWithTags($articleList);
            $articleModel->fillArticleListWithCategories($articleList);
            foreach ($articleList as &$article)
            {
                $article[DWModuleFrontuiArticle::ArticleUrlKey] = DWModuleFrontuiArticle::sharedInstance()->buildArticleUrl($article[DWModelArticle::TablePostColumnName]);
            }
            $articleListCount = count($articleList);

            $status = 'success';
            if (!is_array($articleList))
            {
                $status = 'failed';
            }

        	return array(
                'status' => $status,
                'requestCount' => $count,
                'returnCount' => $articleListCount,
                'noMoreData' => (($articleListCount < $safeCount || $articleListCount == 0) ? true : false),
    			'articleList' => $articleList,
        	);
        }
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
    
    public function urlForCategoryById($id)
    {
    	if (is_numeric($id))
    	{
    		$router = DWLibGlobals::getGlobal(DWLibGlobals::GlobalKeyDefaultRouter);
    		$baseUrl = $router->makeURI($this->moduleGroup(), $this->moduleName(), self::ActionCategory);
    		return $baseUrl . '/' . self::GETArgId . '/' . $id;
    	}
    }
    
    public function urlForCategoryBySlug($slug)
    {
    	if (!empty($slug))
    	{
//     		$router = DWLibGlobals::getGlobal(DWLibGlobals::GlobalKeyDefaultRouter);
//     		$baseUrl = $router->makeURI($this->moduleGroup(), $this->moduleName(), self::ActionCategory);
//     		return $baseUrl . '/' . self::GETArgName . '/' . $slug;
    		$url = SUBSITE_DIR;
    		if (!empty($url))
    		{
    			$url = "/$url";
    		}
    		$url .= '/' . $this->moduleName() . '/' . self::ActionCategory . "/$slug";
    		return $url;
    	}
    }
    
    public function urlForTagBySlug($slug)
    {
    	if (!empty($slug))
    	{
    		$url = SUBSITE_DIR;
    		if (!empty($url))
    		{
    			$url = "/$url";
    		}
    		$url .= '/' . $this->moduleName() . '/' . self::ActionTag . "/$slug";
    		return $url;
    	}
    }

    public function thumbnailUrlForCategory($slug = 'default')
    {
        $imageName = "category_thumbnail/category_thumbnail_$slug.jpg";
        $router = DWLibGlobals::getGlobal(DWLibGlobals::GlobalKeyDefaultRouter);
        $imagePath = $router->makeResourceLocalPath(DWLibRouter::ResourceTypeImage, $imageName, $this->moduleGroup());
        if (DWLibUtility::validateFile($imagePath, false))
        {
            return $router->makeResourceURI(DWLibRouter::ResourceTypeImage, $imageName, $this->moduleGroup());
        }
        else
        {
            return $this->thumbnailUrlForCategory();
        }
    }
}

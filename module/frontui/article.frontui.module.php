<?php
class DWModuleFrontuiArticle extends DWModuleFrontuiAbstract
{
	const GETArgPostName = 'article';
	const GETArgPostId = 'id';
	
	const HTMLExt = 'html';
	
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
    						self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiArticle',
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
    						self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiArticle',
    						self::SubmoduleLayoutConfigKeyAction => self::ActionPrefix,
    				),
    				array(
    						self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiCommon',
    						self::SubmoduleLayoutConfigKeyAction => DWModuleFrontuiCommon::ActionBanner,
    				),
    				array(
    						self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiArticle',
    						self::SubmoduleLayoutConfigKeyAction => self::ActionContent,
    				),
                    array(
                        self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiComment',
                        self::SubmoduleLayoutConfigKeyAction => DWModuleFrontuiComment::ActionLoader,
                    ),
    				array(
    						self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiArticle',
    						self::SubmoduleLayoutConfigKeyAction => self::ActionSuffix,
    				),
    				array(
    						self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiFoot',
    				),
    		),
    );
    
    protected function prepareVarsForTemplate($action)
    {
    	if ($action == self::DefaultAction)
    	{
    		$articleModel = DWModelArticle::sharedModel();
    		$article = NULL;
    		
    		$getArgs = &$this->configs[self::ConfigKeyGET];
    		if (is_array($getArgs))
    		{
    			if (array_key_exists(self::GETArgPostId, $getArgs))
    			{
    				$article = $articleModel->getFullInfoById($getArgs[self::GETArgPostId]);
    			}
    			else if (array_key_exists(self::GETArgPostName, $getArgs))
    			{
    				$article = $articleModel->getFullInfoByName($getArgs[self::GETArgPostName]);
    			}
    			else if (array_key_exists(DWLibRouter::GETKeyLastComponent, $getArgs))
    			{
    				$fileName = $getArgs[DWLibRouter::GETKeyLastComponent];
    				$fileComponents = explode('.', $fileName);
    				$article = $articleModel->getFullInfoByName($fileComponents[0]);
    			}
    		}

            if (!$article)
            {
                DWLibError::throwE404Exception("No article found");
                return NULL;
            }

            //post meta
            $tmpArticleList = [&$article];
            $articleModel->fillArticleListWithTags($tmpArticleList);
            $articleModel->fillArticleListWithCategories($tmpArticleList);

            //related posts
            $relatedPosts = [];
            if ($article['categories'])
            {
                foreach ($article['categories'] as $category)
                {
                    $newerPosts = $articleModel->getRelatedBaseInfoByTermTaxonomyId($category[DWModelTerm::TableTermTaxonomyColumnTaxonomyId], $article[DWModelArticle::TablePostColumnId], true);
                    foreach ($newerPosts as $key => $newerPost)
                    {
                        $newerPosts[$key][DWModuleFrontuiArticle::ArticleUrlKey] = $this->buildArticleUrl($newerPost[DWModelArticle::TablePostColumnName]);
                    }
                    $olderPosts = $articleModel->getRelatedBaseInfoByTermTaxonomyId($category[DWModelTerm::TableTermTaxonomyColumnTaxonomyId], $article[DWModelArticle::TablePostColumnId], false);
                    foreach ($olderPosts as $key => $olderPost)
                    {
                        $olderPosts[$key][DWModuleFrontuiArticle::ArticleUrlKey] = $this->buildArticleUrl($olderPost[DWModelArticle::TablePostColumnName]);
                    }
                    $related = [
                        'next' => $newerPosts,
                        'prev' => $olderPosts
                    ];
                    array_push($relatedPosts, $related);
                }
            }

            //ad
            $ad = [
                'url' => '',
                'image' => DWModuleFrontuiArticlelist::sharedInstance()->thumbnailUrlForCategory('fake-cate-ad'),
                'list' =>[
                    DWLibAd::baiduUnionAd(DWLibAd::BaiduUnionAdArticle),
                ],
            ];
            //var_dump($ad);
    		//var_dump($article);
            $bannerTitle = $article[DWModelArticle::TablePostColumnTitle];
    		$bannerDigest = $article[DWModelArticle::TablePostColumnDate];
    		return array(
                'htmlTitle' => $bannerTitle,
    			'article' => $article,
                'related' => $relatedPosts,
                //ad
                'articleAd' => $ad,
                //for banner
                'narrowBanner' => true,
    			'bannerTitle' => $bannerTitle,
    			//'bannerDigest' => $bannerDigest,
    		);
    	}
    }
    
    protected function submodulesConfigLayout()
    {
    	return $this->subModuleLayout;
    }
    
    protected function cacheKeyForAction($action)
    {
    	if ($action == 'body')
    	{
    		$getArgs = &$this->configs[self::ConfigKeyGET];
    		if (is_array($getArgs))
    		{
    			if (array_key_exists(self::GETArgPostId, $getArgs))
    			{
    				return $action . self::GETArgPostId . $getArgs[self::GETArgPostId];
    			}
    			else if (array_key_exists(self::GETArgPostName, $getArgs))
    			{
    				return $action . self::GETArgPostName . $getArgs[self::GETArgPostName];
    			}
    		}
    	}
    	return parent::cacheKeyForAction($action);
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
    
    const ArticleUrlKey = 'url';
    public function buildArticleUrl($idOrName)
    {
//     	$router = DWLibGlobals::getGlobal(DWLibGlobals::GlobalKeyDefaultRouter);
//     	$baseUrl = $router->makeURI($this->moduleGroup(), $this->moduleName());

		$url = SUBSITE_DIR;
		if (!empty($url))
		{
			$url = "/$url";
		}
		$url .= '/' . $this->moduleName() . "/$idOrName." . self::HTMLExt;
    	return $url;
    }
}

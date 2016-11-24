<?php
class DWModuleFrontuiHomepage extends DWModuleFrontuiAbstract
{
    const ActionContentprefix = 'contentprefix';
    const ActionContentsuffix = 'contentsuffix';

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
    					self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiHomepage',
    					self::SubmoduleLayoutConfigKeyAction => self::ActionBody,
    			),
    			array(
    					self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiHtml',
    					self::SubmoduleLayoutConfigKeyAction => self::ActionSuffix,
    			),
    	),
    	self::ActionBody => array(
    		array(
    				self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiHomepage',
    				self::SubmoduleLayoutConfigKeyAction => self::ActionPrefix,
    		),
            array(
                self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiHead',
            ),
    		array(
    				self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiHomepage',
    				self::SubmoduleLayoutConfigKeyAction => self::ActionContent,
    		),
    		array(
    				self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiFoot',
    		),
            array(
                self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiHomepage',
                self::SubmoduleLayoutConfigKeyAction => self::ActionSuffix,
            ),
    	),
    	self::ActionContent => array(
            array(
                self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiHomepage',
                self::SubmoduleLayoutConfigKeyAction => self::ActionContentprefix,
            ),
            array(
                self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiCommon',
                self::SubmoduleLayoutConfigKeyAction => DWModuleFrontuiCommon::ActionSlider,
            ),
            array(
                self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiCommon',
                self::SubmoduleLayoutConfigKeyAction => DWModuleFrontuiCommon::ActionBlurb,
            ),
            array(
                self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiCommon',
                self::SubmoduleLayoutConfigKeyAction => DWModuleFrontuiCommon::ActionCateblock,
            ),
            array(
                self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiHomepage',
                self::SubmoduleLayoutConfigKeyAction => self::ActionContentsuffix,
            ),
    	),
        self::ActionContentprefix => [],
        self::ActionContentsuffix => [],
    );
    protected function submodulesConfigLayout()
    {
    	return $this->subModuleLayout;
    }

    protected static $HomePagePostTitle = 'DWPF:HOMEPAGE';
    protected static $HomePagePostMetaBlurbList = 'DWPF_BLURB_LIST';
    protected static $HomePagePostMetaCategoryList = 'DWPF_CATE_LIST';
    protected static $HomePagePostMetaSlideConfig = 'DWPF_SLIDE_LIST';
    protected static $HomePagePostMetaSlideConfigRef = 'DWPF_SLIDE_LIST_REF';



    const CategoryGridAsAD = 'DWPF_CATE_AD';
    const BlurbCateKeyBlurbImage = 'blimg';
    protected function prepareVarsForTemplate($action)
    {
        if ($action == self::DefaultAction)
        {
            $articleModel = DWModelArticle::sharedModel();
            $article = $articleModel->getFullInfoByTitle(self::$HomePagePostTitle);
            $metas = $articleModel->getMetaByPostId($article[DWModelArticle::TablePostColumnId]);
            $slideConfig = $metas[self::$HomePagePostMetaSlideConfig];
            $slideConfigRef = $metas[self::$HomePagePostMetaSlideConfigRef];
            $blurbCateSlugListStr = $metas[self::$HomePagePostMetaBlurbList];
            $blurbCateSlugList = explode(',', $blurbCateSlugListStr);
            $gridCateSlugListStr = $metas[self::$HomePagePostMetaCategoryList];
            $gridCateSlugList = explode(',', $gridCateSlugListStr);

            //slide
            ///@TODO support multi-sliders
            $articleContent = $article[DWModelArticle::TablePostColumnContent];
            if (!$slideConfig)
            {
                $slideConfig = DWLibUtility::getContentBetweenMark($slideConfigRef, ':', $articleContent);
            }
            $slideList = NULL;
            if ($slideConfig)
            {
                $commonModule = DWModuleFrontuiCommon::sharedInstance($this->configs);
                $slideList = $commonModule->parseSliderConfigJson($slideConfig, $articleContent);
            }

            //blurb
            $termModel = DWModelTerm::sharedModel();
            $blurbCateList = $termModel->getCategoriesBySlugs($blurbCateSlugList);
            foreach ($blurbCateList as $key => $blurbCate)
            {
                $blurbImage = $this->blurbImageUrlForCategory($blurbCate[DWModelTerm::TableTermColumnSlug]);
                $blurbCateList[$key][self::BlurbCateKeyBlurbImage] = $blurbImage;
            }

            //grid cate
            $adPositions = [];
            $gridBlockAds = [];
            foreach($gridCateSlugList as $key => $slug)
            {
                if ($slug == self::CategoryGridAsAD)
                {
                    $adPositions[] = $key;
                }
            }
            $gridCateList = $termModel->getCategoriesBySlugs($gridCateSlugList);
            $termModel->fillCateTreeArticleLists($gridCateList, 5);
            foreach($adPositions as $position)
            {
                $gridCateList[$position] = self::CategoryGridAsAD;
                $gridBlockAds[$position]['content'] = DWLibAd::baiduUnionAd(DWLibAd::BaiduUnionAdHomepage);
                $gridBlockAds[$position]['image'] = DWModuleFrontuiArticlelist::sharedInstance()->thumbnailUrlForCategory('fake-cate-ad');
            }

            //ad
            /*
             <script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
                        <!-- HomepageBlockAd -->
                        <ins class="adsbygoogle"
                             style="display:block"
                             data-ad-client="ca-pub-6988128944880914"
                             data-ad-slot="7625964789"
                             data-ad-format="auto"></ins>
                        <script>
                        (adsbygoogle = window.adsbygoogle || []).push({});
                        </script>
            *******
             <script type="text/javascript">var cpro_id = "u1591559";</script><script src="http://cpro.baidustatic.com/cpro/ui/c.js" type="text/javascript"></script>
             */

            return [
                'blurbCateList' => $blurbCateList,
                'gridCateList' => $gridCateList,
                'slideList' => $slideList,
                'fullWidthSlide' => true,
                'gridBlockAds' => $gridBlockAds,
            ];
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

    public function blurbImageUrlForCategory($slug = 'default')
    {
        $router = DWLibGlobals::getGlobal(DWLibGlobals::GlobalKeyDefaultRouter);
        $imageName = "category_blurb/category_blurb_$slug.png";
        $url = $router->makeResourceURI(DWLibRouter::ResourceTypeImage, $imageName, $this->moduleGroup());
        return $url;
    }
}

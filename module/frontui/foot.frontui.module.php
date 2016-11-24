<?php

class DWModuleFrontuiFoot extends DWModuleFrontuiAbstract
{
    private $subModuleLayout = array(
        self::DefaultAction => array(
        ),
    );

    protected function submodulesConfigLayout()
    {
        return $this->subModuleLayout;
    }

	public function recordShownOnce($action)
	{
		
	}
	
	protected function prepareVarsForTemplate($action)
	{
		if ($action == self::DefaultAction)
		{
			//recent posts
			$articleModel = DWModelArticle::sharedModel();
			$recentPosts = $articleModel->getRecentPosts(5);
			$articleModule = DWModuleFrontuiArticle::sharedInstance($this->defaultConfigs());
			foreach ($recentPosts as &$post)
			{
				$post['url'] = $articleModule->buildArticleUrl($post[DWModelArticle::TablePostColumnName]);
			}
			
			//tags
			$termModel = DWModelTerm::sharedModel();
			$tags = $termModel->getTags(DWModelTerm::TableTermTaxonomyColumnContentCount, 'DESC');
            $termModel->fillTagsWithCommonInfo($tags);

            $sortedTags = [];
            $maxFontSize = 24;
            $minFontSize = 10;
            $fontSizeDifference = $maxFontSize-$minFontSize;
            $tags = array_slice($tags, 0, 25);
            $termModel->buildTagsWeightByContentCount($tags);
            foreach ($tags as $tag)
            {
                $weight = $tag[DWModelTerm::TagKeyWeight];
                $tag['fontSize'] = $fontSizeDifference*$weight + $minFontSize;
                $sortedTags[$tag[DWModelTerm::TableTermTaxonomyColumnTaxonomyId]] = $tag;
            }
            ksort($sortedTags, SORT_NUMERIC);

            //links
            $linkModel = DWModelLink::sharedModel();
            $links = $linkModel->getAllPublicLinks();

            //about
            $optionModel = DWModelOption::sharedModel();
            $about = $optionModel->getOption(DWModelOption::TableOptionColumnNameValueBlogDescription);

            //ICP number
            $ICPNumber = $optionModel->getOption(DWModelOption::TableOptionColumnNameValueICPNumber);

			return array(
                'recentPosts' => $recentPosts,
                'tags' => $sortedTags,
                'links' => $links,
                'about' => $about,
                'ICPNumber' => $ICPNumber,
            );
		}
	}
	
}
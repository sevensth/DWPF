<?php
class DWModuleFrontuiFeed extends DWModuleFrontuiAbstract
{
    private $subModuleLayout = array(
    		self::DefaultAction => array(
    		),
    );
     
    protected function prepareVarsForTemplate($action)
    {
    	if ($action == self::DefaultAction)
    	{
            $articleModel = DWModelArticle::sharedModel();
            $articleList = $articleModel->getRecentPosts(50);
            $feedWriter = new DWLibFeed();
            foreach ($articleList as $article)
            {
                $feedItem = $feedWriter->createNewItem();
                $feedWriter->addItem($feedItem);
            }
            $feedData = $feedWrite->generateFeed();
            return $feedData;
    	}
    }
    
    protected function submodulesConfigLayout()
    {
    	return $this->subModuleLayout;
    }

    public function recordShownOnce($action)
    {
    }
}

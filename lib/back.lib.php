<?php
class DWLibBack
{
	public static function redirectOldURIIfNeeded($URI, $moduleConfigs)
    {
        $redirectUrl = NULL;
        $router = DWLibGlobals::getGlobal(DWLibGlobals::GlobalKeyDefaultRouter);

        //check
        if (!$redirectUrl)
        {
            $redirectUrl = self::checkSimpleArticleById($URI, $moduleConfigs, $router);
        }
        if (!$redirectUrl)
        {
            $redirectUrl = self::checkStaticArticleByName($URI, $moduleConfigs, $router);
        }
        if (!$redirectUrl)
        {
            $redirectUrl = self::checkStaticArticleByNameSimple($URI, $moduleConfigs, $router);
        }
        if (!$redirectUrl)
        {
            $redirectUrl = self::checkStaticArticleListByTag($URI, $moduleConfigs, $router);
        }
        if (!$redirectUrl)
        {
            $redirectUrl = self::checkStaticArticleListByCategory($URI, $moduleConfigs, $router);
        }

        //
        if ($redirectUrl)
        {
            DWLibError::SendHTTPStatusCodeHeader(301, $redirectUrl);
            return true;
        }
        else
        {
            return false;
        }
	}

    //http://www.dreamingwish.com/?p=2051
    protected static function checkSimpleArticleById($URI, $moduleConfigs, $router)
    {
        $redirectUrl = NULL;
        $GETArgs = $moduleConfigs[DWModuleAbstract::ConfigKeyGET];
        if (is_array($GETArgs))
        {
            //article by id
            if (array_key_exists('p', $GETArgs))
            {
                $articleUrl = $router->makeURI('frontui', 'article');
                $redirectUrl = $articleUrl . '?' . DWModuleFrontuiArticle::GETArgPostId . '=' . $GETArgs['p'];
            }
        }
        return $redirectUrl;
    }

    //http://www.dreamingwish.com/dream-2012/the-of-of-gcd-tutorial.html
    protected static function checkStaticArticleByName($URI, $moduleConfigs, $router)
    {
        $redirectUrl = NULL;
        if (preg_match('/.*\/dream-\d+\/(.+\.html.*)/i', $URI, $matches))
        {
            $articleUrl = $router->makeURI('frontui', 'article');
            $redirectUrl = $articleUrl . '/' . $matches[1];
        }
        return $redirectUrl;
    }

    //http://www.dreamingwish.com/%E5%85%B3%E4%BA%8E%E6%A2%A6%E7%BB%B4#dreamingwish-faq
    protected static function checkStaticArticleByNameSimple($URI, $moduleConfigs, $router)
    {
        $redirectUrl = NULL;
        if (preg_match('/.*\/([^?#&\/]+.*)/', $URI, $matches))
        {
            $articleUrl = $router->makeURI('frontui', 'article');
            $redirectUrl = $articleUrl . '/' . $matches[1];
        }
        return $redirectUrl;
    }

    //http://www.dreamingwish.com/dream-tag/opengl
    protected static function checkStaticArticleListByTag($URI, $moduleConfigs, $router)
    {
        $redirectUrl = NULL;
        if (preg_match('/.*\/dream-tag\/([^?#&]+.*)/i', $URI, $matches))
        {
            $articleUrl = $router->makeURI('frontui', 'articlelist', 'tag');
            $redirectUrl = $articleUrl . '/' . $matches[1];
        }
        return $redirectUrl;
    }

    //http://www.dreamingwish.com/dream-category/toturial/ios-core-animation-guide
    protected static function checkStaticArticleListByCategory($URI, $moduleConfigs, $router)
    {
        $redirectUrl = NULL;
        if (preg_match('/.*\/dream-category\/([^?#&]+.*)/i', $URI, $matches))
        {
            $articleUrl = $router->makeURI('frontui', 'articlelist', 'category');
            $redirectUrl = $articleUrl . '/' . $matches[1];
        }
        return $redirectUrl;
    }
}

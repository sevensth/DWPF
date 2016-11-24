<?php

require_once 'feed/Item.php';
require_once 'feed/Feed.php';

class DWLibFeed extends Feed
{
    public function __construct()
    {
        parent::__construct(self::RSS2);

        $optionModel = DWModelOption::sharedModel();
        $this->setTitle($optionModel->getOption(DWModelOption::TableOptionColumnNameValueBlogName));
        //@TODO read link from db
        $this->setLink('http://www.dreamingwish.com');
        $this->setDescription($optionModel->getOption(DWModelOption::TableOptionColumnNameValueBlogDescription));
        $this->setChannelElement('language', $optionModel->getOption(DWModelOption::TableOptionColumnNameValueBlogCharset));
    }
}



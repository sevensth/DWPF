<?php
class DWModuleFrontuiComment extends DWModuleFrontuiAbstract
{
    const ActionLoader = 'loader';
    const ActionReply = 'reply';
    const ActionAjaxreply = 'ajaxreply';

    const ActionAjaxContent = 'ajaxcontent';
    const ActionCommentList = 'list';
    const ActionCommentForm = 'form';

    private $subModuleLayout = [
        self::ActionLoader => [],
        self::ActionReply => [],
        self::ActionAjaxreply => [],

        self::ActionAjaxContent => [
            [
                self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiComment',
                self::SubmoduleLayoutConfigKeyAction => self::ActionPrefix,
            ],
            [
                self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiComment',
                self::SubmoduleLayoutConfigKeyAction => self::ActionCommentList,
            ],
            [
                self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiComment',
                self::SubmoduleLayoutConfigKeyAction => self::ActionCommentForm,
            ],
            [
                self::SubmoduleLayoutConfigKeyModule => 'DWModuleFrontuiComment',
                self::SubmoduleLayoutConfigKeyAction => self::ActionSuffix,
            ],
        ],
    ];

    protected function submodulesConfigLayout()
    {
        return $this->subModuleLayout;
    }

    public function recordShownOnce($action)
    {
    }

    const GetParamPostId = 'p';
    const SubmitHumanCheckLevel = 7;
    protected function prepareVarsForTemplate($action)
    {
        $getArgs = &$this->configs[self::ConfigKeyGET];

        if ($action == self::ActionLoader)
        {
            $ajaxCommentsLoadUrl = $this->ajaxCommentsLoadUrlByPostId('');
            return [
                'ajaxCommentsLoadUrl' => $ajaxCommentsLoadUrl,
            ];
        }
        else if ($action == self::ActionAjaxContent)
        {
            $postId = intval($getArgs[self::GetParamPostId]);

            $commentTree = NULL;
            $commentCount = 0;
            if ($postId > 0)
            {
                $commentModel = DWModelComment::sharedModel();
                $returnArr = $commentModel->getCommentsTreeByPostId($postId);
                $commentTree = $returnArr[0];
                $commentCount = intval($returnArr[1]);
            }

            $formActionUrl = $this->ajaxCommentSubmitUrl();

            //human check
            $diffIndex = rand(0, self::SubmitHumanCheckLevel-1);
            $humanCheckColors = $this->htmlHexColorArrayForHumanCheck(self::SubmitHumanCheckLevel, $diffIndex);
            $colorCheckList = $this->saltArrayForColorfyHumanCheck(self::SubmitHumanCheckLevel);
            $humanCheckCode = $this->generateSubmitHumanCheck($colorCheckList[$diffIndex]);

            return [
                'postId' => $postId,
                'commentCount' => $commentCount,
                'commentList' => $commentTree,
                'formActionUrl' => $formActionUrl,
                'humanCheckInfo' => [
                    'colors' => $humanCheckColors,
                    'check' => $colorCheckList,
                    'code' => $humanCheckCode,
                ],
            ];
        }
        else if ($action == self::ActionReply)
        {

        }
        else if ($action == self::ActionAjaxreply)
        {
            $HTTPPOSTArgs = $this->configs[self::ConfigKeyPOST];

            //check human
            if (!$this->checkSubmitIsByHuman($HTTPPOSTArgs))
            {
                return [
                    'status' => 'error',
                    'message' => $this->i18n->i18n('Please pick the block with different color(warm/cold), then submit'),
                    'relatedField' => 'dw_human_check',
                ];
            }

            $validation = $this->validatedCommentPostFields($HTTPPOSTArgs);
            $returnArr = NULL;
            if ($validation['valid'])
            {
                $ret = NULL;
                try
                {
                    $ret = DWLibRequestForward::forwardCommentSubmit($validation['postParam'], $this->configs[self::ConfigKeyCOOKIE], $this->configs[self::ConfigKeySERVER]);
                    if ($ret['message'])
                    {
                        $ret['message'] = $this->i18n->i18n($ret['message']);
                    }
                }
                catch(Exception $exception)
                {
                    $ret['status'] = DWLibRequestForward::CommentResponseError;
                    $ret['message'] = $exception->getMessage();
                }

                $returnArr = [
                    'status' => $ret['status'],
                    'message' => $ret['message'],
                    'relatedField' => $ret['field'],
                ];
            }
            else
            {
                $returnArr = [
                    'status' => DWLibRequestForward::CommentResponseError,
                    'message' => $validation['message'],
                    'relatedField' => $validation['field'],
                ];
            }

            return $returnArr;
        }
    }

    public function validatedCommentPostFields($postParams)
    {
        $validationFail = ['valid' => false];
        $validationSuccess = ['valid' => true];

        $author = isset($postParams[DWLibRequestForward::CommentPostArgNameAuthor]) ? trim($postParams[DWLibRequestForward::CommentPostArgNameAuthor]) : '';
        //author and comment are required.
        if (empty($author))
        {
            $validationFail['message'] = $this->i18n->i18n('Comment requires a name');
            $validationFail['field'] = 'author';
            return $validationFail;
        }
        if (mb_strlen($author) > 20)
        {
            $validationFail['message'] = $this->i18n->i18n('Your name is too long');
            $validationFail['field'] = 'author';
            return $validationFail;
        }

        $comment = isset($postParams[DWLibRequestForward::CommentPostArgNameComment]) ? trim($postParams[DWLibRequestForward::CommentPostArgNameComment]) : '';
        //author and comment are required.
        if (empty($comment))
        {
            $validationFail['message'] = $this->i18n->i18n('Comment should not be empty');
            $validationFail['field'] = 'comment';
            return $validationFail;
        }
        if (mb_strlen($comment) > 2000)
        {
            $validationFail['message'] = $this->i18n->i18n('You really have so much to say? Come on, make it shorter');
            $validationFail['field'] = 'comment';
            return $validationFail;
        }

        $email = isset($postParams[DWLibRequestForward::CommentPostArgNameEmail]) ? trim($postParams[DWLibRequestForward::CommentPostArgNameEmail]) : '';
        if (mb_strlen($email) > 255)
        {
            $validationFail['message'] = $this->i18n->i18n('Your E-mail is too long');
            $validationFail['field'] = 'email';
            return $validationFail;
        }

        $url = isset($postParams[DWLibRequestForward::CommentPostArgNameUrl]) ? trim($postParams[DWLibRequestForward::CommentPostArgNameUrl]) : '';
        if (mb_strlen($url) > 255)
        {
            $validationFail['message'] = $this->i18n->i18n('Your website address is too long');
            $validationFail['field'] = 'url';
            return $validationFail;
        }

        $postId = isset($postParams[DWLibRequestForward::CommentPostArgNamePostId]) ? intval($postParams[DWLibRequestForward::CommentPostArgNamePostId]) : 0;
        $commentParentId = isset($postParams[DWLibRequestForward::CommentPostArgNameCommentParent]) ? intval($postParams[DWLibRequestForward::CommentPostArgNameCommentParent]) : 0;

        $validationSuccess['postParam'] = [
            DWLibRequestForward::CommentPostArgNameAuthor => $author,
            DWLibRequestForward::CommentPostArgNameComment => $comment,
            DWLibRequestForward::CommentPostArgNameEmail => $email,
            DWLibRequestForward::CommentPostArgNameUrl => $url,
            DWLibRequestForward::CommentPostArgNamePostId => $postId,
            DWLibRequestForward::CommentPostArgNameCommentParent => $commentParentId,
        ];
        return $validationSuccess;
    }

    public function ajaxCommentSubmitUrl()
    {
        $url = SUBSITE_DIR;
        if (!empty($url))
        {
            $url = "/$url";
        }
        $ajaxCommentSubmitUrl = $url . '/' . $this->moduleGroup() . "/comment/" . self::ActionAjaxreply;
        return $ajaxCommentSubmitUrl;
    }

    /*
     * @TODO: use the router
     */
    public function ajaxCommentsLoadUrlByPostId($id)
    {
        $url = SUBSITE_DIR;
        if (!empty($url))
        {
            $url = "/$url";
        }
        $ajaxCommentsLoadUrl = $url . '/' . $this->moduleGroup() . "/comment/" . self::ActionAjaxContent . '?' . self::GetParamPostId . "=$id";
        return $ajaxCommentsLoadUrl;
    }

    protected function htmlHexColorArrayForHumanCheck($count, $diffIndex)
    {
        //自 PHP 4.2.0 起，不再需要用 srand() 或 mt_srand() 函数给随机数发生器播种，现在已自动完成
        $warmOrCold = rand(0,1);
        $colorArray = [];
        for ($index = 0; $index < $count; $index++)
        {
            $colorHex = DWLibUtility::getRandomHtmlHexColor(($index == $diffIndex ? $warmOrCold : !$warmOrCold));
            array_push($colorArray, $colorHex);
        }
        return $colorArray;
    }

    protected static $CommentHumanCheckSalt = '9ec1500fc1d017a7a99dab43c06da02b9a58af9ee528aded75c512f2ec44384f';
    protected function saltArrayForColorfyHumanCheck($count)
    {
        $saltArray = [];
        for ($index = 0; $index < $count; $index++)
        {
            $salt = hash('md5', self::$CommentHumanCheckSalt . rand());
            array_push($saltArray, $salt);
        }
        return $saltArray;
    }

    protected function checkSubmitIsByHuman($HTTPPOSTArgs)
    {
        if(!isset($HTTPPOSTArgs['human_check_hash']) || !isset($HTTPPOSTArgs['human_check_value']))
        {
            return false;
        }

        $human_check_hash = $HTTPPOSTArgs['human_check_hash'];
        $human_check_value = $HTTPPOSTArgs['human_check_value'];
        $targetCode = $this->generateSubmitHumanCheck($human_check_value);
        return ($human_check_hash == $targetCode);
    }

    protected function generateSubmitHumanCheck($seed)
    {
        $humanCheckCode = hash('sha256', GLOBAL_SALT . self::$CommentHumanCheckSalt . $seed);
        return $humanCheckCode;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: songyue
 * Date: 14-7-21
 * Time: 上午11:27
 */
class DWLibRequestForward
{
    const CommentPostURI = '/wp-comments-post.php';

    const CommentPostArgNameAuthor = 'author';
    const CommentPostArgNameEmail = 'email';
    const CommentPostArgNameUrl = 'url';
    const CommentPostArgNameComment = 'comment';
//    const CommentPostArgNameSubmit = 'submit';
    const CommentPostArgNamePostId = 'comment_post_ID';
    const CommentPostArgNameCommentParent = 'comment_parent';

    const CommentResponseSuccess = 'success';
    const CommentResponseError = 'error';

    static public function forwardCommentSubmit($postParams, $cookies, $server)
    {
        $result = [];
//        return [
//            'status' => 'error',
//            'message' => serialize($server),
//        ];

        $dwpbUrl = DWModelOption::sharedModel()->getOption("siteurl");
        $url = $dwpbUrl . self::CommentPostURI;
        $userIP = $server['REMOTE_ADDR'];
        $userIP = "#URL:$userIP:URL";
        $ua = isset($server['HTTP_USER_AGENT']) ? substr($server['HTTP_USER_AGENT'], 0, 254-strlen($userIP)) : '';
        $ua .= $userIP;
        $options = [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                'Accept' => '*/*',
            ],
            'useragent' => $ua,
            'connecttimeout' => 5,
            'timeout' => 10,
            'redirect' => 0,//Wordpress will redirect page after insert the comment, but we don't want it.
        ];
        $commentRequest = new HttpRequest($url, HTTP_METH_POST, $options);
        $commentRequest->setPostFields($postParams);
        $message = $commentRequest->send();
        $httpResponseCode = $message->getResponseCode();
        switch ($httpResponseCode)
        {
            //wordpress comment returns 301 or 302 for success, because it's going to refresh the page.
            case 301:
            case 302:
                $result = [
                    'status' => self::CommentResponseSuccess,
                ];
                break;

            case 409:
                $result = [
                    'status' => self::CommentResponseError,
                    'message' => 'Duplicated comment',
                ];
                break;

            ///@TODO: i think there's a bug because currently every comment request is send from dwpf actually.
            case 429:
                $result = [
                    'status' => self::CommentResponseError,
                    'message' => 'Too many comment requests',
                ];
                break;
            ///@TODO: handle more details.
            default:
                $result = [
                    'status' => self::CommentResponseError,
                    'message' => $message->toString(),
                ];
        }

        return $result;
    }

}
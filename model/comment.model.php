<?php
/**
 * Created by PhpStorm.
 * User: seven
 * Date: 14-7-3
 * Time: 下午9:54
 */
class DWModelComment extends DWModelAbstract
{
    const TableComments = 'comments';

    const TableCommentsColumnId = 'comment_ID';
    const TableCommentsColumnPostId = 'comment_post_ID';
    const TableCommentsColumnAuthor = 'comment_author';
    const TableCommentsColumnAuthorEmail = 'comment_author_email';
    const TableCommentsColumnAuthorUrl = 'comment_author_url';
    const TableCommentsColumnAuthorIP = 'comment_author_IP';
    const TableCommentsColumnDate = 'comment_date';
    const TableCommentsColumnDateGMT = 'comment_date_gmt';
    const TableCommentsColumnKarma = 'comment_karma';
    const TableCommentsColumnApproved = 'comment_approved';
    const TableCommentsColumnAgent = 'comment_agent';
    const TableCommentsColumnType = 'comment_type';
    const TableCommentsColumnParent = 'comment_parent';
    const TableCommentsColumnUserId = 'user_id';
    const TableCommentsColumnContent = 'comment_content';

    const TableCommentsColumnApprovedValueApproved = 1;

    protected $commentsTableName;
    protected $userTableName;
//    protected $postTableName;

    public function __construct($dbInfo, $tablePrefix = '')
    {
        parent::__construct($dbInfo, $tablePrefix);
        $this->commentsTableName = $this->tablePrefix . self::TableComments;
        $this->userTableName = $this->tablePrefix . DWModelUser::TableUsers;
//        $this->postTableName = $this->tablePrefix . DWModelArticle::TablePost;
    }

    public function getCommentsByPostId($id)
    {
        $select = DWLibSqlSelect::select($this->commentsTableName, '*');
        $select->leftJoin($this->userTableName,  [ '=' => [
            [$this->userTableName, DWModelUser::TableUsersColumnId],
            [$this->commentsTableName, self::TableCommentsColumnUserId]
        ]]);
//        $select->leftJoin($this->postTableName,  [ '=' => [
//            [$this->postTableName, DWModelArticle::TablePostColumnId],
//            [$this->commentsTableName, self::TableCommentsColumnPostId]
//        ]]);
        $select->where(['and' => [
            [
                '=',
                [$this->commentsTableName, self::TableCommentsColumnApproved],
                self::TableCommentsColumnApprovedValueApproved,
            ],
            [
                '=',
                [$this->commentsTableName, self::TableCommentsColumnPostId],
                ':postId'
            ]
        ]]);
        $select->orderBy([$this->commentsTableName => self::TableCommentsColumnDate]);

        $queryPrepare = $select->getSQL();
        $statement = $this->dbConnection->prepare($queryPrepare);
        if ($statement)
        {
            $statement->execute([':postId' => $id]);
            self::increaseDBSelectTimes();
            return $this->fetchAllAsAsscociationArray($statement);
        }
    }

    const CommentsTreeKeySubComments = 'subComments';
    public function getCommentsTreeByPostId($id)
    {
        $commentList = $this->getCommentsByPostId($id);
        if ($commentList)
        {
            $commentTree = DWLibUtility::makeTreeFromFlatList($commentList, self::TableCommentsColumnId, self::TableCommentsColumnParent, self::CommentsTreeKeySubComments, 0);
            return [$commentTree, count($commentList)];
        }
    }
}
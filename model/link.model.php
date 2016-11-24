<?php
/**
 * Created by PhpStorm.
 * User: seven
 * Date: 14-6-1
 * Time: 上午8:54
 */
class DWModelLink extends DWModelAbstract
{
    const TableLink = 'links';
    const TableLinkColumnUrl = 'link_url';
    const TableLinkColumnName = 'link_name';
    const TableLinkColumnDescription = 'link_description';
    const TableLinkColumnVisible = 'link_visible';
    const TableLinkColumnUpdated = 'link_updated';

    const TableLinkColumnVisibleValueYes = 'Y';
    const TableLinkColumnVisibleValueNo = 'N';


    protected $linkTableName;

    public function __construct($dbInfo, $tablePrefix = '')
    {
        parent::__construct($dbInfo, $tablePrefix);
        $this->linkTableName = $this->tablePrefix . self::TableLink;
    }

    public function getAllPublicLinks()
    {
        $select = DWLibSqlSelect::select($this->linkTableName);
        $select->where(['=' => [
            self::TableLinkColumnVisible,
            '?'
        ]]);
        $select->orderBy(self::TableLinkColumnUpdated, 'DESC');
        $prepareSQL = $select->getSQL();
        $statement = $this->dbConnection->prepare($prepareSQL);
        if ($statement)
        {
            $statement->execute([self::TableLinkColumnVisibleValueYes]);
            self::increaseDBSelectTimes();
            return $this->fetchAllAsAsscociationArray($statement);
        }
    }
}
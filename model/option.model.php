<?php
/**
 * Created by PhpStorm.
 * User: seven
 * Date: 14-6-1
 * Time: 上午9:21
 */
class DWModelOption extends DWModelAbstract
{
    const TableOption = 'options';
    const TableOptionColumnName = 'option_name';
    const TableOptionColumnValue = 'option_value';

    const TableOptionColumnNameValueBlogDescription = 'blogdescription';
    const TableOptionColumnNameValueBlogName = 'blogname';
    const TableOptionColumnNameValueBlogKeyword = 'blogkeyword';
    const TableOptionColumnNameValueBlogCharset = 'blog_charset';
    const TableOptionColumnNameValueICPNumber = 'zh_cn_l10n_icp_num';

    protected $optionTableName = self::TableOption;

    public function __construct($dbInfo, $tablePrefix = '')
    {
        parent::__construct($dbInfo, $tablePrefix);
        $this->optionTableName = $this->tablePrefix . self::TableOption;
    }

    public function getAllOptions()
    {
        static $options = NULL;
        if ($options) return $options;

        //load
        $select = DWLibSqlSelect::select($this->optionTableName);
        $prepareSQL = $select->getSQL();
        $statement = $this->dbConnection->prepare($prepareSQL);
        if ($statement)
        {
            $statement->execute();
            self::increaseDBSelectTimes();
            $rawOptions = $this->fetchAllAsAsscociationArray($statement);
            if ($rawOptions)
            {
                $options = [];
                foreach ($rawOptions as $rawOption)
                {
                    $options[$rawOption[self::TableOptionColumnName]] = $rawOption[self::TableOptionColumnValue];
                }
            }
            return $options;
        }
    }

    public function getOption($name)
    {
        $options = $this->getAllOptions();
        return $options[$name];
    }
}

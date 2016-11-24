<?php
class DWLibUtility
{
    public static function removeItem($item)
    {
        unlink($item);
        rmdir($item);
    }

    public static function loadFileContent($file)
    {
        if (self::validateFile($file))
        {
            return file_get_contents($file);
        }
    }

    public static function validateDirectory($dir)
    {
        if (is_string($dir) && strlen($dir) > 0)
        {
            clearstatcache();
            if(file_exists($dir) && is_dir($dir) && is_readable($dir) && is_writable($dir))
            {
                return true;
            }
        }
        return false;
    }

    public static function createDirectory($dir, $force = false)
    {
        if (self::validateDirectory($dir))
        {
            return true;
        }
        else
        {
            if ($force)
            {
                self::removeItem($dir);
            }
            return mkdir($dir, 0755, true);
        }
        return false;
    }

    public static function validateFile($file, $requireWrite = true)
    {
        if (is_string($file) && strlen($file) > 0)
        {
            clearstatcache();
            if(file_exists($file) && !is_dir($file) && is_readable($file))
            {
            	if (!$requireWrite || is_writable($file))
            	{
            		return true;
            	}
            }
        }
        return false;
    }

    public static function createFile($file, $force = false)
    {
        if (self::validateFile($file))
        {
            return true;
        }
        else
        {
            if ($force)
            {
                self::removeItem($file);
            }
            $fp = fopen($file, 'r+');
            if ($fp)
            {
                fclose($fp);
                return true;
            }
        }
        return false;
    }

    public static function appendPathComponent($path, $component)
    {
        return $path . DIRECTORY_SEPARATOR . $component;
    }
    
    /*
        array (
            1 => array(
                'id' => 1,
                'parentId' => NULL,
                'name' => 'Menu',
                'childNodes' => array(
                    2 => array(
                        'id' => 2,
                        'parentId' => 1,
                        'name' => 'Item 1-1',
                        'childNodes' => array (
                            4 => array (
                                'id' => 4,
                                'parentId' => 2,
                                'name' => 'Item 1-2',
                                'childNodes' => array (
                                ),
                            ),
                        ),
                    ),
                    3 =>  array (
                        'id' => 3,
                        'parentId' => 1,
                        'name' => 'Item 2-1',
                        'childNodes' => array (
                        ),
                    ),
                ),
            ),
        )
     */
    public static function makeTreeFromFlatList(array $flat, $idField, $parentIdField, $childNodesField, $rootId)
    {
        $indexed = array();
        // first pass - get the array indexed by the primary id  
        foreach ($flat as $row) {
            $indexed[$row[$idField]] = $row;
            $indexed[$row[$idField]][$childNodesField] = array();
        }

        //second pass  
        $root = NULL;
        $rootList = array();
        foreach ($indexed as $id => $row)
        {
            $indexed[$row[$parentIdField]][$childNodesField][$id] =& $indexed[$id];
            if ($id == $rootId)
            {
                $root = $id;
            }
            if ($row[$parentIdField] == $rootId)
            {
                $rootList[$id] = '';
            }
        }

        if ($root)
        {
            return array($root => $indexed[$root]);
        }
        else
        {
            foreach ($rootList as $id => $value)
            {
                $rootList[$id] = $indexed[$id];
            }
            return $rootList;
        }
    }

    public static function findNodeFromTree(array $tree, $key, $value, $childrenListKey)
    {
        if (array_key_exists($childrenListKey, $tree))
        {
            if (array_key_exists($key, $tree) && $tree[$key] == $value)
            {
                return $tree;
            }
            else
            {
                return self::findNodeFromTree($tree[$childrenListKey], $key, $value, $childrenListKey);
            }
        }
        else
        {
            foreach ($tree as $node)
            {
                $subTree = self::findNodeFromTree($node, $key, $value, $childrenListKey);
                if ($subTree)
                {
                    return $subTree;
                }
            }
        }
    }
    
    public static function accumulateSubNodeValueOfTree(&$tree, $accumulateKey, $childrenListKey)
    {
        $accumulate = 0;
        if (is_array($tree))
        {
            if (array_key_exists($childrenListKey, $tree))
            {
                if (array_key_exists($accumulateKey, $tree))
                {
                    $accumulate = $tree[$accumulateKey];
                }
                $accumulate += self::accumulateSubNodeValueOfTree($tree[$childrenListKey], $accumulateKey, $childrenListKey);
                $tree[$accumulateKey] = $accumulate;
            }
            else
            {
                foreach ($tree as &$node)
                {
                    $accumulate += self::accumulateSubNodeValueOfTree($node, $accumulateKey, $childrenListKey);
                }
            }
        }
        return $accumulate;
    }

    public static function arrayApplyDefaults(array &$destArray, array $defaultsArray)
    {
        foreach ($defaultsArray as $key => $default)
        {
            if (!array_key_exists($key, $destArray))
            {
                $destArray[$key] = $default;
            }
            else
            {
                if (is_array($default) && is_array($destArray[$key]))
                {
                    self::arrayApplyDefaults($destArray[$key], $default);
                }
            }
        }
    }

    ///@TODO encode read from model options.
    public static function getContentBetweenMark($mark, $delimiter, $str, $trim = true)
    {
        $beginPos = mb_strpos($str, $mark.$delimiter);
        if ($beginPos !== false)
        {
            $beginPos += mb_strlen($mark.$delimiter);
            $endPos = mb_strpos($str, $delimiter . $mark, $beginPos);
            if ($endPos > $beginPos)
            {
                $result =  mb_substr($str, $beginPos, $endPos-$beginPos);
                if ($result && $trim)
                {
                    $result = trim($result);
                }
                return $result;
            }
        }
        return NULL;
    }

    public static function srand()
    {
        function make_seed()
        {
            list($usec, $sec) = explode(' ', microtime());
            return (float) $sec + ((float) $usec * 100000);
        }
        srand(make_seed());
    }

    public static function getRandomHtmlHexColor($isWarm, $difference = 0x80)
    {
        $lowComponent1 = rand(0, min(0xFF-$difference, 0x88));
        $middleComponent = rand(0, min(0xFF-$difference, 0x88));
        $highComponent = rand(max($lowComponent1, $middleComponent)+$difference, 0xFF);
        $highComponent = max($highComponent, 0x88);
        if($isWarm)
        {
            $color = ($highComponent<<16) + ($middleComponent<<8) + $lowComponent1;
        }
        else
        {
            $color = ($lowComponent1<<16) + ($middleComponent<<8) + $highComponent;
        }
        $colorHex = sprintf('%06X', $color);
        return $colorHex;
    }

    public static function isHTTPS() {
        return (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off');
    }
}

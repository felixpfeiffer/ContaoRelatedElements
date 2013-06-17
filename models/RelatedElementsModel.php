<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2013 Leo Feyer
 *
 * @package   relatedelements
 * @author    Felix Pfeiffer : Neue Medien
 * @license   LGPL
 * @copyright 2013 Felix Pfeiffer : Neue Medien
 */


/**
 * Namespace
 */
namespace Contao;


/**
 * Class ModelRelatedElements
 *
 * @copyright  2013 Felix Pfeiffer : Neue Medien
 * @author     Felix Pfeiffer : Neue Medien
 * @package    Devtools
 */
class RelatedElementsModel extends \Model
{
    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_content';

    /**
     * Find all child content elements by their mother ID and parent table
     *
     * @param integer $intMid         The ID of the mother element
     * @param string  $strParentTable The parent table name
     * @param array   $arrOptions     An optional options array
     *
     * @return \Model\Collection|null A collection of models or null if there are no content elements
     */
    public static function findChildsByMidAndTable($intMid, $strParentTable, array $arrOptions=array())
    {

        // Also handle empty ptable fields (backwards compatibility)
        if ($strParentTable == 'tl_article')
        {
            $arrColumns = array("mid=? AND (ptable=? OR ptable='')");
        }
        else
        {
            $arrColumns = array("mid=? AND ptable=?");
        }

        if (!BE_USER_LOGGED_IN)
        {
            $arrColumns[] = "invisible=''";
        }

        $arrColumns[] = "id!=?";

        if (!isset($arrOptions['order']))
        {
            $arrOptions['order'] = "sorting";
        }

        return static::findBy($arrColumns, array($intMid, $strParentTable, $intMid), $arrOptions);
    }

    /**
     * Find all child content elements by their parent ID, mother ID and parent table
     *
     * @param integer $intMid         The ID of the mother element
     * @param integer $intPid         The ID of the parent element
     * @param string  $strParentTable The parent table name
     * @param array   $arrOptions     An optional options array
     *
     * @return \Model\Collection|null A collection of models or null if there are no content elements
     */
    public static function findChildsByPidMidAndTable($intMid, $intPid, $strParentTable, array $arrOptions=array())
    {
        $t = static::$strTable;

        // Also handle empty ptable fields (backwards compatibility)
        if ($strParentTable == 'tl_article')
        {
            $arrColumns = array("$t.pid=? AND $t.mid=? AND (ptable=? OR ptable='')");
        }
        else
        {
            $arrColumns = array("$t.pid=? AND $t.mid=? AND ptable=?");
        }

        if (!BE_USER_LOGGED_IN)
        {
            $arrColumns[] = "$t.invisible=''";
        }

        if (!isset($arrOptions['order']))
        {
            $arrOptions['order'] = "$t.sorting";
        }

        return static::findBy($arrColumns, array($intPid, $intMid, $strParentTable), $arrOptions);
    }

}

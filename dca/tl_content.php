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
 * Table tl_content
 */
$GLOBALS['TL_DCA']['tl_content']['config']['sql']['keys']['mid'] = 'index';

$GLOBALS['TL_DCA']['tl_content']['fields']['mid'] = array(
    'sql'   => "int(10) unsigned NOT NULL default '0'"
);
$GLOBALS['TL_DCA']['tl_content']['fields']['childs'] = array(
    'sql'   => "varchar(255) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_content']['fields']['relatedsorting'] = array(
    'sql'   => "int(2) unsigned NOT NULL default '0'"
);


$GLOBALS['TL_DCA']['tl_content']['config']['onsubmit_callback'][] = array('tl_related_elements','createRelatedElements');
$GLOBALS['TL_DCA']['tl_content']['config']['ondelete_callback'][] = array('tl_related_elements','deleteRelatedElements');
$GLOBALS['TL_DCA']['tl_content']['config']['oncopy_callback'][] = array('tl_related_elements','copyRelatedElements');


class tl_related_elements extends tl_content
{

    public function createRelatedElements(DataContainer $dc)
    {

        // Return if there is no active record (override all)
        if (!$dc->activeRecord)
        {
            return;
        }


        $key = $dc->activeRecord->type;
        $intId = $dc->activeRecord->id;
        $intPid = $dc->activeRecord->pid;
        $intSorting = $dc->activeRecord->sorting;
        $pTable = $dc->activeRecord->ptable;

        if($GLOBALS['RELATED_ELEMENTS'] && is_array($GLOBALS['RELATED_ELEMENTS']) && array_key_exists($dc->activeRecord->type,$GLOBALS['RELATED_ELEMENTS']))
        {

            $arrSetBase = array(
                'tstamp'    => time(),
                'mid'       => $intId,
                'pid'       => $intPid,
                'ptable'    => $pTable

            );

            $arrSets = $this->prepareSets($key);

            $objChilds = RelatedElementsModel::findChildsByMidAndTable($intId,$pTable);


            switch ($key)
            {
                case 'accordionStart':
                case 'sliderStart':

                    // Write the initial record
                    if($objChilds === null)
                    {

                        $this->moveFollowingElements($intPid,$pTable,$intSorting,1);
                        $i=1;
                        $arrChilds = array();
                        foreach($arrSets as $k=>$v)
                        {
                            $arrSet = $arrSetBase;
                            foreach($v as $col)
                            {
                                $arrSet[$col]   = $dc->activeRecord->$col;
                            }
                            $arrSet['type'] = $k;
                            $arrSet['relatedsorting'] = $i;
                            $arrSet['sorting'] = ($intSorting + ($i*128));
                            $objElement = new \RelatedElementsModel();
                            $arrChilds[] = $objElement->setRow($arrSet)->save()->id;
                            $i++;
                        }

                        $this->Database->prepare("UPDATE tl_content %s WHERE id=?")->set(array('tstamp'=>time(),'childs'=>$arrChilds,'mid'=>$intId))->execute($intId);


                    }

                    // Update all child records
                    else
                    {
                        while($objChilds->next())
                        {
                            $type = $objChilds->type;
                            $arrPalette = $arrSets[$type];
                            $arrSet = array();
                            foreach($arrPalette as $col)
                            {
                                $objChilds->$col   = $dc->activeRecord->$col;
                            }
                            $objChilds->tstamp = time();
                            $objChilds->type = $type;
                            $objChilds->save();

                        }

                    }
                    break;
                default:
                    // HOOK: add function for other elements
                    if (isset($GLOBALS['TL_HOOKS']['createRelatedElements']) && is_array($GLOBALS['TL_HOOKS']['createRelatedElements']))
                    {
                        foreach ($GLOBALS['TL_HOOKS']['createRelatedElements'] as $callback)
                        {
                            $this->import($callback[0]);
                            $this->$callback[0]->$callback[1]($objChilds,$dc->activeRecord,$arrSets,$intSorting,$key);
                        }
                    }
                    break;
            }
        }
        else if(!array_key_exists($dc->activeRecord->type,$GLOBALS['RELATED_ELEMENTS']) && $dc->activeRecord->childs != '')
        {
            $this->Database->prepare("DELETE FROM tl_content WHERE id!=? AND mid=? AND pid=? AND ptable=?")->execute($intId,$intId,$intPid,$pTable);
        }

    }

    public function deleteRelatedElements(DataContainer $dc)
    {
        // Return if there is no active record (override all)
        if (!$dc->activeRecord)
        {
            return;
        }

        $intId = $dc->activeRecord->id;
        $pTable = $dc->activeRecord->ptable;

        if(array_key_exists($dc->activeRecord->type,$GLOBALS['RELATED_ELEMENTS']))
        {
            $objChilds = RelatedElementsModel::findChildsByMidAndTable($intId,$pTable);

            if($objChilds === null)
            {
                return;
            }
            else
            {
                while($objChilds->next())
                {
                    $objChilds->delete();
                }
            }
        }

    }


    /**
     * Get all fields from the mother palette that have to be set in the child palettes
     * @param $key
     * @return array
     */
    protected function prepareSets($key)
    {

        $arrChilds = $GLOBALS['RELATED_ELEMENTS'][$key];

        $arrPalettes = array();

        // Get all columns from the mother palette
        $strPalette = preg_replace("/(\{[\w\:]+\},)/","",$GLOBALS['TL_DCA']['tl_content']['palettes'][$key]);
        $arrMother = preg_split("/([\,\;])/",$strPalette,-1);
        $arrMother = $this->getSubpalettes($arrMother);

        // Get the columns from the child palettes found in the mother palette
        foreach($arrChilds as $v)
        {
            $strPalette = preg_replace("/(\{[\w\:]+\},)/","",$GLOBALS['TL_DCA']['tl_content']['palettes'][$v]);
            #$arrPal = explode(';',$strPalette);
            $arrPalette = preg_split("/([\,\;])/",$strPalette,-1);
            $arrPalette = $this->getSubpalettes($arrPalette);
            $arrPalettes[$v] = array_intersect($arrPalette,$arrMother);

        }

        return $arrPalettes;

    }

    /**
     * Get all elements from the subpalettes and merge them with the palette array
     * @param $arrPalettes
     * @return array
     */
    protected function getSubpalettes($arrPalettes)
    {
        $arrReturn = array();
        foreach($arrPalettes as $v)
        {
            if(array_key_exists($v,$GLOBALS['TL_DCA']['tl_content']['subpalettes']))
            {
                $arrPalette = preg_split("/([\,\;])/",$GLOBALS['TL_DCA']['tl_content']['subpalettes'][$v],-1);
                $arrReturn = array_merge($arrPalette,$arrReturn);
            }
        }

        return array_merge($arrPalettes,$arrReturn);
    }

    /**
     * Move all following elements if needed so there is enough sorting space to insert the new created elements
     * @param $intPid
     * @param $strPTable
     * @param $intSorting
     * @param $intCount
     */
    public function moveFollowingElements($intPid,$strPTable,$intSorting,$intCount)
    {
        $intSortDiff = $intCount*128;

        $objSorting = $this->Database->prepare("SELECT MIN(sorting) AS minsorting FROM tl_content WHERE pid=? AND ptable=? AND (sorting>? AND sorting<?) ORDER BY sorting")
            ->execute($intPid,$strPTable,$intSorting,($intSorting+$intSortDiff));

        if($objSorting->minsorting)
        {
            $this->Database->prepare("UPDATE tl_content SET sorting=sorting+".$intSortDiff." WHERE pid=? AND sorting>? AND ptable=?")
                ->execute($intPid,$intSorting,$strPTable);
        }

    }

}
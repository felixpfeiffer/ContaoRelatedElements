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
 * RELATED ELEMENTS
 *
 * Related elements are stored in a global array called "TL_RELATED_ELEMENTS". You can
 * register your own related elements by adding them to the array.
 *
 * $GLOBALS['RELATED_ELEMENTS'] = array
 * (
 *      'accordionStart' => array('accordionStop')
 * );
 *
 * Related elements are elements, that should be created at the same time.
 * The key is the mother element, all items are child elements.
 * If there are more then one item, they should appear in this order. All items
 * can appear more then one time, except the last item, that appears only
 * one time.
 * If there is only one item, it appears only one time.
 */
$GLOBALS['RELATED_ELEMENTS'] = array
(
    'accordionStart'    => array('accordionStop'),
    'sliderStart'       => array('sliderStop')
);
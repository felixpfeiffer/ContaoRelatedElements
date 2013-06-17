<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2013 Leo Feyer
 *
 * @package Relatedelements
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	// Classes
	'Contao\RelatedElements'      => 'system/modules/relatedelements/classes/RelatedElements.php',

	// Models
	'Contao\RelatedElementsModel' => 'system/modules/relatedelements/models/RelatedElementsModel.php',
));

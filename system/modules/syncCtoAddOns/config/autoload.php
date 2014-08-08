<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package SyncCto
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	'SyncCtoAddOns' => 'system/modules/syncCtoAddOns/SyncCtoAddOns.php'
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array());

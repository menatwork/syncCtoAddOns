<?php

/**
 * Contao Open Source CMS
 *
 * @copyright  MEN AT WORK 2014
 * @package    syncCto
 * @license    GNU/LGPL
 * @filesource
 */

/**
 * syncDBUpdateBeforeDrop
 */
$GLOBALS['TL_HOOKS']['syncDBUpdateBeforeDrop'][] = array('SyncCtoAddOns', 'updateBeforeDrop');

$GLOBALS['SYNCCTO_ADDONS']['syncDBUpdateBeforeDrop']['unpublished_root'] = array
(
	'function'  => 'unpublishedPages',
	'clients'   => array(1),
	'direction' => array('to'),
	'pages'     => array(29, 30),
);





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
 * Description of SyncCtoAddOns
 *
 * @author stefan.heimes
 */
class SyncCtoAddOns
{

	/**
	 * Sync directions.
	 */
	const SYNC_DIRECTION_TO      = 'to';
	const SYNC_DIRECTION_FROM    = 'from';
	const SYNC_DIRECTION_UNKNOWN = 'dead';

	/**
	 * List with preset information.
	 *
	 * @var array
	 */
	protected $arrNeededInformation = array
	(
		'unpublishedPages' => array
		(
			'tables' => array
			(
				'tl_page'
			)
		)
	);

	/**
	 * Id of the client.
	 *
	 * @var int
	 */
	protected $intClientId = null;

	/**
	 * List with all tables for the sync.
	 *
	 * @var array
	 */
	protected $arrTables = array();

	/**
	 * Name of the table in the url.
	 *
	 * @var string
	 */
	protected $strGetTable = null;

	/**
	 * The direction to or from.
	 *
	 * @var string
	 */
	protected $strDirection = null;

	/**
	 * Init.
	 */
	public function __construct()
	{
		$this->readGetParams();
	}

	/**
	 * Read some get params from the url.
	 */
	protected function readGetParams()
	{
		// Get tha table name form the get url param.
		$this->strGetTable = \Input::get('table');

		// Get the direction.
		if ($this->strGetTable == 'tl_syncCto_clients_syncTo')
		{
			$this->strDirection = self::SYNC_DIRECTION_TO;
		}
		else if ($this->strGetTable == 'tl_syncCto_clients_syncFrom')
		{
			$this->strDirection = self::SYNC_DIRECTION_FROM;
		}
		else
		{
			$this->strDirection = self::SYNC_DIRECTION_UNKNOWN;
		}
	}

	/**
	 * Check settings if the current set is valid.
	 *
	 * @param array $arrSettings The list of all settings.
	 *
	 * @param int   $intClientId The id of the client.
	 *
	 * @param array $arrTables   The list of tables for the sync.
	 *
	 * @return bool TRUE => Nothing found clear for run || FALSE => Missing information abort.
	 */
	public function  isCurrentEventValid($arrSettings, $intClientId, $arrTables)
	{
		// Check if we have a function.
		if (!isset($arrSettings['function']))
		{

			return false;
		}

		// Check direction.
		if (is_array($arrSettings['direction']))
		{
			if (!in_array($this->strDirection, $arrSettings['direction']))
			{
				return false;
			}
		}
		elseif ($this->strDirection != $arrSettings['direction'])
		{
			return false;
		}

		// Check if we have a client list.
		if (!isset($arrSettings['clients']))
		{
			return false;
		}

		// Check if the current client is in the list.
		if (is_array($arrSettings['clients']))
		{
			if (!in_array($intClientId, $arrSettings['clients']))
			{
				return false;
			}
		}
		elseif ($intClientId != $arrSettings['clients'])
		{
			return false;
		}

		// Check the preset information for the function.
		if (!$this->checkPresetInformation($arrSettings, $arrTables))
		{
			return false;
		}

		// Nothing found return "ALL SYSTEM GREEN - GO"
		return true;
	}

	/**
	 * Check if the function need some special vars for the work.
	 *
	 * @param array $arrSettings The list of all settings.
	 *
	 * @param array $arrTables   The list of tables for the sync.
	 *
	 * @return bool TRUE => Nothing found clear for run || FALSE => Missing information abort.
	 */
	public function checkPresetInformation($arrSettings, $arrTables)
	{
		// Get the name of the function to run.
		$strFunction = $arrSettings['function'];

		// if the function is not set, we need no more information.
		if (!isset($this->arrNeededInformation[$strFunction]))
		{
			return true;
		}

		// Check if we have to check the tables.
		if (isset($this->arrNeededInformation[$strFunction]['tables']))
		{
			$blnContainsOneTable = false;
			foreach ($this->arrNeededInformation[$strFunction]['tables'] as $strTable)
			{
				if (in_array($strTable, $arrTables))
				{
					$blnContainsOneTable = true;
					break;
				}
			}

			if (!$blnContainsOneTable)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Run the before drop functions.
	 *
	 * @param int   $intClientID   ID of the client.
	 *
	 * @param array $arrSyncTables List of all tables fpr the current sync.
	 *
	 * @param array $arrLastSQL    List with all SQL.
	 *
	 * @return array The list with the SQL.
	 */
	public function updateBeforeDrop($intClientID, $arrSyncTables, $arrLastSQL)
	{
		// Check if we have a direction.
		if ($this->strDirection == self::SYNC_DIRECTION_UNKNOWN)
		{
			return $arrLastSQL;
		}

		// Set global information.
		$this->intClientId = $intClientID;
		$this->arrTables   = $arrSyncTables;

		foreach ($GLOBALS['SYNCCTO_ADDONS']['syncDBUpdateBeforeDrop'] as $strCustomName => $arrSettings)
		{
			if (!$this->isCurrentEventValid($arrSettings, $intClientID, $arrSyncTables))
			{
				continue;
			}

			try
			{
				switch ($arrSettings['function'])
				{
					case 'unpublishedPages':
						$this->unpublishedPages($arrLastSQL, $arrSettings);
						break;
				}
			}
			catch (Exception $e)
			{
				\System::log('Error on running a add on for syncCto. Name ' . $strCustomName . ' || Error: ' . $e->getMessage(), __CLASS__ . ' || ' . __FUNCTION__, TL_ERROR);
			}
		}

		return $arrLastSQL;
	}

	/**
	 * Unpublished a page or pages with the given id.
	 *
	 * @param array $arrLastSQL  List with all SQL.
	 *
	 * @param array $arrSettings The list of all settings.
	 */
	protected function unpublishedPages(&$arrLastSQL, $arrSettings)
	{
		$arrReturn = array();

		if (is_array($arrSettings['pages']))
		{
			// Clean up the array.
			$arrId = array_map('intval', $arrSettings['pages']);
			$arrId = array_filter($arrId, function ($strValue)
			{
				return !empty($strValue);
			});

			// Check if we have a value.
			if (empty($arrId))
			{
				return;
			}

			$strUpdate = 'UPDATE synccto_temp_tl_page SET published = \'\' WHERE id IN (' . implode(", ", $arrId) . ')';
		}
		else
		{
			// Clean up the id.
			$intID = intval($arrSettings['pages']);

			// Check if we have a value.
			if (empty($intID))
			{
				return;
			}

			$strUpdate = 'UPDATE synccto_temp_tl_page SET published = \'\' WHERE id = ' . $intID;
		}

		// Merge new data with the old ones.
		$arrReturn[] = array(
			'query' => $strUpdate,
		);

		$arrLastSQL = array_merge((array)$arrLastSQL, $arrReturn);
	}

}

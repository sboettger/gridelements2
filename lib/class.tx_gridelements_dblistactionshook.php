<?php

require_once(PATH_typo3 . 'interfaces/interface.localrecordlist_actionsHook.php');

/**
 * Class/Function which manipulates the query parts while fetching tt_content records within the list module.
 *
 * @author		Jo Hasenau <info@cybercraft.de>
 * @package		TYPO3
 * @subpackage	tx_gridelements
 */
class tx_gridelements_dbListActionsHook implements localRecordList_actionsHook {

	/**
	 * modifies Web>List clip icons (copy, cut, paste, etc.) of a displayed row
	 *
	 * @param	string		the current database table
	 * @param	array		the current record row
	 * @param	array		the default clip-icons to get modified
	 * @param	object		Instance of calling object
	 * @return	array		the modified clip-icons
	 */
	public function makeClip($table, $row, $cells, &$parentObject) {
		/*if ($table == 'tt_content' && get_class($parentObject) == 'localRecordList') {
			if(intval($row['colPos'] < 0)) {
				$cells['pasteInto'] = $parentObject->spaceIcon;
				$cells['pasteAfter'] = $parentObject->spaceIcon;
			}
		}*/

		return $cells;
	}


	/**
	 * modifies Web>List control icons of a displayed row
	 *
	 * @param	string		the current database table
	 * @param	array		the current record row
	 * @param	array		the default control-icons to get modified
	 * @param	object		Instance of calling object
	 * @return	array		the modified control-icons
	 */
	public function makeControl($table, $row, $cells, &$parentObject) {
		/*if ($table == 'tt_content' && get_class($parentObject) == 'localRecordList') {
			if(intval($row['colPos'] < 0)) {
				$cells['move'] = $parentObject->spaceIcon;
				$cells['new'] = $parentObject->spaceIcon;
				$cells['moveUp'] = $parentObject->spaceIcon;
				$cells['moveDown'] = $parentObject->spaceIcon;
			}
		}*/

		return $cells;
	}


	/**
	 * modifies Web>List header row columns/cells
	 *
	 * @param	string		the current database table
	 * @param	array		Array of the currently displayed uids of the table
	 * @param	array		An array of rendered cells/columns
	 * @param	object		Instance of calling (parent) object
	 * @return	array		Array of modified cells/columns
	 */
	public function renderListHeader($table, $currentIdList, $headerColumns, &$parentObject) {
		return $headerColumns;
	}


	/**
	 * modifies Web>List header row clipboard/action icons
	 *
	 * @param	string		the current database table
	 * @param	array		Array of the currently displayed uids of the table
	 * @param	array		An array of the current clipboard/action icons
	 * @param	object		Instance of calling (parent) object
	 * @return	array		Array of modified clipboard/action icons
	 */
	public function renderListHeaderActions($table, $currentIdList, $cells, &$parentObject) {
		return $cells;
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/class.tx_gridelements_dblistactionshook.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/class.tx_gridelements_dblistactionshook.php']);
}

?>
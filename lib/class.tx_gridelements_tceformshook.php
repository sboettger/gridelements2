<?php

/**
 * Class/Function which offers TCE main hook functions.
 *
 * @author		Jo Hasenau <info@cybercraft.de>
 * @package		TYPO3
 * @subpackage	tx_gridelements
 */

class tx_gridelements_TCEformsHook {

	/**
	 * Function to set the colPos of an element depending on
	 * whether it is a child of a parent container or not
	 * changes are applied to the FieldArray of the parent object by reference
	 *
	 * @param   string          $table: The name of the table we are currently working on
	 * @param   string          $field: The name of the field we are currently working on
	 * @param   array           $row: The data of the current record
	 * @param   \t3lib_TCEforms $parentObject: The parent object that triggered the hook
	 * @return  void
	 *
	 */
	public function getSingleField_beforeRender($table, $field, $row, &$parentObject) {

		if ($field == 'pi_flexform' && $row['CType'] == 'gridelements_pi1' && $row['tx_gridelements_backend_layout']) {
			$layoutSetup = t3lib_div::makeInstance('tx_gridelements_layoutsetup', $row['pid']);
			$parentObject['fieldConf']['config']['ds']['*,gridelements_pi1'] = $layoutSetup->getFlexformConfiguration($row['tx_gridelements_backend_layout']);
		}
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/class.tx_gridelements_tceformshook.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/class.tx_gridelements_tceformshook.php']);
}

?>
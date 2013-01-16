<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Jo Hasenau <info@cybercraft.de>
 *  (c) 2013 Stefan Froemken <froemken@gmail.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class/Function which offers TCE main hook functions.
 *
 * @author		Jo Hasenau <info@cybercraft.de>
 * @package		TYPO3
 * @subpackage	tx_gridelements
 */
class tx_gridelements_tcemain_processCmdmapAfterFinish extends tx_gridelements_tcemain_abstract {

	/**
	 * Process all grid elements that have been copied or created in workspace.
	 * Adjuste tx_gridelement_container to proper values for versions and placeholders
	 *
	 * Hook for t3lib_tcemain l. 2542
	 *
	 * @author: Martin R. Krause, martin.r.krause@gmx.de
	 * @access: public
	 *
	 * @param $parentObj
	 * @return void
	 */
	public function processCmdmapAfterFinish(&$parentObj) {
		if(isset($GLOBALS['actionOnGridElement']) && count($GLOBALS['actionOnGridElement'])) {
			foreach ($GLOBALS['actionOnGridElement'] as $action) {
				$placeHolderRecord = t3lib_BEfunc::getRecord($action['table'], $action['id']);

				if (strlen(strval($action['value'])) == 0) $action['value'] = $placeHolderRecord['sys_language_uid'];

				// we only care about gridelements on workspaces
				if ($placeHolderRecord['CType'] == 'gridelements_pi1' && $parentObj->BE_USER->workspace > 0) {

					$versionRecord = t3lib_BEfunc::getRecordsByField($action['table'], 't3ver_oid', $placeHolderRecord['uid'], 'AND pid < 0 AND sys_language_uid = ' . $action['value']);
					$versionRecord = $versionRecord[0];

					if (is_array($versionRecord)) {
						$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
							'uid, pid, tx_gridelements_container, tx_gridelements_columns',
							$action['table'],
							'l18n_parent in (select uid from ' . $action['table'] . ' where tx_gridelements_container = ' . $placeHolderRecord['l18n_parent'] . ')'
						);
						$rows = array();
						while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
							$rows[] = $row;
						}
						$GLOBALS['TYPO3_DB']->sql_free_result($res);

						foreach ($rows as $cElement) {
							$updateArray = array('tx_gridelements_container' => $placeHolderRecord['uid']);
							$GLOBALS['TYPO3_DB']->exec_UPDATEquery($action['table'], 'uid = ' . $cElement['uid'], $updateArray);
						}
					}
				}
			}
			unset($GLOBALS['actionOnGridElement']);
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/tcemain/class.tx_gridelements_tcemain_processCmdmap.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/class.tx_gridelements_tcemain_processCmdmap.php']);
}
?>
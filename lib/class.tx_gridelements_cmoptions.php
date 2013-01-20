<?php

/**
 * Class/Function which
 *
 * @author		Jo Hasenau <info@cybercraft.de>
 * @package		TYPO3
 * @subpackage	tx_gridelements
 */
class tx_gridelements_cmoptions {

	/**
	 * @param clickMenu $backRef
	 * @param array $menuItems
	 * @param string $table
	 * @param integer $uid
	 * @return array
	 */
	public function main(clickMenu &$backRef, array $menuItems, $table, $uid) {
		global $BE_USER;

		// add copied item handler to "(un)copy" link in clickmenu
		if(strpos($menuItems['copy'][0], 't3-icon-edit-copy-release') === false) {
			preg_match('@&uid=(?P<digit>\d+)&@', $menuItems['copy'][3], $arrMatches);
			$strUidInLink = $arrMatches[1];
			$menuItems['copy'][3] = str_replace('return false;', ' GridElementsDD.listenForCopyItem(' . $strUidInLink . '); return false;', $menuItems['copy'][3]);
		}

		// add "paste reference after"
		$parkItem = $menuItems['pasteafter'];
		if($parkItem) {
			unset($menuItems['pasteafter']);
			$menuItems['pasteafter'] = $parkItem;
			if($backRef->clipObj->currentMode() == 'copy') {
				$parkItem[1] = $GLOBALS['LANG']->sL('LLL:EXT:gridelements/locallang_db.xml:tx_gridelements_clickmenu_pastereference');
				$parkItem[3] = preg_replace('/formToken/', 'reference=1&formToken', $parkItem[3]);
				$menuItems['pastereference'] = $parkItem;
			}
		}
		return $menuItems;
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/class.tx_gridelements_cmoptions.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/class.tx_gridelements_cmoptions.php']);
}
?>
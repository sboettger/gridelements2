<?php

require_once(PATH_typo3 . 'interfaces/interface.cms_newcontentelementwizarditemshook.php');

/**
 * Class/Function which manipulates the rendering of items within the new content element wizard
 *
 * @author		Jo Hasenau <info@cybercraft.de>
 * @package		TYPO3
 * @subpackage	tx_gridelements
 */
class tx_gridelements_wizardItemsHook implements cms_newContentElementWizardsHook {

	/**
	 * Prcesses the items of the new content element wizard
	 * and inserts necessary default values for items created within a grid
	 *
	 * @param	array				$wizardItems: The array containing the current status of the wizard item list before rendering
	 * @param	\db_new_content_el	$parentObject: The parent object that triggered this hook
	 *
	 */
	public function manipulateWizardItems(&$wizardItems, &$parentObject) {

		$container = intval(t3lib_div::_GP('tx_gridelements_container'));
		$column = intval(t3lib_div::_GP('tx_gridelements_columns'));

		foreach($wizardItems as $key => $wizardItem) {

			if($container != 0) {
				$wizardItems[$key]['tt_content_defValues']['tx_gridelements_container'] = $container;
				$wizardItems[$key]['params'] .= '&defVals[tt_content][tx_gridelements_container]=' . $container;
			}

			if($column != 0) {
				$wizardItems[$key]['tt_content_defValues']['tx_gridelements_columns'] = $column;
				$wizardItems[$key]['params'] .= '&defVals[tt_content][tx_gridelements_columns]=' . $column;
			}

		}

		$pageID = $parentObject->pageinfo['uid'];

		$BEfunc = t3lib_div::makeInstance('t3lib_BEfunc');
		$TSconfig = $BEfunc->getPagesTSconfig($pageID);

		// TODO: Ist this needed anyway? top_level_layout can be set in record ans page TSconfig.
		if($container && $TSconfig['TCEFORM.']['tt_content.']['tx_gridelements_backend_layout.']['itemsProcFunc.']['topLevelLayouts']) {
			$excludeArray[] = $TSconfig['TCEFORM.']['tt_content.']['tx_gridelements_backend_layout.']['itemsProcFunc.']['topLevelLayouts'];
		}

		// TODO: Ist this needed anyway? top_level_layout can be set in record ans page TSconfig.
		$excludeLayouts = $TSconfig['TCEFORM.']['tt_content.']['tx_gridelements_backend_layout.']['itemsProcFunc.']['excludeLayouts'];

		if($excludeLayouts) {
			$excludeArray[] = $excludeLayouts;
		}

		$userExcludeLayouts = $TSconfig['TCEFORM.']['tt_content.']['tx_gridelements_backend_layout.']['itemsProcFunc.']['userExcludeLayouts'];

		if($userExcludeLayouts) {
			$excludeArray[] = $userExcludeLayouts;
		}

		$excludeList = 0;

		if(count($excludeArray) > 0) {
			$excludeList = implode(',', $excludeArray);
		}

		$wizardItems['toplevelgrids']['header'] = $GLOBALS['LANG']->sL('LLL:EXT:gridelements/locallang_db.xml:tx_gridelements_backend_layout_wizard_label');

		$gridItems = t3lib_div::makeInstance('tx_gridelements_layoutsetup', $pageID)
			->getLayoutWizardItems($parentObject->colPos);

		if(count($gridItems)) {

			foreach($gridItems as $key => $item) {
				$wizardItems['toplevelgrids_grid_' . $item['uid']] = array(
					'title'                 => $item['title'],
					'description'           => $item['description'],
					'params'                => ($item['icon'][1] ? '&largeIconImage=' . $item['icon'][1] : '') .
					                        '&defVals[tt_content][CType]=gridelements_pi1&defVals[tt_content][tx_gridelements_backend_layout]=' . $item['uid'] .
											($container ? '&defVals[tt_content][tx_gridelements_container]=' . $container : '') .
											($column ? '&defVals[tt_content][tx_gridelements_columns]=' . $column : ''),
					'tt_content_defValues'  => array(
						'CType'                             => 'gridelements_pi1',
						'tx_gridelements_backend_layout'    => $item['uid']
					),
				);

				if($container != 0) {
					$wizardItems['toplevelgrids_grid_' . $item['uid']]['tx_gridelements_container'] = $container;
				}

				if($column != 0) {
					$wizardItems['toplevelgrids_grid_' . $item['uid']]['tx_gridelements_columns'] = $column;
				}

				if($item['icon'][0]) {
					$wizardItems['toplevelgrids_grid_' . $item['uid']]['icon'] = $item['icon'][0];
				} else {
					$wizardItems['toplevelgrids_grid_' . $item['uid']]['icon'] = t3lib_extMgm::extRelPath('gridelements') . 'res/img/new_content_el.gif';
				}

			}

		}

	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/class.tx_gridelements_wizarditemshook.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/class.tx_gridelements_wizarditemshook.php']);
}

?>
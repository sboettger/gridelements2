<?php

/**
 * Class/Function which manipulates the item-array for table/field tt_content_tx_gridelements_columns.
 *
 * @author		Jo Hasenau <info@cybercraft.de>
 * @package		TYPO3
 * @subpackage	tx_gridelements
 */
class tx_gridelements_tt_content {

	/**
	 * ItemProcFunc for columns items
	 *
	 * @param	array	$params: An array containing the items and parameters for the list of items
	 * @return	void
	 */
	public function columnsItemsProcFunc(&$params) {

		$containerUid = intval($params['row']['tx_gridelements_container']);

		if ($containerUid > 0) {

			$parentLayout = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
				'tx_gridelements_backend_layout',
				'tt_content',
				'tt_content.uid=' . $containerUid
			);

			$params['items'] = t3lib_div::makeInstance('tx_gridelements_layoutsetup', $params['row']['pid'])
				->getLayoutColumnsSelectItems($parentLayout['tx_gridelements_backend_layout']);

		}

	}

	/**
	 * ItemProcFunc for container items
	 * removes items of the children chain from the list of selectable containers
	 * if the element itself already is a container
	 *
	 * @param	array	$params: An array containing the items and parameters for the list of items
	 * @return	void
	 */
	public function containerItemsProcFunc(&$params) {

		if ($params['row']['CType'] == 'gridelements_pi1' && count($params['items']) > 1) {
			$items = $params['items'];
			$params['items'] = array(
				0 => array_shift($items)
			);

			foreach ($items as $item) {
				$possibleContainers[$item['1']] = $item;
			}

			if ($params['row']['uid'] > 0) {
				$this->lookForChildContainersRecursively(intval($params['row']['uid']), $possibleContainers);
			}

		}

		if (count($possibleContainers) > 0) {
			$params['items'] = array_merge($params['items'], $possibleContainers);
		}

	}

	/**
	 * ItemProcFunc for layout items
	 * removes items that are available for grid boxes on the first level only
	 * and items that are excluded for a certain branch or user
	 *
	 * @param	array	$params: An array containing the items and parameters for the list of items
	 * @return	void
	 */
	public function layoutItemsProcFunc(&$params) {

		$layoutSelectItems = t3lib_div::makeInstance('tx_gridelements_layoutsetup', $params['row']['pid'])
					->getLayoutSelectItems($params['row']['colPos']);

		$params['items'] = t3lib_div::keepItemsInArray($layoutSelectItems, $params['items'], TRUE);

	}

	/**
	 * Recursive function to remove any container from the list of possible containers
	 * that is already a subcontainer on any level of the current container
	 *
	 * @param CSV	$containerId: A list determining containers that should be checked
	 * @param array	$possibleContainers: The result list containing the remaining containers after the check
	 *
	 * @return	void
	 */
	public function lookForChildContainersRecursively($containerIds, &$possibleContainers) {

		$childrenOnNextLevel = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid, tx_gridelements_container',
			'tt_content',
			'CType=\'gridelements_pi1\' AND tx_gridelements_container IN (' . $containerIds . ')'
		);

		if (count($childrenOnNextLevel) && count($possibleContainers)) {
			$containerIds = '';

			foreach ($childrenOnNextLevel as $childOnNextLevel) {

				if (isset($possibleContainers[$childOnNextLevel['uid']])) {
					unset($possibleContainers[$childOnNextLevel['uid']]);
				}

				$containerIds .= $containerIds
					? ',' . intval($childOnNextLevel['uid'])
					: intval($childOnNextLevel['uid']);

				if ($containerIds != '') {
					$this->lookForChildContainersRecursively($containerIds, $possibleContainers);
				}

			}

		}

	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/class.tx_gridelements_tt_content.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/class.tx_gridelements_tt_content.php']);
}

?>
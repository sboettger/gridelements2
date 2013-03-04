<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Jo Hasenau <info@cybercraft.de>
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
 * Class/Function which manipulates the item-array for table/field tt_content_tx_gridelements_columns.
 *
 * @author		Jo Hasenau <info@cybercraft.de>
 * @package		TYPO3
 * @subpackage	tx_gridelements
 */
class tx_gridelements_tt_content {

	/**
	 * @var tx_gridelements_layoutsetup
	 */
	protected $layoutSetup;

	/**
	 * inject layout setup
	 *
	 * @param tx_gridelements_layoutsetup $layoutSetup
	 * @return void
	 */
	public function injectLayoutSetup(tx_gridelements_layoutsetup $layoutSetup) {
		$this->layoutSetup = $layoutSetup;
	}

	/**
	 * initializes this class
	 *
	 * @param integer $pageUid
	 * @return void
	 */
	public function init($pageUid) {
		if (!$this->layoutSetup instanceof tx_gridelements_layoutsetup) {
			$this->injectLayoutSetup(
				t3lib_div::makeInstance('tx_gridelements_layoutsetup')
					->init($pageUid)
			);
		}
	}

	/**
	 * ItemProcFunc for columns items
	 *
	 * @param	array	$params: An array containing the items and parameters for the list of items
	 * @return	void
	 */
	public function columnsItemsProcFunc(&$params) {
		$this->init($params['row']['pid']);
		$gridContainerId = intval($params['row']['tx_gridelements_container']);
		
		if ($gridContainerId > 0) {
			$gridElement = $this->layoutSetup->cacheCurrentParent($gridContainerId, TRUE);
			$params['items'] = $this->layoutSetup
				->getLayoutColumnsSelectItems($gridElement['tx_gridelements_backend_layout']);
				
			if($params['row']['CType'] != '' && count($params['items']) > 0) {
				foreach($params['items'] as $itemKey => $itemArray) {
					if($itemArray[3] != '' && !t3lib_div::inList($itemArray[3], $params['row']['CType'])) {
						unset($params['items'][$itemKey]);
					}
				}
			}
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
		$this->init($params['row']['pid']);
		$this->removesItemsFromListOfSelectableContainers($params, $possibleContainers);

		if (count($possibleContainers) > 0) {
			$params['items'] = array_merge($params['items'], $possibleContainers);
		}

		$itemUidList = '';
		if(count($params['items']) > 1) {
			foreach($params['items'] as $key => $container) {
				if($container[1] > 0) {
					$itemUidList .= $itemUidList ? ',' . $container[1] : $container[1];
				}
			}
		}

		if ($itemUidList) {
			$this->deleteUnallowedContainer($params, $itemUidList);
		}
	}

	/**
	 * removes items of the children chain from the list of selectable containers
	 *
	 * @param array $params
	 * @param $possibleContainers
	 * @return void
	 */
	public function removesItemsFromListOfSelectableContainers(array &$params, &$possibleContainers) {
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
	}

	/**
	 * delete containers from params which are not allowed
	 *
	 * @param array $params
	 * @param string $itemUidList comma seperated list of uids
	 * @return void
	 */
	public function deleteUnallowedContainer(array &$params, $itemUidList = '') {
		$layoutSetups = $this->layoutSetup->getLayoutSetup();
		if($itemUidList) {
			$containerRecords = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'uid,tx_gridelements_backend_layout',
				'tt_content',
				'uid IN (' . $itemUidList . ')',
				'',
				'',
				'',
				'uid'
			);

			foreach($params['items'] as $key => $container) {
				$allowed = $layoutSetups[$containerRecords[$container[1]]['tx_gridelements_backend_layout']]['allowed'];
				if($container[1] > 0 && $allowed) {
					if(!t3lib_div::inList($allowed, $params['row']['CType']) && !t3lib_div::inList($allowed, '*')) {
						unset($params['items'][$key]);
					}
				}
			}
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
		$this->init($params['row']['pid']);
		$layoutSelectItems = $this->layoutSetup
			->getLayoutSelectItems($params['row']['colPos']);

		$params['items'] = t3lib_div::keepItemsInArray($layoutSelectItems, $params['items'], TRUE);
	}

	/**
	 * Recursive function to remove any container from the list of possible containers
	 * that is already a subcontainer on any level of the current container
	 *
	 * @param CSV	$containerIds: A list determining containers that should be checked
	 * @param array	$possibleContainers: The result list containing the remaining containers after the check
	 * @return	void
	 */
	public function lookForChildContainersRecursively($containerIds, &$possibleContainers) {
		if($containerIds) {
			$childrenOnNextLevel = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'uid, tx_gridelements_container',
				'tt_content',
				'CType=\'gridelements_pi1\' AND tx_gridelements_container IN (' . $containerIds . ')'
			);
		}

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
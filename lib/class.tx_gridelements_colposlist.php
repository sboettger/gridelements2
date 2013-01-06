<?php

/**
 * Class/Function which manipulates the item-array for table/field tt_content colPos.
 *
 * @author		Jo Hasenau <info@cybercraft.de>
 * @package		TYPO3
 * @subpackage	tx_gridelements
 */

class tx_gridelements_colPosList {

	/**
	 * ItemProcFunc for colpos items
	 *
	 * @param	array	$params: The array of parameters that is used to render the item list
	 * @return	void
	 */
	public function itemsProcFunc(&$params) {

		if ($params['row']['pid'] > 0) {
			$params['items'] = $this->addColPosListLayoutItems($params['row']['pid'], $params['items']);
		} else {
			// negative uid_pid values indicate that the element has been inserted after an existing element
			// so there is no pid to get the backendLayout for and we have to get that first
			$existingElement = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('pid', 'tt_content', 'uid=' . -(intval($params['row']['pid'])));
			if ($existingElement['pid'] > 0) {
				$params['items'] = $this->addColPosListLayoutItems($existingElement['pid'], $params['items']);
			}
		}

	}

	/**
	 * Adds items to a colpos list
	 *
	 * @param	int		$pageId: The uid of the page we are currently working on
	 * @param	array	$items: The array of items before the action
	 * @return	array   $items: The ready made array of items
	 */
	protected function addColPosListLayoutItems($pageId, $items) {

		$layout = $this->getSelectedBackendLayout($pageId);

		if ($layout && $layout['__items']) {
			$items = $layout['__items'];
		}
		$items[] = array(
			$GLOBALS['LANG']->sL('LLL:EXT:gridelements/locallang_db.xml:tt_content.tx_gridelements_container'),
			'-1'
		);
		return $items;
	}

	/**
	 * Gets the selected backend layout
	 *
	 * @param	int			$id: The uid of the page we are currently working on
	 * @return	array|null	$backendLayout: An array containing the data of the selected backend layout as well as a parsed version of the layout configuration
	 */
	public function getSelectedBackendLayout($id) {

		$rootline = t3lib_BEfunc::BEgetRootLine($id);
		$backendLayoutUid = NULL;

		for ($i = count($rootline); $i > 0; $i--) {
			$page = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
				'uid, backend_layout, backend_layout_next_level',
				'pages',
				'uid=' . intval($rootline[$i]['uid'])
			);
			$selectedBackendLayout = intval($page['backend_layout']);
			$selectedBackendLayoutNextLevel = intval($page['backend_layout_next_level']);
			if ($selectedBackendLayout != 0 && $page['uid'] == $id) {
				if ($selectedBackendLayout > 0) {
					// Backend layout for current page is set
					$backendLayoutUid = $selectedBackendLayout;
				}
				break;
			} else if ($selectedBackendLayoutNextLevel == -1 && $page['uid'] != $id) {
				// Some previous page in our rootline sets layout_next to "None"
				break;
			} else if ($selectedBackendLayoutNextLevel > 0 && $page['uid'] != $id) {
				// Some previous page in our rootline sets some backend_layout, use it
				$backendLayoutUid = $selectedBackendLayoutNextLevel;
				break;
			}
		}

		$backendLayout = NULL;
		if ($backendLayoutUid) {
			$backendLayout = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
				'*',
				'backend_layout',
				'uid=' . $backendLayoutUid
			);

			if ($backendLayout) {
				/** @var t3lib_TSparser $parser  */
				$parser = t3lib_div::makeInstance('t3lib_TSparser');
				$parser->parse($backendLayout['config']);

				$backendLayout['__config'] = $parser->setup;
				$backendLayout['__items'] = array();
				$backendLayout['__colPosList'] = array();

				// create items and colPosList
				if ($backendLayout['__config']['backend_layout.'] && $backendLayout['__config']['backend_layout.']['rows.']) {
					foreach ($backendLayout['__config']['backend_layout.']['rows.'] as $row) {
						if (isset($row['columns.']) && is_array($row['columns.'])) {
							foreach ($row['columns.'] as $column) {
								$backendLayout['__items'][] = array(
									t3lib_div::isFirstPartOfStr($column['name'], 'LLL:')
										? $GLOBALS['LANG']->sL($column['name']) : $column['name'],
									$column['colPos'],
									NULL
								);
								$backendLayout['__colPosList'][] = $column['colPos'];
							}
						}
					}
				}
			}
		}

		return $backendLayout;

	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/class.tx_gridelements_colposlist.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/class.tx_gridelements_colposlist.php']);
}

?>
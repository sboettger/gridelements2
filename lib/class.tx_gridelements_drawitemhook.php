<?php

require_once(t3lib_extMgm::extPath('cms') . 'layout/interfaces/interface.tx_cms_layout_tt_content_drawitemhook.php');

/**
 * Class/Function which manipulates the rendering of item example content and replaces it with a grid of child elements.
 *
 * @author		Jo Hasenau <info@cybercraft.de>
 * @package		TYPO3
 * @subpackage	tx_gridelements
 */
class tx_gridelements_drawItemHook implements tx_cms_layout_tt_content_drawItemHook {

	/**
	 * @var language
	 */
	var $lang;

	/**
	 * @var t3lib_queryGenerator
	 */
	protected $tree;

	public function __construct() {
		$this->lang = t3lib_div::makeInstance('language');
		$this->lang->init($GLOBALS['BE_USER']->uc['lang']);
	}

	/**
	 * Processes the item to be rendered before the actual example content gets rendered
	 * Deactivates the original example content output
	 *
	 * @param \TYPO3\CMS\Backend\View\PageLayoutView     $parentObject: The parent object that triggered this hook
	 * @param boolean           $drawItem: A switch to tell the parent object, if the item still must be drawn
	 * @param string            $headerContent: The content of the item header
	 * @param string            $itemContent: The content of the item itself
	 * @param array             $row: The current data row for this item
	 * @return	void
	 */
	public function preProcess(\TYPO3\CMS\Backend\View\PageLayoutView &$parentObject, &$drawItem, &$headerContent, &$itemContent, array &$row) {
		if ($row['CType']) {
			$showHidden = $parentObject->tt_contentConfig['showHidden'] ? '' : t3lib_BEfunc::BEenableFields('tt_content');
			$deleteClause = t3lib_BEfunc::deleteClause('tt_content');

			if($GLOBALS['BE_USER']->uc['hideContentPreview']) {
				$drawItem = FALSE;
			}

			switch ($row['CType']) {
				case 'gridelements_pi1':
					$drawItem = FALSE;
					$itemContent .= $this->renderCTypeGridelements($parentObject, $row, $showHidden, $deleteClause);
					$refIndexObj = t3lib_div::makeInstance('t3lib_refindex');
					/* @var $refIndexObj t3lib_refindex */
					$refIndexObj->updateRefIndexTable('tt_content', $row['uid']);
					break;
				case 'shortcut':
					$drawItem = FALSE;
					$itemContent .= $this->renderCTypeShortcut($parentObject, $row, $showHidden, $deleteClause);
					break;
			}
		}
		$headerContent = '<div id="ce' . $row['uid'] . '" class="t3-ctype-' . $row['CType'] . '">' . $headerContent . '</div>';
	}

	/**
	 * renders the HTML output for elements of the CType gridelements_pi1
	 *
	 * @param tx_cms_layout     $parentObject: The parent object that triggered this hook
	 * @param array             $row: The current data row for this item
	 * @param string            $showHidden: query String containing enable fields
	 * @param string            $deleteClause: query String to check for deleted items
	 * @return string           $itemContent: The HTML output for elements of the CType gridelements_pi1
	 */
	public function renderCTypeGridelements(tx_cms_layout $parentObject, &$row, &$showHidden, &$deleteClause) {
		$head = array();
		$gridContent = array();
		$editUidList = array();
		$colPosValues = array();
		$singleColumn = FALSE;

		// get the layout record for the selected backend layout if any
		$gridContainerId = $row['uid'];
		/** @var $layoutSetup tx_gridelements_layoutsetup */
		$layoutSetup = t3lib_div::makeInstance('tx_gridelements_layoutsetup');
		$gridElement = $layoutSetup->init($row['pid'])->cacheCurrentParent($gridContainerId, TRUE);
		$layoutUid = $gridElement['tx_gridelements_backend_layout'];
		$layout = $layoutSetup->getLayoutSetup($layoutUid);
		$parserRows = $layout['config']['rows.'];

		// if there is anything to parse, lets check for existing columns in the layout

		if (is_array($parserRows) && count($parserRows) > 0) {
			$this->setMultipleColPosValues($parserRows, $colPosValues);
		} else {
			$singleColumn = TRUE;
			$this->setSingleColPosItems($parentObject, $colPosValues, $gridElement, $showHidden, $deleteClause);
		}

		// if there are any columns, lets build the content for them
		if (count($colPosValues) > 0) {
			$this->renderGridColumns($parentObject, $colPosValues, $gridContent, $gridElement, $editUidList, $singleColumn, $head, $showHidden, $deleteClause);
		}

		// if we got a selected backend layout, we have to create the layout table now
		if ($layoutUid && isset($layout['config'])) {
			$itemContent = $this->renderGridLayoutTable($layout, $gridElement, $head, $gridContent);
		} else {
			$itemContent = '<div class="t3-gridContainer">';
			$itemContent .= '<table border="0" cellspacing="1" cellpadding="4" width="100%" height="100%" class="t3-page-columns t3-gridTable">';
			$itemContent .= '<tr><td valign="top" class="t3-gridCell t3-page-column t3-page-column-0">' . $gridContent[0] . '</td></tr>';
			$itemContent .= '</table></div>';
		}
		return $itemContent;
	}

	/**
	 * renders the HTML output for elements of the CType shortcut
	 *
	 * @param tx_cms_layout     $parentObject: The parent object that triggered this hook
	 * @param array             $row: The current data row for this item
	 * @param string            $showHidden: query String containing enable fields
	 * @param string            $deleteClause: query String to check for deleted items
	 * @return string           $shortcutContent: The HTML output for elements of the CType shortcut
	 */
	public function renderCTypeShortcut(tx_cms_layout $parentObject, &$row, &$showHidden, &$deleteClause) {
		$shortcutContent = '';
		if ($row['records']) {
			$shortcutItems = t3lib_div::trimExplode(',', $row['records']);
			$collectedItems = array();
			foreach ($shortcutItems as $shortcutItem) {
				if (strpos($shortcutItem, 'pages_') !== FALSE) {
					$this->collectContentDataFromPages($shortcutItem, $collectedItems, $row['recursive'], $showHidden, $deleteClause);
				} else if (strpos($shortcutItem, '_') === FALSE || strpos($shortcutItem, 'tt_content_') !== FALSE) {
					$this->collectContentData($shortcutItem, $collectedItems, $showHidden, $deleteClause);
				}
			}
			if (count($collectedItems)) {
				foreach ($collectedItems as $itemRow) {
					if ($itemRow){
						$className = $itemRow['tx_gridelements_reference_container'] ? 'reference container_reference' : 'reference';
						$shortcutContent .= '<div class="' . $className . '">';
						$shortcutContent .= $this->renderSingleElementHTML($parentObject, $itemRow);
						// NOTE: this is the end tag for <div class="t3-page-ce-body">
						// because of bad (historic) conception, starting tag has to be placed inside tt_content_drawHeader()
						$shortcutContent .= '<div class="reference-overlay"></div></div></div><br />';
					}
				}
			}
		}
		return $shortcutContent;
	}

	/**
	 * Sets column positions based on a selected gridelement layout
	 *
	 * @param array $parserRows: The parsed rows of the gridelement layout
	 * @param array $colPosValues: The column positions that have been found for that layout
	 * @return void
	 */
	public function setMultipleColPosValues($parserRows, &$colPosValues) {
		if (is_array($parserRows)) {
			foreach ($parserRows as $parserRow) {
				if (is_array($parserRow['columns.']) && count($parserRow['columns.']) > 0) {
					foreach ($parserRow['columns.'] as $parserColumns) {
						$name = $this->lang->sL($parserColumns['name'], true);
						if ($parserColumns['colPos'] !== '') {
							$colPosValues[intval($parserColumns['colPos'])] = array(
								'name' => $name,
								'allowed' => $parserColumns['allowed']
							);
						} else {
							$colPosValues[32768] = array(
								'name' => $this->lang->getLL('notAssigned'),
								'allowed' => ''
							);
						}
					}
				}
			}
		}
	}

	/**
	 * Directly returns the items for a single column if the rendering mode is set to single columns only
	 *
	 * @param tx_cms_layout $parentObject: The parent object that triggered this hook
	 * @param array $colPosValues: The column positions that have been found for that layout
	 * @param array $row: The current data row for the container item
	 * @param string $showHidden: query String containing enable fields
	 * @param string $deleteClause: query String to check for deleted items
	 * @return array collected items for this column
	 */
	public function setSingleColPosItems(tx_cms_layout $parentObject, &$colPosValues, &$row, $showHidden, $deleteClause) {
		// Due to the pid being "NOT USED" in makeQueryArray we have to set pidSelect here
		$originalPidSelect = $parentObject->pidSelect;
		$parentObject->pidSelect = 'pid = ' . $row['pid'];
		$specificUid = tx_gridelements_helper::getInstance()->getSpecificUid($row);

		$queryParts = $parentObject->makeQueryArray(
			'tt_content',
			$row['pid'],
			'AND colPos = -1 AND tx_gridelements_container=' .
				$specificUid .
				$showHidden .
				$deleteClause .
				$parentObject->showLanguage
		);

		// Due to the pid being "NOT USED" in makeQueryArray we have to reset pidSelect here
		$parentObject->pidSelect = $originalPidSelect;

		$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
		$colPosValues[] = array(0, '');
		return $parentObject->getResult($result);
	}

	/**
	 * renders the columns of a grid layout
	 *
	 * @param tx_cms_layout		$parentObject: The parent object that triggered this hook
	 * @param array				$colPosValues: The column positions we want to get the content for
	 * @param array				$gridContent: The rendered content data of the grid columns
	 * @param array				$row: The current data row for the container item
	 * @param array				$editUidList: determines if we will get edit icons or not
	 * @param boolean			$singleColumn: Determines if we are in single column mode or not
	 * @param array				$head: An array of headers for each of the columns
	 * @param string			$showHidden: query String containing enable fields
	 * @param string			$deleteClause: query String to check for deleted items
	 * @return void
	 */
	public function renderGridColumns(tx_cms_layout $parentObject, &$colPosValues, &$gridContent, &$row, &$editUidList, &$singleColumn, &$head, $showHidden, $deleteClause) {
		foreach ($colPosValues as $colPos => $values) {
			// first we have to create the column content separately for each column
			// so we can check for the first and the last element to provide proper sorting
			if ($singleColumn === FALSE) {
				$items = $this->collectItemsForColumn($parentObject, $colPos, $row, $showHidden, $deleteClause);
			} else {
				$items = array();
			}
			// if there are any items, we can create the HTML for them just like in the original TCEform
			if (count($items) > 0) {
				$this->renderSingleGridColumn($parentObject, $items, $colPos, $gridContent, $editUidList);
			}
			// we will need a header for each of the columns to activate mass editing for elements of that column
			$this->setColumnHeader($parentObject, $head, $colPos, $row, $values['name'], $editUidList);
		}
	}

	/**
	 * Collects tt_content data from a single tt_content element
	 *
	 * @param tx_cms_layout		$parentObject: The parent object that triggered this hook
	 * @param int			   	$colPos: The column position to collect the items for
	 * @param array			 	$row: The current data row for the container item
	 * @param string			$showHidden: query String containing enable fields
	 * @param string			$deleteClause: query String to check for deleted items
	 * @return array			collected items for the given column
	 */
	public function collectItemsForColumn(tx_cms_layout $parentObject, &$colPos, &$row, &$showHidden, &$deleteClause) {
		// Due to the pid being "NOT USED" in makeQueryArray we have to set pidSelect here
		$originalPidSelect = $parentObject->pidSelect;
		$parentObject->pidSelect = 'pid = ' . $row['pid'];

		$specificUid = tx_gridelements_helper::getInstance()->getSpecificUid($row);
		$queryParts = $parentObject->makeQueryArray(
			'tt_content',
			$row['pid'],
			'AND colPos = -1 AND tx_gridelements_container=' .
				$specificUid .
				' AND tx_gridelements_columns=' .
				$colPos .
				$showHidden .
				$deleteClause .
				$parentObject->showLanguage
		);

		// Due to the pid being "NOT USED" in makeQueryArray we have to reset pidSelect here
		$parentObject->pidSelect = $originalPidSelect;

		$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
		return $parentObject->getResult($result);
	}

	/**
	 * renders a single column of a grid layout and sets the edit uid list
	 *
	 * @param tx_cms_layout     $parentObject: The parent object that triggered this hook
	 * @param array             $items: The content data of the column to be rendered
	 * @param int               $colPos: The column position we want to get the content for
	 * @param array             $gridContent: The rendered content data of the grid column
	 * @param array             $editUidList: determines if we will get edit icons or not
	 * @return void
	 */
	public function renderSingleGridColumn(tx_cms_layout $parentObject, &$items, &$colPos, &$gridContent, &$editUidList) {
		foreach ($items as $itemRow) {
			if (is_array($itemRow)) {
				$statusHidden = $parentObject->isDisabled('tt_content', $itemRow)
					? ' t3-page-ce-hidden'
					: '';
				$gridContent[$colPos] .= '<div class="t3-page-ce' . $statusHidden . '">' .
					$this->renderSingleElementHTML($parentObject, $itemRow) .	'</div></div>';
				$editUidList[$colPos] .= $editUidList[$colPos]
					? ',' . $itemRow['uid']
					: $itemRow['uid'];
			}
		}
	}

	/**
	 * Sets the headers for a grid before content and headers are put together
	 *
	 * @param tx_cms_layout     $parentObject: The parent object that triggered this hook
	 * @param array             $head: The collected item data rows
	 * @param int               $colPos: The column position we want to get a header for
	 * @param array             $row: The current data row for the container item
	 * @param string            $name: The name of the header
	 * @param array             $editUidList: determines if we will get edit icons or not
	 * @return void
	 */
	public function setColumnHeader(tx_cms_layout $parentObject, &$head, &$colPos, &$row, &$name, &$editUidList) {
		$specificUid = tx_gridelements_helper::getInstance()->getSpecificUid($row);

		if ($colPos < 32768) {
			$newP = $parentObject->newContentElementOnClick(
				$row['pid'],
				'-1' .
					'&tx_gridelements_container=' . $specificUid .
					'&tx_gridelements_columns=' . $colPos,
				$parentObject->lP
			);
		}
		$head[$colPos] = $this->tt_content_drawColHeader(
			$name,
			($parentObject->doEdit && $editUidList[$colPos])
				? '&edit[tt_content][' . $editUidList[$colPos] . ']=edit' .
				$parentObject->pageTitleParamForAltDoc
				: '',
			$newP,
			$parentObject);
	}

	/**
	 * Draw header for a content element column:
	 *
	 * @param string $colName Column name
	 * @param string $editParams Edit params (Syntax: &edit[...] for alt_doc.php)
	 * @param string $newParams New element params (Syntax: &edit[...] for alt_doc.php)
	 * @param tx_cms_layout $parentObject
	 * @return string HTML table
	 */
	function tt_content_drawColHeader($colName, $editParams, $newParams, &$parentObject) {

		$icons = '';
		// Create command links:
		if ($parentObject->tt_contentConfig['showCommands']) {
			// New record:
			if ($newParams) {
				$icons .= '<a href="#" onclick="' . htmlspecialchars($newParams) . '" title="' . $GLOBALS['LANG']->getLL('newInColumn', TRUE) . '">' .
					t3lib_iconWorks::getSpriteIcon('actions-document-new') .
					'</a>';
			}
			// Edit whole of column:
			if ($editParams) {
				$icons .= '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($editParams, $parentObject->backPath)) . '" title="' . $GLOBALS['LANG']->getLL('editColumn', TRUE) . '">' .
					t3lib_iconWorks::getSpriteIcon('actions-document-open') .
					'</a>';
			}
			$icons .= '<a href="#" class="toggle-content toggle-up" title="' . $this->lang->sL('LLL:EXT:gridelements/locallang_db.xml:tx_gridelements_togglecontent') . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-move-to-top') .
				'</a>';
			$icons .= '<a href="#" class="toggle-content toggle-down" title="' . $this->lang->sL('LLL:EXT:gridelements/locallang_db.xml:tx_gridelements_togglecontent') . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-move-to-bottom') .
				'</a>';
		}
		if (strlen($icons)) {
			$icons = '<div class="t3-page-colHeader-icons">' . $icons . '</div>';
		}

		// Create header row:
		$out = '<div class="t3-page-colHeader t3-row-header">
					' . $icons . '
					<div class="t3-page-colHeader-label">' . htmlspecialchars($colName) . '</div>
				</div>';
		return $out;
	}

	/**
	 * Renders the grid layout table after the HTML content for the single elements has been rendered
	 *
	 * @param array     $layoutSetup: The setup of the layout that is selected for the grid we are going to render
	 * @param array     $row: The current data row for the container item
	 * @param array     $head: The data for the column headers of the grid we are going to render
	 * @param array     $gridContent: The content data of the grid we are going to render
	 * @return string
	 */
	public function renderGridLayoutTable($layoutSetup, $row, $head, $gridContent) {
		$specificUid = tx_gridelements_helper::getInstance()->getSpecificUid($row);

		$grid = '<div class="t3-gridContainer' .
			($layoutSetup['frame']
				? ' t3-gridContainer-' . $layoutSetup['frame']
				: ''
			) .
			($layoutSetup['top_level_layout']
				? ' t3-gridTLContainer'
				: ''
			) .
			'">';
		if ($layoutSetup['frame']) {
			$grid .= '<h4 class="t3-gridContainer-title-' . $layoutSetup['frame'] . '">' .
				$this->lang->sL($layoutSetup['title'], TRUE) .
				'</h4>';
		}
		$grid .= '<table border="0" cellspacing="1" cellpadding="4" width="100%" height="100%" class="t3-page-columns t3-gridTable">';
		// add colgroups
		$colCount = intval($layoutSetup['config']['colCount']);
		$rowCount = intval($layoutSetup['config']['rowCount']);
		$grid .= '<colgroup>';
		for ($i = 0; $i < $colCount; $i++) {
			$grid .= '<col style="width:' . (100 / $colCount) . '%"></col>';
		}
		$grid .= '</colgroup>';
		// cycle through rows
		for ($layoutRow = 1; $layoutRow <= $rowCount; $layoutRow++) {
			$rowConfig = $layoutSetup['config']['rows.'][$layoutRow . '.'];
			if (!isset($rowConfig)) {
				continue;
			}
			$grid .= '<tr>';
			for ($col = 1; $col <= $colCount; $col++) {
				$columnConfig = $rowConfig['columns.'][$col . '.'];
				if (!isset($columnConfig)) {
					continue;
				}
				// which column should be displayed inside this cell
				$columnKey = $columnConfig['colPos'] != '' ? intval($columnConfig['colPos']) : 32768;
				// allowed CTypes
				$allowedCTypes = t3lib_div::trimExplode(',', $columnConfig['allowed'], 1);
				foreach($allowedCTypes as &$ctype){
					$ctype = 't3-allow-' . $ctype;
				}
				// render the grid cell
				$colSpan = intval($columnConfig['colspan']);
				$rowSpan = intval($columnConfig['rowspan']);
				$grid .= '<td valign="top"' .
					(isset($columnConfig['colspan'])
						? ' colspan="' . $colSpan . '"'
						: '') .
					(isset($columnConfig['rowspan'])
						? ' rowspan="' . $rowSpan . '"'
						: '') .
					'id="column-' . $specificUid . 'x' . $columnKey . '" class="t3-gridCell t3-page-column t3-page-column-' . $columnKey .
					(!isset($columnConfig['colPos']) || $columnConfig['colPos'] == ''
						? ' t3-gridCell-unassigned'
						: '') .
					(isset($columnConfig['colspan']) && $columnConfig['colPos'] != ''
						? ' t3-gridCell-width' . $colSpan
						: '') .
					(isset($columnConfig['rowspan']) && $columnConfig['colPos'] != ''
						? ' t3-gridCell-height' . $rowSpan
						: '') .
					' ' . (count($allowedCTypes) ? join(' ', $allowedCTypes) : 't3-allow-all') .
					'">';

				$grid .= ($GLOBALS['BE_USER']->uc['hideColumnHeaders'] ? '' : $head[$columnKey]) . $gridContent[$columnKey];
				$grid .= '</td>';
			}
			$grid .= '</tr>';
		}
		$grid .= '</table></div>';
		return $grid;
	}

	/**
	 * Collects tt_content data from a single page or a page tree starting at a given page
	 *
	 * @param string    $shortcutItem: The single page to be used as the tree root
	 * @param array     $collectedItems: The collected item data rows ordered by parent position, column position and sorting
	 * @param int       $recursive: The number of levels for the recursion
	 * @param string    $showHidden: query String containing enable fields
	 * @param string    $deleteClause: query String to check for deleted items
	 * @return void
	 */
	public function collectContentDataFromPages($shortcutItem, &$collectedItems, $recursive = 0, &$showHidden, &$deleteClause) {
		$itemList = str_replace('pages_', '', $shortcutItem);
		if ($recursive) {
			if (!$this->tree instanceof t3lib_queryGenerator) {
				$this->tree = t3lib_div::makeInstance('t3lib_queryGenerator');
			}
			$itemList = $this->tree->getTreeList($itemList, intval($recursive), 0, 1);
		}
		$itemRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'tt_content',
			'pid IN (' . $itemList . ') AND colPos >= 0 ' .
				$showHidden .
				$deleteClause,
			'',
			'FIND_IN_SET(pid, \'' . $itemList . '\'),colPos,sorting'
		);
		foreach ($itemRows as $itemRow) {
			if($GLOBALS['BE_USER']->workspace > 0) {
				t3lib_BEfunc::workspaceOL('tt_content', $itemRow, $GLOBALS['BE_USER']->workspace);
			}
			$itemRow['tx_gridelements_reference_container'] = $itemRow['pid'];
			$collectedItems[] = $itemRow;
		}
	}

	/**
	 * Collects tt_content data from a single tt_content element
	 *
	 * @param string    $shortcutItem: The tt_content element to fetch the data from
	 * @param array     $collectedItems: The collected item data row
	 * @param string    $showHidden: query String containing enable fields
	 * @param string    $deleteClause: query String to check for deleted items
	 * @return void
	 */
	public function collectContentData($shortcutItem, &$collectedItems, &$showHidden, &$deleteClause) {
		$shortcutItem = str_replace('tt_content_', '', $shortcutItem);
		$itemRow = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'*',
			'tt_content',
			'uid=' .
				$shortcutItem .
				$showHidden .
				$deleteClause
		);
		if($GLOBALS['BE_USER']->workspace > 0) {
			t3lib_BEfunc::workspaceOL('tt_content', $itemRow, $GLOBALS['BE_USER']->workspace);
		}
		$collectedItems[] = $itemRow;
	}

	/**
	 * Renders the HTML code for a single tt_content element
	 *
	 * @param tx_cms_layout     $parentObject: The parent object that triggered this hook
	 * @param array             $itemRow: The data row to be rendered as HTML
	 * @return string
	 */
	public function renderSingleElementHTML(tx_cms_layout $parentObject, $itemRow) {
		$singleElementHTML = $parentObject->tt_content_drawHeader(
			$itemRow,
			$parentObject->tt_contentConfig['showInfo']
				? 15
				: 5,
			$parentObject->defLangBinding && $parentObject->lP > 0,
			TRUE);
		$isRTE = $parentObject->RTE && $parentObject->isRTEforField('tt_content', $itemRow, 'bodytext');
		$singleElementHTML .= '<div ' .
			(!empty($itemRow['_ORIG_uid'])
				? ' class="ver-element"'
				: '') .
			'>' .
			$parentObject->tt_content_drawItem($itemRow, $isRTE) .
			'</div>';
		return $singleElementHTML;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/class.tx_gridelements_drawitemhook.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/class.tx_gridelements_drawitemhook.php']);
}

?>
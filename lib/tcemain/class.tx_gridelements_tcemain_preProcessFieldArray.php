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
 * Class/Function which offers TCE main hook functions.
 *
 * @author		Jo Hasenau <info@cybercraft.de>
 * @package		TYPO3
 * @subpackage	tx_gridelements
 */
class tx_gridelements_tcemain_preProcessFieldArray extends tx_gridelements_tcemain_abstract {

	/**
	 * Function to set the colPos of an element depending on
	 * whether it is a child of a parent container or not
	 * will set colPos according to availability of the current grid column of an element
	 * 0 = no column at all
	 * -1 = grid element column
	 * -2 = non used elements column
	 * changes are applied to the field array of the parent object by reference
	 *
	 * @param	array $fieldArray: The array of fields and values that have been saved to the datamap
	 * @param	string $table: The name of the table the data should be saved to
	 * @param	integer $id: The parent uid of either the page or the container we are currently working on
	 * @param	\t3lib_TCEmain $parentObj: The parent object that triggered this hook
	 * @return void
	 */
	public function preProcessFieldArray(&$fieldArray, $table, $id, &$parentObj) {
		$this->init($table, $id, $parentObj);
		$this->saveCleanedUpFieldArray($fieldArray);
		$this->processFieldArrayForTtContent($fieldArray);
	}

	/**
	 * save cleaned up field array
	 *
	 * @param array $fieldArray
	 * @return array cleaned up field array
	 */
	public function saveCleanedUpFieldArray(array $fieldArray) {
		unset($fieldArray['pi_flexform']);
		$changedFieldArray = $this->tceMain->compareFieldArrayWithCurrentAndUnset($this->getTable(), $this->getPageUid(), $fieldArray);
		if ((isset($changedFieldArray['tx_gridelements_backend_layout']) && $this->getTable() == 'tt_content') || (isset($changedFieldArray['backend_layout']) && $this->getTable() == 'pages') || (isset($changedFieldArray['backend_layout_next_level']) && $this->getTable() == 'pages')) {
			$this->setUnusedElements($changedFieldArray);
		}
	}

	/**
	 * process field array for table tt_content
	 *
	 * @param array $fieldArray
	 * @return void
	 */
	public function processFieldArrayForTtContent(array &$fieldArray) {
		if ($this->getTable() == 'tt_content') {
			$pid = intval(t3lib_div::_GET('DDinsertNew'));

			if($fieldArray['tx_gridelements_backend_layout']) {
				$GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds']['*,gridelements_pi1'] = $this->layoutSetup->getFlexformConfiguration($fieldArray['tx_gridelements_backend_layout']);
			}
			if(abs($pid) > 0) {
				$this->setDefaultFieldValues($fieldArray, $pid);
				$this->getDefaultFlexformValues($fieldArray);
			}
		}
		$this->setFieldEntries($fieldArray, $pid);
	}

	/**
	 * set default field values for new records
	 *
	 * @param array $fieldArray
	 * @param int $pid
	 * @return void
	 */

	public function setDefaultFieldValues(&$fieldArray, $pid = 0) {
		// Default values:
		$newRow = array(); // Used to store default values as found here:

		// Default values as set in userTS:
		$TCAdefaultOverride = $GLOBALS['BE_USER']->getTSConfigProp('TCAdefaults');
		if (is_array($TCAdefaultOverride['tt_content.'])) {
			foreach ($TCAdefaultOverride['tt_content.'] as $theF => $theV) {
				if (isset($GLOBALS['TCA']['tt_content']['columns'][$theF])) {
					$newRow[$theF] = $theV;
				}
			}
		}

		if ($pid < 0) {
			$record = t3lib_beFunc::getRecord('tt_content', abs($pid), 'pid');
			$id = $record['pid'];
			unset($record);
		} else {
			$id = intval($pid);
		}

		$pageTS = t3lib_beFunc::getPagesTSconfig($id);

		if (isset($pageTS['TCAdefaults.'])) {
			$TCAPageTSOverride = $pageTS['TCAdefaults.'];
			if (is_array($TCAPageTSOverride['tt_content.'])) {
				foreach ($TCAPageTSOverride['tt_content.'] as $theF => $theV) {
					if (isset($GLOBALS['TCA']['tt_content']['columns'][$theF])) {
						$newRow[$theF] = $theV;
					}
				}
			}
		}

		// Default values as submitted:
		$this->defVals = t3lib_div::_GP('defVals');
		$this->overrideVals = t3lib_div::_GP('overrideVals');
		if (!is_array($this->defVals) && is_array($this->overrideVals))	{
			$this->defVals = $this->overrideVals;
		}
		if (is_array($this->defVals['tt_content'])) {
			foreach ($this->defVals['tt_content'] as $theF => $theV) {
				if (isset($GLOBALS['TCA']['tt_content']['columns'][$theF])) {
					$newRow[$theF] = $theV;
				}
			}
		}

		// Fetch default values if a previous record exists
		if ($pid < 0 && $GLOBALS['TCA']['tt_content']['ctrl']['useColumnsForDefaultValues']) {
			// Fetches the previous record:
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_content', 'uid=' . abs($id) . t3lib_BEfunc::deleteClause('tt_content'));
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				// Gets the list of fields to copy from the previous record.
				$fArr = t3lib_div::trimExplode(',', $GLOBALS['TCA']['tt_content']['ctrl']['useColumnsForDefaultValues'], 1);
				foreach ($fArr as $theF) {
					if (isset($GLOBALS['TCA']['tt_content']['columns'][$theF])) {
						$newRow[$theF] = $row[$theF];
					}
				}
			}
		}
		$fieldArray = array_merge($newRow, $fieldArray);
	}

	/**
	 * checks for default flexform values for new records and sets them accordingly
	 *
	 * @param array $fieldArray
	 * @return void
	 */

	public function getDefaultFlexformValues(&$fieldArray) {
		foreach($GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds'] as $key => $dataStructure) {
			$types = t3lib_div::trimExplode(',', $key);
			if(($types[0] == $fieldArray['list_type'] || $types[0] == '*') && ($types[1] == $fieldArray['CType'] || $types[1] == '*')) {
				$fieldArray['pi_flexform'] = $this->extractDefaultDataFromDatastructure($dataStructure);
			}
		}
	}

	/**
	 * extracts the default data out of a given XML data structure
	 *
	 * @param string $dataStructure
	 * @return string $defaultData
	 */

	public function extractDefaultDataFromDataStructure($dataStructure) {
		$returnXML = '';
		$sheetArray = array();
		if($dataStructure) {
			$structureArray = t3lib_div::xml2array($dataStructure);
			if(!isset($structureArray['sheets']) && isset($structureArray['ROOT'])) {
				$structureArray['sheets']['sDEF']['ROOT'] = $structureArray['ROOT'];
				unset($structureArray['ROOT']);
			}
			if(isset($structureArray['sheets']) && count($structureArray['sheets']) > 0) {
				foreach($structureArray['sheets'] as $sheetName => $sheet) {
					if(is_array($sheet['ROOT']['el']) && count($sheet['ROOT']['el']) > 0) {
						$elArray = array();
						foreach($sheet['ROOT']['el'] as $elName => $elConf) {
							$config = $elConf['TCEforms']['config'];
							$elArray[$elName]['vDEF'] = $config['default'];
							if(!$elArray[$elName]['vDEF'] && $config['type'] == 'select' && count($config['items']) > 0) {
								$elArray[$elName]['vDEF'] = $config['items'][0][1];
							}
						}
						$sheetArray['data'][$sheetName]['lDEF'] = $elArray;
					}
				};
			}
			if(count($sheetArray) > 0) {
				$flexformTools = t3lib_div::makeInstance('t3lib_flexformtools');
				$returnXML = $flexformTools->flexArray2Xml($sheetArray, true);
			}
		}
		return $returnXML;
	}

	/**
	 * set initial entries to field array
	 *
	 * @param array $fieldArray
	 * @param integer $pid
	 * @return void
	 */
	public function setFieldEntries(array &$fieldArray, $pid) {
		if ($pid > 0) {
			$this->setFieldEntriesForTargets($fieldArray, $pid);
		} else if (intval($fieldArray['tx_gridelements_container']) > 0 && strpos(key($this->getTceMain()->datamap['tt_content']), 'NEW') !== false) {
			$containerUpdateArray[intval($fieldArray['tx_gridelements_container'])] = 1;
			$this->doGridContainerUpdate($containerUpdateArray);
		}
		$this->setFieldEntriesForGridContainers($fieldArray);
	}

	/**
	 * set initial entries to field array
	 *
	 * @param array $fieldArray
	 * @param integer $pid
	 * @return void
	 */
	public function setFieldEntriesForTargets(array &$fieldArray, $pid) {
		if (count($fieldArray) && strpos($fieldArray['pid'], 'x') !== false) {
			$target = t3lib_div::trimExplode('x', $fieldArray['pid']);
			$fieldArray['pid'] = $pid;
			$targetUid = abs(intval($target[0]));
			$this->setFieldEntriesForColumnTargets($fieldArray, $targetUid, $target);
		} else {
			$this->setFieldEntriesForSimpleTargets($fieldArray);
		}
	}

	/**
	 * set entries to column targets
	 *
	 * @param array $fieldArray
	 * @param integer $targetUid
	 * @param array $target
	 * @return void
	 */
	public function setFieldEntriesForColumnTargets(array &$fieldArray, $targetUid, array $target) {
		if ($targetUid != $this->getPageUid()) {
			$fieldArray['colPos'] = -1;
			$fieldArray['sorting'] = 0;
			$fieldArray['tx_gridelements_container'] = $targetUid;
			$fieldArray['tx_gridelements_columns'] = intval($target[1]);
			$containerUpdateArray[$targetUid] = 1;
		} else {
			$fieldArray['colPos'] = intval($target[1]);
			$fieldArray['sorting'] = 0;
			$fieldArray['tx_gridelements_container'] = 0;
			$fieldArray['tx_gridelements_columns'] = 0;
		}

		$this->doGridContainerUpdate($containerUpdateArray);
	}

	/**
	 * set entries to simple targets
	 *
	 * @param array $fieldArray
	 * @return void
	 */
	public function setFieldEntriesForSimpleTargets(array &$fieldArray) {
		$targetElement = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'*',
			'tt_content',
			'uid=' . abs($fieldArray['pid'])
		);
		if ($targetElement['uid']) {
			if ($targetElement['tx_gridelements_container'] > 0) {
				$containerUpdateArray[$targetElement['tx_gridelements_container']] = 1;
				$this->doGridContainerUpdate($containerUpdateArray);
				$fieldArray['tx_gridelements_container'] = $targetElement['tx_gridelements_container'];
				$fieldArray['tx_gridelements_columns'] = $targetElement['tx_gridelements_columns'];
				$fieldArray['colPos'] = -1;
			}
			$fieldArray['colPos'] = $targetElement['colPos'];
			$fieldArray['sorting'] = $targetElement['sorting'] + 2;
		}
	}

	/**
	 * set/override entries to gridelements container
	 *
	 * @param array $fieldArray
	 * @return void
	 */
	public function setFieldEntriesForGridContainers(array &$fieldArray) {
		if (intval($fieldArray['tx_gridelements_container']) > 0 && isset($fieldArray['colPos']) && intval($fieldArray['colPos']) != -1) {
			$fieldArray['colPos'] = -1;
			$fieldArray['tx_gridelements_columns'] = 0;
		} else if (isset($fieldArray['tx_gridelements_container']) && intval($fieldArray['tx_gridelements_container']) === 0 && intval($fieldArray['colPos']) === -1) {
			$originalContainer = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
				'tx_gridelements_container',
				'tt_content',
				'uid=' . $this->getPageUid()
			);
			$containerUpdateArray[$originalContainer['tx_gridelements_container']] = -1;
			$this->doGridContainerUpdate($containerUpdateArray);

			$fieldArray['colPos'] = $this->checkForRootColumn(intval($this->getPageUid()));
			$fieldArray['tx_gridelements_columns'] = 0;
		}
	}

	/**
	 * Function to move elements to/from the unused elements column while changing the layout of a page or a grid element
	 *
	 * @param	array $fieldArray: The array of fields and values that have been saved to the datamap
	 * return void
	 */
	public function setUnusedElements(&$fieldArray) {
		$changedGridElements = array();
		$changedElements = array();
		$changedSubPageElements = array();

		if ($this->getTable() == 'tt_content') {
			$changedGridElements[$this->getPageUid()] = true;
			$availableColumns = $this->getAvailableColumns($fieldArray['tx_gridelements_backend_layout'], 'tt_content', $this->getPageUid());
			$childElementsInUnavailableColumns = array_keys(
				$GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'uid',
					'tt_content',
					'tx_gridelements_container = ' . $this->getPageUid() . '
					AND tx_gridelements_columns NOT IN (' . $availableColumns . ')',
					'',
					'',
					'',
					'uid'
				)
			);
			if(count($childElementsInUnavailableColumns) > 0) {
				$GLOBALS['TYPO3_DB']->sql_query('
					UPDATE tt_content
					SET colPos = -2, backupColPos = -1
					WHERE uid IN (' . join(',', $childElementsInUnavailableColumns) . ')
				');
				array_flip($childElementsInUnavailableColumns);
			} else {
				$childElementsInUnavailableColumns = array();
			}

			$childElementsInAvailableColumns = array_keys(
				$GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'uid',
					'tt_content',
					'tx_gridelements_container = ' . $this->getPageUid() . '
					AND tx_gridelements_columns IN (' . $availableColumns . ')',
					'',
					'',
					'',
					'uid'
				)
			);
			if(count($childElementsInAvailableColumns) > 0) {
				$GLOBALS['TYPO3_DB']->sql_query('
					UPDATE tt_content
					SET colPos = -1, backupColPos = -2
					WHERE uid IN (' . join(',', $childElementsInAvailableColumns) . ')
				');
				array_flip($childElementsInAvailableColumns);
			} else {
				$childElementsInAvailableColumns = array();
			}

			$changedGridElements = array_merge(
				$changedGridElements,
				$childElementsInUnavailableColumns,
				$childElementsInAvailableColumns
			);
		}

		if ($this->getTable() == 'pages') {
			$rootline = $this->beFunc->BEgetRootLine($this->getPageUid());
			for ($i = count($rootline); $i > 0; $i--) {
				$page = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
					'uid, backend_layout, backend_layout_next_level',
					'pages',
					'uid=' . intval($rootline[$i]['uid'])
				);
				$selectedBackendLayoutNextLevel = intval($page['backend_layout_next_level']);
				if ($page['uid'] == $this->getPageUid()) {
					if ($fieldArray['backend_layout_next_level'] != 0) {
						// Backend layout for subpages of the current page is set
						$backendLayoutNextLevelUid = intval($fieldArray['backend_layout_next_level']);
					}
					if ($fieldArray['backend_layout'] != 0) {
						// Backend layout for current page is set
						$backendLayoutUid = $fieldArray['backend_layout'];
						break;
					}
				} else if ($selectedBackendLayoutNextLevel == -1 && $page['uid'] != $this->getPageUid()) {
					// Some previous page in our rootline sets layout_next to "None"
					break;
				} else if ($selectedBackendLayoutNextLevel > 0 && $page['uid'] != $this->getPageUid()) {
					// Some previous page in our rootline sets some backend_layout, use it
					$backendLayoutUid = $selectedBackendLayoutNextLevel;
					break;
				}
			}

			if (isset($fieldArray['backend_layout'])) {
				$availableColumns = $this->getAvailableColumns($backendLayoutUid, 'pages', $this->getPageUid());
				$elementsInUnavailableColumns = array_keys(
					$GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
						'uid',
						'tt_content',
						'pid = ' . $this->getPageUid() . '
						AND colPos NOT IN (' . $availableColumns . ')',
						'',
						'',
						'',
						'uid'
					)
				);
				if(count($elementsInUnavailableColumns) > 0) {
					$GLOBALS['TYPO3_DB']->sql_query('
						UPDATE tt_content
						SET backupColPos = colPos, colPos = -2
						WHERE uid IN (' . join(',', $elementsInUnavailableColumns) . ')
					');
					array_flip($elementsInUnavailableColumns);
				} else {
					$elementsInUnavailableColumns = array();
				}

				$elementsInAvailableColumns = array_keys(
					$GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
						'uid',
						'tt_content',
						'pid = ' . $this->getPageUid() . '
						AND pid = ' . $this->getPageUid() . '
						AND backupColPos != -2
						AND backupColPos IN (' . $availableColumns . ')',
						'',
						'',
						'',
						'uid'
					)
				);
				if(count($childElementsInAvailableColumns) > 0) {
					$GLOBALS['TYPO3_DB']->sql_query('
						UPDATE tt_content
						SET colPos = backupColPos, backupColPos = -2
						WHERE uid IN (' . join(',', $elementsInAvailableColumns) . ')
					');
					array_flip($elementsInAvailableColumns);
				} else {
					$elementsInAvailableColumns = array();
				}

				$changedElements = array_merge(
					$elementsInUnavailableColumns,
					$elementsInAvailableColumns
				);
			}

			if (isset($fieldArray['backend_layout_next_level'])) {
				$backendLayoutUid = $backendLayoutNextLevelUid ? $backendLayoutNextLevelUid : $backendLayoutUid;
				$subpages = array();
				$this->getSubpagesRecursively($this->getPageUid(), $subpages);
				if (count($subpages)) {
					$changedSubPageElements = array();
					foreach ($subpages as $page) {
						$availableColumns = $this->getAvailableColumns($backendLayoutUid, 'pages', $page['uid']);
						$subPageElementsInUnavailableColumns = array_keys(
							$GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
								'uid',
								'tt_content',
								'pid = ' . $page['uid'] . '
								AND colPos NOT IN (' . $availableColumns . ')',
								'',
								'',
								'',
								'uid'
							)
						);
						if(count($subPageElementsInUnavailableColumns) > 0) {
							$GLOBALS['TYPO3_DB']->sql_query('
								UPDATE tt_content
								SET backupColPos = colPos, colPos = -2
								WHERE uid IN (' . join(',', $subPageElementsInUnavailableColumns) . ')
							');
							array_flip($subPageElementsInUnavailableColumns);
						} else {
							$subPageElementsInUnavailableColumns = array();
						}

						$subPageElementsInAvailableColumns = array_keys(
							$GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
								'uid',
								'tt_content',
								'pid = ' . $page['uid'] . '
								AND backupColPos != -2
								AND backupColPos IN (' . $availableColumns . ')',
								'',
								'',
								'',
								'uid'
							)
						);
						if(count($subPageElementsInAvailableColumns) > 0) {
							$GLOBALS['TYPO3_DB']->sql_query('
								UPDATE tt_content
								SET colPos = backupColPos, backupColPos = -2
								WHERE uid IN (' . join(',', $subPageElementsInAvailableColumns) . ')
							');
							array_flip($subPageElementsInAvailableColumns);
						} else {
							$subPageElementsInAvailableColumns = array();
						}

						$changedPageElements = array_merge(
							$subPageElementsInUnavailableColumns,
							$subPageElementsInAvailableColumns
						);
						$changedSubPageElements = array_merge(
							$changedSubPageElements,
							$changedPageElements
						);
					}
				}
			}
		}

		$changedElementUids = array_merge(
			$changedGridElements,
			$changedElements,
			$changedSubPageElements
		);
		if(count($changedElementUids) > 0) {
			foreach($changedElementUids as $uid => $value) {
				$this->tceMain->updateRefIndex('tt_content', $uid);
			}
		}
	}

	/**
	 * gets all subpages of the current page and traverses recursivley unless backend_layout_next_level is set or unset (!= 0)
	 *
	 * @param $pageUid
	 * @param $subpages
	 * @internal param int $id : the uid of the parent page
	 * @return  array   $subpages: Reference to a list of all subpages
	 */
	public function getSubpagesRecursively($pageUid, &$subpages) {
		$childPages = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid, backend_layout, backend_layout_next_level',
			'pages',
			'pid = ' . $pageUid
		);

		if (count($childPages)) {
			foreach ($childPages as $page) {
				if ($page['backend_layout'] == 0) {
					$subpages[] = $page;
				}
				if ($page['backend_layout_next_level'] == 0) {
					$this->getSubpagesRecursively($page['uid'], $subpages);
				}
			}
		}
	}

	/**
	 * Function to recursively determine the colPos of the root container
	 * so that an element that has been removed from any container
	 * will still remain in the same major page column
	 *
	 * @param	integer	$contentId: The uid of the current content element
	 * @param	integer $colPos: The current column of this content element
	 * @return integer $colPos: The new column of this content element
	 */
	public function checkForRootColumn($contentId, $colPos = 0) {
		$parent = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			't1.colPos, t1.tx_gridelements_container',
			'tt_content AS t1, tt_content AS t2',
			't1.uid=t2.tx_gridelements_container AND t2.uid=' . $contentId
		);

		if (count($parent) > 0 && $parent['tx_gridelements_container'] > 0) {
			$colPos = $this->checkForRootColumn($parent['tx_gridelements_container'], $parent['colPos']);
		} else {
			$colPos = intval($parent['colPos']);
		}

		return $colPos;
	}

	/**
	 * fetches all available columns for a certain grid container based on TCA settings and layout records
	 *
	 * @param   string  $layout: The selected backend layout of the grid container or the page
	 * @param   string  $table: The name of the table to get the layout for
	 * @param   int     $id: the uid of the parent container - being the page id for the table "pages"
	 * @return  CSV     $availableColumns: The columns available for the selected layout as CSV list
	 *
	 */
	public function getAvailableColumns($layout = '', $table = '', $id = 0) {
		$tcaColumns = array();

		if ($layout && $table == 'tt_content') {
			$tcaColumns = $this->layoutSetup->getLayoutColumns($layout);
			$tcaColumns = $tcaColumns['CSV'];
		} else if ($table == 'pages') {
			$tsConfig = $this->beFunc->getModTSconfig($id, 'TCEFORM.tt_content.colPos');
			$tcaConfig = $GLOBALS['TCA']['tt_content']['columns']['colPos']['config'];

			$tcaColumns = $tcaConfig['items'];
			$tcaColumns = $this->tceForms->addItems($tcaColumns, $tsConfig['properties']['addItems.']);

			if (isset($tcaConfig['itemsProcFunc']) && $tcaConfig['itemsProcFunc']) {
				$backendLayoutColumns = $this->getBackendLayoutColumns($layout, 'backend_layout');
				if (count($backendLayoutColumns)) {
					$tcaColumns = $backendLayoutColumns;
				}
			}

			foreach (t3lib_div::trimExplode(',', $tsConfig['properties']['removeItems'], 1) as $removeId) {
				foreach ($tcaColumns as $key => $item) {
					if ($item[1] == $removeId) {
						unset($tcaColumns[$key]);
					}
				}
			}

			$_tcaColumns = $tcaColumns;
			$tcaColumns = array(-2, -1);
			foreach($_tcaColumns as $item) {
				$tcaColumns[] = $item[1];
			}
			$tcaColumns = implode(',', $tcaColumns);
		}

		return $tcaColumns;
	}

	/**
	 * fetches all available columns for a certain backend layout
	 *
	 * @param   int     $layout: The selected backend layout of the grid container or the page
	 * @param   string  $table: The name of the table to get the layout from
	 * @param   int     $id: the uid of the parent container - being the page id for the table "pages"
	 * @return  array   $availableColumns: The columns available for the selected layout
	 *
	 */
	public function getBackendLayoutColumns($layout = 0, $table = '') {
		$backendLayout = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'*',
			$table,
			'uid=' . $layout
		);

		$availableColumns = array();

		if (isset($backendLayout['config']) && $backendLayout['config']) {
			$parser = t3lib_div::makeInstance('t3lib_TSparser');
			$parser->parse($backendLayout['config']);

			$backendLayout['__config'] = $parser->setup;

			// create colPosList
			if ($backendLayout['__config']['backend_layout.'] && $backendLayout['__config']['backend_layout.']['rows.']) {
				foreach ($backendLayout['__config']['backend_layout.']['rows.'] as $row) {
					if (isset($row['columns.']) && is_array($row['columns.'])) {
						foreach ($row['columns.'] as $column) {
							$availableColumns[] = array(
								1 => $column['colPos']
							);
						}
					}
				}
			}
		}

		return $availableColumns;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/tcemain/class.tx_gridelements_tcemain_preProcessFieldArray.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/class.tx_gridelements_tcemain_preProcessFieldArray.php']);
}
?>
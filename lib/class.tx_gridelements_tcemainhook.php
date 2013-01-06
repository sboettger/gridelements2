<?php

/**
 * Class/Function which offers TCE main hook functions.
 *
 * @author		Jo Hasenau <info@cybercraft.de>
 * @package		TYPO3
 * @subpackage	tx_gridelements
 */

class tx_gridelements_TCEmainHook {

	protected $layoutSetup;

	/**
	 * Function to set the colPos of an element depending on
	 * whether it is a child of a parent container or not
	 * will set colPos according to availability of the current grid column of an element
	 * 0 = no column at all
	 * -1 = grid element column
	 * -2 = non used elements column
	 * changes are applied to the field array of the parent object by reference
	 *
	 * @param	array           $fieldArray: The array of fields and values that have been saved to the datamap
	 * @param	str             $table: The name of the table the data should be saved to
	 * @param	int             $id: The uid of the page we are currently working on
	 * @param	\t3lib_TCEmain  $parentObj: The parent object that triggered this hook
	 * @return	void
	 *
	 */
	public function processDatamap_preProcessFieldArray(&$fieldArray, $table, $id, &$parentObj) {

        if (($table == 'tt_content' || $table == 'pages') && !$parentObj->isImporting) {

	        $this->layoutSetup = t3lib_div::makeInstance('tx_gridelements_layoutsetup', $id);

	        $checkFieldArray = $fieldArray;
            unset($checkFieldArray['pi_flexform']);

            $changedFieldArray = $parentObj->compareFieldArrayWithCurrentAndUnset($table, $id, $checkFieldArray);

            if ((isset($changedFieldArray['tx_gridelements_backend_layout']) && $table == 'tt_content') || (isset($changedFieldArray['backend_layout']) && $table == 'pages') || (isset($changedFieldArray['backend_layout_next_level']) && $table == 'pages')) {
                $this->setUnusedElements($table, $id, $changedFieldArray);
            }

            if ($table == 'tt_content') {
                $pid = intval(t3lib_div::_GET('DDinsertNew'));

                if ($pid > 0) {
                    if (count($fieldArray) && strpos($fieldArray['pid'], 'x') !== false) {
                        $target = t3lib_div::trimExplode('x', $fieldArray['pid']);
                        $fieldArray['pid'] = $pid;
                        $targetUid = abs(intval($target[0]));

                        if ($targetUid != $id) {
                            $fieldArray['colPos'] = -1;
                            $fieldArray['sorting'] = 0;
                            $fieldArray['tx_gridelements_container'] = $targetUid;
                            $fieldArray['tx_gridelements_columns'] = intval($target[1]);
                            $containerUpdateArray[] = $targetUid;
                        } else {
                            $fieldArray['colPos'] = intval($target[1]);
                            $fieldArray['sorting'] = 0;
                            $fieldArray['tx_gridelements_container'] = 0;
                            $fieldArray['tx_gridelements_columns'] = 0;
                        }

                        $this->doGridContainerUpdate($containerUpdateArray, $parentObj, 1);
                    } else {
                        $targetElement = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
                            '*',
                            'tt_content',
                            'uid=' . abs($fieldArray['pid'])
                        );
                        if ($targetElement['uid']) {
                            if ($targetElement['tx_gridelements_container'] > 0) {
                                $containerUpdateArray[] = $targetElement['tx_gridelements_container'];
                                $this->doGridContainerUpdate($containerUpdateArray, $parentObj, 1);
                                $fieldArray['tx_gridelements_container'] = $targetElement['tx_gridelements_container'];
                                $fieldArray['tx_gridelements_columns'] = $targetElement['tx_gridelements_columns'];
                                $fieldArray['colPos'] = -1;
                            }
                            $fieldArray['colPos'] = $targetElement['colPos'];
                            $fieldArray['sorting'] = $targetElement['sorting'] + 2;
                        }
                    }

                } else if (intval($fieldArray['tx_gridelements_container']) > 0 && strpos(key($parentObj->datamap['tt_content']), 'NEW') !== false) {
                    $containerUpdateArray[] = intval($fieldArray['tx_gridelements_container']);
                    $this->doGridContainerUpdate($containerUpdateArray, $parentObj, 1);
                }

                if (intval($fieldArray['tx_gridelements_container']) > 0 && intval($fieldArray['colPos']) != -1) {
                    $fieldArray['colPos'] = -1;
                    $fieldArray['tx_gridelements_columns'] = 0;
                } else if (isset($fieldArray['tx_gridelements_container']) && intval($fieldArray['tx_gridelements_container']) === 0 && intval($fieldArray['colPos']) === -1) {
                    $originalContainer = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
                        'tx_gridelements_container',
                        'tt_content',
                        'uid=' . $id
                    );
                    $containerUpdateArray[] = $originalContainer['tx_gridelements_container'];
                    $this->doGridContainerUpdate($containerUpdateArray, $parentObj, -1);

                    $fieldArray['colPos'] = $this->checkForRootColumn(intval($id));
                    $fieldArray['tx_gridelements_columns'] = 0;
                }

            }
        }

	}

	/**
	 * Function to set the colPos of an element depending on
	 * whether it is a child of a parent container or not
	 * will set colPos according to availability of the current grid column of an element
	 * 0 = no column at all
	 * -1 = grid element column
	 * -2 = non used elements column
	 * changes are applied to the field array of the parent object by reference
	 *
	 * @param	array           $fieldArray: The array of fields and values that have been saved to the datamap
	 * @param	str             $table: The name of the table the data should be saved to
	 * @param	int             $id: The uid of the page we are currently working on
	 * @param	\t3lib_TCEmain  $parentObj: The parent object that triggered this hook
	 * @return	void
	 *
	 */
	public function processDatamap_postProcessFieldArray($status, $table, $id, &$fieldArray, &$parentObj) {
		$cmd = t3lib_div::_GET('cmd');
		if(count($cmd) &&
				key($cmd) == 'tt_content' &&
				$status == 'new' &&
				strpos($cmd['tt_content'][key($cmd['tt_content'])]['copy'], 'x') !== FALSE &&
				!$parentObj->isImporting
		) {
			$positionArray = t3lib_div::trimexplode('x', $cmd['tt_content'][key($cmd['tt_content'])]['copy']);
			if($positionArray[0] < 0) {
				$parentPage = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('pid', 'tt_content', 'uid = ' . abs($positionArray[0]));
				if($parentPage['pid']) {
					$pid = $parentPage['pid'];
				}
			} else {
				$pid = intval($positionArray[0]);
			}
			$fieldArray['sorting'] = $parentObj->getSortNumber('tt_content', 0, $pid);
		}
	}

	/**
	 * Function to recursively determine the colPos of the root container
	 * so that an element that has been removed from any container
	 * will still remain in the same major page column
	 *
	 * @param	int	$contentId: The uid of the current content element
	 * @param	int $colPos: The current column of this content element
	 * @return  int	$colPos: The new column of this content element
	 *
	 */
	public function checkForRootColumn($contentId, $colPos = 0) {

		$parent = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			't1.colPos, t1.tx_gridelements_container',
			'tt_content AS t1, tt_content AS t2',
			't1.uid=t2.tx_gridelements_container
				AND t2.uid=' . $contentId
		);

		if (count($parent) > 0 && $parent['tx_gridelements_container'] > 0) {
			$colPos = $this->checkForRootColumn($parent['tx_gridelements_container'], $parent['colPos']);
		} else {
			$colPos = intval($parent['colPos']);
		}

		return $colPos;

	}

	/**
	 * Function to handle record movement to the first position of a column
	 *
	 * @param string            $table: The name of the table we are working on
	 * @param int               $uid: The uid of the record that is going to be moved
	 * @param string            $destPid: The target the record should be moved to
	 * @param array             $propArr: The array of properties for the move action
	 * @param array             $moveRec: An array of some values of the record that is going to be moved
	 * @param int               $resolvedPid: The calculated id of the page the record should be moved to
	 * @param boolean           $recordWasMoved: A switch to tell the parent object, if the record has been moved
	 * @param \t3lib_TCEmain    $parentObj: The parent object that triggered this hook
	 *
	 */
	public function moveRecord($table, $uid, &$destPid, &$propArr, &$moveRec, $resolvedPid, &$recordWasMoved, &$parentObj) {

		if ($table == 'tt_content' && !$parentObj->isImporting) {
			$cmd = t3lib_div::_GET('cmd');
			$originalElement = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
				'*',
				'tt_content',
				'uid=' . $uid
			);

			$containerUpdateArray[] = $originalElement['tx_gridelements_container'];

			if (strpos($cmd['tt_content'][$uid]['move'], 'x') !== false) {
				$target = t3lib_div::trimExplode('x', $cmd['tt_content'][$uid]['move']);
				$targetUid = abs(intval($target[0]));

				if ($targetUid != $uid && intval($target[0]) < 0) {
					$containerUpdateArray[] = $targetUid;
					$column = intval($target[1]);
					$updateArray = array(
						'colPos' => -1,
						'sorting' => 0,
						'tx_gridelements_container' => $targetUid,
						'tx_gridelements_columns' => $column
					);
				} else {
					$updateArray = array(
						'colPos' => intval($target[1]),
						'sorting' => 0,
						'tx_gridelements_container' => 0,
						'tx_gridelements_columns' => 0
					);
					if($targetUid != $uid) {
						$updateArray['pid'] = intval($target[0]);
					}
				}

				$destPid = -$uid;
				$parentObj->updateDB('tt_content', $uid, $updateArray);
				$this->doGridContainerUpdate($containerUpdateArray, $parentObj);
			} else if($cmd['tt_content'][$uid]['move']) {
				// to be done: handle moving with the up and down arrows via list module correctly

				/* $destPid = -$uid;
				$parentObj->updateDB('tt_content', $uid, $updateArray);
				$this->doGridContainerUpdate($containerUpdateArray, $parentObj);*/
			} else if(!count($cmd) && !$parentObj->moveChildren) {
				// pasting into the page via list module without knowing the desired column

				if($originalElement['CType'] == 'gridelements_pi1') {
					$parentObj->moveChildren = true;
				}

				$updateArray = array(
					'colPos' => 0,
					'sorting' => 0,
					'tx_gridelements_container' => 0,
					'tx_gridelements_columns' => 0
				);
				$parentObj->updateDB('tt_content', $uid, $updateArray);
				$this->doGridContainerUpdate($containerUpdateArray, $parentObj);
			}

		}

	}

	/**
	 * Function to handle record actions between different grid containers
	 *
	 * @param int               $uid: The uid of the grid container that needs an update
	 * @param \t3lib_TCEmain    $parentObj: The parent object that triggered this hook
	 * @param int               $newElement: Set this to 1 for updates of newly inserted elements or -1 when elements are removed from a container
	 * return void
	 *
	 */
	public function doGridContainerUpdate($containerUpdateArray = array(), &$parentObj, $newElement = 0) {

		foreach ($containerUpdateArray as $containerUid) {
			$fieldArray = array(
				'tx_gridelements_children' => 'tx_gridelements_children + ' . $newElement
			);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_content', 'uid=' . $containerUid, $fieldArray, 'tx_gridelements_children');
		}

	}

	/**
	 * Function to move elements to/from the unused elements column while changing the layout of a page or a grid element
	 *
	 * @param string    $table: The name of the table - should be tt_content or pages
	 * @param int       $uid: The uid of the grid container that needs an update
	 * return void
	 *
	 */
	public function setUnusedElements($table, $id, &$fieldArray) {

		if ($table == 'tt_content') {

			$availableColumns = $this->getAvailableColumns($fieldArray['tx_gridelements_backend_layout'], 'tt_content', $id);

			$GLOBALS['TYPO3_DB']->sql_query('
				UPDATE tt_content
				SET colPos = -2, backupColPos = -1
				WHERE tx_gridelements_container = ' . $id . '
				AND tx_gridelements_columns NOT IN (' . $availableColumns . ')
			');
			$GLOBALS['TYPO3_DB']->sql_query('
				UPDATE tt_content
				SET colPos = -1, backupColPos = -2
				WHERE tx_gridelements_container = ' . $id . '
				AND tx_gridelements_columns IN (' . $availableColumns . ')
			');
		}

		if ($table == 'pages') {

			$rootline = t3lib_BEfunc::BEgetRootLine($id);

			for ($i = count($rootline); $i > 0; $i--) {
				$page = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
					'uid, backend_layout, backend_layout_next_level',
					'pages',
					'uid=' . intval($rootline[$i]['uid'])
				);
				$selectedBackendLayout = intval($page['backend_layout']);
				$selectedBackendLayoutNextLevel = intval($page['backend_layout_next_level']);
				if ($page['uid'] == $id) {
					if ($fieldArray['backend_layout_next_level'] != 0) {
						// Backend layout for subpages of the current page is set
						$backendLayoutNextLevelUid = intval($fieldArray['backend_layout_next_level']);
					}
					if ($fieldArray['backend_layout'] != 0) {
						// Backend layout for current page is set
						$backendLayoutUid = $fieldArray['backend_layout'];
						break;
					}
				} else if ($selectedBackendLayoutNextLevel == -1 && $page['uid'] != $id) {
					// Some previous page in our rootline sets layout_next to "None"
					break;
				} else if ($selectedBackendLayoutNextLevel > 0 && $page['uid'] != $id) {
					// Some previous page in our rootline sets some backend_layout, use it
					$backendLayoutUid = $selectedBackendLayoutNextLevel;
					break;
				}
			}

			if (isset($fieldArray['backend_layout'])) {

				$availableColumns = $this->getAvailableColumns($backendLayoutUid, 'pages', $id);

				$GLOBALS['TYPO3_DB']->sql_query('
					UPDATE tt_content
					SET backupColPos = colPos, colPos = -2
					WHERE pid = ' . $id . '
					AND colPos NOT IN (' . $availableColumns . ')
				');
				$GLOBALS['TYPO3_DB']->sql_query('
					UPDATE tt_content
					SET colPos = backupColPos, backupColPos = -2
					WHERE pid = ' . $id . '
					AND backupColPos != -2
					AND backupColPos IN (' . $availableColumns . ')
				');
			}

			if (isset($fieldArray['backend_layout_next_level'])) {

				$backendLayoutUid = $backendLayoutNextLevelUid ? $backendLayoutNextLevelUid : $backendLayoutUid;

				$subpages = array();
				$this->getSubpagesRecursively($id, $subpages);

				if (count($subpages)) {

					foreach ($subpages as $page) {

						$availableColumns = $this->getAvailableColumns($backendLayoutUid, 'pages', $page['uid']);

						$GLOBALS['TYPO3_DB']->sql_query('
							UPDATE tt_content
							SET backupColPos = colPos, colPos = -2
							WHERE pid = ' . $page['uid'] . '
							AND colPos NOT IN (' . $availableColumns . ')
						');
						$GLOBALS['TYPO3_DB']->sql_query('
							UPDATE tt_content
							SET colPos = backupColPos, backupColPos = -2
							WHERE pid = ' . $page['uid'] . '
							AND backupColPos != -2
							AND backupColPos IN (' . $availableColumns . ')
						');
					}
				}
			}

		}

	}

	/**
	 * gets all subpages of the current page and traverses recursivley unless backend_layout_next_level is set or unset (!= 0)
	 *
	 * @param   int     $id: the uid of the parent page
	 * @return  array   $subpages: Reference to a list of all subpages
	 *
	 */
	public function getSubpagesRecursively($id, &$subpages) {

		$childPages = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid, backend_layout, backend_layout_next_level',
			'pages',
			'pid = ' . $id
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
	 * fetches all available columns for a certain grid container based on TCA settings and layout records
	 *
	 * @param   string  $layout: The selected backend layout of the grid container or the page
	 * @param   string  $table: The name of the table to get the layout for
	 * @param   int     $id: the uid of the parent container - being the page id for the table "pages"
	 * @return  CSV     $availableColumns: The columns available for the selected layout as CSV list
	 *
	 */
	public function getAvailableColumns($layout = '', $table = '', $id = 0) {

		$availableColumns = array();

		if ($layout && $table == 'tt_content') {
			$tcaColumns = $this->layoutSetup->getLayoutColumns($layout);
			$tcaColumns = $tcaColumns['CSV'];
		} else if ($table == 'pages') {
			$tsConfig = t3lib_BEfunc::getModTSconfig($id, 'TCEFORM.tt_content.colPos');
			$tcaConfig = $GLOBALS['TCA']['tt_content']['columns']['colPos']['config'];

			$tceForms = t3lib_div::makeInstance('t3lib_TCEForms');

			$tcaColumns = $tcaConfig['items'];
			$tcaColumns = $tceForms->addItems($tcaColumns, $tsConfig['properties']['addItems.']);

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

	/**
	 * Function to process the drag & drop copy action
	 *
	 * @param string            $command: The command to be handled by the command map
	 * @param string            $table: The name of the table we are working on
	 * @param int               $id: The id of the record that is going to be copied
	 * @param string            $value: The value that has been sent with the copy command
	 * @param boolean           $commandIsProcessed: A switch to tell the parent object, if the record has been copied
	 * @param \t3lib_TCEmain    $parentObj: The parent object that triggered this hook
	 * @return	void
	 *
	 */
	public function processCmdmap($command, $table, $id, $value, &$commandIsProcessed, &$parentObj) {

		$DDcopy = intval(t3lib_div::_GET('DDcopy'));
		$reference = intval(t3lib_div::_GET('reference'));
		$containerUpdateArray = array();

		if ($command == 'copy' &&
		    ($DDcopy == 1 || $reference == 1) &&
		    !$commandIsProcessed &&
		    $table == 'tt_content' &&
		    !$parentObj->isImporting
		) {

			$overrideArray = array();

			if($reference == 1) {
				t3lib_div::loadTCA('tt_content');
				foreach($GLOBALS['TCA']['tt_content']['columns'] as $key => $column) {
					if(strpos(',' . $GLOBALS['TCA']['tt_content']['ctrl']['copyAfterDuplFields'] . ',', ',' . $key . ',') === FALSE) {
						$overrideArray[$key] = '';
					}
				}
				$overrideArray['CType'] = 'shortcut';
				$overrideArray['records'] = $id;
				$overrideArray['header'] = 'Reference';
			}
			
			if (strpos($value, 'x') !== false) {
				$valueArray = t3lib_div::trimExplode('x', $value);
				$overrideArray['sorting'] = 0;

				if ((intval($valueArray[0]) > 0 && $valueArray[1] != '') || (abs($valueArray[0]) == $id)) {
					$overrideArray['tx_gridelements_container'] = 0;
					$overrideArray['tx_gridelements_columns'] = 0;
					$overrideArray['colPos'] = intval($valueArray[1]);
				} else if ($valueArray[1] != '') {
					$containerUpdateArray[] = abs($valueArray[0]);
					$overrideArray['colPos'] = -1;
					$overrideArray['tx_gridelements_container'] = abs($valueArray[0]);
					$overrideArray['tx_gridelements_columns'] = intval($valueArray[1]);
				}

				$parentObj->copyRecord($table, $id, intval($valueArray[0]), 1, $overrideArray);
				if(count($containerUpdateArray) > 0) {
				    $this->doGridContainerUpdate($containerUpdateArray, $parentObj);
				}

			} else {
				$parentObj->copyRecord($table, $id, $value, 1, $overrideArray);
			}

			$commandIsProcessed = true;

		}

	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/class.tx_gridelements_tcemainhook.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/class.tx_gridelements_tcemainhook.php']);
}

?>
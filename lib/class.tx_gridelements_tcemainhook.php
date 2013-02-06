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
class tx_gridelements_TCEmainHook {

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
	 * @param	t3lib_TCEmain   $parentObj: The parent object that triggered this hook
	 * @return void
	 */
	public function processDatamap_preProcessFieldArray(&$fieldArray, $table, $id, t3lib_TCEmain $parentObj) {
		if (($table == 'tt_content' || $table == 'pages') && !$parentObj->isImporting) {
			/** @var $hook tx_gridelements_tcemain_preProcessFieldArray */
			$hook = t3lib_div::makeInstance('tx_gridelements_tcemain_preProcessFieldArray');
			$hook->preProcessFieldArray($fieldArray, $table, $id, $parentObj);
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
	 * @param $status
	 * @param    str             $table: The name of the table the data should be saved to
	 * @param    int             $id: The uid of the page we are currently working on
	 * @param    array           $fieldArray: The array of fields and values that have been saved to the datamap
	 * @param    t3lib_TCEmain   $parentObj: The parent object that triggered this hook
	 * @return   void
	 */
	public function processDatamap_postProcessFieldArray($status, $table, $id, array &$fieldArray, t3lib_TCEmain $parentObj) {
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
	 * Function to handle record movement to the first position of a column
	 *
	 * @param string            $table: The name of the table we are working on
	 * @param int               $uid: The uid of the record that is going to be moved
	 * @param string            $destPid: The target the record should be moved to
	 * @param array             $propArr: The array of properties for the move action
	 * @param array             $moveRec: An array of some values of the record that is going to be moved
	 * @param int               $resolvedPid: The calculated id of the page the record should be moved to
	 * @param boolean           $recordWasMoved: A switch to tell the parent object, if the record has been moved
	 * @param t3lib_TCEmain     $parentObj: The parent object that triggered this hook
	 *
	 */
	public function moveRecord($table, $uid, &$destPid, &$propArr, &$moveRec, $resolvedPid, &$recordWasMoved, &$parentObj) {
		/** @var $hook tx_gridelements_tcemain_moveRecord */
		$hook = t3lib_div::makeInstance('tx_gridelements_tcemain_moveRecord');
		$hook->moveRecord($table, $uid, $destPid, $propArr, $moveRec, $resolvedPid, $recordWasMoved, $parentObj);
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
		/** @var $hook tx_gridelements_tcemain_processCmdmap */
		$hook = t3lib_div::makeInstance('tx_gridelements_tcemain_processCmdmap');
		$hook->processCmdmap($command, $table, $id, $value, $commandIsProcessed, $parentObj);
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/class.tx_gridelements_tcemainhook.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/class.tx_gridelements_tcemainhook.php']);
}
?>
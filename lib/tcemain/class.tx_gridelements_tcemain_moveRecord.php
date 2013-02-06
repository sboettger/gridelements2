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
class tx_gridelements_tcemain_moveRecord extends tx_gridelements_tcemain_abstract {

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
	 * @param t3lib_TCEmain     $parentObj
	 * @return void
	 */
	public function moveRecord($table, $uid, &$destPid, &$propArr, &$moveRec, $resolvedPid, &$recordWasMoved, &$parentObj) {
		$this->init($table, $uid, $parentObj);
		if ($table == 'tt_content' && !$this->getTceMain()->isImporting) {
			$copyAfterDuplFields = $GLOBALS['TCA']['tt_content']['ctrl']['copyAfterDuplFields'];
			$GLOBALS['TCA']['tt_content']['ctrl']['copyAfterDuplFields'] .= ',tx_gridelements_container,tx_gridelements_columns';
			$cmd = t3lib_div::_GET('cmd');
			$originalElement = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
				'*',
				'tt_content',
				'uid=' . $uid
			);
			$containerUpdateArray[$originalElement['tx_gridelements_container']] = -1;

			if (strpos($cmd['tt_content'][$uid]['move'], 'x') !== false) {
				$target = t3lib_div::trimExplode('x', $cmd['tt_content'][$uid]['move']);
				$targetUid = abs(intval($target[0]));
				$updateArray = $this->createUpdateArrayForSplitElements($uid, $destPid, $targetUid, $target, $containerUpdateArray);
			} else if($cmd['tt_content'][$uid]['move']) {
				// to be done: handle moving with the up and down arrows via list module correctly
				$targetElement = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
					'*',
					'tt_content',
					'uid=' . -$destPid
				);
				$containerUpdateArray[$targetElement['tx_gridelements_container']] += 1;
				$this->getTceMain()->moveRecord_raw('tt_content', $uid, $destPid);
				$this->getTceMain()->updateRefIndex('tt_content', $uid);
				$recordWasMoved = true;
			} else if(!count($cmd) && !$this->getTceMain()->moveChildren) {
				$updateArray = $this->createUpdateArrayForContainerMove($originalElement);
			}
			if(count($updateArray) > 0) {
				$this->getTceMain()->updateDB('tt_content', $uid, $updateArray);
			}
			$this->doGridContainerUpdate($containerUpdateArray);
			$GLOBALS['TCA']['tt_content']['ctrl']['copyAfterDuplFields'] = $copyAfterDuplFields;
		}
	}

	/**
	 * create update array for split elements (tt_content)
	 *
	 * @param integer $recordUid
	 * @param $destPid
	 * @param integer $targetUid
	 * @param array $target
	 * @param array $containerUpdateArray
	 * @return array UpdateArray
	 */
	public function createUpdateArrayForSplitElements($recordUid, &$destPid, $targetUid, array $target, array &$containerUpdateArray) {
		if ($targetUid != $recordUid && intval($target[0]) < 0) {
			$containerUpdateArray[$targetUid] += 1;
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
			if($targetUid != $recordUid) {
				$updateArray['pid'] = intval($target[0]);
			}
		}

		$destPid = -$recordUid;

		return $updateArray;
	}

	/**
	 * create update array for split elements (tt_content)
	 *
	 * @param array $originalElement
	 * @return array UpdateArray
	 */
	public function createUpdateArrayForContainerMove(array $originalElement) {
		if($originalElement['CType'] == 'gridelements_pi1') {
			$this->getTceMain()->moveChildren = true;
		}

		$updateArray = array(
			'colPos' => 0,
			'sorting' => 0,
			'tx_gridelements_container' => 0,
			'tx_gridelements_columns' => 0
		);

		return $updateArray;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/tcemain/class.tx_gridelements_tcemain_processCmdmap.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/class.tx_gridelements_tcemain_processCmdmap.php']);
}
?>
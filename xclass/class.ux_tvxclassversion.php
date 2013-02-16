<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Sebastian Böttger <sebastian.boettger@typovision.de>
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
 * XCLASS of 'tx_version_tcemain' extension.
 *
 * @author	Sebastian Böttger <sebastian.boettger@typovision.de>
 * @author  Jo Hasenau <info@cybercraft.de>
 * @package	TYPO3
 * @subpackage	gridelements
 */
class ux_tx_version_tcemain extends tx_version_tcemain {

	/**
	 * Swapping versions of a record
	 * Version from archive (future/past, called "swap version") will get the uid of the "t3ver_oid", the official element with uid = "t3ver_oid" will get the new versions old uid. PIDs are swapped also
	 *
	 * @param string $table Table name
	 * @param integer $id UID of the online record to swap
	 * @param integer $swapWith UID of the archived version to swap with!
	 * @param boolean $swapIntoWS If set, swaps online into workspace instead of publishing out of workspace.
	 * @param t3lib_TCEmain $tcemainObj TCEmain object
	 * @param string $comment Notification comment
	 * @param boolean $notificationEmailInfo Accumulate state changes in memory for compiled notification email?
	 * @param array $notificationAlternativeRecipients comma separated list of recipients to notificate instead of normal be_users
	 * @return void
	 */
	protected function version_swap($table, $id, $swapWith, $swapIntoWS = 0, t3lib_TCEmain $tcemainObj, $comment = '', $notificationEmailInfo = FALSE, $notificationAlternativeRecipients = array()) {

		// First, check if we may actually edit the online record
		if ($tcemainObj->checkRecordUpdateAccess($table, $id)) {

			// Select the two versions:
			$curVersion = t3lib_BEfunc::getRecord($table, $id, '*');
			$swapVersion = t3lib_BEfunc::getRecord($table, $swapWith, '*');
			$movePlh = array();
			$movePlhID = 0;

			if (is_array($curVersion) && is_array($swapVersion)) {
				if ($tcemainObj->BE_USER->workspacePublishAccess($swapVersion['t3ver_wsid'])) {
					$wsAccess = $tcemainObj->BE_USER->checkWorkspace($swapVersion['t3ver_wsid']);
					if ($swapVersion['t3ver_wsid'] <= 0 || !($wsAccess['publish_access'] & 1) || (int)$swapVersion['t3ver_stage'] === -10) {
						if ($tcemainObj->doesRecordExist($table,$swapWith,'show') && $tcemainObj->checkRecordUpdateAccess($table,$swapWith)) {
							if (!$swapIntoWS || $tcemainObj->BE_USER->workspaceSwapAccess()) {

								// Check if the swapWith record really IS a version of the original!
								if ((int)$swapVersion['pid'] == -1 && (int)$curVersion['pid'] >= 0 && !strcmp($swapVersion['t3ver_oid'], $id)) {

									// Lock file name:
									$lockFileName = PATH_site.'typo3temp/swap_locking/' . $table . ':' . $id . '.ser';

									if (!@is_file($lockFileName))	{

										// Write lock-file:
										t3lib_div::writeFileToTypo3tempDir($lockFileName, serialize(array(
											'tstamp' => $GLOBALS['EXEC_TIME'],
											'user'   => $tcemainObj->BE_USER->user['username'],
											'curVersion'  => $curVersion,
											'swapVersion' => $swapVersion
										)));

										// Find fields to keep
										$keepFields = array();
										if (isset($GLOBALS['TCA'][$table]['ctrl']['keepFields']) && $GLOBALS['TCA'][$table]['ctrl']['keepFields'] != '') {
											$keepFields = t3lib_div::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['keepFields']);
										}

										$keepFields = array_merge($keepFields, $tcemainObj->getUniqueFields($table));

										if ($GLOBALS['TCA'][$table]['ctrl']['sortby']) {
											$keepFields[] = $GLOBALS['TCA'][$table]['ctrl']['sortby'];
										}
										// l10n-fields must be kept otherwise the localization
										// will be lost during the publishing
										if (!isset($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable']) && $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']) {
											$keepFields[] = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
										}

										// Swap "keepfields"
										foreach ($keepFields as $fN) {
											$tmp = $swapVersion[$fN];
											$swapVersion[$fN] = $curVersion[$fN];
											$curVersion[$fN] = $tmp;
										}

										// Preserve states:
										$t3ver_state = array();
										$t3ver_state['swapVersion'] = $swapVersion['t3ver_state'];
										$t3ver_state['curVersion'] = $curVersion['t3ver_state'];

										// Modify offline version to become online:
										$tmp_wsid = $swapVersion['t3ver_wsid'];
										// Set pid for ONLINE
										$swapVersion['pid'] = intval($curVersion['pid']);
										// We clear this because t3ver_oid only make sense for offline versions
										// and we want to prevent unintentional misuse of this
										// value for online records.
										$swapVersion['t3ver_oid'] = 0;
										// In case of swapping and the offline record has a state
										// (like 2 or 4 for deleting or move-pointer) we set the
										// current workspace ID so the record is not deselected
										// in the interface by t3lib_BEfunc::versioningPlaceholderClause()
										$swapVersion['t3ver_wsid'] = 0;
										if ($swapIntoWS) {
											if ($t3ver_state['swapVersion'] > 0) {
												$swapVersion['t3ver_wsid'] = $tcemainObj->BE_USER->workspace;
											} else {
												$swapVersion['t3ver_wsid'] = intval($curVersion['t3ver_wsid']);
											}
										}
										$swapVersion['t3ver_tstamp'] = $GLOBALS['EXEC_TIME'];
										$swapVersion['t3ver_stage'] = 0;
										if (!$swapIntoWS) {
											$swapVersion['t3ver_state'] = 0;
										}

										// Moving element.
										if ((int)$GLOBALS['TCA'][$table]['ctrl']['versioningWS'] >= 2)	{
											//  && $t3ver_state['swapVersion']==4   // Maybe we don't need this?
											if ($plhRec = t3lib_BEfunc::getMovePlaceholder($table, $id, 't3ver_state,pid,uid' . ($GLOBALS['TCA'][$table]['ctrl']['sortby'] ? ',' . $GLOBALS['TCA'][$table]['ctrl']['sortby'] : ''))) {
												$movePlhID = $plhRec['uid'];
												$movePlh['pid'] = $swapVersion['pid'];
												$swapVersion['pid'] = intval($plhRec['pid']);

												$curVersion['t3ver_state'] = intval($swapVersion['t3ver_state']);
												$swapVersion['t3ver_state'] = 0;

												if ($GLOBALS['TCA'][$table]['ctrl']['sortby']) {
													// sortby is a "keepFields" which is why this will work...
													$movePlh[$GLOBALS['TCA'][$table]['ctrl']['sortby']] = $swapVersion[$GLOBALS['TCA'][$table]['ctrl']['sortby']];
													$swapVersion[$GLOBALS['TCA'][$table]['ctrl']['sortby']] = $plhRec[$GLOBALS['TCA'][$table]['ctrl']['sortby']];
												}
											}
										}

										// Take care of relations in each field (e.g. IRRE):
										if (is_array($GLOBALS['TCA'][$table]['columns'])) {
											foreach ($GLOBALS['TCA'][$table]['columns'] as $field => $fieldConf) {
												$this->version_swap_procBasedOnFieldType(
													$table, $field, $fieldConf['config'], $curVersion, $swapVersion, $tcemainObj
												);
											}
										}
										unset($swapVersion['uid']);

										// Modify online version to become offline:
										unset($curVersion['uid']);
										// Set pid for OFFLINE
										$curVersion['pid'] = -1;
										$curVersion['t3ver_oid'] = intval($id);
										$curVersion['t3ver_wsid'] = ($swapIntoWS ? intval($tmp_wsid) : 0);
										$curVersion['t3ver_tstamp'] = $GLOBALS['EXEC_TIME'];
										$curVersion['t3ver_count'] = $curVersion['t3ver_count']+1;	// Increment lifecycle counter
										$curVersion['t3ver_stage'] = 0;
										if (!$swapIntoWS) {
											$curVersion['t3ver_state'] = 0;
										}

										// Registering and swapping MM relations in current and swap records:
										$tcemainObj->version_remapMMForVersionSwap($table, $id, $swapWith);

										// Generating proper history data to prepare logging
										$tcemainObj->compareFieldArrayWithCurrentAndUnset($table, $id, $swapVersion);
										$tcemainObj->compareFieldArrayWithCurrentAndUnset($table, $swapWith, $curVersion);

										// Execute swapping:
										$sqlErrors = array();
										$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' . intval($id), $swapVersion);
										if ($GLOBALS['TYPO3_DB']->sql_error()) {
											$sqlErrors[] = $GLOBALS['TYPO3_DB']->sql_error();
										} else {
											$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' . intval($swapWith), $curVersion);
											if ($GLOBALS['TYPO3_DB']->sql_error()) {
												$sqlErrors[] = $GLOBALS['TYPO3_DB']->sql_error();
											} else {
												unlink($lockFileName);
											}
										}

										if (!count($sqlErrors)) {
											// Register swapped ids for later remapping:
											$this->remappedIds[$table][$id] =$swapWith;
											$this->remappedIds[$table][$swapWith] = $id;

											// If a moving operation took place...:
											if ($movePlhID) {
												// Remove, if normal publishing:
												if (!$swapIntoWS) {
													// For delete + completely delete!
													$tcemainObj->deleteEl($table, $movePlhID, TRUE, TRUE);
												} else {
													// Otherwise update the movePlaceholder:
													$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' . intval($movePlhID), $movePlh);
													$tcemainObj->addRemapStackRefIndex($table, $movePlhID);
												}
											}

											// Checking for delete:
											// Delete only if new/deleted placeholders are there.
											if (!$swapIntoWS && ((int)$t3ver_state['swapVersion'] === 1 || (int)$t3ver_state['swapVersion'] === 2)) {
												// Force delete
												$tcemainObj->deleteEl($table, $id, TRUE);
											}

											$tcemainObj->newlog2(($swapIntoWS ? 'Swapping' : 'Publishing') . ' successful for table "' . $table . '" uid ' . $id . '=>' . $swapWith, $table, $id, $swapVersion['pid']);

											// Update reference index of the live record:
											$tcemainObj->addRemapStackRefIndex($table, $id);

											// Set log entry for live record:
											$propArr = $tcemainObj->getRecordPropertiesFromRow($table, $swapVersion);
											if ($propArr['_ORIG_pid'] == -1) {
												$label = $GLOBALS['LANG']->sL ('LLL:EXT:lang/locallang_tcemain.xml:version_swap.offline_record_updated');
											} else {
												$label = $GLOBALS['LANG']->sL ('LLL:EXT:lang/locallang_tcemain.xml:version_swap.online_record_updated');
											}
											$theLogId = $tcemainObj->log($table, $id, 2, $propArr['pid'], 0, $label , 10, array($propArr['header'], $table . ':' . $id), $propArr['event_pid']);
											$tcemainObj->setHistory($table, $id, $theLogId);

											// Update reference index of the offline record:
											$tcemainObj->addRemapStackRefIndex($table, $swapWith);
											// Set log entry for offline record:
											$propArr = $tcemainObj->getRecordPropertiesFromRow($table, $curVersion);
											if ($propArr['_ORIG_pid'] == -1) {
												$label = $GLOBALS['LANG']->sL ('LLL:EXT:lang/locallang_tcemain.xml:version_swap.offline_record_updated');
											} else {
												$label = $GLOBALS['LANG']->sL ('LLL:EXT:lang/locallang_tcemain.xml:version_swap.online_record_updated');
											}
											$theLogId = $tcemainObj->log($table, $swapWith, 2, $propArr['pid'], 0, $label, 10, array($propArr['header'], $table . ':' . $swapWith), $propArr['event_pid']);
											$tcemainObj->setHistory($table, $swapWith, $theLogId);

											$stageId = -20; // Tx_Workspaces_Service_Stages::STAGE_PUBLISH_EXECUTE_ID;
											if ($notificationEmailInfo) {
												$notificationEmailInfoKey = $wsAccess['uid'] . ':' . $stageId . ':' . $comment;
												$this->notificationEmailInfo[$notificationEmailInfoKey]['shared'] = array($wsAccess, $stageId, $comment);
												$this->notificationEmailInfo[$notificationEmailInfoKey]['elements'][] = $table . ':' . $id;
												$this->notificationEmailInfo[$notificationEmailInfoKey]['alternativeRecipients'] = $notificationAlternativeRecipients;
											} else {
												$this->notifyStageChange($wsAccess, $stageId, $table, $id, $comment, $tcemainObj, $notificationAlternativeRecipients);
											}
											// Write to log with stageId -20
											$tcemainObj->newlog2('Stage for record was changed to ' . $stageId . '. Comment was: "' . substr($comment, 0, 100) . '"', $table, $id);
											$tcemainObj->log($table, $id, 6, 0, 0, 'Published', 30, array('comment' => $comment, 'stage' => $stageId));

											// Clear cache:
											$tcemainObj->clear_cache($table, $id);

											// Checking for "new-placeholder" and if found, delete it (BUT FIRST after swapping!):
											if (!$swapIntoWS && $t3ver_state['curVersion']>0) {
												// For delete + completely delete!
												$tcemainObj->deleteEl($table, $swapWith, TRUE, TRUE);
											}
										} else $tcemainObj->newlog('During Swapping: SQL errors happened: ' . implode('; ', $sqlErrors), 2);
									} else $tcemainObj->newlog('A swapping lock file was present. Either another swap process is already running or a previous swap process failed. Ask your administrator to handle the situation.', 2);
								} else $tcemainObj->newlog('In swap version, either pid was not -1 or the t3ver_oid didn\'t match the id of the online version as it must!', 2);
							} else $tcemainObj->newlog('Workspace #' . $swapVersion['t3ver_wsid'] . ' does not support swapping.', 1);
						} else $tcemainObj->newlog('You cannot publish a record you do not have edit and show permissions for', 1);
					} else $tcemainObj->newlog('Records in workspace #' . $swapVersion['t3ver_wsid'] . ' can only be published when in "Publish" stage.', 1);
				} else $tcemainObj->newlog('User could not publish records from workspace #' . $swapVersion['t3ver_wsid'], 1);
			} else $tcemainObj->newlog('Error: Either online or swap version could not be selected!', 2);
		} else $tcemainObj->newlog('Error: You cannot swap versions for a record you do not have access to edit!', 1);
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/xclass/class.ux_tvxclassversion.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/xclass/class.ux_tvxclassversion.php']);
}
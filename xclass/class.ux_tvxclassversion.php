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
 * @package	TYPO3
 * @subpackage	tx_tvxclassversion
 */
class ux_tx_version_tcemain extends tx_version_tcemain {



    /**
     * hook that is called when no prepared command was found
     *
     * @param string $command the command to be executed
     * @param string $table the table of the record
     * @param integer $id the ID of the record
     * @param mixed $value the value containing the data
     * @param boolean $commandIsProcessed can be set so that other hooks or
     * 				TCEmain knows that the default cmd doesn't have to be called
     * @param t3lib_TCEmain $tcemainObj reference to the main tcemain object
     * @return	void
     */
    public function processCmdmap($command, $table, $id, $value, &$commandIsProcessed, t3lib_TCEmain $tcemainObj) {
                           #die ('hier wird der swap dann ausgeführt');
        // custom command "version"
        if ($command == 'version') {
            $commandIsProcessed = TRUE;
            $action = (string) $value['action'];
            switch ($action) {

                case 'new':
                    // check if page / branch versioning is needed,
                    // or if "element" version can be used
                    $versionizeTree = -1;
                    if (isset($value['treeLevels'])) {
                        $versionizeTree = t3lib_utility_Math::forceIntegerInRange($value['treeLevels'], -1, 100);
                    }
                    if ($table == 'pages' && $versionizeTree >= 0) {
                        $this->versionizePages($id, $value['label'], $versionizeTree, $tcemainObj);
                    } else {
                        $tcemainObj->versionizeRecord($table, $id, $value['label']);
                    }
                    break;

                case 'swap':
                    #$table = 'tt_content';
                    $GLOBALS['ux_tvxclassversion']['keepfields_backup'] = $GLOBALS['TCA'][$table]['columns'];
                    $old_table = $table;
                    $table = 'tt_content';

                    $GLOBALS['TCA'][$table]['columns']['tx_gridelements_backend_layout']['config']['type'] = 'input';
                    $GLOBALS['TCA'][$table]['columns']['tx_gridelements_backend_layout']['config']['eval'] = 'uniqueInPid';

                    $GLOBALS['TCA'][$table]['columns']['tx_gridelements_children']['config']['type'] = 'input';
                    $GLOBALS['TCA'][$table]['columns']['tx_gridelements_children']['config']['eval'] = 'uniqueInPid';

                    $GLOBALS['TCA'][$table]['columns']['tx_gridelements_container']['config']['type'] = 'input';
                    $GLOBALS['TCA'][$table]['columns']['tx_gridelements_container']['config']['eval'] = 'uniqueInPid';

                    $GLOBALS['TCA'][$table]['columns']['tx_gridelements_columns']['config']['type'] = 'input';
                    $GLOBALS['TCA'][$table]['columns']['tx_gridelements_columns']['config']['eval'] = 'uniqueInPid';
                    $table = $old_table;

                    #die(print_r(, true));
                    $this->version_swap($table, $id, $value['swapWith'], $value['swapIntoWS'], $tcemainObj);
                    $GLOBALS['TCA'][$table]['columns'] = $GLOBALS['ux_tvxclassversion']['keepfields_backup'];
                    break;

                case 'clearWSID':
                    $this->version_clearWSID($table, $id, FALSE, $tcemainObj);
                    break;

                case 'flush':
                    $this->version_clearWSID($table, $id, TRUE, $tcemainObj);
                    break;

                case 'setStage':
                    $elementIds = t3lib_div::trimExplode(',', $id, TRUE);
                    foreach ($elementIds as $elementId) {
                        $this->version_setStage($table, $elementId, $value['stageId'],
                            (isset($value['comment']) && $value['comment'] ? $value['comment'] : $this->generalComment),
                            TRUE,
                            $tcemainObj,
                            $value['notificationAlternativeRecipients']
                        );
                    }
                    break;
            }
        }
    }
}

#t3lib_div::loadTCA('tt_content');
#$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1'] = 'layout,select_key,pages';



?>
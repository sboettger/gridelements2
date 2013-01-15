<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Jo Hasenau <info@cybercraft.de>, Dirk Hoffmann <hoffmann@vmd-jena.de>
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
 * Class/Function which manipulates the query parts while fetching tt_content records within the list module.
 *
 * @author		Jo Hasenau <info@cybercraft.de>
 * @author		Dirk Hoffmann <hoffmann@vmd-jena.de>
 * @package		TYPO3
 * @subpackage	tx_gridelements
 */
class tx_gridelements_db_ListHook {

	/**
	 * @var t3lib_BEfunc
	 */
	var $beFunc;

	public function __construct() {
		$this->beFunc = t3lib_div::makeInstance('t3lib_BEfunc');
	}

	/**
	 * ItemProcFunc for columns items
	 *
	 * @param array	        $queryParts: The array containing the parts to build the query from
	 * @param \recordList   $parent: The parent object that triggered this hook
	 * @param string        $table: The name of the table we are currently working on
	 * @param int           $pageId: The uid of the page we are currently working on
	 * @param string        $addWhere: A string to be added to the WHERE clause
	 * @param string        $fieldList: A list of fields to be considered during the query
	 * @param array         $params: An array of parameters
	 * @return	void
	 */
	public function makeQueryArray_post(&$queryParts, &$parent, $table, $pageId, &$addWhere, &$fieldList, &$params)	{
		if ($table == 'tt_content' && get_class($parent) == 'ux_localRecordList') {
			$queryParts['ORDERBY'] = $this->addValueToList($queryParts['ORDERBY'], 'colPos');
			$queryParts['WHERE'] .= ' AND colPos != -1';

			if ($queryParts['SELECT'] != '*') {
				$queryParts['SELECT'] = $this->addValueToList($queryParts['SELECT'], 'colPos');
			}
		}
	}

	/**
	 * adds a new value to the given list
	 *
	 * @param string $list comma seperated list of values
	 * @param string $value
	 * @return string
	 */
	public function addValueToList($list, $value) {
		$parts = t3lib_div::trimExplode(',', $list, TRUE);
		array_unshift($parts, $value);
		return implode(',', array_unique($parts));
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/hooks/class.tx_gridelements_db_listhook.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/class.tx_gridelements_db_listhook.php']);
}

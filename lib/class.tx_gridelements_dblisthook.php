<?php

/**
 * Class/Function which manipulates the query parts while fetching tt_content records within the list module.
 *
 * @author		Jo Hasenau <info@cybercraft.de>
 * @package		TYPO3
 * @subpackage	tx_gridelements
 */
class tx_gridelements_dbListHook {

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

		if ($table == 'tt_content' && get_class($parent) == 'localRecordList') {
			/** @var t3lib_BEfunc $BEfunc  */
			$BEfunc = t3lib_div::makeInstance('t3lib_BEfunc');
			$TSconfig = $BEfunc->getPagesTSconfig($pageId);

			if ($TSconfig['TCEFORM.']['tt_content.']['tx_gridelements_backend_layout.']['removeChildrenFromList']) {
				$queryParts['WHERE'] .= ' AND colPos != -1';
			} else {
				$queryParts['ORDERBY'] = 'tx_gridelements_container, colPos, tx_gridelements_columns, sorting, '. $queryParts['ORDERBY'];
			}

		}

	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/class.tx_gridelements_dblisthook.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/class.tx_gridelements_dblisthook.php']);
}

?>
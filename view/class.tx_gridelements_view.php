<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Jo Hasenau <info@cybercraft.de>
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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

/**
 * Plugin 'Grid Element' for the 'gridelements' extension.
 *
 * @author	Jo Hasenau <info@cybercraft.de>
 * @package	TYPO3
 * @subpackage	tx_gridelements
 */
class tx_gridelements_view extends tslib_cObj {

	public $prefixId = 'tx_gridelements_view'; // Same as class name
	public $scriptRelPath = 'view/class.tx_gridelements_view.php'; // Path to this script relative to the extension dir.
	public $extKey = 'gridelements'; // The extension key.

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	public function main($content = '', $conf = array()) {

		// first we have to take care of possible flexform values containing additional information
		// that is not available via DB relations. It will be added as "virtual" key to the existing data Array
		// so that you can easily get the values with TypoScript
		$this->initPiFlexForm();
		$this->getPiFlexFormData();

		// now we have to find the children of this grid container regardless of their column
		// so we can get them within a single DB query instead of doing a query per column
		// but we will only fetch those columns that are used by the current grid layout
		$element = $this->cObj->data['uid'];
		$layout = $this->cObj->data['tx_gridelements_backend_layout'];

		/** @var tx_gridelements_layoutsetup $layoutSetup  */
		$layoutSetup = t3lib_div::makeInstance('tx_gridelements_layoutsetup');
		$layoutSetup->init($this->cObj->data['pid'], $conf);

		$availableColumns = $layoutSetup->getLayoutColumns($layout);
		$csvColumns = str_replace('-2,-1,', '', $availableColumns['CSV']);
		$this->getChildren($element, $csvColumns);

		// and we have to determine the frontend setup related to the backend layout record which is assigned to this container
		$typoScriptSetup = $layoutSetup->getTypoScriptSetup($layout);

		// if there are any children available, we can start with the render process
		if (count($this->cObj->data['tx_gridelements_view_children'])) {
			// we need a sorting columns array to make sure that the columns are rendered in the order
			// that they have been created in the grid wizard but still be able to get all children
			// within just one SELECT query
			$sortColumns = t3lib_div::trimExplode(',', $csvColumns);

			$this->renderChildrenIntoParentColumns($typoScriptSetup, $sortColumns, $availableColumns);
			unset($children);
			unset($sortColumns);

			// if there are any columns available, we can go on with the render process
			if (count($this->cObj->data['tx_gridelements_view_columns'])) {
				$content = $this->renderColumnsIntoParentGrid($typoScriptSetup);
			}
		}

		unset($availableColumns);
		unset($csvColumns);

		// finally we can unset the columns setup as well and apply stdWrap operations to the overall result
		// before returning the content
		unset($typoScriptSetup['columns.']);

		$content = count($typoScriptSetup)
			? $this->cObj->stdWrap($content, $typoScriptSetup)
			: $content;

		return $content;

	}

	/**
	 * fetches all available children for a certain grid container
	 *
	 * @param   int     $element: The uid of the grid container
	 * @param string $csvColumns : A list of available column IDs
	 * @return  array   $children: The child elements of this grid container
	 */
	public function getChildren($element = 0, $csvColumns = '') {

		$children = array();

		if ($element) {
			$where = 'tx_gridelements_container = ' . $element .
				$this->cObj->enableFields('tt_content') .
				' AND colPos != -2
				AND pid > 0
				AND tx_gridelements_columns IN (' . $csvColumns . ')
				AND sys_language_uid IN (' . $this->getSysLanguageContent() . ')';

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				'tt_content',
				$where,
				'',
				'sorting ASC'
			);

			if (!$GLOBALS['TYPO3_DB']->sql_error()) {
				$this->cObj->data['tx_gridelements_view_children'] = array();
				while ($child = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					// Versioning preview:
					$GLOBALS['TSFE']->sys_page->versionOL('tt_content', $child);

					// Language overlay:
					if (is_array($child) && $GLOBALS['TSFE']->sys_language_contentOL) {
						$child = $GLOBALS['TSFE']->sys_page->getRecordOverlay('tt_content', $child, $GLOBALS['TSFE']->sys_language_content, $GLOBALS['TSFE']->sys_language_contentOL);
					}

					if (is_array($child)) {
						$this->cObj->data['tx_gridelements_view_children'][] = $child;
						unset($child);
					}
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}
		}
	}

	/**
	 * get sys language content
	 *
	 * @return int|string
	 */
	public function getSysLanguageContent() {
		if ($GLOBALS['TSFE']->sys_language_contentOL) {
			// Sys language content is set to zero/-1 - and it is expected that whatever routine processes the output will
			// OVERLAY the records with localized versions!
			return '0,-1';
		} else {
			return intval($GLOBALS['TSFE']->sys_language_content);
		}
	}

	/**
	 * fetches values from the grid flexform and assigns them to virtual fields in the data array
	 *
	 * @return void
	 */
	public function getPiFlexFormData() {
		$piFlexForm = $this->cObj->data['pi_flexform'];

		if (is_array($piFlexForm['data'])) {
			foreach ($piFlexForm['data'] as $sheet => $data) {
				foreach ($data as $lang => $value) {
					foreach ($value as $key => $val) {
						$this->cObj->data['flexform_' . $key] = $this->getFFvalue($piFlexForm, $key, $sheet);
					}
				}
			}
		}

		unset($piFlexForm);
	}

	/**
	 * renders the children of the grid container and
	 * puts them into their respective columns
	 *
	 * @param array $typoScriptSetup
	 * @param   array   $sortColumns: An Array of column positions within the grid container in the order they got in the grid setup
	 */
	public function renderChildrenIntoParentColumns($typoScriptSetup = array(), $sortColumns = array()) {

		// first we have to make a backup copy of the original data array
		// and we have to modify the depth counter to avoid stopping too early

		$currentParentGrid = $this->copyCurrentParentGrid();
		$columns = $this->getUsedColumns($sortColumns);
		$parentGridData = $this->getParentGridData($currentParentGrid['data']);
		$parentGridData['tx_gridelements_view_columns'] = $columns;

		$counter = count($this->cObj->data['tx_gridelements_view_children']);
		$parentRecordNumbers = array();
		$GLOBALS['TSFE']->cObjectDepthCounter += $counter;

		// each of the children will now be rendered separately and the output will be added to it's particular column
		foreach ($this->cObj->data['tx_gridelements_view_children'] as $child) {
			$renderedChild = $child;
			$this->renderChildIntoParentColumn($columns, $renderedChild, $parentGridData, $parentRecordNumbers, $typoScriptSetup);
			$currentParentGrid['data']['tx_gridelements_view_child_' . $child['uid']] = $renderedChild;
			unset($renderedChild);
		}

		// now we can reset the depth counter and the data array so that the element will behave just as usual
		// it will just contain the additional tx_gridelements_view section with the prerendered elements
		// it is important to do this before any stdWrap functions are applied to the grid container
		// since they will depend on the original data
		$GLOBALS['TSFE']->cObjectDepthCounter -= $counter;

		$this->resetCurrentParentGrid($currentParentGrid);
		if(count($sortColumns)) {
			$this->cObj->data['tx_gridelements_view_columns'] = array();
			foreach ($sortColumns as $sortKey) {
				if (isset($parentGridData['tx_gridelements_view_columns'][$sortKey])) {
					$this->cObj->data['tx_gridelements_view_columns'][$sortKey] = $parentGridData['tx_gridelements_view_columns'][$sortKey];
				}
			}
		}
		unset($parentGridData);
		unset($currentParentGrid);

	}

	/**
	 *
	 *
	 * @param array $sortColumns
	 * @return array
	 */
	public function getUsedColumns($sortColumns = array()) {
		$columns = array();

		// we need the array values as keys
		if(count($sortColumns) > 0) {
			foreach($sortColumns as $column_number) {
				$columns[$column_number] = '';
			}
		}

		unset($sortColumns);

		return $columns;
	}

	/**
	 *
	 *
	 * @return array
	 */
	public function copyCurrentParentGrid() {

		$data['record'] = $this->cObj->currentRecord;
		$data['data'] = $this->cObj->data;
		$data['parentRecordNumber'] = $this->cObj->parentRecordNumber;

		return $data;

	}

	/**
	 * @param $data
	 * @return array
	 */
	public function resetCurrentParentGrid($data = array()) {

		$this->cObj->currentRecord = $data['record'];
		$this->cObj->data = $data['data'];

		$this->cObj->parentRecordNumber = $data['parentRecordNumber'];

		unset($data);
	}

	/**
	 *
	 *
	 * @param $data
	 * @return array
	 */
	public function getParentGridData($data = array()) {

		$parentGridData = array();

		foreach($data as $key => $value) {
			if(substr($key, 0, 11) != 'parentgrid_' && substr($key, 0, 21) != 'tx_gridelements_view_') {
				$parentGridData['parentgrid_'.$key] = $value;
			}
		}

		unset($data);

		return $parentGridData;
	}


	/**
	 * renders the columns of the grid container and returns the actual content
	 *
	 * @param array $child
	 * @param array $parentGridData
	 * @param array $parentRecordNumbers
	 * @param array $typoScriptSetup
	 * @return  void
	 */
	public function renderChildIntoParentColumn($columns, &$child, &$parentGridData, &$parentRecordNumbers, $typoScriptSetup = array()) {

		$column_number = intval($child['tx_gridelements_columns']);
		$columnKey = $column_number . '.';

		if (!isset($typoScriptSetup['columns.'][$columnKey])) {
			$columnSetupKey = 'default.';
		} else {
			$columnSetupKey = $columnKey;
		}

		if($child['uid'] > 0) {

			$this->cObj->start(array_merge($child, $parentGridData), 'tt_content');

			$parentRecordNumbers[$columnKey]++;
			$this->cObj->parentRecordNumber = $parentRecordNumbers[$columnKey];

			// we render each child into the children key to provide them prerendered for usage with your own templating
			$child = $this->cObj->cObjGetSingle(
				$typoScriptSetup['columns.'][$columnSetupKey]['renderObj'],
				$typoScriptSetup['columns.'][$columnSetupKey]['renderObj.']
			);
			// then we assign the prerendered child to the appropriate column
			if(isset($columns[$column_number])) {
				$parentGridData['tx_gridelements_view_columns'][$column_number] .= $child;
			}
			unset($columns);
		}

		unset($typoScriptSetup);
	}

	/**
	 * renders the columns of the grid container and returns the actual content
	 *
	 * @param   array   $setup: The adjusted setup of the grid container
	 * @return  array   $content: The raw HTML output of the grid container before stdWrap functions will be applied to it
	 *
	 */
	public function renderColumnsIntoParentGrid($setup = array()) {

		$content = '';

		foreach ($this->cObj->data['tx_gridelements_view_columns'] as $column => $columnContent) {
			// if there are any columns available, we have to determine the corresponding TS setup
			// and if there is none we are going to use the default setup
			$tempSetup = isset($setup['columns.'][$column . '.'])
				? $setup['columns.'][$column . '.']
				: $setup['columns.']['default.'];
			// now we just have to unset the renderObj
			// before applying the rest of the keys via the usual stdWrap operations
			unset($tempSetup['renderObj']);
			unset($tempSetup['renderObj.']);

			// we render each column into the column key to provide them prerendered for usage  with your own templating
			$this->cObj->data['tx_gridelements_view_column_' . $column] = count($tempSetup)
				? $this->cObj->stdWrap($columnContent, $tempSetup)
				: $columnContent;
			$content .= $this->cObj->data['tx_gridelements_view_column_' . $column];
		}

		return $content;

	}

	/**
	 * renders a recursive pidList to reference content from a list of pages
	 *
	 */
	public function user_getTreeList() {
		$GLOBALS['TSFE']->register['pidInList'] = trim(
			($this->cObj->data['uid'] .
				',' .
				($GLOBALS['TSFE']->register['tt_content_shortcut_recursive'] ?
					$this->cObj->getTreeList(
						$this->cObj->data['uid'],
						$GLOBALS['TSFE']->register['tt_content_shortcut_recursive']
					) : ''
				)
			),
			','
		);
	}

	/**
	 * Converts $this->cObj->data['pi_flexform'] from XML string to flexForm array.
	 *
	 * @param	string		Field name to convert
	 * @return	void
	 */
	function initPIflexForm($field='pi_flexform')	{
		// Converting flexform data into array:
		if (!is_array($this->cObj->data[$field]) && $this->cObj->data[$field])	{
			$this->cObj->data[$field] = t3lib_div::xml2array($this->cObj->data[$field]);
			if (!is_array($this->cObj->data[$field]))	$this->cObj->data[$field]=array();
		}
	}

	/**
	 * Return value from somewhere inside a FlexForm structure
	 *
	 * @param	array		FlexForm data
	 * @param	string		Field name to extract. Can be given like "test/el/2/test/el/field_templateObject" where each part will dig a level deeper in the FlexForm data.
	 * @param	string		Sheet pointer, eg. "sDEF"
	 * @param	string		Language pointer, eg. "lDEF"
	 * @param	string		Value pointer, eg. "vDEF"
	 * @return	string		The content.
	 */
	function getFFvalue($T3FlexForm_array,$fieldName,$sheet='sDEF',$lang='lDEF',$value='vDEF')	{
		$sheetArray = is_array($T3FlexForm_array) ? $T3FlexForm_array['data'][$sheet][$lang] : '';
		if (is_array($sheetArray))	{
			return $this->getFFvalueFromSheetArray($sheetArray,explode('/',$fieldName),$value);
		}
	}

	/**
	 * Returns part of $sheetArray pointed to by the keys in $fieldNameArray
	 *
	 * @param	array		Multidimensiona array, typically FlexForm contents
	 * @param	array		Array where each value points to a key in the FlexForms content - the input array will have the value returned pointed to by these keys. All integer keys will not take their integer counterparts, but rather traverse the current position in the array an return element number X (whether this is right behavior is not settled yet...)
	 * @param	string		Value for outermost key, typ. "vDEF" depending on language.
	 * @return	mixed		The value, typ. string.
	 * @access private
	 * @see pi_getFFvalue()
	 */
	function getFFvalueFromSheetArray($sheetArray,$fieldNameArr,$value)	{

		$tempArr=$sheetArray;
		foreach($fieldNameArr as $k => $v)	{
			if(t3lib_div::compat_version('4.6')) {
				$checkedValue = t3lib_utility_Math::canBeInterpretedAsInteger($v);
			} else {
				$checkedValue = t3lib_div::testInt($v);
			}
			if ($checkedValue)	{
				if (is_array($tempArr))	{
					$c=0;
					foreach($tempArr as $values)	{
						if ($c==$v)	{
							#debug($values);
							$tempArr=$values;
							break;
						}
						$c++;
					}
				}
			} else {
				$tempArr = $tempArr[$v];
			}
		}
		return $tempArr[$value];
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/view/class.tx_gridelements_view.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/view/class.tx_gridelements_view.php']);
}
?>
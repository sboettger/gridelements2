<?php
require_once('conf.php');

if($BACK_PATH_ABS !== false){
	require($BACK_PATH_ABS . 'init.php');
	require($BACK_PATH_ABS . 'template.php');
}else{
	require($BACK_PATH . 'init.php');
	require($BACK_PATH . 'template.php');
}

$LANG->includeLLFile('EXT:lang/locallang_wizards.xml');

/**
 * Script Class for grid wizard
 *
 * @author	T3UXW09 Team1 <modernbe@cybercraft.de>
 * @package TYPO3
 * @subpackage core
 */
class SC_wizard_gridelements_backend_layout {

	// GET vars:
	protected $P; // Wizard parameters, coming from TCEforms linking to the wizard.

	/**
	 * document template object
	 *
	 * @var smallDoc
	 */
	public $doc;
	protected $content; // Accumulated content.


	/**
	 * Initialises the Class
	 *
	 * @return	void
	 */
	public function init() {


		// Setting GET vars (used in frameset script):
		$this->P = t3lib_div::_GP('P', 1);

		//data[layouts][2][config]
		$this->formName = $this->P['formName'];
		$this->fieldName = $this->P['itemName'];
		$this->md5ID = $this->P['md5ID'];
		$uid = intval($this->P['uid']);

		// Initialize document object:
		$this->doc = t3lib_div::makeInstance('noDoc');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];

		$pageRenderer = $this->doc->getPageRenderer();
		$pageRenderer->addJsFile($GLOBALS['BACK_PATH'] . t3lib_extMgm::extRelPath('gridelements') . 'res/js/grideditor.js');
		$pageRenderer->addJsInlineCode('storeData', '
			function storeData(data)	{
				if (parent.opener && parent.opener.document && parent.opener.document.' . $this->formName . ' && parent.opener.document.' . $this->formName . '["' . $this->fieldName . '"])	{
					parent.opener.document.' . $this->formName . '["' . $this->fieldName . '"].value = data;
					parent.opener.TBE_EDITOR.fieldChanged("backend_layout","' . $uid . '","config","data[backend_layout][' . $uid . '][config]");
				}
			}
		');

		$languageLabels = array(
			'save' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_wizards.xml:grid_labelSave', 1),
			'title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_wizards.xml:grid_windowTitle', 1),
			'name' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_wizards.xml:grid_labelName', 1),
			'column' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_wizards.xml:grid_labelColumn', 1),
			'editCell' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_wizards.xml:grid_editCell', 1),
			'mergeCell' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_wizards.xml:grid_mergeCell', 1),
			'splitCell' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_wizards.xml:grid_splitCell', 1),
			'name' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_wizards.xml:grid_name', 1),
			'column' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_wizards.xml:grid_column', 1),
			'notSet' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_wizards.xml:grid_notSet', 1),
			'nameHelp' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_wizards.xml:grid_nameHelp', 1),
			'columnHelp' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_wizards.xml:grid_columnHelp', 1),
			'allowedElementTypes' => $GLOBALS['LANG']->sL('LLL:EXT:gridelements/locallang_db.xml:allowedElementTypes', 1),
			'allowedElementTypesHelp' => $GLOBALS['LANG']->sL('LLL:EXT:gridelements/locallang_db.xml:allowedElementTypesHelp', 1),

		);
		$pageRenderer->addInlineLanguageLabelArray($languageLabels);

		// add gridelement wizard options information
		$ctypeLabels = array();
		$ctypeIcons = array();
		foreach($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] as $item){
			$itemKey = $item[1];
			if(substr($itemKey, 0, 2) !== '--'){
				$ctypeLabels[$itemKey] = $GLOBALS['LANG']->sL($item[0], 1);
				if(strstr($item[2], '/typo3')){
					$ctypeIcons[$itemKey] = '../../../' . $item[2];
				}else{
					$ctypeIcons[$itemKey] = '../../../' . '../typo3/sysext/t3skin/icons/gfx/' . $item[2];
				}
			}
		}
		$pageRenderer->addInlineLanguageLabelArray($ctypeLabels);
		$pageRenderer->addJsInlineCode('availableCTypes', '
			TYPO3.Backend.availableCTypes = ["' . join('","', array_keys($ctypeLabels)) . '"];
			TYPO3.Backend.availableCTypeIcons = ["' . join('","', $ctypeIcons) . '"];
		');

			// select record
		$record = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($this->P['field'], $this->P['table'], 'uid=' . intval($this->P['uid']));

		if (trim($record[0][$this->P['field']]) == '') {
			$t3GridData = "[[{colspan:1,rowspan:1,spanned:false,name:''}]]";
			$colCount = 1;
			$rowCount = 1;
		} else {

			// load TS parser
			$parser = t3lib_div::makeInstance('t3lib_TSparser');
			$parser->parse($record[0][$this->P['field']]);
			$data = $parser->setup['backend_layout.'];
			$t3GridData = '[';
			$colCount = $data['colCount'];
			$rowCount = $data['rowCount'];
			$dataRows = $data['rows.'];
			$spannedMatrix = array();

			for ($i = 1; $i <= $rowCount; $i++) {
				$rowString = '';

				for ($j = 1; $j <= $colCount; $j++) {

					if ($j == 1) {
						$row = array_shift($dataRows);
						$columns = $row['columns.'];
						$rowString = '[';
						$cells = array();
					}

					if (!$spannedMatrix[$i][$j]) {

						if (is_array($columns) && count($columns)) {
							$column = array_shift($columns);
							$cellString = '{';
							$cellData = array();

							if (isset($column['colspan'])) {
								$cellData[] = 'colspan:' . intval($column['colspan']);

								if (isset($column['rowspan'])) {

									for ($spanRow = 0; $spanRow < intval($column['rowspan']); $spanRow++) {

										for ($spanColumn = 0; $spanColumn < intval($column['colspan']); $spanColumn++) {
											$spannedMatrix[$i + $spanRow][$j + $spanColumn] = 1;
										}
									}

								} else {

									for ($spanColumn = 0; $spanColumn < intval($column['colspan']); $spanColumn++) {
										$spannedMatrix[$i][$j + $spanColumn] = 1;
									}

								}

							} else {
								$cellData[] = 'colspan:1';

								if (isset($column['rowspan'])) {

									for ($spanRow = 0; $spanRow < intval($column['rowspan']); $spanRow++) {
										$spannedMatrix[$i + $spanRow][$j] = 1;
									}

								}

							}

							if (isset($column['rowspan'])) {
								$cellData[] = 'rowspan:' . intval($column['rowspan']);
							} else {
								$cellData[] = 'rowspan:1';
							}

							if (isset($column['name'])) {
								$cellData[] = 'name:\'' . $column['name'] . '\'';
							}

							if (isset($column['colPos'])) {
								$cellData[] = 'column:' . $column['colPos'];
							}

							if (isset($column['allowed'])) {
								$cellData[] = 'allowed:\'' . $column['allowed'] . '\'';
							}

							$cellString .= implode(',', $cellData) . '}';
							$cells[] = $cellString;

						}

					} else {
						$cells[] = '{colspan:1,rowspan:1,spanned:1}';
					}

				}

				$rowString .= implode(',', $cells);

				if ($rowString) {
					$rowString .= ']';
				}

				$rows[] = $rowString;

				if (count($spannedMatrix[$i])) {
					ksort($spannedMatrix[$i]);
				}

			}

			$t3GridData .= implode(',', $rows) . ']';

		}

		$pageRenderer->enableExtJSQuickTips();

		$pageRenderer->addExtOnReadyCode('
			t3Grid = new TYPO3.Backend.t3Grid({
				data: ' . $t3GridData . ',
				colCount: ' . $colCount . ',
				rowCount: ' . $rowCount . ',
				targetElement: \'editor\'
			});
			t3Grid.drawTable();
			');


		$this->doc->styleSheetFile_post = t3lib_extMgm::extRelPath('gridelements') . 'res/css/grideditor.css';

	}

	/**
	 * Main Method, rendering either colorpicker or frameset depending on ->showPicker
	 *
	 * @return	void
	 */
	public function main() {

		$content = '<a href="#" title="' .
		            $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveDoc', TRUE) . '" onclick="storeData(t3Grid.export2LayoutRecord());return true;">' .
		            t3lib_iconWorks::getSpriteIcon('actions-document-save') . '</a>';

		$content .= '<a href="#" title="' .
		            $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveCloseDoc', TRUE) . '" onclick="storeData(t3Grid.export2LayoutRecord());window.close();return true;">' .
		            t3lib_iconWorks::getSpriteIcon('actions-document-save-close') . '</a>';

		$content .= '<a href="#" title="' .
		            $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.closeDoc', TRUE) . '" onclick="window.close();return true;">' .
		            t3lib_iconWorks::getSpriteIcon('actions-document-close') . '</a>';


		$content .= $this->doc->spacer(10);

		$content .= '
		<table border="0" width="100%" height="100%" id="outer_container">
			<tr>
				<td class="editor_cell">
					<div id="editor">
					</div>
				</td>
				<td width="20" valign="center">
					<a class="addCol" href="#" title="' . $GLOBALS['LANG']->getLL('grid_addColumn') . '" onclick="t3Grid.addColumn(); t3Grid.drawTable(\'editor\');">
						<img src="../res/img/t3grid-tableright.png" border="0" />
					</a><br />
					<a class="removeCol" href="#" title="' . $GLOBALS['LANG']->getLL('grid_removeColumn') . '" onclick="t3Grid.removeColumn(); t3Grid.drawTable(\'editor\');">
						<img src="../res/img/t3grid-tableleft.png" border="0" />
					</a>
				</td>
			</tr>
			<tr>
				<td colspan="2" height="20" align="center">
					<a class="addCol" href="#" title="' . $GLOBALS['LANG']->getLL('grid_addRow') . '" onclick="t3Grid.addRow(); t3Grid.drawTable(\'editor\');">
						<img src="../res/img/t3grid-tabledown.png" border="0" />
					</a>
					<a class="removeCol" href="#" title="' . $GLOBALS['LANG']->getLL('grid_removeRow') . '" onclick="t3Grid.removeRow(); t3Grid.drawTable(\'editor\');">
						<img src="../res/img/t3grid-tableup.png" border="0" />
					</a>
				</td>
			</tr>
		</table>
		';

		$this->content = $content;
	}

	/**
	 * Returns the sourcecode to the browser
	 *
	 * @return	void
	 */
	public function printContent() {
		echo $this->doc->render(
			'Grid wizard',
			$this->content
		);
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/wizard_gridelements_backend_layout.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/wizard_gridelements_backend_layout.php']);
}

// Make instance:
/* @var $SOBE SC_wizard_gridelements_backend_layout */
$SOBE = t3lib_div::makeInstance('SC_wizard_gridelements_backend_layout');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>
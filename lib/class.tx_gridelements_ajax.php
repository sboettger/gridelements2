<?php
// load models and views

/**
 * AJAX request disatcher
 *
 * @author      Dirk Hoffmann <dirk-hoffmann@telekom.de>
 * @package     TYPO3
 * @subpackage  tx_gridelements
 */
class tx_gridelements_ajax {

	/**
	 * The content for the ajax output
	 *
	 * @var	string
	 */
	protected $content;

	/**
	 * Hold all valid params
	 *
	 * @var	array
	 */
	protected $validParams = array(
		'cmd',
		'table',	// table name
		'uid',		// uid of the record
		'level'		// the current level
	);

	/**
	 * Hold values of valid GP params
	 *
	 * @var	array
	 */
	protected $params = array();

	/**
	 * Initialize method
	 *
	 * @param	mixed	$params		not used yet
	 * @param	object	$ajaxObj	the parent ajax object
	 *
	 * @return void
	 */
	public function init($params, &$ajaxObj) {

		// fill local params because that's not done in typo3/ajax.php yet ($params is always empty)
		foreach($this->validParams as $validParam){
			$gpValue = t3lib_div::_GP($validParam);
			if($gpValue !== NULL){
				$this->paramValues[$validParam] = $gpValue;
			}
		}

		// set ajaxObj to render JSON
		$ajaxObj->setContentFormat('jsonbody');

		$this->dispatch($ajaxObj);
	}

	/**
	 * Creates the content depending on the 'cmd' parameter and fills $ajaxObj
	 *
	 * @param	object	$ajaxObj
	 * @return	void
	 **/
	protected function dispatch(&$ajaxObj) {
		if (!is_string($this->paramValues['cmd'])) {
			$ajaxObj->addContent('error', array('message' => 'cmd is not a string'));

		} else {
			switch ($this->paramValues['cmd']) {
				case 'getListRows':
					$this->getListRows($ajaxObj);
					break;
			}
		}
	}

	/**
	 *
	 * @param	object	$ajaxObj	the parent ajax object
	 * @return	void
	 */
	public function getListRows(&$ajaxObj) {
		$uid = (int) $this->getParamValue('uid');
		if ($uid > 0) {
			$table = (string) $this->getParamValue('table');
			$table = $table ? $table : 'tt_content';

			$level = (int) $this->getParamValue('level');

			$row = t3lib_BEfunc::getRecord($table, $uid);

			require_once(PATH_typo3 . 'class.db_list.inc');
			require_once(PATH_typo3 . 'class.db_list_extra.inc');

			$this->initializeTemplateContainer();

			$elementChilds = tx_gridelements_helper::getInstance()->getChildren($table, $uid);


			/** @var $recordList localRecordList */
			$recordList = t3lib_div::makeInstance('localRecordList');

			$recordList->start($row['pid'], $table, 0, '', '', 10);

//			$recordList->dontShowClipControlPanels = false;
			$recordList->clipObj = t3lib_div::makeInstance('t3lib_clipboard');
			$recordList->showClipboard = true;
			$recordList->clipObj->current = 'normal';

			$recordList->generateList();
			$recordList->calcPerms = $GLOBALS['BE_USER']->calcPerms(t3lib_BEfunc::getRecord('pages',$row['pid']));

			$level++;
			foreach ($elementChilds as $elementChild) {
				$listRows[] = $recordList->renderListRow(
					$elementChild->getTable(),
					t3lib_BEfunc::getRecord($elementChild->getTable(), $elementChild->getId()),
					0,
					$GLOBALS['TCA'][$table]['ctrl']['label'],
					$GLOBALS['TCA'][$table]['ctrl']['thumbnail'],
					1, // indent
					$level
				);
			}

#			t3lib_utility_Debug::debug($elementChilds);
#			die;
			$ajaxObj->addContent('list', $listRows);
		}

	}

	/**
	 * Initializes an anonymous template container.
	 * The created container can be compared to alt_doc.php in backend-only disposal.
	 *
	 * @return	void
	 */
	public function initializeTemplateContainer() {
		$GLOBALS['SOBE'] = new stdClass();

		// Create an instance of the document template object
		require_once(PATH_typo3 . 'template.php');
		$GLOBALS['SOBE']->doc = t3lib_div::makeInstance('template');
	}


	/**
	 * Returns the param with given key
	 *
	 * @param	string	$param
	 * @return	mixed
	 */
	public function getParamValue($param) {
		return $this->paramValues[$param];
	}

}

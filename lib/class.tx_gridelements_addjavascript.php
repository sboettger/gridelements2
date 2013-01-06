<?php

/**
 * Class/Function which adds the necessary ExtJS and pure JS stuff for the grid elements.
 *
 * @author		Jo Hasenau <info@cybercraft.de>
 * @package		TYPO3
 * @subpackage	tx_gridelements
 */

class tx_gridelements_addjavascript {
	/**
	 * method that adds JS files within the page renderer
	 *
	 * @param	array	            $parameters: An array of available parameters while adding JS to the page renderer
	 * @param	\t3lib_PageRenderer $pageRenderer: The parent object that triggered this hook
	 * @return	void
	 */
	public function addJS($parameters, &$pageRenderer) {
		
		$formprotection = t3lib_formprotection_Factory::get();
		
		if (count($parameters['jsFiles'])) {
			
			if (method_exists($GLOBALS['SOBE']->doc, 'issueCommand')) {
				/** @var t3lib_clipboard $clipObj  */
				$clipObj = t3lib_div::makeInstance('t3lib_clipboard');		// Start clipboard
				$clipObj->initializeClipboard();

				$clipBoardHasContent = false;

				if(isset($clipObj->clipData['normal']['el']) && strpos(key($clipObj->clipData['normal']['el']), 'tt_content') !== false) {
					$pasteURL = str_replace('&amp;', '&', $clipObj->pasteUrl('tt_content', 'DD_PASTE_UID', 0));
					if (isset($clipObj->clipData['normal']['mode'])) {
						$clipBoardHasContent = 'copy';
					} else {
						$clipBoardHasContent = 'move';
					}
				}
				
				$moveParams = '&cmd[tt_content][DD_DRAG_UID][move]=DD_DROP_UID';
				$moveURL = str_replace('&amp;', '&', htmlspecialchars($GLOBALS['SOBE']->doc->issueCommand($moveParams, 1)));
				$copyParams = '&cmd[tt_content][DD_DRAG_UID][copy]=DD_DROP_UID&DDcopy=1';
				$copyURL = str_replace('&amp;', '&', htmlspecialchars($GLOBALS['SOBE']->doc->issueCommand($copyParams, 1)));
				
				// add JavaScript library
				$pageRenderer->addJsFile(
					$GLOBALS['BACK_PATH'] . t3lib_extMgm::extRelPath('gridelements') . 'res/js/GridElementsDD.js',
					$type = 'text/javascript',
					$compress = TRUE,
					$forceOnTop = FALSE,
					$allWrap = ''
				);
				
				if (!$pageRenderer->getCharSet()) {
					$pageRenderer->setCharSet($GLOBALS['LANG']->charSet ? $GLOBALS['LANG']->charSet : 'utf-8');
				}
				
				if(is_array($clipObj->clipData['normal']['el'])) {
				    $arrCBKeys = array_keys($clipObj->clipData['normal']['el']);
				    $intFirstCBEl = str_replace('tt_content|', '', $arrCBKeys[0]);
				}
				
				# pull locallang_db.xml to JS side - only the tx_gridelements_js-prefixed keys
				$pageRenderer->addInlineLanguageLabelFile('EXT:gridelements/locallang_db.xml', 'tx_gridelements_js');
				
				# add l10n support to TYPO3 global - 4.6.x brings this, for 4.5 we fake it
				if(TYPO3_branch != '4.5'){
					$pageRenderer->addJsFile('../' . t3lib_extMgm::siteRelPath('lang') . 'res/js/be/typo3lang.js');
					$pRaddExtOnReadyCode = "";
				} else {
					$pRaddExtOnReadyCode = "
						TYPO3.l10n = {
							localize: function(langKey){
								return TYPO3.lang[langKey];
							}
						}
					";
				}
				
				// add Ext.onReady() code from file
				$pageRenderer->addExtOnReadyCode(
					// add some more JS here
					$pRaddExtOnReadyCode . "
						top.pasteURL = '" . $pasteURL . "';
						top.moveURL = '" . $moveURL . "';
						top.copyURL = '" . $copyURL . "';
						top.pasteTpl = top.copyURL.replace('DDcopy=1', 'reference=DD_REFYN').replace('&redirect=1', '');
						top.DDtceActionToken = '" . $formprotection->generateToken('tceAction') . "';
						top.DDtoken = '" . $formprotection->generateToken('editRecord') . "';
						top.DDpid = '" . intval(t3lib_div::_GP('id')) . "';
						top.DDclipboardfilled = '" . ($clipBoardHasContent ? $clipBoardHasContent : 'false') . "';
						top.DDclipboardElId = '" . $intFirstCBEl . "';
					" . 
					// replace placeholder for detail info on draggables
					str_replace(
						array(
							'top.skipDraggableDetails = 0;',
							// set extension path
							'insert_ext_baseurl_here',
							// set current server time
							'insert_server_time_here',
						),
						array(
							$GLOBALS['BE_USER']->uc['dragAndDropHideNewElementWizardInfoOverlay'] ? 'top.skipDraggableDetails = true;' : 'top.skipDraggableDetails = false;',
							// set extension path
							t3lib_div::locationHeaderUrl('/' . t3lib_extMgm::siteRelPath('gridelements')),
							// set current server time
							// format matches "+new Date" in JS, accuracy in seconds is fine
							time() . '000',
						),
						// load content from file
						file_get_contents(
							t3lib_extMgm::extPath('gridelements') . 'res/js/GridElementsDD_onReady.js'
						)
					),
					true
				);
			}
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/class.tx_gridelements_addjavascript.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/class.tx_gridelements_addjavascript.php']);
}

?>
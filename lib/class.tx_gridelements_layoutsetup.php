<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Arno Dudek <webmaster@adgrafik.at>
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
 * Utilities for gridelements.
 *
 * @author		Arno Dudek <webmaster@adgrafik.at>
 * @package		TYPO3
 * @subpackage	tx_gridelements
 */
class tx_gridelements_layoutsetup {

	protected $layoutSetup = array();
	protected $typoScriptSetup;
	protected $flexformConfigurationPathAndFileName = 'EXT:gridelements/res/flexform/default_flexform_configuration.xml';

	/**
	 * Load page TSconfig
	 *
	 * @param integer $pageId: The current page ID
	 * @param array $typoScriptSetup: The PlugIn configuration
	 * @return tx_gridelements_layoutsetup
	 */
	public function init($pageId, $typoScriptSetup = array()) {
		$pageId = (strpos($pageId, 'NEW') === 0) ? 0 : $pageId;
		$this->loadLayoutSetup($pageId);
		foreach($this->layoutSetup as $key => $setup) {
			$columns = $this->getLayoutColumns($key);
			if($columns['allowed']) {
				$this->layoutSetup[$key]['columns'] = $columns;
				$this->layoutSetup[$key]['allowed'] = $columns['allowed'];
			}
		}
		$this->setTypoScriptSetup($typoScriptSetup);
		return $this;
	}

	/**
	 * setter for layout setup
	 *
	 * @param array $layoutSetup
	 */
	public function setLayoutSetup($layoutSetup) {
		$this->layoutSetup = $layoutSetup;
	}

	/**
	 * setter for layout setup
	 *
	 * @param array $layoutSetup
	 */
	public function setTypoScriptSetup($typoScriptSetup) {
		$this->typoScriptSetup = $typoScriptSetup;
	}

	/**
	 * Returns the grid layout setup.
	 *
	 * @param string $layoutId: If set only requested layout setup, else all layout setups will be returned.
	 * @return array
	 */
	public function getLayoutSetup($layoutId = '') {
		// Continue only if setup for given layout ID found.
		if (isset($this->layoutSetup[$layoutId])) {
			return $this->layoutSetup[$layoutId];
		} else {
			return $this->layoutSetup;
		}
	}

	/**
	 * fetches the setup for each of the columns
	 * assigns a default setup if there is none available
	 *
	 * @param string $layoutId: The selected backend layout of the grid container
	 * @return array $setup: The adjusted TypoScript setup for the container or a default setup
	 * @author Jo Hasenau <info@cybercraft.de>
	 */
	public function getTypoScriptSetup($layoutId) {
		$typoScriptSetup = array();

		if ($layoutId == '0' && isset($this->typoScriptSetup['setup.']['default.'])) {
			$typoScriptSetup = $this->typoScriptSetup['setup.']['default.'];
		} else if ($layoutId && isset($this->typoScriptSetup['setup.'][$layoutId . '.'])) {
			$typoScriptSetup = $this->typoScriptSetup['setup.'][$layoutId . '.'];
		} else if ($layoutId) {
			$typoScriptSetup = $this->typoScriptSetup['setup.']['default.'];
		}

		// if there is none, we will use a reference to the tt_content setup as a default renderObj
		// without additional stdWrap functionality
		if (!count($typoScriptSetup)) {
			$typoScriptSetup['columns.']['default.']['renderObj'] = '<tt_content';
		}

		return $typoScriptSetup;
	}

	/**
	 * Sets the flexformConfigurationPathAndFileName
	 *
	 * @param string $flexformConfigurationPathAndFileName
	 * @return void
	 */
	public function setFlexformConfigurationPathAndFileName($flexformConfigurationPathAndFileName) {
		$this->flexformConfigurationPathAndFileName = $flexformConfigurationPathAndFileName;
	}

	/**
	 * Returns the flexformConfigurationPathAndFileName
	 *
	 * @return string $flexformConfigurationPathAndFileName
	 */
	public function getFlexformConfigurationPathAndFileName() {
		return $this->flexformConfigurationPathAndFileName;
	}

	/**
	 * Caches Container-Records and their setup to avoid multiple selects of the same record during a single request
	 *
	 * @param int $gridContainerId The ID of the current grid container
	 * @param bool $doReturn
	 * @return void|array
	 */
	public function cacheCurrentParent($gridContainerId = 0, $doReturn = FALSE) {
		if($gridContainerId > 0) {
			if(!$GLOBALS['tx_gridelements']['parentElement'][$gridContainerId]) {
				$GLOBALS['tx_gridelements']['parentElement'][$gridContainerId] = t3lib_BEfunc::getRecordWSOL('tt_content', $gridContainerId);
			}
		}
		if($doReturn === TRUE) {
			return $GLOBALS['tx_gridelements']['parentElement'][$gridContainerId];
		};
	}

	/**
	 * fetches all available columns for a certain grid container
	 *
	 * @param string $layoutId: The selected backend layout of the grid container
	 * @return array $availableColumns: first key is 'CSV' The columns available for the selected layout as CSV list and the allowed elements for each of the columns
	 */
	public function getLayoutColumns($layoutId) {
		$availableColumns = array();
		if (isset($this->layoutSetup[$layoutId])) {

			$availableColumns['CSV'] = '-2,-1';
			$setup = $this->layoutSetup[$layoutId];

			if (isset($setup['config']) && $setup['config']) {

				// create colPosList
				if ($setup['config']['rows.']) {
					foreach ($setup['config']['rows.'] as $row) {
						if (isset($row['columns.']) && is_array($row['columns.'])) {
							foreach ($row['columns.'] as $column) {
								$availableColumns['CSV'] .= ','. $column['colPos'];
								$availableColumns[$column['colPos']] = $column['allowed'] ? $column['allowed'] : '*';
								$availableColumns['allowed'] .= $availableColumns['allowed'] ? ',' . $availableColumns[$column['colPos']] : $availableColumns[$column['colPos']];
							}
						}
					}
				}
			}
		}
		return $availableColumns;
	}

	/**
	 * Returns the item array for form field selection.
	 *
	 * @param integer $colPos: The selected content column position.
	 * @return array
	 */
	public function getLayoutSelectItems($colPos) {
		$selectItems = array();
		foreach ($this->layoutSetup as $layoutId => $item) {
			if ($colPos == -1 && $item['top_level_layout']) {
				continue;
			}

			$selectItems[] = array(
				$GLOBALS['LANG']->sL($item['title']),
				$layoutId,
				$item['icon'][0],
			);
		}
		return $selectItems;
	}

	/**
	 * Returns the item array for form field selection.
	 *
	 * @param string $layoutId: The selected layout ID of the grid container
	 * @return array
	 * @author Jo Hasenau <info@cybercraft.de>
	 */
	public function getLayoutColumnsSelectItems($layoutId) {
		$selectItems = array();
		$setup = $this->getLayoutSetup($layoutId);

		if ($setup['config']['rows.']) {
			foreach ($setup['config']['rows.'] as $row) {
				if (isset($row['columns.']) && is_array($row['columns.'])) {
					foreach ($row['columns.'] as $column) {
						$selectItems[] = array(
							$GLOBALS['LANG']->sL($column['name']),
							$column['colPos'],
							NULL,
							$column['allowed']
						);
					}
				}
			}
		}
		return $selectItems;
	}

	/**
	 * Returns the item array for form field selection.
	 *
	 * @return  array
	 */
	public function getLayoutWizardItems($colPos, $excludeLayouts = array()) {
		$wizardItems = array();
		$excludeLayouts = array_flip(t3lib_div::trimExplode(',', $excludeLayouts));
		foreach ($this->layoutSetup as $layoutId => $item) {

			if (($colPos == -1 && $item['top_level_layout']) || array_key_exists($item['uid'], $excludeLayouts)) {
				continue;
			}

			$wizardItems[] = array(
				'uid' => $layoutId,
				'title' => $GLOBALS['LANG']->sL($item['title']),
				'description' => $GLOBALS['LANG']->sL($item['description']),
				'icon' => $item['icon'],
                'tll' => $item['top_level_layout'],
			);

		}

		return $wizardItems;
	}

	/**
	 * Returns the FlexForm configuration of a grid layout.
	 *
	 * @param string $layoutId: The current layout ID of the grid container
	 * @return string
	 */
	public function getFlexformConfiguration($layoutId) {
		$layoutSetup = $this->getLayoutSetup($layoutId);
		// Get flexform file from pi_flexform_ds if pi_flexform_ds_file not set and "FILE:" found in pi_flexform_ds for backward compatibility.
		if ($layoutSetup['pi_flexform_ds_file']) {
			$flexformConfiguration = t3lib_div::getURL(t3lib_div::getFileAbsFileName($layoutSetup['pi_flexform_ds_file']));
		} else if (strpos($layoutSetup['pi_flexform_ds'], 'FILE:') === 0) {
			$flexformConfiguration = t3lib_div::getURL(t3lib_div::getFileAbsFileName(substr($layoutSetup['pi_flexform_ds'], 5)));
		} else if ($layoutSetup['pi_flexform_ds']) {
			$flexformConfiguration = $layoutSetup['pi_flexform_ds'];
		} else {
			$flexformConfiguration = t3lib_div::getURL(t3lib_div::getFileAbsFileName($this->flexformConfigurationPathAndFileName));
		}

		return $flexformConfiguration;
	}

	/**
	 * Returns the page TSconfig merged with the grid layout records.
	 *
	 * @param integer $pageId: The uid of the page we are currently working on
	 * @return void
	 */
	protected function loadLayoutSetup($pageId) {
		// Load page TSconfig.
		$BEfunc = t3lib_div::makeInstance('t3lib_BEfunc');
		$pageTSconfig = $BEfunc->getPagesTSconfig($pageId);

		$excludeLayoutIds = isset($pageTSconfig['tx_gridelements.']['excludeLayoutIds'])
			? t3lib_div::trimExplode(',', $pageTSconfig['tx_gridelements.']['excludeLayoutIds'])
			: array();

		$overruleRecords = (isset($pageTSconfig['tx_gridelements.']['overruleRecords']) && $pageTSconfig['tx_gridelements.']['overruleRecords'] == '1');

		$gridLayoutConfig = array();

		if (isset($pageTSconfig['tx_gridelements.'])) {

			foreach ($pageTSconfig['tx_gridelements.']['setup.'] as $layoutId => $item) {
				// remove tailing dot of layout ID
				$layoutId = substr($layoutId, 0, -1);

				// Continue if layout is excluded.
				if (in_array($layoutId, $excludeLayoutIds)) {
					continue;
				}

				// Parse icon path for records.
				if($item['icon']) {
					$icons = t3lib_div::trimExplode(',', $item['icon']);
					foreach($icons as &$icon) {
						if (strpos($icon, 'EXT:') === 0) {
							$icon = str_replace(PATH_site, '../', t3lib_div::getFileAbsFileName($icon));
						}
					}
					$item['icon'] = $icons;
				}

				// remove tailing dot of config
				if (isset($item['config.'])) {
					$item['config'] = $item['config.'];
					unset($item['config.']);
				}

				// Change topLevelLayout to top_level_layout.
				$item['top_level_layout'] = $item['topLevelLayout'];
				unset($item['topLevelLayout']);

				// Change flexformDS to pi_flexform_ds.
				$item['pi_flexform_ds'] = $item['flexformDS'];
				unset($item['flexformDS']);

				$gridLayoutConfig[$layoutId] = $item;

			}
		}

		$storagePid = isset($pageTSconfig['TCEFORM.']['pages.']['_STORAGE_PID'])
            ? $pageTSconfig['TCEFORM.']['pages.']['_STORAGE_PID']
            : 0;
		$pageTSconfigId = isset($pageTSconfig['TCEFORM.']['tt_content.']['tx_gridelements_backend_layout.']['PAGE_TSCONFIG_ID'])
            ? $pageTSconfig['TCEFORM.']['tt_content.']['tx_gridelements_backend_layout.']['PAGE_TSCONFIG_ID']
            : 0;

		// Load records.
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
//			'uid, title, description, frame, icon, config, pi_flexform_ds',
			'*',
			'tx_gridelements_backend_layout',
			'(
				( ' . $pageTSconfigId . ' = 0 AND ' . $storagePid . ' = 0 ) OR
				( tx_gridelements_backend_layout.pid = ' . $pageTSconfigId . ' OR tx_gridelements_backend_layout.pid = ' . $storagePid . ' ) OR
				( ' . $pageTSconfigId . ' = 0 AND tx_gridelements_backend_layout.pid = ' . $pageId . ' )
			) AND NOT tx_gridelements_backend_layout.hidden AND NOT tx_gridelements_backend_layout.deleted',
			'',
			'sorting ASC',
			'',
			'uid'
		);

		$gridLayoutRecords = array();

		foreach ($result as $layoutId => $item) {
			// Continue if layout is excluded.
			if (in_array($layoutId, $excludeLayoutIds)) {
				continue;
			}

			// Prepend icon path for records.
			if($item['icon']) {
				$icons = t3lib_div::trimExplode(',', $item['icon']);
				foreach($icons as &$icon) {
					$icon = '../' . $GLOBALS['TCA']['tx_gridelements_backend_layout']['ctrl']['selicon_field_path'] . '/' . htmlspecialchars($icon);
				}
				$item['icon'] = $icons;
			}

			// parse config
			if ($item['config']) {
				$parser = t3lib_div::makeInstance('t3lib_TSparser');
				$parser->parse($parser->checkIncludeLines($item['config']));
				if (isset($parser->setup['backend_layout.'])) {
					$item['config'] = $parser->setup['backend_layout.'];
				}
			}

			$gridLayoutRecords[$layoutId] = $item;

		}

		if ($overruleRecords === TRUE) {
			$this->setLayoutSetup(
				t3lib_div::array_merge_recursive_overrule($gridLayoutConfig, $gridLayoutRecords)
			);
		} else {
			$this->setLayoutSetup(
				t3lib_div::array_merge_recursive_overrule($gridLayoutRecords, $gridLayoutConfig)
			);
		}
	}
}
?>
<?php
/***************************************************************
 *  Copyright notice
 *
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

class tx_gridelements_wizarditemshookTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var t3lib_db
	 */
	var $tempT3libDb;





	public function setUp() {
		$this->tempT3libDb = $GLOBALS['TYPO3_DB'];
	}

	public function tearDown() {
		$GLOBALS['TYPO3_DB'] = $this->tempT3libDb;
	}





	/**
	 * test columns items proc func
	 */
	public function testManipulateWizardItems() {
		$ttContent = t3lib_div::makeInstance('tx_gridelements_wizardItemsHook');

		$ttContent->manipulateWizardItems($wizardItems, $parentObject);
		$this->assertEquals($expectedParams, $params);
	}

	/**
	 * test init wizard items
	 *
	 * @test
	 */
	public function testAddGridValuesToWizardItems() {
		$ttContent = t3lib_div::makeInstance('tx_gridelements_wizardItemsHook');

		$wizardItems = array();
		$container = 0;
		$columns = 0;
		$ttContent->addGridValuesToWizardItems($wizardItems, $container, $column);
		$this->assertEquals(array(), $wizardItems);

		$wizardItems['common']['header'] = 'Content elements';
		$wizardItems['common_text']['title'] = 'Text';
		$wizardItems['common_text']['description'] = 'Typical Text Element';
		$wizardItems['common_image']['title'] = 'Images';
		$wizardItems['common_image']['description'] = 'Amount of images';
		$wizardItems['forms']['header'] = 'Forms';
		$wizardItems['forms_login']['title'] = 'Login';
		$wizardItems['forms_login']['description'] = 'Inserts a login/logout formular';
		$expectedWizardItems = $wizardItemsForTesting = $wizardItems;
		$ttContent->addGridValuesToWizardItems($wizardItemsForTesting, $container, $column);
		$this->assertEquals($expectedWizardItems, $wizardItemsForTesting);

		$container = 1;
		$columns = 0;
		$expectedWizardItems['common_text']['tt_content_defValues']['tx_gridelements_container'] = 1;
		$expectedWizardItems['common_text']['params'] .= '&defVals[tt_content][tx_gridelements_container]=1';
		$expectedWizardItems['common_image']['tt_content_defValues']['tx_gridelements_container'] = 1;
		$expectedWizardItems['common_image']['params'] .= '&defVals[tt_content][tx_gridelements_container]=1';
		$expectedWizardItems['forms_login']['tt_content_defValues']['tx_gridelements_container'] = 1;
		$expectedWizardItems['forms_login']['params'] .= '&defVals[tt_content][tx_gridelements_container]=1';
		$wizardItemsForTesting = $wizardItems;
		$ttContent->addGridValuesToWizardItems($wizardItemsForTesting, $container, $column);
		$this->assertEquals($expectedWizardItems, $wizardItemsForTesting);

		$container = 1;
		$column = 2;
		$expectedWizardItems['common_text']['tt_content_defValues']['tx_gridelements_columns'] = 2;
		$expectedWizardItems['common_text']['params'] .= '&defVals[tt_content][tx_gridelements_columns]=2';
		$expectedWizardItems['common_image']['tt_content_defValues']['tx_gridelements_columns'] = 2;
		$expectedWizardItems['common_image']['params'] .= '&defVals[tt_content][tx_gridelements_columns]=2';
		$expectedWizardItems['forms_login']['tt_content_defValues']['tx_gridelements_columns'] = 2;
		$expectedWizardItems['forms_login']['params'] .= '&defVals[tt_content][tx_gridelements_columns]=2';
		$wizardItemsForTesting = $wizardItems;
		$ttContent->addGridValuesToWizardItems($wizardItemsForTesting, $container, $column);
		$this->assertEquals($expectedWizardItems, $wizardItemsForTesting);
	}
}
?>
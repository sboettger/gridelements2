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

class tx_gridelements_pi1Test extends Tx_Extbase_BaseTestCase {

	/**
	 * @var tx_gridelements_pi1
	 */
	var $pi1;





	public function setUp() {
		$GLOBALS['TSFE'] = t3lib_div::makeInstance('tslib_fe', $TYPO3_CONF_VARS, 0);
		$GLOBALS['TSFE']->sys_page = t3lib_div::makeInstance('t3lib_pageSelect');
		$this->pi1 = t3lib_div::makeInstance('tx_gridelements_pi1');
		$this->pi1->cObj = t3lib_div::makeInstance('tslib_cObj');
	}

	public function tearDown() {
		unset($this->pi1);
	}





	/**
	 * test get sys language content
	 *
	 * @test
	 */
	public function testGetSysLanguageContent() {
		$result = $this->pi1->getSysLanguageContent();
		$this->assertEquals(0, $result);

		$GLOBALS['TSFE']->sys_language_content = 'test';
		$result = $this->pi1->getSysLanguageContent();
		$this->assertEquals(0, $result);

		$GLOBALS['TSFE']->sys_language_content = '12';
		$result = $this->pi1->getSysLanguageContent();
		$this->assertEquals(12, $result);

		$GLOBALS['TSFE']->sys_language_contentOL = '25';
		$result = $this->pi1->getSysLanguageContent();
		$this->assertEquals('0,-1', $result);

		unset($GLOBALS['TSFE']);
	}

	/**
	 * fetches all available children for a certain grid container
	 *
	 * @test
	 */
	public function testGetChildrenWithEmptyValues() {
		$result = $this->pi1->getChildren();
		$this->assertEquals(array(), $result);
		// add additional test here with DB
	}
}
?>
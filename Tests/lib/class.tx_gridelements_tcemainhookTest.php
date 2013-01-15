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

class tx_gridelements_tcemainhookTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

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
	 * test process datamap after database operations
	 *
	 * @test
	 */
	public function testProcessDatamapAfterDatabaseOperations() {
		$hook = t3lib_div::makeInstance('tx_gridelements_TCEmainHook');

		$status = '';
		$table = '';
		$id = 12;
		$fieldArray = array();
		$parentObj = t3lib_div::makeInstance('t3lib_TCEmain');
		$parentObj->substNEWwithIDs[$id] = 'Hello world';
		unset($GLOBALS['actionOnGridElement']);
		$hook->processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, $parentObj);
		$this->assertEquals(array(), $GLOBALS['actionOnGridElement']);

		$fieldArray['CType'] = 'gridelements_pi1';
		$fieldArray['sys_language_uid'] = 2;
		unset($GLOBALS['actionOnGridElement']);
		$expectedResult[0]['table'] = $table;
		$expectedResult[0]['id'] = 'Hello world';
		$expectedResult[0]['value'] = $fieldArray['sys_language_uid'];
		$hook->processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, $parentObj);
		$this->assertEquals($expectedResult, $GLOBALS['actionOnGridElement']);
	}

	/**
	 * test process datamap post process field array
	 *
	 * @test
	 */
	public function processDatamapPostProcessFieldArray() {
		$hook = t3lib_div::makeInstance('tx_gridelements_TCEmainHook');

		$status = '';
		$table = 'tt_content';
		$id = 12;
		$fieldArray = array();
		$map = array(
			array('tt_content', 0, NULL, 'noPid'),
			array('tt_content', 0, 23, 123)
		);
		$parentObj = $this->getMock('t3lib_TCEmain', array('getSortNumber'));
		$parentObj->isImporting = FALSE;
		$parentObj
			->expects($this->any())
			->method('getSortNumber')
			->will($this->returnValueMap($map));
		$hook->processDatamap_postProcessFieldArray($status, $table, $id, $fieldArray, $parentObj);
		$this->assertEquals(array(), $fieldArray);

		$_GET['cmd'] = array(
			'tt_content' => array(
				12 => array(
					'copy' => '23x24'
				)
			)
		);
		$status = 'new';
		$expectedFieldArray['sorting'] = 123;
		$hook->processDatamap_postProcessFieldArray($status, $table, $id, $fieldArray, $parentObj);
		$this->assertEquals($expectedFieldArray, $fieldArray);

		$_GET['cmd'] = array(
			'tt_content' => array(
				12 => array(
					'copy' => '-2x24'
				)
			)
		);
		$t3lib_db = $this->getMock('t3lib_db', array('exec_SELECTgetSingleRow'));
		$t3lib_db
			->expects($this->once())
			->method('exec_SELECTgetSingleRow')
			->will($this->returnValue(array(
				'pid' => 0
			)
		));
		$GLOBALS['TYPO3_DB'] = $t3lib_db;
		$expectedFieldArray['sorting'] = 'noPid';
		$hook->processDatamap_postProcessFieldArray($status, $table, $id, $fieldArray, $parentObj);
		$this->assertEquals($expectedFieldArray, $fieldArray);

		$t3lib_db = $this->getMock('t3lib_db', array('exec_SELECTgetSingleRow'));
		$t3lib_db
			->expects($this->once())
			->method('exec_SELECTgetSingleRow')
			->will($this->returnValue(array(
				'pid' => 23
			)
		));
		$GLOBALS['TYPO3_DB'] = $t3lib_db;
		$expectedFieldArray['sorting'] = '123';
		$hook->processDatamap_postProcessFieldArray($status, $table, $id, $fieldArray, $parentObj);
		$this->assertEquals($expectedFieldArray, $fieldArray);
	}
}
?>
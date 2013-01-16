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

// Include classes
require_once (PATH_typo3 . '/class.db_list.inc');
require_once (PATH_typo3 . '/class.db_list_extra.inc');

class tx_gridelements_db_listhookTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * test make query array post
	 *
	 * @test
	 */
	public function testMakeQueryArrayPost() {
		$dbList = t3lib_div::makeInstance('tx_gridelements_db_ListHook');

		$queryParts = array(
			'SELECT' => '*',
			'FROM' => 'tt_content',
			'WHERE' => 'uid = 1',
			'GROUPBY' => '',
			'ORDERBY' => '',
			'LIMIT' => ''
		);
		$parent = t3lib_div::makeInstance('ux_localRecordList');
		$table = 'pages';
		$pageId = 12;
		$addWhere = 'AND hidden = 0';
		$fieldList = 'uid, pid';
		$params = array();
		$expectedQueryParts = $testQueryParts = $queryParts;
		$dbList->makeQueryArray_post($testQueryParts, $parent, $table, $pageId, $addWhere, $fieldList, $params);
		$this->assertEquals($expectedQueryParts, $testQueryParts);
		$this->assertEquals('ux_localRecordList', get_class($parent));
		$this->assertEquals('pages', $table);
		$this->assertEquals(12, $pageId);
		$this->assertEquals('AND hidden = 0', $addWhere);
		$this->assertEquals('uid, pid', $fieldList);
		$this->assertEquals(array(), $params);

		$table = 'tt_content';
		$expectedQueryParts['ORDERBY'] = 'colPos';
		$expectedQueryParts['WHERE'] = 'uid = 1 AND colPos != -1';
		$testQueryParts = $queryParts;
		$dbList->makeQueryArray_post($testQueryParts, $parent, $table, $pageId, $addWhere, $fieldList, $params);
		$this->assertEquals($expectedQueryParts, $testQueryParts);
		$this->assertEquals('ux_localRecordList', get_class($parent));
		$this->assertEquals('tt_content', $table);
		$this->assertEquals(12, $pageId);
		$this->assertEquals('AND hidden = 0', $addWhere);
		$this->assertEquals('uid, pid', $fieldList);
		$this->assertEquals(array(), $params);

		$table = 'tt_content';
		$expectedQueryParts['ORDERBY'] = 'colPos';
		$testQueryParts = $queryParts;
		$dbList->makeQueryArray_post($testQueryParts, $parent, $table, $pageId, $addWhere, $fieldList, $params);
		$this->assertEquals($expectedQueryParts, $testQueryParts);
		$this->assertEquals('ux_localRecordList', get_class($parent));
		$this->assertEquals('tt_content', $table);
		$this->assertEquals(12, $pageId);
		$this->assertEquals('AND hidden = 0', $addWhere);
		$this->assertEquals('uid, pid', $fieldList);
		$this->assertEquals(array(), $params);

		$table = 'tt_content';
		$testQueryParts = $queryParts;
		$testQueryParts['SELECT'] = 'title';
		$expectedQueryParts['ORDERBY'] = 'colPos';
		$expectedQueryParts['SELECT'] = 'colPos,title';
		$dbList->makeQueryArray_post($testQueryParts, $parent, $table, $pageId, $addWhere, $fieldList, $params);
		$this->assertEquals($expectedQueryParts, $testQueryParts);
		$this->assertEquals('ux_localRecordList', get_class($parent));
		$this->assertEquals('tt_content', $table);
		$this->assertEquals(12, $pageId);
		$this->assertEquals('AND hidden = 0', $addWhere);
		$this->assertEquals('uid, pid', $fieldList);
		$this->assertEquals(array(), $params);
	}

	/**
	 * test add value to list
	 *
	 * @test
	 */
	public function testAddValueToList() {
		$dbList = t3lib_div::makeInstance('tx_gridelements_db_ListHook');

		$list = 'uid,pid, bodytext, title';
		$value = 'colPos';
		$result = $dbList->addValueToList($list, $value);
		$this->assertEquals('colPos,uid,pid,bodytext,title', $result);

		$list = 'uid,pid, colPos,bodytext, title';
		$value = 'colPos';
		$result = $dbList->addValueToList($list, $value);
		$this->assertEquals('colPos,uid,pid,bodytext,title', $result);

		$list = 'uid,pid, colpos,bodytext, title';
		$value = 'colPos';
		$result = $dbList->addValueToList($list, $value);
		$this->assertEquals('colPos,uid,pid,colpos,bodytext,title', $result);
	}
}
?>
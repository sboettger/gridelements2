<?php
/**
 * Gridelements helper class
 *
 * @author      Dirk Hoffmann <dirk-hoffmann@telekom.de>
 * @package     TYPO3
 * @subpackage  tx_gridelements
 */
class tx_gridelements_helper {

	/**
	 * @var tx_gridelements_helper
	 */
	protected static $instance = NULL;

	/**
	 * Get instance from the class.
	 *
	 * @static
	 * @return	tx_gridelements_helper
	 */
	public static function getInstance() {
		if (!self::$instance instanceof tx_gridelements_helper) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function getChilds($table = '', $uid = 0) {
		$retVal = array();

		if (trim($table) && $uid > 0) {
			/** @var $dependency t3lib_utility_Dependency */
			$dependency = t3lib_div::makeInstance('t3lib_utility_Dependency');
			$dependencyFactory = $dependency->getFactory();
			$dependencyElement = $dependencyFactory->getElement($table, $uid, array(), $dependency);
			$dependencyChilds = $dependencyElement->getChildren();
		}

		foreach($dependencyChilds as $key => $dependencyChild) {
			if ($dependencyChild->getField() != 'tx_gridelements_container' && $dependencyChild->getElement()->getTable() == $table) {
//			if ($dependencyChild->getElement()->getTable() == $table) {
				$retVal[] = $dependencyChild->getElement();
			}
		}

		return $retVal;
	}

}

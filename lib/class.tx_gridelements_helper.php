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

	public function getChildren($table = '', $uid = 0) {
		$retVal = array();

		if (trim($table) && $uid > 0) {

			/** @var $dependency t3lib_utility_Dependency */
			$dependency = t3lib_div::makeInstance('t3lib_utility_Dependency');

			$dependencyElement = $dependency->addElement($table, $uid);
			$children = $dependencyElement->getChildren();

			foreach($children as $key => $child) {
				if ($child->getElement()->getTable() == $table && $child->getField() == 'tx_gridelements_children') {
					$retVal[] = $child->getElement();
				}
			}
		}

		return $retVal;
	}

}

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

	public function getChildren($table = '', $uid = 0, $sortingField = '', $sortRev = 0) {
		$retVal = array();

		if (trim($table) && $uid > 0) {

			/** @var $dependency t3lib_utility_Dependency */
			$dependency = t3lib_div::makeInstance('t3lib_utility_Dependency');

			$dependencyElement = $dependency->addElement($table, $uid);
			$children = $dependencyElement->getChildren();

			foreach($children as $key => $child) {
				if ($child->getElement()->getTable() == $table && $child->getField() == 'tx_gridelements_children') {
					$record = $child->getElement()->getRecord();

					if (trim($sortingField) && isset($record[$sortingField]) && $sortingField != 'sorting') {
						$sortField = $record[$sortingField];
					} else {
						$sortField = sprintf('%1$011d', $record['sorting']);
					}
					$sortKey = sprintf('%1$011d', $record['tx_gridelements_columns']) . '.' . $sortField . ':' . sprintf('%1$011d', $record['uid']);

					$retVal[$sortKey] = $child->getElement();
				}
			}
		}

		ksort($retVal);
		if ($sortRev) {
			$retVal = array_reverse($retVal);
		}

		return $retVal;
	}

	/**
	 * Gets the uid of a record depending on the current context.
	 * If in workspace mode, the overlay uid is used (if available),
	 * otherwise the regular uid is used.
	 *
	 * @param array $record Overlayed record data
	 * @return integer
	 */
	public function getSpecificUid(array $record) {
		$specificUid = $uid = (int) $record['uid'];

		if ($this->getBackendUser()->workspace > 0 && !empty($record['_ORIG_uid'])) {
			$specificUid = (int) $record['_ORIG_uid'];
		}

		return $specificUid;
	}

	/**
	 * Gets the current backend user.
	 *
	 * @return t3lib_beUserAuth
	 */
	public function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

}

<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_gridelements_backend_layout=1
');

t3lib_extMgm::addPageTSConfig('
	mod.wizards.newContentElement.renderMode = tabs
');

t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_gridelements_pi1.php', '_pi1', 'CType', 1);
?>
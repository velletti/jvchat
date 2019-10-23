<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY,'pi1/class.tx_jvchat_pi1.php','_pi1','list_type',1);
$TYPO3_CONF_VARS['FE']['eID_include']['tx_jvchat_pi1'] = 'EXT:jvchat/pi1/fe_index.php';

<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');


//adding sysfolder icon
// # \TYPO3\CMS\Core\Utility\GeneralUtility::loadTCA('pages');
// $TCA['pages']['columns']['module']['config']['items'][$_EXTKEY]['0'] = 'LLL:EXT:vjchat/Resources/Private/Language/locallang_db.xlf:tx_vjchat.sysfolder';
// $TCA['pages']['columns']['module']['config']['items'][$_EXTKEY]['1'] = $_EXTKEY;

// # \TYPO3\CMS\Core\Utility\GeneralUtility::loadTCA('tt_content');
// $TCA["tt_content"]["types"]["list"]["subtypes_excludelist"][$_EXTKEY."_pi1"]="layout,select_key";
//  $TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='pi_flexform';


//\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY,"pi1/static/","Chat");
if (TYPO3_MODE=='BE')	{
	// Adds wizard icon to the content element wizard.
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_vjchat_pi1_wizicon'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'pi1/class.tx_vjchat_pi1_wizicon.php';

}
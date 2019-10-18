<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');
$_EXTKEY = "vjchat" ;
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    Array('LLL:EXT:vjchat/Resources/Private/Language/locallang.xlf:pi1_title',
    'vjchat_pi1') ,
    'list_type' ,
    'vjchat'

);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('vjchat_pi1', 'FILE:EXT:vjchat/Configuration/FlexForms/flexform_ds.xml');

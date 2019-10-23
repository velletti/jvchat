<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');
$_EXTKEY = "jvchat" ;
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    Array('LLL:EXT:jvchat/Resources/Private/Language/locallang.xlf:pi1_title',
    'jvchat_pi1') ,
    'list_type' ,
    'jvchat'

);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('jvchat_pi1', 'FILE:EXT:jvchat/Configuration/FlexForms/flexform_ds.xml');

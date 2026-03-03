<?php
if (!defined ('TYPO3')) die ('Access denied in jvchat.');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    Array('LLL:EXT:jvchat/Resources/Private/Language/locallang.xlf:pi1_title',
    'jvchat_pi1') ,
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT ,
    'jvchat'
);


// BOTH Lines are needed to see the Flexform in Backend !!1
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', '--div--;Configuration,pi_flexform,', 'jvchat_pi1', 'after:subheader');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('*', 'FILE:EXT:jvchat/Configuration/FlexForms/flexform_ds.xml', 'jvchat_pi1');

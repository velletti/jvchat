<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

if (!defined ('TYPO3')) die ('Access denied in jvchat.');
$groupIdentifier = "chat" ;
if ( !isset($GLOBALS['TCA']['tt_content']['columns'][ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT]['config']['itemGroups'][$groupIdentifier]) )
{
    $GLOBALS['TCA']['tt_content']['columns'][ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT]['config']['itemGroups'][$groupIdentifier] =
        'LLL:EXT:jvchat/Resources/Private/Language/locallang.xlf:pi1_title'  ;
}

<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

$tempColumns = Array (
    "tx_jvchat_chatstyle" => Array (
        "exclude" => 1,
        "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:fe_users.tx_jvchat_chatstyle",
        "config" => Array (
            "type" => "input",
            "size" => "30",
        )
    ),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns("fe_users",$tempColumns,1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes("fe_users","tx_jvchat_chatstyle");
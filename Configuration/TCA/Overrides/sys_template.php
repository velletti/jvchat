<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');
$_EXTKEY = "jvchat" ;
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY,'Configuration/TypoScript/', 'VJ Chat');
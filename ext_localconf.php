<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

 \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY,'pi1/class.tx_jvchat_pi1.php','_pi1','list_type',0);

$TYPO3_CONF_VARS['FE']['eID_include']['tx_jvchat_pi1'] = 'EXT:jvchat/Classes/Eid/JvchatEid.php';

/*
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'JV.' . $_EXTKEY,
    'pi1',
    array(
        'Base' => 'chat,rooms,overallusercount',

    ),
    // non-cacheable actions
    array(
        'Base' => 'chat,rooms,overallusercount',

    )
);
*/
$iconRegistry =
    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);

$iconRegistry->registerIcon(
    'extension-jvchat',
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    ['source' => 'EXT:jvchat/Resources/Public/Icons/Extension.svg']
);

// wizards
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    'mod {
            wizards.newContentElement.wizardItems.plugins {
                elements {
                    jvchat {
                        iconIdentifier = extension-jvchat
                        title = LLL:EXT:jvchat/Resources/Private/Language/locallang.xlf:pi1_title
                        description = LLL:EXT:jvchat/Resources/Private/Language/locallang.xlf:pi1_plus_wiz_description
                        tt_content_defValues {
                            CType = list
                            list_type = jvchat_pi1
                        }
                    }
                }
                show = *
            }
       }'
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['JV\Jvchat\Scheduler\MailchatsTask'] = array(
    'extension'        =>  $_EXTKEY,
    'title'            => 'Send New Chat Notifications',
    'description'      => 'set only frequency ',
    'additionalFields' => 'JV\Jvchat\Scheduler\MailchatsTaskAdditionalFieldProvider'
);

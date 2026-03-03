<?php


if (!defined ('TYPO3')) 	die ('Access denied.');

 \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43('jvchat','','_pi1','list_type',0);


$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][JVelletti\Jvchat\Scheduler\MailchatsTask::class] = ['extension'        =>  'jvchat', 'title'            => 'Send New Chat Notifications', 'description'      => 'set only frequency ', 'additionalFields' => \JVelletti\Jvchat\Scheduler\MailchatsTaskAdditionalFieldProvider::class];


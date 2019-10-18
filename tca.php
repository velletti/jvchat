<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE == 'BE')	{
	$conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['vjchat']);
	if($conf['showRealNamesListing']) {
		$TCA['tx_vjchat_room']['columns']['experts']['config']['itemsProcFunc'] = 'tx_vjchat_itemsProcFunc->user_vjchat_getFeUser';
		$TCA['tx_vjchat_room']['columns']['moderators']['config']['itemsProcFunc'] = 'tx_vjchat_itemsProcFunc->user_vjchat_getFeUser';
		$TCA['tx_vjchat_room']['columns']['bannedusers']['config']['itemsProcFunc'] = 'tx_vjchat_itemsProcFunc->user_vjchat_getFeUser';				
	}
}
?>
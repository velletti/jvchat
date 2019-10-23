<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE == 'BE')	{
	$conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['jvchat']);
	if($conf['showRealNamesListing']) {
		$TCA['tx_jvchat_room']['columns']['experts']['config']['itemsProcFunc'] = 'tx_jvchat_itemsProcFunc->user_jvchat_getFeUser';
		$TCA['tx_jvchat_room']['columns']['moderators']['config']['itemsProcFunc'] = 'tx_jvchat_itemsProcFunc->user_jvchat_getFeUser';
		$TCA['tx_jvchat_room']['columns']['bannedusers']['config']['itemsProcFunc'] = 'tx_jvchat_itemsProcFunc->user_jvchat_getFeUser';
	}
}
?>
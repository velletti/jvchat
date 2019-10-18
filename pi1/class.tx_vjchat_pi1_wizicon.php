<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2002-2004 Kasper Skårhøj (kasper@typo3.com)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is 
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
* 
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
* 
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/** 
 * Class that adds the wizard icon.
 *
 * $Id: class.tx_vjchat_pi1_wizicon.php,v 1.2 2004/02/02 07:04:48 typo3 Exp $
 *
 * @author	Kasper Skårhøj (kasper@typo3.com)
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   56: class tx_vjchat_pi1_wizicon 
 *   64:     function proc($wizardItems)	
 *   84:     function includeLocalLang()	
 *
 * TOTAL FUNCTIONS: 2
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */





/**
 * Class that adds the wizard icon.
 * 
 * @author	Kasper Skårhøj (kasper@typo3.com)
 * @package TYPO3
 * @subpackage tx_vjchat
 */
class tx_vjchat_pi1_wizicon {

	/**
	 * Adds the vjchat wizard icon
	 * 
	 * @param	array		Input array with wizard items for plugins
	 * @return	array		Modified input array, having the item for vjchat added.
	 */
	function proc($wizardItems)	{

		$wizardItems['plugins_tx_vjchat_pi1'] = array(
			'icon'=>\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('vjchat').'pi1/ce_wiz.gif',
			'title'=> \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('pi1_title', 'vjchat') ,
			'description'=> \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('pi1_plus_wiz_description', 'vjchat') ,
			'params'=>'&defVals[tt_content][CType]=list&defVals[tt_content][list_type]=vjchat_pi1'
		);

		return $wizardItems;
	}

}

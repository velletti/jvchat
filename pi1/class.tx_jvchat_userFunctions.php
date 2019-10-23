<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Vincent Tietz (vincent.tietz@vj-media.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
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
 * Plugin 'Chat' for the 'jvchat' extension.
 *
 * @author	Vincent Tietz <vincent.tietz@vj-media.de>
 */

require_once('class.tx_jvchat_db.php');
 
class tx_jvchat_userFunctions {

	function __construct() {
		$this->db = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_jvchat_db');
		$this->cObj = $cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
	}

	function user_getUserCountOfRoomFromPage($content, $conf) {

		$pageId = $this->cObj->stdWrap($conf['pageId'], $conf['pageId.']);

		$rooms = $this->db->getRoomsOfPage($pageId);

		$userCount = 0;
		foreach($rooms as $room) {
			$userCount = $userCount + $this->db->getUserCountOfRoom($room->uid);
		}

		return $userCount;

	}

	var $db;

	var $cObj;
	
}

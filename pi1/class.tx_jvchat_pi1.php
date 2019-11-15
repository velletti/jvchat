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

use \JV\Jvchat\Utility\LibUtility ;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_jvchat_pi1 extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {

    /** @var array */
    public $settings ;

    /** @var string  */
    var $prefixId = 'tx_jvchat_pi1';

    /** @var string  */
    var $extKey = 'jvchat';

    var $pi_checkCHash = FALSE;

    var $chatScript;

    /** @var  \JV\Jvchat\Domain\Repository\DbRepository  */
    var $db;

    var $user;

	/**
	 */
	function main($content,$conf)	{
		$this->conf = $conf;
		$this->settings = $conf;

		$chatScript = 'https://' . $_SERVER['SERVER_NAME'] . '/index.php?eID=tx_jvchat_pi1';

		$GLOBALS['TSFE']->additionalHeaderData['tx_jvchat_inc'] = '
			<script language="JavaScript" type="text/javascript">
			//<![CDATA[
				function tx_jvchat_openNewChatWindow(url, chatId) {
					var concatinator = "&";
					if(url.indexOf("?") == -1) {
						var concatinator = "?";
					}
						
					var vHWindow = window.open(url+concatinator+"tx_jvchat_pi1[uid]="+chatId+"&tx_jvchat_pi1[view]=chat&tx_jvchat_pi1[popup]=1","chatwindow"+chatId,"'.$this->conf['chatPopupJSWindowParams'].'");
					vHWindow.focus();
				}		
			//]]>
			</script>
		';


		$this->chatScript = $chatScript;


		$this->pi_USER_INT_obj=1;	// Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
		$GLOBALS['TSFE']->set_no_cache(); // disable frontend caching on this page

		$this->pi_setPiVarDefaults();
		$this->pi_loadLL("EXT:jvchat/Resources/Private/Language/locallang.xlf");
		$this->user = $GLOBALS['TSFE']->fe_user->user;

		$this->loadFLEX();

		/** @var \JV\Jvchat\Domain\Repository\DbRepository db */
		$this->db = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('JV\Jvchat\Domain\Repository\DbRepository');


		if($this->piVars['leaveRoom'] && $this->db->isMemberOfRoom($this->piVars['leaveRoom'], $this->user['uid'])) {
			$this->db->leaveRoom($this->piVars['leaveRoom'], $this->user['uid'], true, $this->pi_getLL('user_leaves_chat'));
		}


		if($action = $this->piVars['action'])
			switch($action) {
				case 'delete':
					$content = $this->deleteEntry($this->piVars['entryId']);
				break;
			}

		// dynamic view set in frontend
		if($view = $this->piVars['view'])
			switch($view) {
				case 'chat':
					$content = $this->displayChatRoom($this->piVars['uid']);
					break;
				case 'sessions' :
					$content = $this->displaySessionsOfRoom($this->piVars['uid']);
					break;
				case 'session' :
					$content = $this->displaySession($this->piVars['uid']);
					break;

			}
		else
			// if nothing set use default view from FLEX form
			switch($this->conf['FLEX']['display']) {
				case 'rooms': $content = $this->displayRooms();
				break;
				case 'chat': $content = $this->displayChatRoom($this->conf['FLEX']['chatroom']);
				break;
				case 'overallusercount': $content = $this->displayOverallChatuserNumber();
				break;
			}



		//\TYPO3\CMS\Core\Utility\GeneralUtility::debug($this->conf);
		return $this->pi_wrapInBaseClass($content);
	}		// Same as class name

	function loadFLEX() {

		$this->pi_initPIflexForm(); // Init FlexForm configuration for plugin

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'display', 'sDEF');
		$this->conf['FLEX']['display'] = $value ? $value : 'rooms';

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'chatroom', 'sDEF');
		$this->conf['FLEX']['chatroom'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'initChatWithMessagesBefore', 'chatDEF');
		$this->conf['FLEX']['initChatWithMessagesBefore'] = $value ? $value : 10;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'reloadTimeIfRoomFull', 'chatDEF');
		$this->conf['FLEX']['reloadTimeIfRoomFull'] = $value ? $value : 30;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'refreshMessagesTime', 'chatDEF');
		$this->conf['FLEX']['refreshMessagesTime'] = $value ? $value : 5;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'refreshUserListTime', 'chatDEF');
		$this->conf['FLEX']['refreshUserListTime'] = $value ? $value : 15;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showFormatting', 'chatDEF');
		$this->conf['FLEX']['showFormatting'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showEmoticons', 'chatDEF');
		$this->conf['FLEX']['showEmoticons'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showStyles', 'chatDEF');
		$this->conf['FLEX']['showStyles'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'chatwindow', 'sDEF');
		$value = $value ? $value : $this->conf['defaultChatpopupPid'];
		$this->conf['FLEX']['chatwindow'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'targetwindow', 'sDEF');
		$value = $value ? $value : $this->conf['targetwindow'];
		$this->conf['FLEX']['targetwindow'] = $value;


		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'colorizeNicks', 'chatDEF');
		$this->conf['FLEX']['colorizeNicks'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showTime', 'chatDEF');
		$this->conf['FLEX']['showTime'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'enableSound', 'chatDEF');
		$this->conf['FLEX']['enableSound'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showSendButton', 'chatDEF');
		$this->conf['FLEX']['showSendButton'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'maxUserCount', 'sDEF');
		$this->conf['FLEX']['maxUserCount'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'hideEmptyRooms', 'sDEF');
		$this->conf['FLEX']['hideEmptyRooms'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'hideClosedRooms', 'sDEF');
		$this->conf['FLEX']['hideClosedRooms'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'hidePrivateRooms', 'sDEF');
		$this->conf['FLEX']['hidePrivateRooms'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showModerators', 'sDEF');
		$this->conf['FLEX']['showModerators'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showUsers', 'sDEF');
		$this->conf['FLEX']['showUsers'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showExperts', 'sDEF');
		$this->conf['FLEX']['showExperts'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showUserCount', 'sDEF');
		$this->conf['FLEX']['showUserCount'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showDescription', 'sDEF');
		$this->conf['FLEX']['showDescription'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showDescriptionInChat', 'chatDEF');
		$this->conf['FLEX']['showDescriptionInChat'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'typoscriptRoomsTemplate', 'sDEF');
		$this->conf['tsRooms'] = $value ? $value : 'rooms';

		if($this->conf['tsRooms'] == 'custom') {
			$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'typoscriptRoomsTemplateCustom', 'sDEF');
			$this->conf['tsRooms'] = $value ? $value : 'rooms';
		}

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'chatrooms', 'sDEF');
		$this->conf['FLEX']['chatrooms'] = $value ? $value : null;

		if(!$this->conf['FLEX']['chatrooms']) {
			$value = $this->cObj->data['pages'] ? $this->pi_getPidList($this->cObj->data['pages'], $this->cObj->data['recursive']) : null;
			$this->conf['pidList'] = $value ? $value : $this->conf['pidList'];

		}
	}

	function deleteEntry($entryId) {
		// check rights
		$entry = $this->db->getEntry($entryId);
        /** @var \JV\Jvchat\Domain\Model\Room $newRoom */
		$room = $this->db->getRoom($entry->room);

		if(!LibUtility::checkAccessToRoom($room, $this->user))
			return $this->displayErrorMessage($this->pi_getLL('access_denied'));

		if(!LibUtility::isModerator($room, $this->user['uid']))
			return $this->displayErrorMessage($this->pi_getLL('access_denied'));

		return $this->db->deleteEntry($entryId);
	}

	function displayErrorMessage($message, $stdWrap = "") {
		$theValue = $this->cObj->stdWrap($message, $this->conf['errorMessagesStdWrap.']);
        $theValue = $this->cObj->stdWrap($theValue, $stdWrap);
		return $this->cObj->stdWrap($theValue, $this->conf['errorMessagesAllWrap.']);
	}
	
	function displayChatRoom($roomId) {


		$this->db->cleanUpRooms();
        /** @var \JV\Jvchat\Domain\Model\Room $newRoom */
		if(!$room = $this->db->getRoom($roomId)) {
            return $this->displayErrorMessage($this->pi_getLL('error_room_not_found'), $this->conf['views.']['chat.']['stdWrap.']);
        }
        // remove old message entries if set
		if($this->db->extCONF['autoDeleteEntries']) {
            $this->db->deleteEntries($this->db->extCONF['autoDeleteEntries']);
        }

        // $debug = $this->db->getFeUsersMayAccessRoom( $this->db->getRoom(10 )   ) ;
        // $entries = $this->db->getEntrieslastXseconds($this->db->getRoom(12 ), 60*60*24*21  ) ;
        // var_dump($debug) ;
        //  die;
		$roomData = $this->getRoomData($room);
		$this->cObj->data = $roomData ;
		if(!$this->conf['FLEX']['showDescriptionInChat'])
			unset($this->cObj->data['description']);

		if(!LibUtility::isSuperuser($room, $this->user)) {

			if(LibUtility::isBanned($room, $this->user['uid']))
				return $this->displayErrorMessage($this->pi_getLL('error_banned'), $this->conf['views.']['chat.']['stdWrap.']);

			// check if user is kicked
			if($res = $this->db->isUserKicked($room->uid, $this->user['uid']))
				return $this->displayErrorMessage(sprintf($this->pi_getLL('error_kicked'),$res), $this->conf['views.']['chat.']['stdWrap.']);

			// check if this is a private room and if the user is an invited member
			if($room->private && !LibUtility::isMember($room, $this->user['uid']))
				return $this->displayErrorMessage($this->pi_getLL('error_not_invited'), $this->conf['views.']['chat.']['stdWrap.']);

			// remove user who left room and remove system messages
			$this->db->cleanUpUserInRoom($room->uid, 20, true, $this->pi_getLL('user_leaves_chat'));

			//check rights to view room
			if(!LibUtility::checkAccessToRoom($room, $this->user))
				return $this->displayErrorMessage($this->pi_getLL('error_room_access_denied'), $this->conf['views.']['chat.']['stdWrap.']);

		}

		/* ***********************************   LTS 9 ******************************** */
        /** @var   \TYPO3\CMS\Fluid\View\StandaloneView $renderer */
        $setup = LibUtility::getSetUp();
        $renderer = LibUtility::getRenderer( $setup, "DisplayChatRoom" , "html" ) ;
        $renderer->assign('settings', $setup['settings'] );
        $renderer->assign('server',  \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'));

        $resUpdate = $this->db->updateUserInRoom($roomId, $this->user['uid']);


        if ( $resUpdate == "entered") {

            // check if we need to put a message for NEW Users in the Room:  Wir Holen 3 und wenn da keine SInd schien Wir die welcom message..
            $entries = $this->db->getEntries($room, 0 , 0 ,2 ,  $this->user['uid']);
            if ( count( $entries ) < 2 ) {
                $msg = $room->welcomemessage ;
                if ( $room->isPrivate() ) {
                    $msg .= "\n" . $this->pi_getLL('after_welcome_message') ;
                }
                $msg .= "\n" . $this->pi_getLL('after_welcome_message_in_private_room') ;
                $this->db->putMessage( $room->uid ,  $msg , 0 , 0 , 1 , 0 , $this->user['uid']);

            }
        }
        // there are two subparts: CHATROOM and CHATROOM_FULL
        // these markers can be used by both types
        $marker['CHATROOM_NAME'] = $room->name;
        $marker['CHATROOM_ID'] = $roomId;

        $marker['USERLIST_PM_CONTENT'] = $this->cObj->stdWrap($this->conf['userlistPMContent'], $this->conf['userlistPMContent.']);
        $marker['USERLIST_PR_CONTENT'] = $this->cObj->stdWrap($this->conf['userlistPRContent'], $this->conf['userlistPRContent.']);
        $marker['USERLIST_PM_INFO'] =    $this->slashJS($this->pi_getLL('userlistPMInfo'));
        $marker['USERLIST_PR_INFO'] =    $this->slashJS($this->pi_getLL('userlistPRInfo'));

        $marker['SNIPPETS_ERROR'] =      $this->slashJS($this->pi_getLL('snippetsError'));
        $marker['LOADING_MESSAGE'] = $this->cObj->cObjGetSingle($this->conf['loadingMessage'], $this->conf['loadingMessage.']);


        $dataString  = ' data-roomid="' . $roomId  . '"' ;
        $dataString .= ' data-userid="' . $this->user['uid']  . '"' ;


        $dataString .= ' data-lang="' . $GLOBALS['TSFE']->config['config']['language']  . '"' ;

        $time = $this->db->getTime()-($this->conf['FLEX']['initChatWithMessagesBefore']*60);

        $dataString .= ' data-initialid="' . $this->db->getLatestEntryId($room, $time)  . '"' ;


        // seperators for splits
        $dataString .= ' data-usernameglue="'       . LibUtility::getUserNamesGlue()  . '"' ;
        $dataString .= ' data-messagesglue="'       . LibUtility::getMessagesGlue()  . '"' ;
        $dataString .= ' data-usernamesfieldglue="' . LibUtility::getUserNamesFieldGlue()  . '"' ;
        $dataString .= ' data-idglue="'             . LibUtility::getIdGlue()  . '"' ;

        if($this->conf['FLEX']['chatwindow'])
            $newwindowurl = $this->conf['FLEX']['chatwindow'] ? $this->pi_linkTP_keepPIvars_url(array(), 0, true, $this->conf['FLEX']['chatwindow']) : $marker['LEAVEURL'];
        else
            $newwindowurl= ($this->pi_linkTP_keepPIvars_url(array(), 0, true)).'&type='.($this->conf['chatwindow.']['typeNum']);



        $dataString .= ' data-newwindowurl="' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL'). $newwindowurl . '"';
        $dataString .= ' data-scripturl="' . $this->chatScript  . '"' ;
        $dataString .= ' data-leaveurl="' . $this->pi_linkTP_keepPIvars_url(array(), 0, true)  . '"' ;

        if( $this->conf['FLEX']['showTime'] ) {
            $dataString .= ' data-showtime="true"' ;
        } else {
            $dataString .= ' data-showtime="false"' ;
        }
        if( $this->conf['FLEX']['showEmoticons'] ) {
            $dataString .= ' data-showemoticons="true"' ;
        } else {
            $dataString .= ' data-showemoticons="false"' ;
        }
        if( $this->conf['FLEX']['showStyles'] ) {
            $dataString .= ' data-showstyles="true"' ;
        } else {
            $dataString .= ' data-showstyles="false"' ;
        }

        if( $this->db->extCONF['allowPrivateRooms'] ) {
            $dataString .= ' data-allowPrivateRooms="true"' ;
            $dataString .= ' data-privateroomcode="'. $this->conf['privateRoomCode'] . '"' ;
        } else {
            $dataString .= ' data-allowPrivateRooms="false"' ;
        }
        if( $this->db->extCONF['allowPrivateMessages'] ) {
            $dataString .= ' data-allowPrivateMessages="true"' ;
            $dataString .= ' data-privatemsgcode="'. $this->conf['privateMsgCode'] . '"' ;
        } else {
            $dataString .= ' data-allowPrivateMessages="false"' ;
        }
        if( $this->cObj->data['popup']  ) {
            $dataString .= ' data-ispopup="true"' ;
        } else {
            $dataString .= ' data-ispopup="false"' ;
        }
        $dataString .= ' data-popupparams="' . $this->conf['chatPopupJSWindowParams']  . '"' ;
        $dataString .= ' data-talkToNewRoomName="' . $this->slashJS($this->pi_getLL('talktoroomname'))  . '"' ;

        $tooltipOffsetXY = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->conf['tooltipOffsetXY']);
        $dataString .= ' data-allowtooltipoffset-x="' . $tooltipOffsetXY[0] . '"' ;
        $dataString .= ' data-allowtooltipoffset-y="' . $tooltipOffsetXY[1] . '"' ;
        $dataString .= ' data-refreshMessagesTime="' . $this->conf['FLEX']['refreshMessagesTime']*1000 . '"' ;
        $dataString .= ' data-refreshUserListTime="' . $this->conf['FLEX']['refreshUserListTime']*1000 . '"' ;
        $dataString .= ' data-pid="' . $GLOBALS['TSFE']->id  . '"' ;


        if( $this->piVars['debug'] ) {
            $dataString .= ' data-debug="true"' ;
        }




        $marker['isFull'] = $this->cObj->data['isFull'] ;

        // display CHATROOM
        if(! $marker['isFull']) {


            $marker['SUBMIT_MESSAGE'] = $this->pi_getLL('submit_message');
            $marker['LABEL_NEW_MESSAGE'] = $this->pi_getLL('new_message');

            $renderer->assign("emoticons" ,  LibUtility::getEmoticonsForChatRoom() ) ;


            if($this->conf['useSnippets']) {
                // try to add user
                $this->db->updateUserInRoom($room->uid, $this->user['uid'], LibUtility::isSuperuser($this->room, $this->user), $this->pi_getLL('user_enters_chat'));

                // prepare the user's snippets
                $this->db->setUserlistSnippet($room->uid, $this->user['uid'], $this->getSnippet($room, $this->user ));

            }

        }
        // display CHATROOM_FULL
        else {

            $marker['CHATROOM_ID'] = max( 1 , $roomId ) ;
            $marker['SCRIPTURL'] = $this->chatScript;
            $marker['RELOAD_TIME'] = max ( 10000 , $this->conf['FLEX']['reloadTimeIfRoomFull']*1000 );
            $marker['CHATURL'] = $this->pi_linkTP_keepPIvars_url(array(), 0, false);
            $marker['USERID'] = $this->user['uid'];
        }

        $renderer->assign("user" , $this->user ) ;
        $renderer->assign("marker" , $marker ) ;

        $renderer->assign("room" , $room ) ;
        $renderer->assign("roomData" , $roomData ) ;
        $renderer->assign("confFLEX" , $this->conf['FLEX'] ) ;

        $renderer->assign("dataString" , $dataString ) ;

        // only for debuggin ..
       //  $renderer->assign("DisplayIcons" , true ) ;

        $content = $renderer->render();
        return $content ;
	}
	
	function getRoomData($room) {
		$theValue = $room->toArray();

		$theValue['userCount'] = $this->db->getUserCountOfRoom($room->uid);
		$theValue['showUserCount'] = $this->conf['FLEX']['maxUserCount'];
		$theValue['sessionCount'] = $this->db->getSessionsCountOfRoom($room->uid);
		$theValue['isFull'] = $this->db->isRoomFull($room) && !LibUtility::isSuperuser($room, $this->user) && !$this->db->isMemberOfRoom($room->uid, $this->user['uid']);


        $theValue['enableEmoticons'] = $this->conf['FLEX']['showEmoticons'];
        $theValue['enableTime'] = $this->conf['FLEX']['showTime'];
        $theValue['enableImageUpload'] = $this->conf['enableImageUpload'];

		$conf = $this->conf['views.'][$this->conf['tsRooms'].'.'];

		$experts = $moderators = $users = array();


		if($this->conf['FLEX']['showExperts']) {
			$experts = $this->db->getOnlineExperts($room->uid);
			$this->cObj->data['userType'] = 'expert';
			$theValue['onlineExperts'] = LibUtility::getUsernames($experts, true, $conf['usersGlue'], $this->cObj, $conf['users_stdWrap.']);
		}

        if($this->conf['FLEX']['showModerators']) {
			$moderators = $this->db->getOnlineModerators($room->uid);
			$this->cObj->data['userType'] = 'moderator';
			$theValue['onlineModerators'] = LibUtility::getUsernames($moderators, true, $conf['usersGlue'], $this->cObj, $conf['users_stdWrap.']);
		}
		if($this->conf['FLEX']['showUsers']) {
			$users = $this->db->getOnlineUsers($room->uid);
			$this->cObj->data['userType'] = 'user';
			$theValue['onlineUsers'] = LibUtility::getUsernames($users, (($room->showFullNames) ? true : false), $conf['usersGlue'], $this->cObj, $conf['users_stdWrap.']);
		}

		$allUsers = array_merge( $experts, $moderators, $users);
		$theValue['allUserNicknames'] = LibUtility::getUsernames($allUsers, false, $conf['usersGlue'], $this->cObj, $conf['users_stdWrap.']);

		$snippets = array();
		foreach($allUsers as $user) {
			$userSnippets = $this->db->getSnippets($room->uid, $user['uid']);
			$this->cObj->data = array_merge($this->cObj->data, $user);
			$singleSnippet = $this->cObj->stdWrap($userSnippets['userlistsnippet'], $conf['users_stdWrap.']);
			$snippets[] = $singleSnippet;
		}

		$theValue['allUserSnippets'] = implode($conf['usersGlue'], $snippets);

		if(!$theValue['isFull'] && !$room->isClosed() && !$this->piVars['popup'])
			$theValue['chatwindow'] = $this->conf['FLEX']['chatwindow'];
		else
			$theValue['chatwindow'] = false;

		if($this->conf['FLEX']['chatwindow'])
			$theValue['newWindowUrl'] = $this->conf['FLEX']['chatwindow'] ? $this->pi_linkTP_keepPIvars_url(array(), 0, true, $this->conf['FLEX']['chatwindow']) : $this->pi_linkTP_keepPIvars_url(array(), 0, true) ;
		else
			$theValue['newWindowUrl'] = ($this->pi_linkTP_keepPIvars_url(array(), 0, true)).'&type='.($this->conf['chatwindow.']['typeNum']);

		$theValue['newWindowUrl'] = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL').$theValue['newWindowUrl'];

//		$theValue['newWindowUrl'] = $this->conf['FLEX']['chatwindow'] ? $this->pi_linkTP_keepPIvars_url(array(), 0, true, $this->conf['FLEX']['chatwindow']) : $marker['###LEAVEURL###'];

		$theValue['popup'] = $this->piVars['popup'];
		$theValue['leaveChat'] = ($this->conf['FLEX']['display'] == 'rooms') && ($theValue['popup'] == false);


		return $theValue;
	}

	/*
	* @param  \JV\Jvchat\Domain\Model\Room $room
	* @param  fe_user $user

	*/
	function getSnippet($room, $user) {
        $setup = LibUtility::getSetUp();
        $extConf = LibUtility::getExtConf() ;

        /** @var   \TYPO3\CMS\Fluid\View\StandaloneView $renderer */
        $renderer = LibUtility::getRenderer($setup , "GetUsers" , "html" )  ;
        $renderer->assign("showFullNames" , $room->showFullNames() ) ;
        if( $setup['settings']['userlist']['avatar']['useNemUserImgPath']) {
            $setup['settings']['userlist']['avatar']['nemUserImgPath']  = 'uploads/tx_feusers_img/' . $subPath = substr( "0000" . intval( round( $user['uid'] / 1000 , 0 )) , -4 , 4 ) . "/"  ;
        }

        $renderer->assign("thisUser" , $this->user ) ;
        $renderer->assign("extConf" , $extConf ) ;
        $renderer->assign("settings" , $setup['settings'] ) ;

        $renderer->assign("user" , $user ) ;
        return $renderer->render() ;

	}
	
	function displaySessionsOfRoom($roomId) {
        /** @var \JV\Jvchat\Domain\Model\Room $room */
		if(!$room = $this->db->getRoom($roomId))
			return	$this->displayErrorMessage($this->pi_getLL('error_room_not_found'), $this->conf['views.']['sessions.']['stdWrap.']);

		//check rights to view room
		if(!LibUtility::checkAccessToRoom($room, $this->user))
			return  $this->displayErrorMessage($this->pi_getLL('error_room_access_denied'), $this->conf['views.']['sessions.']['stdWrap.']);

		if(!$sessions = $this->db->getSessionsOfRoom($roomId))
			return $this->displayErrorMessage($this->pi_getLL('sessions_not_found'), $this->conf['views.']['sessions.']['stdWrap.']);
		$theValue = '' ;
		foreach($sessions as $session) {

			$this->cObj->data = $this->getSessionData($session);
			$this->cObj->data['entriesCount'] = $this->db->getEntriesCountOfSession($session);

			// render COBJ from TS with current data
			$theValue .= $this->cObj->cObjGet($this->conf['views.']['sessions.']['oneSession.']);
		}

		$this->cObj->data = array_merge($this->cObj->data, $this->prefixAssocArrayKeys('room.', $room->toArray()));


		return $this->cObj->stdWrap($theValue, $this->conf['views.']['sessions.']['stdWrap.']);

	}
	
	function getSessionData($session) {
		$theValue = $session->toArray();
		$entryStart = $this->db->getEntry($session->startid);
		$entryEnd = $this->db->getEntry($session->endid);
		$theValue['startdate'] = $entryStart->tstamp;
		$theValue['enddate'] = $entryEnd->tstamp;

		return $theValue;
	}
	
	function prefixAssocArrayKeys($prefix, $array) {
		$theValue = array();
		foreach($array as $key => $value) {
			$theValue[$prefix.$key] = $value;
		}
		return $theValue;
	}

	function displaySession($sessionId) {

		if(!$session = $this->db->getSession($sessionId))
			return $this->displayErrorMessage($this->pi_getLL('session_not_found'), $this->conf['views.']['session.']['stdWrap.']);
        /** @var \JV\Jvchat\Domain\Model\Room $room */
		if(!$room = $this->db->getRoom($session->room))
			return $this->displayErrorMessage($this->pi_getLL('room_not_found'), $this->conf['views.']['session.']['stdWrap.']);

		$this->db->cleanUpUserInRoom($room->uid, 10, true, $this->pi_getLL('user_leaves_chat'));

		//check rights to view room
		if(!LibUtility::checkAccessToRoom($room, $this->user))
			return $this->displayErrorMessage($this->pi_getLL('access_denied'));


		$entries = $this->db->getEntriesOfSession($session);

		$isModerator = LibUtility::isModerator($room, $this->user['uid']);
		$theValue = '' ;
		foreach($entries as $entry) {
			$this->cObj->data = $entry->toArray();
			$this->cObj->data['isModerator'] = $isModerator;

			$feuser = $this->db->getFeUser($entry->feuser);
			if($feuser['username'])
				$this->cObj->data['username'] = $room->showFullNames() ? $feuser['name'] : $feuser['username'];
			else
				$this->cObj->data['username'] = 'SYSTEM';

			$this->cObj->data['type'] = 0;
			if(LibUtility::isModerator($room, $entry->feuser))
				$this->cObj->data['type'] = 1;

			if(LibUtility::isSystem($entry->feuser))
				$this->cObj->data['type'] = 2;

			if(LibUtility::isExpert($room, $entry->feuser))
				$this->cObj->data['type'] = 3;

            $extConf = LibUtility::getExtConf();
			$this->cObj->data['entry'] = LibUtility::formatMessage($entry->entry , $extConf->setup['settings']['emoticons'] ,  $room->enableEmoticons );

			// render COBJ from TS with current data
			$theValue .= $this->cObj->cObjGet($this->conf['views.']['session.']['oneEntry.']);
		}

		$this->cObj->data = $this->getSessionData($session);
		$this->cObj->data['entriesCount'] = count($entries);


		$this->cObj->data = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge($this->cObj->data, $this->prefixAssocArrayKeys('room.', $room->toArray()));

		return $this->cObj->stdWrap($theValue, $this->conf['views.']['session.']['stdWrap.']);

	}
	
	function displayRooms() {

		$rooms = $this->getRoomsFromFlexConf();
        $theValue = '' ;
		foreach($rooms as $room) {

			$this->cObj->data = $this->getRoomData($room);

			if(!$this->conf['FLEX']['showDescription'])
				unset($this->cObj->data['description']);

			// render COBJ from TS with current data
			$theValue .= $this->cObj->cObjGet($this->conf['views.'][$this->conf['tsRooms'].'.']['oneRoom.']);
		}

		$this->cObj->data['popup'] = $this->piVars['popup'];

		return $this->cObj->stdWrap($theValue, $this->conf['views.'][$this->conf['tsRooms'].'.']['stdWrap.']);

	}
	
	function getRoomsFromFlexConf() {
		if(!$this->conf['FLEX']['chatrooms']) {
            if($this->conf['FLEX']['hidePrivateRooms']) {
                $rooms = $this->db->_getRooms($this->conf['pidList'] , false);
            } else{
                $rooms = $this->db->getRooms($this->conf['pidList']);
            }

        } else {
			$rooms = array();
			$roomsIds = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->conf['FLEX']['chatrooms']);
			foreach($roomsIds as $id) {
				$rooms[] = $this->db->getRoom($id);
			}
		}



		$theValue = array();
		foreach($rooms as $room) {

			//check rights to view room
			//if(!LibUtility::checkAccessToRoom($room, $this->user))
			//	continue;

			$this->db->cleanUpUserInRoom($room->uid, 20, true, $this->pi_getLL('user_leaves_chat'));

			if($this->conf['FLEX']['hideEmptyRooms'] && ($this->db->getUserCountOfRoom($room->uid) == 0))
				continue;

			if($this->conf['FLEX']['hideEmptyRooms'] && $room->isClosed())
				continue;

			if($this->conf['FLEX']['hidePrivateRooms'] && $room->isPrivate())
				continue;

			$theValue[] = $room;

		}

		return $theValue;

	}
	
	function displayOverallChatuserNumber() {
	
		$rooms = $this->getRoomsFromFlexConf();
	
		$roomIds = array();
		foreach($rooms as $room) {
			$roomIds[] = $room->uid;
		}

		$this->cObj->data['overallChatUserCount'] = $this->db->getUserCountOfRoom($roomIds);
		$this->cObj->data['targetpid'] = $this->conf['FLEX']['targetwindow'];
		return $this->cObj->cObjGet($this->conf['views.']['overallChatUserCount.']);
	}

    /**
     * This function is used to escape any ' -characters when transferring text to JavaScript!
     *
     * @param string $string String to escape
     * @param bool $extended If set, also backslashes are escaped.
     * @param string $char The character to escape, default is ' (single-quote)
     * @return string Processed input string
     */
    public static function slashJS($string, $extended = false, $char = '\'')
    {
        if ($extended) {
            $string = str_replace('\\', '\\\\', $string);
        }
        return str_replace($char, '\\' . $char, $string);
    }



	

}


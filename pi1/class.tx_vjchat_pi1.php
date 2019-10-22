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
 * Plugin 'Chat' for the 'vjchat' extension.
 *
 * @author	Vincent Tietz <vincent.tietz@vj-media.de>
 */


require_once('class.tx_vjchat_db.php');
require_once('class.tx_vjchat_lib.php');

class tx_vjchat_pi1 extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {

    /** @var array */
    public $settings ;

	/**
	 */
	function main($content,$conf)	{
		$this->conf = $conf;
		$this->settings = $conf;

		$chatScript = 'https://' . $_SERVER['SERVER_NAME'] . '/index.php?eID=tx_vjchat_pi1';

		$GLOBALS['TSFE']->additionalHeaderData['tx_vjchat_inc'] = '
			<script language="JavaScript" type="text/javascript">
			//<![CDATA[
				function tx_vjchat_openNewChatWindow(url, chatId) {
					var concatinator = "&";
					if(url.indexOf("?") == -1) {
						var concatinator = "?";
					}
						
					var vHWindow = window.open(url+concatinator+"tx_vjchat_pi1[uid]="+chatId+"&tx_vjchat_pi1[view]=chat&tx_vjchat_pi1[popup]=1","chatwindow"+chatId,"'.$this->conf['chatPopupJSWindowParams'].'");
					vHWindow.focus();
				}		
			//]]>
			</script>
		';


		$this->chatScript = $chatScript;


		$this->pi_USER_INT_obj=1;	// Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
		$GLOBALS['TSFE']->set_no_cache(); // disable frontend caching on this page

		$this->pi_setPiVarDefaults();
		$this->pi_loadLL("EXT:vjchat/Resources/Private/Language/locallang.xlf");
		$this->user = $GLOBALS['TSFE']->fe_user->user;

		$this->loadFLEX();

		/** @var tx_vjchat_db db */
		$this->db = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_vjchat_db');


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
        /** @var tx_vjchat_room $room */
		$room = $this->db->getRoom($entry->room);
		if(!tx_vjchat_lib::checkAccessToRoom($room, $this->user))
			return $this->displayErrorMessage($this->pi_getLL('access_denied'));

		if(!tx_vjchat_lib::isModerator($room, $this->user['uid']))
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
        /** @var tx_vjchat_room $room */
		if(!$room = $this->db->getRoom($roomId)) {
            return $this->displayErrorMessage($this->pi_getLL('error_room_not_found'), $this->conf['views.']['chat.']['stdWrap.']);
        }
        // remove old message entries if set
		if($this->db->extCONF['autoDeleteEntries']) {
            $this->db->deleteEntries($this->db->extCONF['autoDeleteEntries']);
        }

       // $debug = $this->db->changeRoomMembership( $this->db->getRoom(394) , 128927 , 'members' , true   ) ;
        // var_dump($debug) ;
        // die;
		$roomData = $this->getRoomData($room);
		$this->cObj->data = $roomData ;
		if(!$this->conf['FLEX']['showDescriptionInChat'])
			unset($this->cObj->data['description']);

		if(!tx_vjchat_lib::isSuperuser($room, $this->user)) {

			if(tx_vjchat_lib::isBanned($room, $this->user['uid']))
				return $this->displayErrorMessage($this->pi_getLL('error_banned'), $this->conf['views.']['chat.']['stdWrap.']);

			// check if user is kicked
			if($res = $this->db->isUserKicked($room->uid, $this->user['uid']))
				return $this->displayErrorMessage(sprintf($this->pi_getLL('error_kicked'),$res), $this->conf['views.']['chat.']['stdWrap.']);

			// check if this is a private room and if the user is an invited member
			if($room->private && !tx_vjchat_lib::isMember($room, $this->user['uid']))
				return $this->displayErrorMessage($this->pi_getLL('error_not_invited'), $this->conf['views.']['chat.']['stdWrap.']);

			// remove user who left room and remove system messages
			$this->db->cleanUpUserInRoom($room->uid, 20, true, $this->pi_getLL('user_leaves_chat'));

			//check rights to view room
			if(!tx_vjchat_lib::checkAccessToRoom($room, $this->user))
				return $this->displayErrorMessage($this->pi_getLL('error_room_access_denied'), $this->conf['views.']['chat.']['stdWrap.']);

		}

		/* ***********************************   LTS 9 ******************************** */
        /** @var   \TYPO3\CMS\Fluid\View\StandaloneView $renderer */
        $renderer = tx_vjchat_lib::getRenderer($this->settings , "DisplayChatRoom" , "html" ) ;

        $renderer->assign('settings', $this->settings );
        $renderer->assign('server',  \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'));

        $this->db->updateUserInRoom($roomId, $this->user['uid']);

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
        $dataString .= ' data-usernameglue="'       . tx_vjchat_lib::getUserNamesGlue()  . '"' ;
        $dataString .= ' data-messagesglue="'       . tx_vjchat_lib::getMessagesGlue()  . '"' ;
        $dataString .= ' data-usernamesfieldglue="' . tx_vjchat_lib::getUserNamesFieldGlue()  . '"' ;
        $dataString .= ' data-idglue="'             . tx_vjchat_lib::getIdGlue()  . '"' ;

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


        if( $this->piVars['debug'] ) {
            $dataString .= ' data-debug="true"' ;
        }



        $setup = $this->conf['chatbuttons_on.'];
        //$sKeyArray = t3lib_TStemplate::sortedKeyList($setup);
        $chatbuttons_on = array();
        $chatbuttons_keys = array();
        foreach($setup as $key => $value)	{
            $theValue = $setup[$key];
            if (!strstr($key,'.'))	{
                $conf = $setup[$key.'.'];
                $chatbuttons_on[] = $this->cObj->cObjGetSingle($theValue,$conf,$key);	// Get the contentObject
                $chatbuttons_off[] = '';
                $chatbuttons_keys[] = str_replace('_','-',$key);
            }
        }

        $marker['CHATBUTTONS_KEYS'] = "Array('".implode("','", $chatbuttons_keys)."')";
        $marker['CHATBUTTONS_ON'] = "Array('".implode("','", $chatbuttons_on)."')";
        $marker['CHATBUTTONS_OFF'] = "Array('".implode("','", $chatbuttons_off)."')";

        $marker['isFull'] = $this->cObj->data['isFull'] ;

        // display CHATROOM
        if(! $marker['isFull']) {


            $marker['SUBMIT_MESSAGE'] = $this->pi_getLL('submit_message');
            $marker['LABEL_NEW_MESSAGE'] = $this->pi_getLL('new_message');

/*
            if(!$this->conf['FLEX']['showFormatting'])
                $marker['CHATBUTTONS'] = false ;
            else {
                $this->cObj->data['enableEmoticons'] = $this->conf['FLEX']['showEmoticons'];
                $this->cObj->data['enableUserstyles'] = $this->conf['FLEX']['showStyles'];
                $this->cObj->data['enableTime'] = $this->conf['FLEX']['showTime'];
                $marker['CHATBUTTONS'] =  $this->cObj->cObjGet($this->conf['chatbuttons.']);
            }
*/
            $marker['EMOTICONS'] = tx_vjchat_lib::getEmoticonsForChatRoom();


            if($this->conf['useSnippets']) {
                // try to add user
                $this->db->updateUserInRoom($room->uid, $this->user['uid'], tx_vjchat_lib::isSuperuser($this->room, $this->user), $this->pi_getLL('user_enters_chat'));

                // prepare the user's snippets
                $this->db->setUserlistSnippet($room->uid, $this->user['uid'], $this->getSnippet($room, $this->user, $this->conf['userlistSnippet.']));
                $this->db->setTooltipSnippet($room->uid, $this->user['uid'], $this->getSnippet($room, $this->user, $this->conf['tooltipSnippet.']));
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

       // $theValue = $this->cObj->substituteMarkerArrayCached($subpart, $markerArray, $subpartMarkerArray);
        // prepend the subpart COMMON
       // $common = $this->cObj->getSubpart($template, 'COMMON');
        // $common = $this->cObj->substituteMarkerArray($common, $markerArray);

        $renderer->assign("user" , $this->user ) ;
        $renderer->assign("marker" , $marker ) ;
        $renderer->assign("room" , $room ) ;
        $renderer->assign("roomData" , $roomData ) ;
        $renderer->assign("confFLEX" , $this->conf['FLEX'] ) ;

        $renderer->assign("dataString" , $dataString ) ;
        $content = $renderer->render();
        return $content ;
	}
	
	function getRoomData($room) {
		$theValue = $room->toArray();

		$theValue['userCount'] = $this->db->getUserCountOfRoom($room->uid);
		$theValue['showUserCount'] = $this->conf['FLEX']['maxUserCount'];
		$theValue['sessionCount'] = $this->db->getSessionsCountOfRoom($room->uid);
		$theValue['isFull'] = $this->db->isRoomFull($room) && !tx_vjchat_lib::isSuperuser($room, $this->user) && !$this->db->isMemberOfRoom($room->uid, $this->user['uid']);


        $theValue['enableEmoticons'] = $this->conf['FLEX']['showEmoticons'];
        $theValue['enableTime'] = $this->conf['FLEX']['showTime'];
        $theValue['enableImageUpload'] = $this->conf['enableImageUpload'];

		$conf = $this->conf['views.'][$this->conf['tsRooms'].'.'];

		$experts = $moderators = $users = array();


		if($this->conf['FLEX']['showExperts']) {
			$experts = $this->db->getOnlineExperts($room->uid);
			$this->cObj->data['userType'] = 'expert';
			$theValue['onlineExperts'] = tx_vjchat_lib::getUsernames($experts, true, $conf['usersGlue'], $this->cObj, $conf['users_stdWrap.']);
		}

        if($this->conf['FLEX']['showModerators']) {
			$moderators = $this->db->getOnlineModerators($room->uid);
			$this->cObj->data['userType'] = 'moderator';
			$theValue['onlineModerators'] = tx_vjchat_lib::getUsernames($moderators, true, $conf['usersGlue'], $this->cObj, $conf['users_stdWrap.']);
		}
		if($this->conf['FLEX']['showUsers']) {
			$users = $this->db->getOnlineUsers($room->uid);
			$this->cObj->data['userType'] = 'user';
			$theValue['onlineUsers'] = tx_vjchat_lib::getUsernames($users, (($room->showFullNames) ? true : false), $conf['usersGlue'], $this->cObj, $conf['users_stdWrap.']);
		}

		$allUsers = array_merge( $experts, $moderators, $users);
		$theValue['allUserNicknames'] = tx_vjchat_lib::getUsernames($allUsers, false, $conf['usersGlue'], $this->cObj, $conf['users_stdWrap.']);

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

		//\TYPO3\CMS\Core\Utility\GeneralUtility::debug($theValue);

		return $theValue;
	}	// Datasource
	
	/**
	  * Generates a javascript array with colors for the users
	  */
	function getUserColorArray() {

		$array = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',',$this->conf['userColors']);
		$out = '' ;
		for($i = 0; $i<count($array); $i++) {

			$out = $out.'\''.$array[$i].'\'';

			if($i<count($array)-1)
				$out = $out.',';
		}

		return ' Array('.$out.')';

	}
	
	function getCssUserColors() {

		$array = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',',$this->conf['userColors']);
		$out = '' ;
		for($i = 0; $i<count($array); $i++) {

			$out = $out." .usercolor-$i { color: ".$array[$i]."; } ";

			if($i<count($array)-1)
				$out = $out.chr(10);
		}

		$out = '<style type="text/css">'.chr(10).$out.chr(10).'</style>';

		return $out;


	}
	
	/* gets configuration from plugin-flexform */

	function getCssUserColorsCount() {

		$array = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',',$this->conf['userColors']);
		return count($array);

	}
	
	function getStylingContainer() {

		// it must be defined at least two styles (default + 1)
		if(!$this->conf['messageStyles.']['1'])
			return "";

		$out = "";

		$this->conf['messageStyles.']['0'] = $this->conf['messageStyles.']['default'];
		$this->conf['messageStyles.']['0.'] = $this->conf['messageStyles.']['default.'];

		$i = 0;
		while($this->conf['messageStyles.'][$i]) {

			$this->cObj->data['number'] = $i;
			$out .= $this->cObj->cObjGetSingle($this->conf['messageStyles.'][$i], $this->conf['messageStyles.'][$i.'.']);
			$i++;
		}

		return $out;

	}
	/*
	* @param  tx_vjchat_room $room
	* @param  fe_user $user
	* @param array $conf

	*/
	function getSnippet($room, $user, $conf) {
		if(!$conf || !$user)
			return '';

		$cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');

			// this makes sure that only fields are available, that are defined in showuserinfo_users, ...
		$type = tx_vjchat_lib::getUserTypeString($room, $user);
		$details = $room->getDetailsField($type);
		foreach($details as $key) {
			if($room->showDetailOf($type,$key))
			    if ( $key ) {
                    $cObj->data[$key] = $user[$key];
                }
		}

			// these are always available
		$cObj->data['username'] = $room->showFullNames() ? $user['name'] : $user['username'];
		$cObj->data['tx_nem_image'] = $user['tx_nem_image'];
		$cObj->data['userpath'] = 'uploads/tx_feusers_img/' . $subPath = substr( "0000" . intval( round( $user['uid'] / 1000 , 0 )) , -4 , 4 ) . "/" . $user['tx_nem_image'] ;
		$cObj->data['uid'] = $user['uid'];
		return $cObj->cObjGet($conf);
	}
	
	function displaySessionsOfRoom($roomId) {
        /** @var tx_vjchat_room $room */
		if(!$room = $this->db->getRoom($roomId))
			return	$this->displayErrorMessage($this->pi_getLL('error_room_not_found'), $this->conf['views.']['sessions.']['stdWrap.']);

		//check rights to view room
		if(!tx_vjchat_lib::checkAccessToRoom($room, $this->user))
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
        /** @var tx_vjchat_room $room */
		if(!$room = $this->db->getRoom($session->room))
			return $this->displayErrorMessage($this->pi_getLL('room_not_found'), $this->conf['views.']['session.']['stdWrap.']);

		$this->db->cleanUpUserInRoom($room->uid, 10, true, $this->pi_getLL('user_leaves_chat'));

		//check rights to view room
		if(!tx_vjchat_lib::checkAccessToRoom($room, $this->user))
			return $this->displayErrorMessage($this->pi_getLL('access_denied'));


//		var_dump($session);

		$entries = $this->db->getEntriesOfSession($session);

		$isModerator = tx_vjchat_lib::isModerator($room, $this->user['uid']);
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
			if(tx_vjchat_lib::isModerator($room, $entry->feuser))
				$this->cObj->data['type'] = 1;

			if(tx_vjchat_lib::isSystem($entry->feuser))
				$this->cObj->data['type'] = 2;

			if(tx_vjchat_lib::isExpert($room, $entry->feuser))
				$this->cObj->data['type'] = 3;

			$this->cObj->data['entry'] = tx_vjchat_lib::formatMessage($entry->entry);

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

			// set data (current room array to cobj)
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
		if(!$this->conf['FLEX']['chatrooms'])
			$rooms = $this->db->getRooms($this->conf['pidList']);
		else {
			$rooms = array();
			$roomsIds = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->conf['FLEX']['chatrooms']);
			foreach($roomsIds as $id) {
				$rooms[] = $this->db->getRoom($id);
			}
		}



		$theValue = array();
		foreach($rooms as $room) {

			//check rights to view room
			//if(!tx_vjchat_lib::checkAccessToRoom($room, $this->user))
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


    var $prefixId = 'tx_vjchat_pi1';

    var $scriptRelPath = 'pi1/class.tx_vjchat_pi1.php';

    var $extKey = 'vjchat';
	
	var $pi_checkCHash = FALSE;
	
	var $chatScript;

	/** @var  tx_vjchat_db  */
    var $db;

	var $user;
	

}


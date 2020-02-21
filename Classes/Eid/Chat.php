<?php
/** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpUndefinedFieldInspection */

namespace JV\Jvchat\Eid;

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
 
use \JV\Jvchat\Utility\LibUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;


// was : class tx_jvchat_chat {
class Chat {

    /** @var \TYPO3\CMS\Lang\LanguageService $lang */
    var $lang;

    var $commands;

    /** @var  \JV\Jvchat\Domain\Repository\DbRepository  */
    var $db;

    var $env;

    var $debug = false;

    /**********************************************************************************************/
    // GENERAL HELPER FUNCTIONS
    /**********************************************************************************************/

    var $debugMessages = array();

    /** @var \JV\Jvchat\Domain\Model\Room $newRoom */
    var $room;

    var $user;

    var $lastMessageId;

    /** @var array The typoscript setup includings views cObjects and the settings array  */
    var $setup = array() ;

    var $extConf;

	function init($user, $charset , $room = false ) {
		// load language files
		// at this moment it is impossible to modify this via TypoScript
		//$LLKey = $GLOBALS['TSFE']->config['config']['language'];
		$this->microtime = microtime();

		$this->extConf = LibUtility::getExtConf();



		// get parameters
		$this->env['user'] = $user->user;
		$this->env['room_id'] = intval(GeneralUtility::_GP('r'));
		$this->env['pid'] = intval(GeneralUtility::_GP('p'));
		$this->env['charset'] = $charset;

		$this->env['msg'] = GeneralUtility::_GP('m');

		$this->env['msg'] = rawurldecode($this->env['msg']) ;
		$this->env['msg'] = str_replace('<', '&lt;', $this->env['msg']);
		$this->env['msg'] = str_replace('>', '&gt;', $this->env['msg']);

		$this->env['action'] = htmlspecialchars(GeneralUtility::_GP('a'));
		$this->env['lastid'] = intval(GeneralUtility::_GP('t'));
		$this->env['uid'] = intval(GeneralUtility::_GP('uid'));
		$this->env['usercolor'] = intval(GeneralUtility::_GP('uc'));
		$this->env['LLKey'] = htmlspecialchars(GeneralUtility::_GP('l'));


		$this->lang = GeneralUtility::makeInstance('TYPO3\\CMS\\Lang\\LanguageService');
		$this->lang->init($this->env['LLKey']);
		if( $this->env['LLKey'] == "en" || $this->env['LLKey'] == "default" || $this->env['LLKey'] == '') {
			$this->lang->includeLLFile("EXT:jvchat/Resources/Private/Language/locallang.xlf");
		} else {
			$this->lang->includeLLFile("EXT:jvchat/Resources/Private/Language/"  . $this->env['LLKey'] . ".locallang.xlf"  );
		}

	        /** @var \JV\Jvchat\Domain\Repository\DbRepository db */
		$this->db = GeneralUtility::makeInstance('JV\Jvchat\Domain\Repository\DbRepository');
		$this->db->lang = $this->lang;

		if ( $room ) {
            $this->room = $room ;
        } else {
            $this->room = $this->db->getRoom($this->env['room_id']);
        }
		$this->user = $this->env['user'];

		if(GeneralUtility::_GP('d') == 'true')
			$this->debug = true;

		$this->debugMessage('init');

		$this->lastMessageId = $this->env['lastid'];
        $this->setup = LibUtility::getSetUp( $this->env['pid'] );

		// init commands
		$this->initCommands($this->room );

	}

	function debugMessage($function) {
		$this->debugMessages[] = $function.':'.$this->getMicrotime();
	}

	function getMicrotime() {
		$result = $this->getMicrotimeAsFloat() - $this->getMicrotimeAsFloat($this->microtime);
		$this->microtime = microtime();
		return $result;
	}
	
	function getMicrotimeAsFloat($microtime = NULL) {
		if(!$microtime)
			$microtime = microtime();

		list($usec, $sec) = explode(" ", $microtime);
		return ((float)$usec + (float)$sec);
	}

	function initCommands($room) {
		$initCmd = array(
			'help' => array(
				'callback' => '_help',
				'description' => $this->lang->getLL('command_help'),
				'rights' => '1111',
			),

			'smilies' => array(
				'callback' => '_smilies',
				'description' => $this->lang->getLL('command_smileys'),
				'rights' => '1111',
                'parameters' => array(
                    'group' => array(
                        'description' => ' the icon category: food, emoji, signs ',
                        'regExp' =>'/.(.*)/i',
                        'required' => 0,
                    ),
                ),
			),

			'quit' => array(
				'callback' => '_quit',
				'description' => $this->lang->getLL('command_quit'),
				'parameters' => array(
					'msg' => array(
						'description' => $this->lang->getLL('command_param_reason'),
						'required' => 0,
					),
				),
				'rights' => '1111',
			),

			'restart' => array(
				'callback' => '_restart',
				'description' => $this->lang->getLL('command_restart'),
				'rights' => '1111',
			),
			'stop' => array(
				'callback' => '_stop',
				'description' => $this->lang->getLL('command_stop'),
				'rights' => '1111',
			),

			'roomlist' => array(
				'callback' => '_roomlist',
				'description' => $this->lang->getLL('command_roomlist'),
				'rights' => '1111'
				),
			'invite' => array(
				'callback' => '_invite',
				'description' => $this->lang->getLL('command_invite'),
				'parameters' => array(
					'name' => array(
						'regExp' =>'/.(.*)/i',
						'description' => $this->lang->getLL('command_param_userid'),
						'required' => 0,
					),
				),
				'rights' => ($this->room && ($this->room->private && LibUtility::isOwner($this->room, $this->user['uid'])) ? '1111' : '0000'),
			    ),
			'msg' => array(
					'callback' => '_msg',
					'hidefeedback' => '1',
					'description' => $this->lang->getLL('command_msg'),
					'parameters' => array(
						'userId' => array(
							'regExp' =>'/(#([0-9]*)|[alphanum])?/i',
							'description' => $this->lang->getLL('command_param_userid'),
							'required' => 1,
						),
						'message' => array(
							'description' => $this->lang->getLL('command_param_message'),
							'required' => 1,
						),
					),
					'rights' => $this->extConf['allowPrivateMessages'] ? '1111' : '0001',
				),
                'notifyme' => array(
                    'callback' => '_notifyme',
                    'hidefeedback' => '1',
                    'hideinhelp' => '1',
                    'description' => $this->lang->getLL('command_notifyme') ,
                    'rights' =>  ( $this->room->private ? '0001' : '1111' ) ,
                ),
				'kick' => array(
					'callback' => '_kick',
					'description' => $this->lang->getLL('command_kick'),
					'parameters' => array(
						'userId' => array(
							'regExp' =>'/(#([0-9]*)|[alphanum])?/i',
							'description' => $this->lang->getLL('command_param_userid'),
							'required' => 1,
						),
						'time' => array(
							'regExp' =>'/[0-9]*/',
							'description' => $this->lang->getLL('command_kick_param_time'),
							'required' => 0,
							'default' => 20,
						),
						'reason' => array(
							'description' => $this->lang->getLL('command_param_reason'),
							'required' => 0,
						),
					),
					'rights' => '0011',
				),
				'ban' => array(
					'callback' => '_ban',
					'description' => $this->lang->getLL('command_ban'),
					'parameters' => array(
						'userId' => array(
							'regExp' =>'/(#([0-9]*)|[alphanum])?/i',
							'description' => $this->lang->getLL('command_param_userid'),
							'required' => 1,
						),
						'reason' => array(
							'description' => $this->lang->getLL('command_param_reason'),
							'required' => 0,
						),
					),
					'rights' => '0011',
				),
				'redeem' => array(
					'callback' => '_redeem',
					'description' => $this->lang->getLL('command_redeem'),
					'parameters' => array(
						'userId' => array(
							'regExp' =>'/(#([0-9]*)|[alphanum])?/i',
							'description' => $this->lang->getLL('command_param_userid'),
							'required' => 1,
						),
						'reason' => array(
							'description' => $this->lang->getLL('command_param_reason'),
							'required' => 0,
						),
					),
					'rights' => '0011',
				),
                'whois' => array(
                    'callback' => '_whois',
                    'description' => $this->lang->getLL('command_who_is'),
                    'parameters' => array(
                        'userId' => array(
                            'regExp' =>'/(#([0-9]*)|[alphanum])?/i',
                            'description' => $this->lang->getLL('command_param_userid'),
                            'required' => 1,
                        ),
                    ),
                    'rights' => '0001',
                ),

                'email' => array(
                    'callback' => '_email',
                    'description' => $this->lang->getLL('command_email'),
                    'rights' => '1111',
                ),

				'makesession' => array(
					'callback' => '_makesession',
					'description' => $this->lang->getLL('command_makesession'),
					'parameters' => array(
						'firstId' => array(
							'regExp' => '/^[0-9]*$/',
							'description' => $this->lang->getLL('command_makesession_param_firstid'),
							'required' => 1,
						),
						'lastId' => array(
							'regExp' => '/^[0-9]*$/',
							'description' => $this->lang->getLL('command_makesession_param_lastid'),
							'required' => 1,
						),
						'name' => array(
							'regExp' =>'/.(.*)/i',
							'description' => $this->lang->getLL('command_makesession_param_name'),
							'required' => 1,
						),
					),
					'rights' => $this->extConf['createSessions'] ? '0011' : '0000',
				),
				'makeexpert' => array(
					'callback' => '_makeexpert',
					'description' => $this->lang->getLL('command_makeexpert'),
					'parameters' => array(
						'name' => array(
							'regExp' =>'/.(.*)/i',
							'description' => $this->lang->getLL('command_param_userid'),
							'required' => 1,
						),
					),
					'rights' => ($this->room && $this->room->isExpertMode()) ? '0011' : '0000',
				),
				'makeuser' => array(
					'callback' => '_makeuser',
					'description' => $this->lang->getLL('command_makeuser'),
					'parameters' => array(
						'name' => array(
							'regExp' =>'/.(.*)/i',
							'description' => $this->lang->getLL('command_param_userid'),
							'required' => 1,
						),
					),
					'rights' => ($this->room && $this->room->isExpertMode()) ? '0011' : '0000',
				),
				'cleanup' => array(
					'callback' => '_cleanuproom',
					'description' => $this->lang->getLL('command_cleanup'),
					'rights' => $this->extConf['createSessions'] ? '0011' : '0000',
				),
				'cleanupall' => array(
					'callback' => '_cleanupall',
					'description' => $this->lang->getLL('command_cleanupall'),
					'rights' => '0001',
				),
				'switch' => array(
					'callback' => '_togglestatus',
					'description' => $this->lang->getLL('command_setstatus'),
					'parameters' => array(
						'name' => array(
							'regExp' =>'/.(.*)/i',
							'description' => $this->lang->getLL('command_param_userid'),
							'required' => 0,
						),
						'status' => array(
							'regExp' =>'/.(.*)/i',
							'description' => $this->lang->getLL('command_param_status'),
							'required' => 1,
						),
					),
					'rights' => '0011',
				),

				'newroom' => array(
					'callback' => '_newroom',
                    'hidefeedback' => '1',
                    'hideinhelp' => '1',
					'description' => $this->lang->getLL('command_newroom'),
					'parameters' => array(
						'name' => array(
							'regExp' =>'/.(.*)/i',
							'description' => $this->lang->getLL('command_newroom_param_name'),
							'required' => 0,
						),
					),
					'rights' => $this->extConf['allowPrivateRooms'] ? '1111' : '0001',
				),

                'talkTo' => array(
                    'callback' => '_talkTo',
                 //   'hidefeedback' => '1',
                 //   'hideinhelp' => '1',
                    'description' => $this->lang->getLL('command_talkto'),
                    'parameters' => array(
                        'uid' => array(
                            'regExp' =>'/[0-9]*)/i',
                            'description' => $this->lang->getLL('command_talkto_param_uid'),
                            'required' => 0,
                        ),
                        'name' => array(
                            'regExp' =>'/.(.*)/i',
                            'description' => $this->lang->getLL('command_talkto_param_name'),
                            'required' => 0,
                        ),
                    ),
                    'rights' => $this->extConf['allowPrivateRooms'] ? '1111' : '0001',
                ),

				'recentinvite' => array(
					'callback' => '_recentinvite',
                    'hidefeedback' => '1',
					'description' => $this->lang->getLL('command_recentinvite'),
					'parameters' => array(
						'name' => array(
							'regExp' =>'/.(.*)/i',
							'description' => $this->lang->getLL('command_param_userid'),
							'required' => 0,
						),
					),
					'hideinhelp' => '1',
					'rights' => '1111',
				),
				'switchroomstatus' => array(
					'callback' => '_toggleroomstatus',
					'description' => $this->lang->getLL('command_setroomstatus'),
					'parameters' => array(
						'status' => array(
							'regExp' =>'/.(.*)/i',
							'description' => $this->lang->getLL('command_param_status'),
							'required' => 1,
						),
					),
					'rights' => $this->extConf['moderatorsAllowSwitchRoomStatus'] ? '0011' : '0001',
				),

			);
        if ( is_array($this->setup['settings']) && is_array($this->setup['settings']['commands'])) {
            $this->commands = array_merge($initCmd , $this->setup['settings']['commands']  ) ;
        } else {
            $this->commands = $initCmd ;
        }
	}

	function perform() {
		switch ($this->env['action']) {
				// check if room is full
			case 'checkfull':
				return $this->checkFull();
				// get messages
			case 'gm':
				return $this->getMessages($this->env['lastid']);
			    break;
				// send message
			case 'sm':
				return $this->putMessage($this->env['msg'],$this->env['lastid']);
			    break;
				// get userlist
			case 'gu':
				return $this->getUserlist();
			    break;
				// unhide message
			case 'commit':
				return $this->commitMessage($this->env['uid']);
			    break;
            case 'del':
                return $this->deleteEntry($this->env['uid']);
                break;
		}
        return '' ;
	}


    function deleteEntry($entryId) {
        // check rights
        $entry = $this->db->getEntry($entryId);
        /** @var \JV\Jvchat\Domain\Model\Room $newRoom */
        $room = $this->db->getRoom($entry->room);

        if(!LibUtility::checkAccessToRoom($room, $this->user) || !$this->user['uid'] ) {
            echo __LINE__ . "-" . $this->lang->getLL('access_denied') ;
        }

        if( ! $entry->feuser == $this->user['uid'] ) {
            if(!LibUtility::isModerator($room, $this->user['uid'])) {
                echo __LINE__ . $this->lang->getLL('access_denied') ;
            }
        }

        return $this->db->deleteEntry($entryId);
    }

	function checkFull() {
		return ($this->db->isRoomFull($this->room) && !$this->db->isMemberOfRoom($this->room->uid, $this->user['uid'])) ? 'full' : 'notfull';
	}

    /**
     * get  an array of Message and convert it to string / http response
     * @param integer $lastid
     * @return string
     */
    function getMessages($lastid) {#


        if(!LibUtility::isSuperuser($this->room, $this->user)) {
            // check if user is banned
            if(LibUtility::isBanned($this->room, $this->user['uid']))
                return $this->returnMessage(array('<span class="tx-jvchat-error">'.$this->lang->getLL('error_banned').'</span>', '/quit'));

            // check if user is kicked
            if($res = $this->db->isUserKicked($this->room->uid, $this->user['uid']))
                return $this->returnMessage(array('<span class="tx-jvchat-error">'.sprintf($this->lang->getLL('error_kicked'),$res).'</span>', '/quit'));

            // check if this is a private room and if the user is an invited member
            if($this->room->private && !LibUtility::isMember($this->room, $this->user['uid']))
                return $this->returnMessage(array('<span class="tx-jvchat-error">'.$this->lang->getLL('error_not_invited').'</span>', '/quit'));

            // remove user who left room and remove system messages
        //    $this->db->cleanUpUserInRoom($this->room->uid, 20, true, $this->lang->getLL('user_leaves_chat'));

            // check if user is allowed to put a message into this room
            if(!LibUtility::checkAccessToRoom($this->room, $this->user))
                return $this->returnMessage(array('<span class="tx-jvchat-error">'.$this->lang->getLL('error_room_access_denied').'</span>','/quit'));

        }

        // updateUserData
        // if user not already in room try to add
        $resUpdate = $this->db->updateUserInRoom($this->room->uid, $this->user['uid'], LibUtility::isSuperuser($this->room, $this->user), $this->lang->getLL('user_enters_chat'));

        // quit here if room is full
        if($resUpdate === "full") {
            return $this->returnMessage('full');
        }




        $entries = $this->db->getEntries($this->room, $lastid);

        if(count($entries) == 0) {

            return $this->returnMessage('NO Messages');
        }

        /** @var   \TYPO3\CMS\Fluid\View\StandaloneView $renderer */
        $renderer = LibUtility::getRenderer($this->setup , "GetMessages" , "html" )  ;


        $messages = array() ;
        /** @var \JV\Jvchat\Domain\Model\Entry $entry */
        foreach($entries as $entry) {

            // if message is a quit message for current client
            if((preg_match('/^\/quit/i', $entry->entry)) && ($this->user['uid'] == $entry->feuser)) {
                $this->db->leaveRoom($this->room->uid, $entry->feuser);
                $this->db->deleteEntry($entry->uid);
                return '/quit';		// will be handled by client javascript
            }

            // delete from db if entry is a command and continue with next entry
            if(preg_match('/^\//i', $entry->entry)) {
                $this->db->deleteEntry($entry->uid);
                continue;
            }

            // first check if this entry should be sent to client
            // a) expert mode
            // - sent if message is not hidden
            // - if it is hidden only sent to moderators client
            // b) normal mode
            // - sent message without checking anything
            // c) private message
            // - sent message only to dest user
            // d) a superuser should receive all messages
            if(!$entry->isPrivate()) {
                if($this->room->isExpertMode() && $entry->hidden) {
                    if(!LibUtility::isSuperuser($this->room, $this->user) && !LibUtility::isModerator($this->room, $this->user['uid']) && ($this->user['uid'] != $entry->feuser))
                        continue;	// skip to next entry
                }
            }
            else {

                $involved = ($entry->tofeuserid == $this->user['uid']) || ($entry->feuser == $this->user['uid']);

                // if this is a private message check if this message should be received by the current user
                // if superuser skip message if he is not allowed to view private messages
                if(LibUtility::isSuperuser($this->room, $this->user) && !$this->extConf['superuserCanReadPMs'] && !$involved)
                    continue;

                // if not a superuser check show message to sender an recipient only
                if(!LibUtility::isSuperuser($this->room, $this->user) && !$involved)
                    continue;	// skip to next entry
            }

            $entryUser = NULL;
            if(! LibUtility::isSystem($entry->feuser)) {
                $entryUser = $this->db->getFeUser($entry->feuser);	// this holds the complete user array
            }

            $recipient = false ;
            // the superuser should know the recipient of a private message
            if($entry->isPrivate()) {
                $recipient = $this->db->getFeUser($entry->tofeuserid);
            }
            $entryText = LibUtility::formatMessage($entry->entry, $this->setup['settings']['emoticons'] );

            $id = "";
            if(LibUtility::isModerator($this->room, $this->user['uid'])) {
                $id = '#'.$entry->uid.'&nbsp;';
            }

            $time = $entry->crdate;
            if( $this->extConf['serverTimeOffset'] ) {
                $time = strtotime($this->extConf['serverTimeOffset'], $time);
            }
            if( array_key_exists( 'timeFormat' , $this->extConf )) {
                $timeFormat = $this->extConf['timeFormat'];
            } else {
                $timeFormat = "%H:%I:%S" ;
            }


            if($entryUser) {
                $userType = LibUtility::getUserTypeString($this->room, $entryUser) ;
            } else {
                $userType = 'system' ;
            }

            $this->lastMessageId = $entry->uid;

            $groupstyles = $this->getUserGroupStyles($entryUser);

            $mid = GeneralUtility::shortMD5(($entry->tstamp).($entry->uid));

            if(LibUtility::isModerator($this->room, $this->user['uid']) && !$entry->isPrivate()) {
                $renderer->assign("needsModeration" , true ) ;
            }

            $ownMsg = $entryUser['uid'] == $this->user['uid'] ? 1 : 0 ;
            $renderer->assign("id" , $id ) ;
            $renderer->assign("mid" , $mid ) ;
            $renderer->assign("entry" , $entry ) ;
            $renderer->assign("entryText" , $entryText ) ;
            $renderer->assign("user" , $this->user ) ;
            $renderer->assign("isPrivateRoom" , $this->room->private ) ;
            $renderer->assign("entryUser" , $entryUser ) ;
            $renderer->assign("ownMsg" , $ownMsg ) ;
            $renderer->assign("recipient" , $recipient ) ;
            $renderer->assign("isModerator" , LibUtility::isModerator($this->room, $this->user['uid']) ) ;

            $renderer->assign("involved"  , $involved ) ;

            $renderer->assign("userType" , $userType ) ;

            $renderer->assign("time" , $time ) ;
            $renderer->assign("timeFormat" , $timeFormat ) ;

            // 2019 j.v. : the translation setting in rendering Template is not setup Correctly .
            // as workaround do translation in php ..
            $this->extConf['LLL']['command_invite'] = $this->lang->getLL('command_invite')  ;

            $renderer->assign("showFullNames" , $this->room->showFullNames() ) ;
            $renderer->assign("extConf" , $this->extConf ) ;

           // $messages[] = $message;
            $messages[] = $renderer->render();
        }

        // if just entered chat
        if($resUpdate === "entered") {
            // welcome message
            $messages[] = $this->room->welcomemessage;
            $messages[] = $this->lang->getLL('after_welcome_message');
        }
        // $messages[] = " JVEDEBUG: --- " . $resUpdate . " --- " ;


        return $this->returnMessage($messages);
	}

	/**
	  * Prepares an array of messages for client. This means prepending each message with [MSG] and adding a timestamp after [TIME]
	  * @param mixed
	  * @return string
	  */
	function returnMessage($messages, $withId = true) {

		if(!is_array($messages))
			$messages = array($messages);

		if(GeneralUtility::_GP('d') == 'alltime')  {
			$messages[] = "ALL: ".($this->getMicrotimeAsFloat() - $this->getMicrotimeAsFloat($GLOBALS['TYPO3_MISC']['microtime_start']));
		}

		if($this->debug) {
			foreach($this->debugMessages as $message)
				$messages[] = $message;
		}

		$out = '';
		if(GeneralUtility::_GP('showJson') == '1')  {

			$jsonMes = array() ;
			$i = 0 ;
			foreach($messages as $message) {
				$jsonSub = preg_split("/<\/span>/" , $message ) ;
				$ii = 0 ;
				foreach($jsonSub as $jsonSubMes) {
					$tempMes = trim( strip_tags( $jsonSubMes )) ;
					$jsonMes[$i][$ii] = $tempMes ;
					if ( $ii == 0) {
						$timeId = $jsonMes[$i][$ii] ;
						$jsonMes[$i][$ii] = substr($timeId , 0 ,8 ) ;
						$ii++ ;
						$jsonMes[$i][$ii] = substr($timeId , 9 ,999 ) ;
					}
					$jsonMes[$i][$ii] = trim( str_replace( "&nbsp;" , " " , htmlspecialchars_decode( $jsonMes[$i][$ii]))) ;
					$ii++ ;
				}
				$i++;
			}
			$out = array( "lastid" => $withId ? ' id="'. $this->lastMessageId.'"' : '',
						  "mes"	=> $jsonMes ) ;

			$jsonOutput = json_encode($out);
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header('Cache-Control: no-cache, must-revalidate');
			header('Pragma: no-cache');
			header('Content-Length: ' . strlen($jsonOutput));
			header('Content-Type: application/json; charset=utf-8');
			header('Content-Transfer-Encoding: 8bit');

			echo $jsonOutput;
			exit ;
		} else {
			$out = '' ;
			foreach($messages as $message) {
				$out .= '<msg><![CDATA['.$message.']]></msg>' .chr(10);
			}

			$id = $withId ? ' id="'.$this->lastMessageId.'"' : '';
			$returnMsg = '<?xml version="1.0" encoding="'.($this->env['charset']).'"?>'.chr(10)  .'<returnmsg'.$id.'>'.$out.'</returnmsg>';

			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header('Cache-Control: no-cache, must-revalidate');
			header('Pragma: no-cache');
		//	header('Access-Control-Allow-Origin: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'));
			header('Content-Length: ' . strlen($returnMsg));
			header('Content-Type: application/xml; charset=utf-8');
			header('Content-Transfer-Encoding: 8bit');
			echo $returnMsg ;
			exit ;
		}

	}
	
	function getUserGroupStyles($user) {
		$groupsOfUser = GeneralUtility::intExplode(',', $user['usergroup']);

		if(!is_array($groupsOfUser) || !count($groupsOfUser))
			return '';

		return ' tx-jvchat-usergroup-'.implode(' tx-jvchat-usergroup-',$groupsOfUser);
	}

	function putMessage($msg, $lastid, $tofeuserid = 0) {

		$this->debugMessage('putMessage');

		if($msg == '')
			return;

		if(!LibUtility::isSuperuser($this->room, $this->user)) {

			// check if user is allowed to put message into this room
			if(!LibUtility::checkAccessToRoom($this->room, $this->user))
				return $this->returnMessage('<span class="tx-jvchat-error">'.$this->lang->getLL('error_room_access_denied').'</span>');

			// check if user is kicked
			if($res = $this->db->isUserKicked($this->room->uid, $this->user['uid']))
				return $this->returnMessage(array('<span class="tx-jvchat-error">'.sprintf($this->lang->getLL('error_kicked'),$res).'</span>', '/quit'));

			// check if user is banned
			if(LibUtility::isBanned($this->room, $this->user['uid']))
				return $this->returnMessage(array('<span class="tx-jvchat-error">'.$this->lang->getLL('error_banned').'</span>', '/quit'));

		}

		// check for commands
		// if it is a command (indicated with first char '/' perform and return result )
		if(substr( $msg , 0 , 1 ) == '/') {
			return $this->performCommand(trim($msg));
		}

		// just put message if it is a normal chat room
		// or the user is a moderator or expert
		// if it is private message ($tofeuserid != null) send a hidden message
		if(!$this->room->isExpertMode() || LibUtility::isModerator($this->room, $this->user['uid'])  || LibUtility::isExpert($this->room, $this->user['uid'])) {
			$this->db->putMessage($this->room->uid, $msg, $this->user['tx_jvchat_chatstyle'], $this->user, ($tofeuserid ? true : false), $this->user['uid'], $tofeuserid);
			return $this->getMessages($lastid);
		}

		// otherwise put a hidden message
		$this->db->putMessage($this->room->uid, $msg, $this->user['tx_jvchat_chatstyle'], $this->user, true, $this->user['uid'], $tofeuserid);
		return $this->getMessages($lastid);

	}
	
	function performCommand($lines) {
		if(!LibUtility::checkAccessToRoom($this->room, $this->env['user']))
			return $this->lang->getLL('error_room_access_denied');

		$lines = GeneralUtility::trimExplode(chr(10), $lines);

		foreach($lines as $line) {

				// check if message contains commands
			$parts = GeneralUtility::trimExplode(' ', $line);

			$found = false;
            $out = '' ;
			foreach($this->commands as $command => $data) {
				if($parts[0] == ('/'.$command)) {
					$found = true;
					// check rights
					unset($parts[0]);
                   if(!$this->grantAccessToCommand($command, $this->env['user'])) {
						$out .= $this->returnMessage('<span class="tx-jvchat-error">'.$this->lang->getLL('error_access_denied').'</span>');
						continue;
					}
                    if ( ! $this->room->isPrivate() && $command == "email") {
                        continue;
                    }


                    // check params
					$paramResult = $this->checkParams($parts, $data['parameters']);

					if($paramResult === true) {
					    $commandFnc = $data['callback'] ;
					    if ( $commandFnc ) {
                            $cmdResult = $this->$commandFnc($parts);
                            if(!$data['hidefeedback']) {
                                $out .= '<span class="tx-jvchat-ok">/'.$command.' '.implode(' ',$parts).'</span>';
                                $out .= '<br><span class="tx-jvchat-ok">'. $cmdResult .'</span>';
                            } else {
                                // error
                                if($cmdResult) {
                                    $out .= '<span class="tx-jvchat-error">'. sprintf($cmdResult,$parts[0]).   '</span>';
                                }
                            }
                        }
					} else {
                        $out .= '<span class="tx-jvchat-error">/'.$command.' '.implode(' ',$parts).': '.$paramResult.'</span>';
                    }
				}

			}

			if(!$found)
				$out .= '<span class="tx-jvchat-error">'.sprintf($this->lang->getLL('command_not_found'),$parts[0]).'</span>';

		}

		return $this->returnMessage($out, false);


	}

	/**********************************************************************************************/
	// FUNCTION CALLED BY CLIENT JAVASCRIPT
	/**********************************************************************************************/	

	function grantAccessToCommand($command , $user = null ) {
		$denied = true;

		if($this->commands[$command]['rights'][0])
			$denied = false;

		if($this->commands[$command]['rights'][1] && LibUtility::isExpert($this->room, $this->user['uid']))
			$denied = false;

		if($this->commands[$command]['rights'][2] && LibUtility::isModerator($this->room, $this->user['uid']))
			$denied = false;

		if($this->commands[$command]['rights'][3] && LibUtility::isSuperuser($this->room, $this->user))
			$denied = false;

		return !$denied;
	}

	function checkParams($params, $data) {

		if(!$data['parameters'])
			return true;

		$number = 1;
		foreach($data as $name => $paramData) {
			if($paramData['regExp'] && !preg_match($paramData['regExp'], $params[$number]))
				return sprintf($this->lang->getLL('command_wrong_parameter'), $name, $paramData['description']);
			$number++;
		}
		return true;
	}

	function getUserlist($room = NULL, $roomlistMode = false) {

		if(!$room) {
            $room = $this->room;
        }


		// check if user is allowed to put message in this room
		if(!LibUtility::checkAccessToRoom($room, $this->user))
			return $this->returnMessage($this->lang->getLL('error_room_access_denied'));

		//$messages = $this->getUserNamesOfRoom($room);
		$messages = $this->getUserlistOfRoom($room, $roomlistMode);
		return $this->returnMessage($messages);
	}

	/**
	  * This is for getUserlist() only
     * @param \JV\Jvchat\Domain\Model\Room $room
     * @param boolean $roomlistMode
	  */
	function getUserlistOfRoom($room, $roomlistMode = false) {


		$users = $this->db->getFeUsersOfRoom($room);
        $glue = LibUtility::getUserNamesGlue() ;
		$messages = array() ;
		foreach($users as $user) {
			if(!$user || !$user['username']) {
                $user['hidden'] = 1 ;
                continue;
            }
            $user['chatType'] = LibUtility::getUserTypeString($room, $user);
            // $snippet = $this->db->getSnippets($room->uid, $user['uid']);
            $userName = $user['username']  ;
            if( $this->extConf['usernameField1']) {
                $userName = $user[$this->extConf['usernameField1']]  ;
            }
            if( $room->showFullNames() ) {
                if( $this->extConf['usernameField1']) {
                    $userName = $user[$this->extConf['usernameField1']]  ;
                }
                if( $this->extConf['usernameField2']) {
                    $userName .= "_" .$user[$this->extConf['usernameField2']]  ;

                }
            }
            $userName = str_replace(" " , "_" , $userName ) ;
            $messages[] = $user['userlistsnippet'] . $glue .$user['chatType'] .  $glue . $user['uid'] . $glue . $userName ;
		}

        return $messages ;

	}
	
	function getUsername($user = NULL)
    {
        if (!$user) {
            $user = $this->user;
        }
        $field1 = $this->extConf['usernameField1'] ;
        if ( !$field1) {
            $field1 = 'name' ;
        }
        $return = $user[$field1] ;
        if( $this->room->showFullNames() ) {
            $field2 = $this->extConf['usernameField2'] ;
            if ( $field2) {
                $return .= " " . Substr( $user[$field2], 0, 1 ) . "." ;
            }
        }
        return $return ;

	}
	
	function commitMessage($entryId) {

		if(!LibUtility::isModerator($this->room, $this->user['uid']))
			return $this->returnMessage('<span class="tx-jvchat-error">'.$this->lang->getLL('error_room_access_denied').'</span>');

		if($this->db->commitMessage($entryId))
			return $this->returnMessage('<span class="tx-jvchat-ok">'.sprintf($this->lang->getLL('message_committed'),$entryId).'</span>');
		else
			return $this->returnMessage('<span class="tx-jvchat-error">'.$this->lang->getLL('error_commit').'</span>');
	}

    /**
     * @param array $entries An array with entries of Type \JV\Jvchat\Domain\Model\Entry
     * @param \JV\Jvchat\Domain\Model\Room $room An array
     * @return string
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
     */
	function getEntryTextForEmail( $entries, $room=false ) {
	    if( !$room ) {
            $room = $this->room ;
        }
        /** @var   \TYPO3\CMS\Fluid\View\StandaloneView $renderer */
        $renderer = LibUtility::getRenderer($this->setup , "GetEmailMessages" , "html" )  ;


        $messages = "" ;
        /** @var \JV\Jvchat\Domain\Model\Entry $entry */
        foreach($entries as $entry) {
            // if entry is a command and continue with next entry
            if(preg_match('/^\//i', $entry->entry) || $entry->isPrivate() ) {
                continue;
            }

            if( LibUtility::isSystem($entry->feuser)) {
                continue;
            }


            // FIRST Convert  all BB Code to HTML and then remove it.
            $entryText = trim(strip_tags( LibUtility::formatMessage($entry->entry, $this->setup['settings']['emoticons'] )) ) ;
            if( strlen($entryText) < 2 ) {
                continue;
            }

            $entryUser = $this->db->getFeUser($entry->feuser);	// this holds the complete user array
            $time = $entry->crdate;
            if( $this->extConf['serverTimeOffset'] ) {
                $time = strtotime($this->extConf['serverTimeOffset'], $time);
            }
            if( array_key_exists( 'timeFormat' , $this->extConf )) {
                $timeFormat = $this->extConf['timeFormat'];
            } else {
                $timeFormat = "%H:%I:%S" ;
            }

            $ownMsg = $entryUser['uid'] == $this->user['uid'] ? 1 : 0 ;
            $renderer->assign("entry" , $entry ) ;
            $renderer->assign("entryText" , $entryText ) ;
            $renderer->assign("entryUser" , $entryUser ) ;
            $renderer->assign("ownMsg" , $ownMsg ) ;
            $renderer->assign("time" , $time ) ;
            $renderer->assign("timeFormat" , $timeFormat ) ;
            $renderer->assign("showFullNames" , $room->showFullNames() ) ;
            $renderer->assign("extConf" , $this->extConf ) ;

            // $messages[] = $message;
            $messages  .=   trim( $renderer->render() )  ;
        }
        return $messages ;
    }

    function _notifyme() {
        if (  GeneralUtility::inList($this->room->notifyme , $this->user['uid'] ) ) {
            $this->db->removeNotifymeToRoom($this->room , $this->user['uid'] ) ;
            return '<div class="tx-jvchat-cmd-success">' . $this->lang->getLL( 'command_notifyme_disabled')  . ' </div>' ;
        } else {
            $this->db->addNotifymeToRoom($this->room , $this->user['uid'] ) ;
            return '<div class="tx-jvchat-cmd-success">' . $this->lang->getLL( 'command_notifyme_enabled') . '</div>' ;
        }
    }


    function _email()
    {
        $entries = $this->db->getEntrieslastXseconds($this->room, 60 * 60 * 24, TRUE, TRUE);

        $members = $this->db->getFeUsersMayAccessRoom($this->room);
        return $this->sendEmails( $entries , $members , $this->room) ;

    }

    /**
     * @param array  $entries elements of type  \JV\Jvchat\Domain\Model\Entry
     * @param array $members type Users
     * @param \JV\Jvchat\Domain\Model\Room  $room
     * @return string
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
     */
    function sendEmails($entries , $members , $room , $sendall=false ) {

        $entryCount =  count($entries)  ;
        $server = trim( GeneralUtility::getIndpEnv('TYPO3_SITE_URL') , "/" ) ;

        if( is_array($members)) {
            $memberCount = count( $members) ;
        }
        if ( $entryCount && $memberCount > 0 ) {

            $params['message'] = "Neue Chat Nachrichten " . $server. " -> " .  $room->name;
            $params['message'] .= "\n" ;
            $params['message'] .= "Room: #" . $room->uid . " " . $room->name;
            $params['message'] .= "\n" ;
            $params['message'] .= "\n(smilies, images or links are only visible online)" ;


            $params['message'] .= "<hr>" . $this->getEntryTextForEmail( $entries , $room ) ;


            $params['message'] .=  " \n" . $server . "/index.php?id=" . $this->env['pid']
                . "&tx_jvchat_pi1[uid]=" .$this->room->uid . "&tx_jvchat_pi1[view]=chat ";
            $params['message'] .= " \n" ;
            $params['email_from'] = "noreply@tangomuenchen.de" ;
            $params['email_fromName'] = "TangoMünchen" ;
            $params['sendCCmail'] = false  ;

            /** @var \Velletti\Mailsignature\Service\SignatureService $mailService */
            $mailService = GeneralUtility::makeInstance("Velletti\\Mailsignature\\Service\\SignatureService");
            $signatur = $mailService->getSignature() ;
            $params['message'] .= $signatur['html'] ;
            $memberCount = 0 ;


            try{
                foreach ($members as $member ) {
                    if( $this->user['email'] != $member['email']   ||  $sendall  ) {
                        if( $member['email'] && GeneralUtility::validEmail($member['email'])) {
                            $params['user']['email'] = $member['email'] ;
                            $memberCount++ ;
                            $mailService->sentHTMLmailService($params) ;
                        }
                    }

                }
            }
            catch(\Exception $e) {
                // ''  ;
            }


            return '<div class="tx-jvchat-cmd-help">Email with ' . $entryCount. ' entries of last 24h was sent to ' .  $memberCount . ' members in this room<br></div>';
        } else {
            if ( $entryCount ) {
                return '<div class="tx-jvchat-cmd-error">No other Room Members Found</div>' ;
            } else {
                return '<div class="tx-jvchat-cmd-error">No Room Entries Found in last 24 hours. Please Write something before you send email</div>' ;
            }
        }
    }
	function _help($params) {

		$out = array();
		$out[] = $this->lang->getLL('command_title').'<br />';
		$out[] = $this->lang->getLL('command_header');
		foreach($this->commands as $name => $data) {
		    // the _email Command is only available in private Rooms
            if ( ! $this->room->isPrivate() && $name == "email") {
                continue;
            }

			if($data['hideinhelp'] || !$this->grantAccessToCommand($name, $this->env['user'])) {
                continue;
            }

			$title = '<div class="tx-jvchat-cmd-help-command-title"><span class="tx-jvchat-cmd-help-link" onClick="javascript:tx_jvchat_pi1_js_chat_instance.insertCommand(\'/'.$name.' \');">/'.$name.'</span></div>';
			$parameterList = '';
			$parameterDscr = '';

			if($data['parameters']) {
				foreach($data['parameters'] as $pname => $pdata) {
					$parameterList .= $pdata['required'] ? (' {'.$pname.'}') : (' ['.$pname.']');
					$parameterDscr .= ' - '.$pname.': '.$pdata['description'].'<br />' ;
				}
			}

			$commandDscr = $data['description'] ? ('<span class="tx-jvchat-cmd-help-command-descr">'.$data['description'].'</span>') : '';
			$parameterList = $parameterList ? '<span class="tx-jvchat-cmd-help-parameter-list">'.$parameterList.'</span>' : '';

			if($this->extConf['showParameterDescription']) {
				$parameterDscr = $parameterDscr ? ('<span class="tx-jvchat-cmd-help-parameter-descr">'.$parameterDscr.'</span>') : '';
			} else {
                $parameterDscr = '';
            }

			$out[] = '<div class="tx-jvchat-cmd-help-command">'.$title.$parameterList.$commandDscr.$parameterDscr.'</div>';
		}
		return '<div class="tx-jvchat-cmd-help">'.implode('', $out).'<br></div>';
	}
	
	function _smilies($params  )
		{

            $emoticons = $this->setup['settings']['emoticons'] ;
            $param = $params[1] ;
            $out ="";
            usort($emoticons, function ($item1, $item2) {
                return $item1['group'] <=> $item2['group'];
            });

            $group = false ;
            foreach($emoticons as $key => $icon ) {
                if( $icon['hideInHelp']) {
                    continue ;
                }
                if( strlen($param) > 0 ) {
                    if( strtolower( trim($param)) != trim( $icon['group'])) {
                        continue ;
                    }
                }

                if( !$group || trim( $icon['group']) != $group ) {
                    $group = $icon['group'] ;
                    $out .="<br ><br style=\"clear:both;\"><b onClick=\"javascript:chat_instance.insertCommand('/smilies " . $icon['group'] .  "');\">/smilies " . $icon['group']. "</b><br>";
                }
                $out .= '<span class="tx-jvchat-cmd-smiley">';
                    $out .= '<span class="tx-jvchat-cmd-smileys-image chatIconColor " onClick="javascript:chat_instance.insertCommand(\'' . $icon['code'] .  '\');" >'.LibUtility::formatMessageEmoji($icon).'</span>';
                    $out .= '<span class="tx-jvchat-cmd-smileys-text">'.$icon['code'].'</span>';
                $out .= '</span>';
                if(trim( $icon['group']) != $group ) {
                    $out = $out.'<br style="clear:both;" />';
                }
                $group = $icon['group'] ;

            }

            $out = '<div class="tx-jvchat-cmd-smileys">'.$out.'<br></div>';

            return $out;
		}
	
	function _roomlist($params) {
			$roomsArray = $this->db->getRooms();

			$htmlOut = '';
			foreach($roomsArray as $room) {
				if ($this->room->uid != $room->uid && !$room->closed && LibUtility::checkAccessToRoom($room, $this->user)) {
					$roomUsers = array();
					$roomUsers = $this->getUserlistOfRoom($room, true);
					$htmlOut.='<div class="tx-jvchat-cmd-roomlist-room"><div class="tx-jvchat-cmd-room-title">'.$room->name.' <span class="tx-jvchat-cmd-roomlist-usercount">('.count($roomUsers).' Users) <a href="javascript:openChatWindow('.$room->uid.');">'.$this->lang->getLL('command_invite_enter_room').'</a></span></div>';
					if (count($roomUsers) >0) {
						$htmlOut .='<ul class=tx-jvchat-cmd-roomlist-userlist">';
						foreach($roomUsers as $user)
									$htmlOut .= '<li class="tx-jvchat-cmd-roomlist-user">'.$user.'</li>';
						$htmlOut .= '</ul>';
					}
					$htmlOut .= '</div>';
				}
			}
			return $htmlOut;
	}
	
	function _who($params) {
		$userNames = $this->getUserinfoOfRoom($this->room,', ', ': ', true);
		$htmlOut = '<div class="tx-jvchat-cmd-who"><span class="tx-jvchat-cmd-who">'.count($userNames).' Users:</div>';
		$htmlOut .='<ul class=tx-jvchat-cmd-who-userlist">';
		foreach($userNames as $user)
			$htmlOut .= '<li class="tx-jvchat-cmd-who-user">'.$user.'</li>';
		$htmlOut .= '</ul></div>';
		return $htmlOut;
	}
	
	/**********************************************************************************************/
	// COMMANDS
	/**********************************************************************************************/	
	
	/**
	  * This is for /who
	  */
	function getUserinfoOfRoom($room, $userNamesGlue = ': ', $userNamesFieldGlue = ', ') {
		$userNamesGlue = LibUtility::getUserNamesGlue();
		$userNamesFieldGlue = LibUtility::getUserNamesFieldGlue();
		$users = $this->db->getFeUsersOfRoom($room);

		$userNames = array();
		foreach($users as $user) {

			if(!$user || !$user['username'])
				continue;

			$parts = $this->getUserInfo($room, $user, $userNamesFieldGlue);
			$userNames[] = implode($userNamesGlue, $parts);
		}
		return $userNames;
	}

	function getUserInfo($room, $user, $userNamesFieldGlue) {

		// user, moderator or expert
		$type = LibUtility::getUserTypeString($room, $user);

		$parts = array();
		//+++ w.f.12.07.11 htmlspecialchars instead of entities
		$parts['username'] = '<strong>'.htmlspecialchars($this->getUsername($user)).'</strong>';

		$details = $room->getDetailsField($type);
		foreach($details as $key) {
			if($room->showDetailOf($type,$key))
				$parts[] = $key.($userNamesFieldGlue.$user[$key]);
		}

		return $parts;
	}



	function _whois($params) {
		// get informations about self
		if(!$params[1]) {
			return implode(', ',$this->getUserInfo($this->room, $this->env['user'], ': '));
		}
		// get userid if username is givem
		else {
			//return '-'.$params[1].'-';
			$user = $this->getFeUserByInput($params[1]);
			if(!$user)
				return sprintf($this->lang->getLL('command_error_user_not_found'), $params[1]);
			return implode(', ',$this->getUserInfo($this->room, $user, ': '));
		}
	}
		
	function getFeUserByInput($input) {

		if(preg_match("/#([0-9]*)/", $input, $matches)) {
			return $this->db->getFeUser($matches[1]);
		}

		$input = str_replace('*','',$input);
		$input = str_replace('%','',$input);

		return $this->db->getFeUserByName($input);

	}


	function _msg($params) {
		$user = $this->getFeUserByInput($params[1]);

		if(!$user)
			return sprintf($this->lang->getLL('command_error_user_not_found'), $params[1]);

		unset($params[1]);
		$message = implode(' ',$params);
		$this->putMessage($message, $this->lastMessageId, $user['uid']);

	}

	function _ban($params) {
		$user = $this->getFeUserByInput($params[1]);
		if(!$user)
			return sprintf($this->lang->getLL('command_error_user_not_found'), $params[1]);

		// send a system notification message
		$systemmessage = sprintf($this->lang->getLL('command_ban_ok'), $user['username'], $this->user['username']);
		unset($params[1]);
		unset($params[2]);
		$systemmessage .= $params[3] ? (' '.sprintf($this->lang->getLL('command_ban_reason'), implode(' ',$params))) : '';
		$this->db->putMessage($this->env['room_id'], $systemmessage);

		sleep(5);

		// and quit
		$this->db->putMessage($this->room->uid, '/quit', $user['uid'], true);

		// and ban
		$this->db->banUser($this->room, $user['uid']);

		return 'OK';

	}

	function _kick($params) {
		$user = $this->getFeUserByInput($params[1]);
		if(!$user)
			return sprintf($this->lang->getLL('command_error_user_not_found'), $params[1]);

		$time = $params[2] ? $params[2] : $this->commands['kick']['parameters']['time']['default'];

		// send a system notification message
		$systemmessage = sprintf($this->lang->getLL('command_kick_ok'), $user['username'], $this->user['username'], $time);
		unset($params[1]);
		unset($params[2]);
		$systemmessage .= $params[3] ? (' '.sprintf($this->lang->getLL('command_kick_reason'), implode(' ',$params))) : '';
	// die anderen müsssen ja nich tsehen wenn jemand gekckt wird !
	//	$this->db->putMessage($this->env['room_id'], $systemmessage);

		sleep(5);

		// and quit
	//	$this->db->putMessage($this->room->uid, '/quit', $user['uid'], true, 0, $user['uid']);

		// and kick
		$this->db->kickUser($this->room->uid, $user['uid'], $time);

		return 'OK';
	}

	function _redeem($params) {
		$user = $this->getFeUserByInput($params[1]);
		if(!$user)
			return sprintf($this->lang->getLL('command_error_user_not_found'), $params[1]);

		$this->db->redeemUser($this->env['room_id'], $user['uid']);

		// send a system notification message
		$systemmessage = sprintf($this->lang->getLL('command_redeem_ok'), $user['username'], $this->user['username']);
		unset($params[1]);
		$systemmessage .= $params[2] ? (' '.sprintf($this->lang->getLL('command_redeem_reason'), implode(' ',$params))) : '';
		$this->db->putMessage($this->env['room_id'], $systemmessage);

		return 'OK';
	}

	function _quit($params) {

		// send a system notification message
		$systemmessage = sprintf($this->lang->getLL('command_quit_ok'), $this->user['username']);
		$systemmessage .= $params[1] ? (' '.sprintf($this->lang->getLL('command_quit_reason'), implode(' ',$params))) : '';
		$this->db->putMessage($this->env['room_id'], $systemmessage);
		sleep(2);

		// and quit
		//$this->db->putMessage($this->env['room_id'], '/quit', 0, $this->user, true, $this->user['uid']);
		//$this->putMessage('/quit', $this->lastMessageId, $user['uid']);
		$this->db->putMessage($this->env['room_id'], '/quit', $this->user['uid'], true);
		return 'OK';
	//	return '/quit';
	}
	
	function _restart($params) {

			// and quit
		//$this->db->putMessage($this->env['room_id'], '/quit', 0, $this->user, true, $this->user['uid']);
		//$this->putMessage('/quit', $this->lastMessageId, $user['uid']);
		$this->db->putMessage($this->env['room_id'], '/restart', $this->user['uid'], true);
		return 'OK';
	//	return '/quit';
	}

	function _stop($params) {

		//$this->db->putMessage($this->env['room_id'], '/quit', 0, $this->user, true, $this->user['uid']);
		//$this->putMessage('/quit', $this->lastMessageId, $user['uid']);
		$this->db->putMessage($this->env['room_id'], '/stop', $this->user['uid'], true);
		return 'OK';
	//	return '/quit';
	}

	function _makesession($params) {

		$startid = $params[1];
		$endid = $params[2];

		unset($params[1]);
		unset($params[2]);

		$name = implode(' ',$params);
		return $this->db->makesession($this->room->uid, $name, '', 0, $startid, $endid);
	}

	function _makeexpert($params) {
		$user = $this->getFeUserByInput($params[1]);
		if(!$user)
			return sprintf($this->lang->getLL('command_error_user_not_found'), $params[1]);

		$res = $this->db->makeExpert($this->room, $user['uid']);
		if($res) {
			$message = 'User '.$user['username'].' is now an expert. Initiated by '.$this->user['username'].'.';
			$this->db->putMessage($this->env['room_id'], $message);
			return 'OK';
		}
		else
			return '<span class="tx-jvchat-error">ERROR OR NOTHING TO DO</span>';


	}

	function _makeuser($params) {
		$user = $this->getFeUserByInput($params[1]);
		if(!$user)
			return sprintf($this->lang->getLL('command_error_user_not_found'), $params[1]);

		$res = $this->db->makeUser($this->room, $user['uid']);
		if($res) {
			$message = 'User '.$user['username'].' is set to a normal user. Initiated by '.$this->user['username'].'.';
			$this->db->putMessage($this->env['room_id'], $message);
			return 'OK';
		}
		else
			return '<span class="tx-jvchat-error">ERROR OR NOTHING TO DO</span>';

	}

	function _cleanuproom() {
		$res = $this->db->cleanUpRoom($this->room);
		return $res ? ($res.' Entries deleted') : 'NOTHING DELETED';
	}
	
	function _cleanupall() {
		$res = $this->db->cleanUpAllRooms();
		return $res ? ($res.' Entries deleted') : 'NOTHING DELETED';
	}
	
	function _togglestatus($params) {

		if($params[2]) {
			$user = $this->getFeUserByInput($params[1]);
			if(!$user)
				return sprintf($this->lang->getLL('command_error_user_not_found'), $params[1]);

			$res = $this->db->setUserStatus($this->room, $user, $params[2]);
		}
		else {
			$res = $this->db->setUserStatus($this->room, $this->user, $params[1]);
		}
		return $res ? 'TOGGLED' : '<span class="tx-jvchat-error">ERROR</span>';
	}

	function _toggleroomstatus($params) {
		$res = $this->db->setRoomStatus($this->room, $params[1]);
		return $res ? ('TOGGLED: '.$params[1].'='.$res) : '<span class="tx-jvchat-error">ERROR</span>';
	}

	function _setmessagestyle($params) {
		return $this->db->setMessageStyle($this->user, $params[1]) ? 'Style '.$params[1].'.' : '<span class="tx-jvchat-error">ERROR</span>';
	}

	function _newroom($params , $returnRoom = false , $members = 0 ) {

		$username = $this->getUsername();

		if(!$params[1]) {
			$name = sprintf($this->lang->getLL('command_newroom_room_default_title'), implode(' ',$params) . " & " . $username);
		}
		else
			$name = implode(' ',$params) .  " & " . $username ;

        /** @var \JV\Jvchat\Domain\Model\Room $newRoom */
        $newRoom = GeneralUtility::makeInstance('JV\\Jvchat\\Domain\\Model\\Room');
		$newRoom->pid = $this->room->pid;


		$newRoom->name = $this->db->getUniqueRoomName($name);
		$newRoom->superusergroup = $this->room->superusergroup;
		$newRoom->description = sprintf($this->lang->getLL('command_newroom_room_default_description'), $username);
		$newRoom->welcomemessage = $this->room->welcomemessage;
		$newRoom->owner = $this->user['uid'];
		$newRoom->moderators = $this->user['uid'];
		$newRoom->private = true;
		$newRoom->fe_group = 1;
		$newRoom->closed = 0;
		$newRoom->experts = FALSE;
		$newRoom->groupaccess = "-2";
		$newRoom->page = $this->room->page;
		$newRoom->mode = $this->room->mode;
		$newRoom->bannedusers = $this->room->bannedusers;
		$newRoom->members = $members ;
		$newRoom->notifyme = $this->user['uid']  ;
		$newRoom->image = '';
		$newRoom->maxusercount = $this->room->maxusercount;

		$newRoom->showfullnames = $this->room->showfullnames;
		$newRoom->showuserinfo_users = $this->room->showuserinfo_users;
		$newRoom->showuserinfo_superusers = $this->room->showuserinfo_superusers;
		$newRoom->showuserinfo_moderators = $this->room->showuserinfo_moderators;
		$newRoom->showuserinfo_experts = $this->room->showuserinfo_experts;
		$newRoom->hidden = $this->extConf['hidePrivateRooms'];

		$roomId = $this->db->createNewRoom($newRoom);
		if ( $roomId > 0 ) {
		    // this is done when user really enteres !
			// $this->db->updateUserInRoom($roomId, $this->user['uid']);

			$msg = sprintf($this->lang->getLL('command_newroom_ok'), $newRoom->name) ;
			$msg .= ' <br/><a href="javascript:openChatWindow('.$roomId.');" onClick="javascript:openChatWindow('.$roomId.'); return false;">'
				.$this->lang->getLL('command_invite_enter_room') . " (id: " . $roomId  . ")" .'</a>' ;
			// $msg .= '<script language="JavaScript" type="text/javascript">openChatWindow('.$roomId. ');</script>';

            if ( $returnRoom ) {
                $newRoom->uid =$roomId ;
                if ( $members ) {
                    $this->db->putMessage($this->room->uid, $msg, 0, $members , true, 0, $this->user['uid'] );
                } else {
                    $this->db->putMessage($this->room->uid, $msg, 0, $this->user['uid'], true, 0, 0 );

                }

                return $newRoom ;
            }

		} else {
			$msg = "Error creating room." ;
		}
		// echo "<br>Line: " . __LINE__ . " : " . " File: " . __FILE__ . '<br>$roomId : ' . var_export($roomId, TRUE) . "<hr>";



		return $msg;
	}
	
	function _invite($params) {

		$user = $this->getFeUserByInput($params[1]);

		if(!$user)
			return sprintf($this->lang->getLL('command_error_user_not_found'), $params[1]);

		return $this->_do_invite($user, $this->room , $params);
	}

    function _talkTo($params) {
	    $user = $this->getFeUserByInput($params[1]);

        if(!$user) {
            return sprintf($this->lang->getLL('command_error_user_not_found'), $params[1]);
        }
        if(!$this->user) {
            return sprintf($this->lang->getLL('command_error_user_not_found'), $this->user['uid']);
        }
        $debug[] = "Ich: " . $this->user['uid'] . " anderer: " . $user['uid'] ;
        $room = $this->db->getLatestPrivateRoomOfUsers($this->user['uid'] , $user['uid']) ;
        $debug[] = "Room: " . $room->uid ;

        if ( !$room) {
            $room = $this->_newroom($params , true, $user['uid']) ;
            $debug[] = $room->uid ;
        }
        return  $this->_do_invite($user, $room , $params);

    }
	
	function _do_invite($user, $room , $params = null ) {
		$this->db->addMemberToRoom($room, $user['uid']);
        $enterRoom = ' <a href="javascript:openChatWindow('.$room->uid.');">'.$this->lang->getLL('command_invite_enter_room').'</a>' ;
        $this->db->putMessage($room->uid, $enterRoom, 0, $this->user, true, 0, $user['uid']);
		if($params[2]) {
			unset($params[1]);
			$msg = implode(' ',$params);
		}
		else {
			$msg = sprintf($this->lang->getLL('command_invite_default_message'), $this->getUsername(), $this->getUsername($user) , $room->name);
		}

		// $msg = $msg. "Uid User: " . $this->user['uid'] . " toUser: " . $user['uid'] ;
		$msg = $msg. $enterRoom ;

		$rooms = $this->db->getRoomsOfUser($user['uid'] , false );

		$roomNums = 0 ;
		if(count($rooms) > 0) {
            // send private system messages to all rooms
            /** @var \JV\Jvchat\Domain\Model\Room $room */
            foreach($rooms as $otherroom) {
                if(! $otherroom->isPrivate()  ) {
                    $roomNums ++ ;
                    $this->db->putMessage($otherroom->uid, $msg, 0, $this->user , false, 0,  $user['uid'] );
                }
            }
            return "#" . $room->uid . " -> " . sprintf($this->lang->getLL('command_invite_enter_room_ok'), $this->getUsername($user), count($rooms)) . $enterRoom ;

        } else {
		    return "Invited user is Offline. " . $enterRoom ;
        }


	}

	function _recentinvite($params) {
		$user = $this->getFeUserByInput($params[1]);

		if(!$user)
			return sprintf($this->lang->getLL('command_error_user_not_found'), $params[1]);

		$rooms = $this->db->getRoomsOfUserAsOwner($this->user['uid']);

		if(count($rooms) == 0)
			return 'No room found';

		return $this->_do_invite($user, $rooms[count($rooms)-1] , $params);

	}
	


}

<?php
namespace JVelletti\Jvchat\Utility;

use JVelletti\Jvchat\Domain\Model\Room;
use JVelletti\Jvchat\Domain\Repository\DbRepository;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Extbase\MVC\Request;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;

class LibUtility {

    static function getUserNamesGlue() {
		return '<user>';
	}

    static function getUserNamesFieldGlue() {
		return ': ';
	}

    static function getMessagesGlue() {
		return '<msg>';
	}

    static function getIdGlue() {
		return '<id>';
	}

    /**
     * @param Room $room
     * @param array $user
     * @return bool
     */
    static function checkAccessToRoom($room, $user) {

		if(!$user)
			return false;

		//is banned?
		//if(self::isBanned($room, $user['uid']))
		//	return true;

		//\TYPO3\CMS\Core\Utility\GeneralUtility::debug($room);

		// Are there any restrictions? if not return true
		if((!$room->groupaccess) && (!$room->fe_group))
			return true;

		// is superuser?
		if(self::isSuperuser($room, $user))
			return true;

		// is moderator?
		if(self::isModerator($room, $user['uid']))
			return true;


		// is closed?
		if($room->closed && !self::isModerator($room, $user['uid']))
			return false;

		// if no usergroup is assigned to the room - allow all users
		if(!$room->groupaccess)
			return true;

		// Show at any login ??
        if( $room->groupaccess == "-2" && $room->isPrivate() ) {

            if(GeneralUtility::inList($room->members, $user['uid'])) {
                return true;
            }
        } else {
            // is user in usergroup?
            $groupsOfUser = GeneralUtility::intExplode(',', $user['usergroup']);
            foreach($groupsOfUser as $g) {

                if(GeneralUtility::inList($room->groupaccess, $g))
                    return true;
                if($g === $room->fe_group)
                    return true;
            }
        }




		// restricted
		return false;
	}

    static function isSuperuser($room, $user) {

		if(!$user)
			return false;

		// is user in usergroup of superusers?
		$groupsOfUser = GeneralUtility::intExplode(',', $user['usergroup']);
		foreach($groupsOfUser as $g) {
			if(GeneralUtility::inList($room->superusergroup, $g)) {
				return true;
			}
		}

		return false;

	}

    static function isModerator($room, $userid) {

		if(!$userid)
			return false;

		// is moderator?
		if(GeneralUtility::inList($room->moderators, $userid))
			return true;

		return false;
	}

    static function isBanned($room, $userid) {

		if(!$userid)
			return false;

		// is banned?
		if(GeneralUtility::inList($room->bannedusers, $userid)) {
			return true;
		}

		return false;
	}

	static function isMember($room, $userid) {

		if(!$userid)
			return false;

		// is member?
		if(GeneralUtility::inList($room->members, $userid))
			return true;

		// is owner?
		if(self::isOwner($room, $userid))
			return true;

		return false;
	}

    static function isOwner($room, $userid) {
		return ($room->owner == $userid);
	}

    static function getUserTypeString($room, $user) {
		if(self::isSuperuser($room, $user))
			return 'superuser';
		if(self::isOwner($room, $user['uid']))
			return 'owner';
		if(self::isModerator($room, $user['uid']))
			return 'moderator';
		if(self::isExpert($room, $user['uid']))
			return 'expert';
		return 'user';
	}

    static function isExpert($room, $userid) {

		if(!$userid)
			return false;

		// is expert?
		if(GeneralUtility::inList($room->experts, $userid))
			return true;

		return false;
	}

    static function getUsernames($feusers, $name = false, $glue = ',&nbsp;', $cObj = null, $stdWrap = null) {
		$userNames = [];
		foreach($feusers as $user) {

			if($name)
				$userName = $user['name'] ?: $user['username'];
			else
				$userName = $user['username'];

			if(!$userName)
				continue;

			if($cObj && $stdWrap) {
				$cObj->data = array_merge($cObj->data, $user);
				$userNames[] = $cObj->stdWrap($userName, $stdWrap);
			}
			else
				$userNames[] = $userName;

		}
		return implode($glue, $userNames);
	}

    static function isSystem($userId) {
		// if no user isset it is a system message
		return ($userId ? false : true);
	}

    static function get_links($body) {
		//Pattern building across multiple lines to avoid page distortion.
		$pattern = "/((http\:\/\/)?www\.[a-z0-9\.\:\-\_\/\~\@\%]*)/i";
		//End pattern building.
		preg_match_all ($pattern, (string) $body, $matches);
		return (is_array($matches)) ? $matches:FALSE;
	}

    static function formatMessage($text, $emoticons , $enableEmoticons = true ) {

        // removing HTML Tags will  brake Links from System !! this should be done befor storing to database ..
        //   $text = strip_tags( $text) ;
        // we start with double size 2x as messages as using smaller Fontsize!
        $faSize = "fa-2x" ;

        if( strlen( (string) $text ) < 30 ) {
            $faSize = "fa-3x" ;
        }
        if( strlen( (string) $text ) < 20 ) {
            $faSize = "fa-4x" ;
        }


		// Check if emoticons are disabled
		if ($enableEmoticons) {
            if ( is_array($emoticons)) {
                // Replace all emoticon codes with images
                foreach($emoticons as $key => $emoji ) {
                    if( trim((string) $text) == trim((string) $emoji['code'])) {
                        $faSize = "fa-5x" ;
                    }
                    $emoji['html'] = str_replace("fa-lg", $faSize , (string) $emoji['html']);

                    $html = self::formatMessageEmoji($emoji) ;
                    $text = str_replace($emoji['code'], $html , (string) $text);
                }
            }

        }
        // replace line breaks with <br>
        $text = str_replace(chr(10), '<br />', (string) $text);

        $text = preg_replace('/\[b\](.*?)\[\/b\]/i', '<span class="tx-jvchat-bold">\1</span>', $text);
        $text = preg_replace('/\[u\](.*?)\[\/u\]/i', '<span class="tx-jvchat-underlined">\1</span>', $text);
        $text = preg_replace('/\[i\](.*?)\[\/i\]/i', '<span class="tx-jvchat-italic">\1</span>', $text);
        $text = preg_replace('/\[s\](.*?)\[\/s\]/i', '<span class="tx-jvchat-stroke">\1</span>', $text);
        $text = preg_replace('/(\*.*?\*)/i', '<span class="tx-jvchat-bold">\1</span>', $text);

        // make https links clickable
        $text = preg_replace('/((http|https)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?)/i', '<a href="\1" target="_blank">\1</a>', $text);



        // j.v. Link to image
        $text = preg_replace('/\[img=(.*?)\](.*?)\[\/img\]/i', '<img title="click me" alt="click me" class="tx-jvchat-img" src="\2" onclick="tx_jvchat_pi1_js_chat_instance.showChatImg(\'\1\');" />', $text);

        return $text;

	}
    static function formatMessageEmoji($code ) {
        if( isset($code['notFontAwesome']) && $code['notFontAwesome'] ) {
            return '<img src="' . PathUtility::getPublicResourceWebPath($code['html']) . '" alt="' . $code['code'] . '"/>' ;

        } else {
            return '<span class="chatIconColor"><i class="' . $code['html'] . '"> </i></span>' ;
        }
    }

    static function unicode_encode($string) {
		$chars = [' ' => '&#32;', '!'	=> '&#33;', '\"'=> '&#34;', '\#'=> '&#35;', '\$'=> '&#36;', '\%'=> '&#37;', '\&'=> '&#38;', '\''=> '&#39;', '('	=> '&#40;', ')'	=> '&#41;', '*'	=> '&#42;', '+'	=> '&#43;', ','	=> '&#44;', '-'	=> '&#45;', '.'	=> '&#46;', '\/'=> '&#47;', '0'	=> '&#48;', '1'	=> '&#49;', '2'	=> '&#50;', '3'	=> '&#51;', '4'	=> '&#52;', '5'	=> '&#53;', '6'	=> '&#54;', '7'	=> '&#55;', '8'	=> '&#56;', '9'	=> '&#57;', ':'	=> '&#58;', ';'	=> '&#59;', '<'	=> '&#60;', '='	=> '&#61;', '>'	=> '&#62;', '?'	=> '&#63;', '@'	=> '&#64;', 'A'	=> '&#65;', 'B'	=> '&#66;', 'C'	=> '&#67;', 'D'	=> '&#68;', 'E'	=> '&#69;', 'F'	=> '&#70;', 'G'	=> '&#71;', 'H'	=> '&#72;', 'I'	=> '&#73;', 'J'	=> '&#74;', 'K'	=> '&#75;', 'L'	=> '&#76;', 'M'	=> '&#77;', 'N'	=> '&#78;', 'O'	=> '&#79;', 'P'	=> '&#80;', 'Q'	=> '&#81;', 'R'	=> '&#82;', 'S'	=> '&#83;', 'T'	=> '&#84;', 'U'	=> '&#85;', 'V'	=> '&#86;', 'W'	=> '&#87;', 'X'	=> '&#88;', 'Y'	=> '&#89;', 'Z'	=> '&#90;', '['	=> '&#91;', '\\'=> '&#92;', ']'	=> '&#93;', '^'	=> '&#94;', '_'	=> '&#95;', '\`'=> '&#96;', 'a'	=> '&#97;', 'b'	=> '&#98;', 'c'	=> '&#99;', 'd'	=> '&#100;', 'e'	=> '&#101;', 'f'	=> '&#102;', 'g'	=> '&#103;', 'h'	=> '&#104;', 'i'	=> '&#105;', 'j'	=> '&#106;', 'k'	=> '&#107;', 'l'	=> '&#108;', 'm'	=> '&#109;', 'n'	=> '&#110;', 'o'	=> '&#111;', 'p'	=> '&#112;', 'q'	=> '&#113;', 'r'	=> '&#114;', 's'	=> '&#115;', 't'	=> '&#116;', 'u'	=> '&#117;', 'v'	=> '&#118;', 'w'	=> '&#119;', 'x'	=> '&#120;', 'y'	=> '&#121;', 'z'	=> '&#122;', '{'	=> '&#123;', '|'	=> '&#124;', '}'	=> '&#125;', '~'	=> '&#126;'];

		$theValue = '';
		for($i = 0; $i < strlen((string) $string);$i++) {
			$theValue .= $chars[$string[$i]] ?: $string[$i];
		}

		return $theValue;

	}
    static function getSettings() {
        return self::getSetUp( self::getPid() , self::getBasePath() ) ;
    }


    static function getEmoticonsForChatRoom(?array $settings = null ) {
		$setup = ($settings ?? self::getSettings() );

		if( ! is_array($setup) ) { return ''   ;}
		if( ! is_array($setup["settings"]) ) { return '' ;}
		if( ! is_array($setup["settings"]["emoticons"]) ) { return '' ;}
        $emoticons = $setup["settings"]["emoticons"] ;
        $emoticonBtnClass = $setup["settings"]["emoticonBtnClass"] ;
		$out = "";
		$out2 = "";
		foreach($emoticons as $key => $emoji) {
            if ( isset($emoji['notFontAwesome']) && $emoji['notFontAwesome']) {
                $code = '<span class="'. $emoticonBtnClass .  '" onClick="setValueToInput(\''.$emoji['code'].'\');" alt="emoji-' . $key . '" title="'.self::unicode_encode($emoji['code']).'">'.
                            '<img src="' . PathUtility::getPublicResourceWebPath($emoji['html']) . '" alt="emoji-' . $key . '"/>' .
                        '</span>';
            } else {
                $code = '<span class="'. $emoticonBtnClass. '"><span class="' . $emoji['html'] . '" onClick="setValueToInput(\''.$emoji['code'].'\');" alt="emoji-' . $key . '" title="'.self::unicode_encode($emoji['code']).'"> </span></span>';
            }

            if ( isset($emoji['inMenu']) && $emoji['inMenu']) {
                $out .= $code ;
            }
            if ( isset($emoji['inMenu2']) &&  $emoji['inMenu2']) {
                $out2 .= $code ;
            }

		}

		return "<span class=\"tx-jvchat-emoticons tx-jvchat-emoticons1\">" . $out . "</span><span class=\"tx-jvchat-emoticons tx-jvchat-emoticons2\">" . $out2 . "</span>";
	}

    static function getExtConf() {
       return GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('jvchat');
	}
    static function getSnippet($room, $user , $thisUser) {
        $setup = self::getSetUp();
        $extConf = self::getExtConf() ;

        $renderer = self::getRenderer($setup , "GetUsers" , "html" )  ;
        $renderer->assign("showFullNames" , $room->showFullNames() ) ;
        if( $setup['settings']['userlist']['avatar']['useNemUserImgPath']) {
            $setup['settings']['userlist']['avatar']['nemUserImgPath']  = 'uploads/tx_feusers_img/' . $subPath = substr( "0000" . intval( round( $user['uid'] / 1000 , 0 )) , -4 , 4 ) . "/"  ;
        }

        $renderer->assign("thisUser" , $thisUser ) ;
        $renderer->assign("extConf" , $extConf ) ;
        $renderer->assign("settings" , $setup['settings'] ) ;

        $renderer->assign("user" , $user ) ;
        return $renderer->render() ;

    }

    static function getDataString(array $user, Room $room , array $extConf , DbRepository $db) : string
    {

        /*
        <div class="p-2 tx-jvchat-chat-intro template-pi1" id="tx-jvchat-config"
        data-roomid="1"
        data-userid="353"
        data-username="jvelletti"
        data-lang="de"
        data-initialid="517627"
        data-usernameglue="&lt;user&gt;"
        data-messagesglue="&lt;msg&gt;"
        data-usernamesfieldglue=": "
        data-idglue="&lt;id&gt;"
        data-newwindowurl="https://connect.allplan.com//de/forum/chat.html?no_cache=1"
        data-scripturl="https://connect.allplan.com/index.php?id=862&amp;eIDMW=tx_jvchat_pi1"
        data-leaveurl="/de/forum/chat.html?no_cache=1"
        data-showtime="true"
        data-showemoticons="true"
        data-showstyles="false"
        data-allowprivaterooms="true"
        data-privateroomcode="&lt;span class='btn btn-default tx-jvchat-pr-link'&gt;&lt;span class='fas fa-user-plus'&gt;&lt;/span&gt;&lt;/span&gt;"
        data-allowprivatemessages="true"
        data-privatemsgcode="&lt;span class='btn btn-default tx-jvchat-pm-link'&gt;&lt;span class='fa fa-comment-alt'&gt;&lt;/span&gt;&lt;/span&gt;"
        data-ispopup="false"
        data-popupparams="width=600,height=760,status=1,resizable=1,location=1"
        data-talktonewroomname="Privater Raum mit %s" data-allowtooltipoffset-x="20"
        data-allowtooltipoffset-y="10"
        data-refreshmessagestime="10000"
        data-refreshuserlisttime="60000"
        data-pid="862">
        */

        $pid = self::getPid()  ;
        
        $dataString  = ' data-roomid="' . $room->uid  . '"' ;
        $dataString .= ' data-userid="' . $user['uid']  . '"' ;
        $userName = $user['username']  ;
        if( $extConf['usernameField1']) {
            $userName = $user[$extConf['usernameField1']]  ;
        }
        if( $room->showFullNames() ) {
            if( $extConf['usernameField1']) {
                $userName = $user[$extConf['usernameField1']]  ;
            }
            if( $extConf['usernameField2']) {
                $userName .= "_" .$user[$extConf['usernameField2']]  ;

            }
        }

        $dataString .= ' data-username="' . $userName . '"' ;


        $language = (isset($params['L']) ? (int)$params['L'] : self::getlanguage());

        $dataString .= ' data-lang="' . self::getlanguageCode($pid , $language )  . '"' ;

        $time = $db->getTime()-($extConf['initChatWithMessagesBefore']*60);

        $dataString .= ' data-initialid="' . $db->getLatestEntryId($room, $time)  . '"' ;


        // seperators for splits
        $dataString .= ' data-usernameglue="'       . LibUtility::getUserNamesGlue()  . '"' ;
        $dataString .= ' data-messagesglue="'       . LibUtility::getMessagesGlue()  . '"' ;
        $dataString .= ' data-usernamesfieldglue="' . LibUtility::getUserNamesFieldGlue()  . '"' ;
        $dataString .= ' data-idglue="'             . LibUtility::getIdGlue()  . '"' ;


        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $site = $siteFinder->getSiteByPageId($pid);
        $router = $site->getRouter();
        $BaseUrl = $router->generateUri(
            $pid,
            [
                '_Language' => $language
            ],
        )->getPath();
        $chatScript = $BaseUrl . '?eIDMW=tx_jvchat_pi1&id=' . $pid  ;

        /*
        if($extConf['chatwindow']) {
            $newwindowurl = $extConf['chatwindow'] ? $this->pi_linkTP_keepPIvars_url(array(), 0, true, $extConf['chatwindow']) : $marker['LEAVEURL'];
        } else {
            $newwindowurl= ($this->pi_linkTP_keepPIvars_url(array(), 0, true)).'&type='.($extConf['chatwindow.']['typeNum']);
        }
        if ( substr( $newwindowurl , 0 , 4 ) != "http") {
            $newwindowurl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL'). $newwindowurl ;
        }
        */


     //   $dataString .= ' data-newwindowurl="' . $newwindowurl . '"';
        $dataString .= ' data-scripturl="' . $chatScript  . '"' ;
        $dataString .= ' data-newwindowurl="?test"' ;
        // $dataString .= ' data-leaveurl="' . $this->pi_linkTP_keepPIvars_url(array(), 0, true)  . '"' ;

        if( $extConf['showTime'] ) {
            $dataString .= ' data-showtime="true"' ;
        } else {
            $dataString .= ' data-showtime="false"' ;
        }
        if( $extConf['showEmoticons'] ) {
            $dataString .= ' data-showemoticons="true"' ;
        } else {
            $dataString .= ' data-showemoticons="false"' ;
        }
        if( $extConf['showStyles'] ) {
            $dataString .= ' data-showstyles="true"' ;
        } else {
            $dataString .= ' data-showstyles="false"' ;
        }

        if( $db->extCONF['allowPrivateRooms'] ) {
            $dataString .= ' data-allowPrivateRooms="true"' ;
            $dataString .= ' data-privateroomcode="'. $extConf['privateRoomCode'] . '"' ;
        } else {
            $dataString .= ' data-allowPrivateRooms="false"' ;
        }
        if( $db->extCONF['allowPrivateMessages'] ) {
            $dataString .= ' data-allowPrivateMessages="true"' ;
            $dataString .= ' data-privatemsgcode="'. $extConf['privateMsgCode'] . '"' ;
        } else {
            $dataString .= ' data-allowPrivateMessages="false"' ;
        }
        if( $$extConf['popup']  ) {
            $dataString .= ' data-ispopup="true"' ;
        } else {
            $dataString .= ' data-ispopup="false"' ;
        }
        $dataString .= ' data-popupparams="' . $extConf['chatPopupJSWindowParams']  . '"' ;
      //  $dataString .= ' data-talkToNewRoomName="' . $this->slashJS($this->pi_getLL('talktoroomname'))  . '"' ;

        $tooltipOffsetXY = GeneralUtility::trimExplode(',', ($extConf['tooltipOffsetXY'] ?? '20,10'));
        $dataString .= ' data-allowtooltipoffset-x="' . $tooltipOffsetXY[0] . '"' ;
        $dataString .= ' data-allowtooltipoffset-y="' . $tooltipOffsetXY[1] . '"' ;
        $dataString .= ' data-refreshMessagesTime="' . ( isset($extConf['refreshMessagesTime']) ? $extConf['refreshMessagesTime']*1000 : 10000 ) . '"' ;
        $dataString .= ' data-refreshUserListTime="' . ( isset($extConf['refreshUserListTime']) ? $extConf['refreshUserListTime']*1000 : 60000 )  . '"' ;
        $dataString .= ' data-pid="' . $pid  . '"' ;


        if( 1==2) {
            $dataString .= ' data-debug="true"' ;
        }
        return $dataString ;
    }

    static function getPid()
    {
        $request = self::getRequest() ;
        $pageArguments = $request->getAttribute('routing');
        return ( $pageArguments ? $pageArguments->getPageId() : 0) ;
    }

    static function getlanguage()
    {
        $languageAspect = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class)->getAspect('language') ;
        return ( $languageAspect ? $languageAspect->getId() : 0) ;
    }
    static function getlanguageCode($pid , $langId ): string
    {

        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $site = $siteFinder->getSiteByPageId($pid);

        $context = GeneralUtility::makeInstance(Context::class);

        $language = $site->getLanguageById($langId);
        if ( !$language ) {
            return "en" ;
        }
        return ($language->getLocale() ?? "en" );
    }

    static function getRequest(): ServerRequestInterface
    {
        if ( $GLOBALS['TYPO3_REQUEST'] ) {
            return $GLOBALS['TYPO3_REQUEST'] ;
        }

        return (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
    }
    static function getBasePath()
    {
        $urlArray = parse_url( GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL')) ;
        if ( isset( $GLOBALS['TYPO3_CONF_VARS']['HTTP']['auth'] ) && is_array( $GLOBALS['TYPO3_CONF_VARS']['HTTP']['auth'] )) {
            $urlArray['host'] = $GLOBALS['TYPO3_CONF_VARS']['HTTP']['auth'][0] . ":" . $GLOBALS['TYPO3_CONF_VARS']['HTTP']['auth'][1] . "@" . $urlArray['host'] ;
        }
        return "https://" . $urlArray['host'] . "/" . $urlArray['path'] .   "?tx_jvtyposcript=tx_jvchat_pi1" ;

    }
    static function getTypoScriptPath()
    {
        $urlArray = parse_url( GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL')) ;
        if( str_ends_with( $urlArray['path'] , "index.php")) {

            $params = explode("&", $urlArray['query']);
            $pid = (isset($params['id']) ? (int)$params['id'] : self::getPid());
            $language = (isset($params['L']) ? (int)$params['L'] : self::getlanguage());

            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            $site = $siteFinder->getSiteByPageId($pid);
            $router = $site->getRouter();
            $urlArray['path'] = $router->generateUri(
                $pid,
                [
                    '_Language' => $language
                ],
            )->getPath();
        }

        return "https://" . $urlArray['host'] . "/" . $urlArray['path'] .   "?tx_jvtyposcript=tx_jvchat_pi1" ;

    }

    static function getShowRoomUrl($roomId , $pid = 0 , $language = 0 )
    {
        $pid = ( $pid ? $pid : self::getPid() ) ;
        $language = ( $language ? $language : self::getlanguage() ) ;
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $site = $siteFinder->getSiteByPageId($pid);
        $router = $site->getRouter();

        $url = $router->generateUri(
            $pid,
            [
                'tx_jvchat_pi1' => ['uid'=> $roomId , 'view'=>'chat'],
                '_Language' => $language
            ],
        )->getPath();
        return $url ;
    }

    static function getSetUp( $pid = 0 , $basePath= ''  ) {
        if ( !$basePath ) {
            $basePath = self::getBasePath() ;
        }
        $ts = TyposcriptUtility::loadTypoScriptviaCurl( $basePath );
        if ( isset($ts['tx_jvchat_pi1'] )) {
            return $ts['tx_jvchat_pi1'] ;
        }
        if ( is_array($ts) && count($ts) > 0 ) {
            return $ts ;
        } else {
            $request = self::getRequest() ;

            return TyposcriptUtility::loadTypoScriptFromRequest( $request  , 'tx_jvchat_pi1' , false , $pid );
        }
    }

    static function trimImplode($glue, $array) {
		foreach($array as $key => $value) {
			if(!$value)
				unset($array[$key]);
		}
		return implode($glue, $array);
	}

	/**
	 * RFC1738 compliant replacement to PHP's rawurldecode - which actually works with unicode (using utf-8 encoding)
	 * @author Ronen Botzer
	 * @param string $source
	 * @param boolean $source
	 * @return string unicode safe rawurldecoded string
	 * @access public
	 */
    static function utf8RawUrlDecode ($source, $utf8) {
		$decodedStr = '';
		$pos = 0;
		$len = strlen ($source);

		while ($pos < $len) {
			$charAt = substr ($source, $pos, 1);
			if ($charAt == '%') {
				$pos++;
				$charAt = substr ($source, $pos, 1);
				if ($charAt == 'u') {
					// we got a unicode character
					$pos++;
					$unicodeHexVal = substr ($source, $pos, 4);
					$unicode = hexdec ($unicodeHexVal);
					$entity = "&#". $unicode . ';';
					$decodedStr .= utf8_encode ($entity);
					$pos += 4;
				}
				else {
					// we have an escaped ascii character
					$hexVal = substr ($source, $pos, 2);
					if($utf8)
						$decodedStr .= utf8_encode(chr (hexdec ($hexVal)));
					else
						$decodedStr .= chr (hexdec ($hexVal));
					$pos += 2;
				}
			}
			else {
				$decodedStr .= $charAt;
				$pos++;
			}
		}

		return $decodedStr;
	}

    /**
      * @param array $settings
      * @param string $templateDefault
      * @param string $format
      * @throws InvalidExtensionNameException if the extension name is not valid
      * @return StandaloneView
      */
     static function getRenderer( $settings= []  , $templateDefault='DisplayChatRoom' , $format="html") {


        /** @var StandaloneView $renderer */
        $renderer = new StandaloneView ;
        if( isset($settings['tx_jvchat_pi1']) && is_array( $settings['tx_jvchat_pi1'] ) ) {
            $settings = $settings['tx_jvchat_pi1'] ;
        }
        $layoutPaths = $settings['view']['layoutRootPaths'] ;

        if(!$layoutPaths || (is_countable($layoutPaths) ? count($layoutPaths) : 0) < 1) {
            $layoutPaths = [0 => "EXT:jvchat/Resources/Private/Layouts/"] ;
        }
        $template = $settings['view']['template'] ?? false ;
        if(!$template) {
            $template = $templateDefault ;
        }

        $templatePaths = $settings['view']['templateRootPaths'] ?? false ;
        if(!$templatePaths) {
            $templatePaths = [0 => "EXT:jvchat/Resources/Private/Templates/"] ;
        }
        $partialPaths = $settings['view']['partialRootPaths'] ?? false ;
        if(!$partialPaths) {
            $partialPaths = [0 => "EXT:jvchat/Resources/Private/Partials/"] ;
        }
        $renderer->getRenderingContext()->getTemplatePaths()->setLayoutRootPaths($layoutPaths);
        $renderer->getRenderingContext()->getTemplatePaths()->setTemplateRootPaths($templatePaths);
        $renderer->getRenderingContext()->getTemplatePaths()->setPartialRootPaths($partialPaths);

        $renderer->setFormat($format) ;

        $renderer->getRenderingContext()->setControllerAction($template);
         $templatePath =   GeneralUtility::getFileAbsFileName( $templatePaths[0]."Pi1/" . $template . "." . $format );
        // /var/www/html/vendor/jv/jvchat/Resources/Private/Templates/Bootstrap4/Pi1/DisplayRooms
        $renderer->getRenderingContext()->getTemplatePaths()->setTemplatePathAndFilename($templatePath);
        return $renderer ;
    }

}

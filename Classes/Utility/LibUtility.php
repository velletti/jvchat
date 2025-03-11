<?php
namespace JVelletti\Jvchat\Utility;

use JVelletti\Jvchat\Domain\Model\Room;
use TYPO3\CMS\Core\Http\ServerRequest;
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



        // j.v. Link to image
        $text = preg_replace('/\[img=(.*?)\](.*?)\[\/img\]/i', '<img title="click me" alt="click me" class="tx-jvchat-img" src="\2" onclick="tx_jvchat_pi1_js_chat_instance.showChatImg(\'\1\');" />', $text);

        return $text;

	}
    static function formatMessageEmoji($code ) {
        if( isset($code['notFontAwesome']) && $code['notFontAwesome'] ) {
            return $code['html'] ;
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


    static function getEmoticonsForChatRoom() {
		$setup = self::getSetUp();

		if( ! is_array($setup) ) { return '' ;}
		if( ! is_array($setup["settings"]) ) { return '' ;}
		if( ! is_array($setup["settings"]["emoticons"]) ) { return '' ;}
        $emoticons = $setup["settings"]["emoticons"] ;
        $emoticonBtnClass = $setup["settings"]["emoticonBtnClass"] ;
		$out = "";
		$out2 = "";
		foreach($emoticons as $key => $emoji) {
            if ( isset($emoji['notFontAwesome']) && $emoji['notFontAwesome']) {
                $code = '<span class="'. $emoticonBtnClass .  '" onClick="setValueToInput(\''.$emoji['code'].'\');" alt="emoji-' . $key . '" title="'.self::unicode_encode($emoji['code']).'">'  .  $emoji['html'] . '</span>';
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

    static function getPid()
    {
        $request = self::getRequest() ;
        $pageArguments = $request->getAttribute('routing');
        $pageId = $pageArguments->getPageId();
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
        return "https://". GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY') . "/"
            . ltrim(GeneralUtility::getIndpEnv('TYPO3_SITE_SCRIPT') , "/" ) .   "?tx_jvtyposcript=tx_jvchat_pi1" ;

    }

    static function getSetUp( $pid = 0 , $basePath= ''  ) {
        if ( $basePath ) {
            return TyposcriptUtility::loadTypoScriptviaCurl( $basePath );
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
        $renderer->setLayoutRootPaths($layoutPaths);
        $renderer->setTemplateRootPaths($templatePaths);
        $renderer->setPartialRootPaths($partialPaths);

        $renderer->setFormat($format) ;

        $renderer->setTemplate($template);
         $templatePath =   GeneralUtility::getFileAbsFileName( $templatePaths[0]."Pi1/" . $template . "." . $format );
        // /var/www/html/vendor/jv/jvchat/Resources/Private/Templates/Bootstrap4/Pi1/DisplayRooms
        $renderer->setTemplatePathAndFilename( $templatePath );
        return $renderer ;
    }

}

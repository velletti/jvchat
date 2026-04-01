<?php

/*
 * This file belongs to the package "TYPO3 Fluid".
 * See LICENSE.txt that was shipped with this package.
 */

namespace JVelletti\Jvchat\ViewHelpers;

use JVelletti\Jvchat\Domain\Repository\DbRepository;
use JVelletti\Jvchat\Utility\LibUtility;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Variables\VariableProviderInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Variable assigning ViewHelper
 *
 * Assigns one template variable which will exist also
 * after the ViewHelper is done rendering, i.e. adds
 * template variables.
 *
 * If you require a variable assignment which does not
 * exist in the template after a piece of Fluid code
 * is rendered, consider using ``f:alias`` ViewHelper instead.
 *
 *
 * @api
 */
class RoomViewHelper extends AbstractViewHelper
{
    public function initializeArguments()
    {
        $this->registerArgument('roomId', '?int', 'Value to assign. will set users and room variables');
    }

    public function render()
    {
        $languageServiceFactory = GeneralUtility::makeInstance(LanguageServiceFactory::class);
        $languageService = $languageServiceFactory->create('default');

        /** @var DbRepository $db */
        $db = GeneralUtility::makeInstance('JVelletti\Jvchat\Domain\Repository\DbRepository');
        $roomId = intval($this->arguments['roomId'] )  ;
        // TOdo: Overwrite from query arguments ???
        $getParams = ($GLOBALS['TYPO3_REQUEST']->getQueryParams()['tx_jvchat_pi1'] ?? null ) ;
        if ( $getParams && isset($getParams['uid']) ) {
            $roomId = intval($getParams['uid'] )  ;
        }
        $room = $db->getRoom( $roomId ) ;
        // todo: check if user has access

        $user = null ;
        /** @var \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication $frontendUser */
        $frontendUser = $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.user');

        $user = ($frontendUser->user ?? null ) ;
        if ( $user ) {
            $db->updateUserInRoom($room->uid, $user['uid'], LibUtility::isSuperuser($room, $user), $languageService->sL('LLL:EXT:jvchat/Resources/Private/Language/lcallang.xlf:user_enters_chat' ));

        }

        if ( !$room ) {
            $room = ["msg" => "room: " . $this->arguments['room'] .  " not found", "error" => TRUE  ] ;
        }
        $extConf = LibUtility::getExtConf() ;
        $settings = LibUtility::getSettings() ;
        $dataString = LibUtility::getDataString($user, $room, $extConf, $db) ;

        $dataString .= ' data-talkToNewRoomName="' . $this->slashJS( $languageService->sL('LLL:EXT:jvchat/Resources/Private/Language/lcallang.xlf:talktoroomname'))  . '"' ;

        // try to add user
        $db->updateUserInRoom($room->uid, $user['uid'], LibUtility::isSuperuser($room, $user), 'user_enters_chat');

        // remove old message entries if set
        if($extConf['autoDeleteEntries']) {
            $db->deleteEntries($extConf['autoDeleteEntries']);
        }

        // remove user who left room and remove system messages
        $db->cleanUpUserInRoom($room->uid, 60, false );


        // prepare the user's snippets
        $db->setUserlistSnippet($room->uid, $user['uid'], LibUtility::getSnippet($room, $user , $user));


        $interface = $this->renderingContext->getVariableProvider() ;
        $interface->add('extConf', $extConf);
        $interface->add('room', $room);
        $interface->add('dataString', $dataString);
        $interface->add('isFull', $db->isRoomFull($room) && !LibUtility::isSuperuser($room, $user) && !$db->isMemberOfRoom($room->uid, $user['uid']));
        $interface->add('user', $user );
        $interface->add('users', $db->getUserList($roomId));
        $interface->add('server', GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'));
        $interface->add('emoticons', LibUtility::getEmoticonsForChatRoom($settings) );
        $interface->add('settings', ($settings['settings'] ?? [])  );
    }

    /**
     * Explicitly set argument name to be used as content.
     */
    public function getContentArgumentName(): string
    {
        return 'room';
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

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
class LatestViewHelper extends AbstractViewHelper
{
    public function initializeArguments()
    {
        $this->registerArgument('roomId', '?int', 'Value to assign. will set users and room variables');
        $this->registerArgument('amount', '?int', 'number of days to get entries for. default 30 days');
    }

    public function render()
    {
        $languageServiceFactory = GeneralUtility::makeInstance(LanguageServiceFactory::class);
        $languageService = $languageServiceFactory->create('default');

        /** @var DbRepository $db */
        $db = GeneralUtility::makeInstance('JVelletti\Jvchat\Domain\Repository\DbRepository');
        $roomId = intval($this->arguments['roomId'] ?? 0 )  ;
        $days = intval($this->arguments['amount'] ?? 30 )  ;
        $room = $db->getRoom( $roomId ) ;
        // todo: check if user has access ??

        // todo allow to get more than one
        $entry = $db->getEntries( $room , 0 , 0 , 1) ;
        $seconds = $days * 24 * 3600;
        $entryCount = $db->getEntryCount( $room , $seconds) ;

        $user = null ;
        /** @var \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication $frontendUser */
        $frontendUser = $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.user');

        if ( !$room ) {
            $room = ["msg" => "room: " . $this->arguments['room'] .  " not found", "error" => TRUE  ] ;
        }
        $extConf = LibUtility::getExtConf() ;
        $settings = LibUtility::getSettings() ;

        $interface = $this->renderingContext->getVariableProvider() ;
        $interface->add('extConf', $extConf);
        $interface->add('room', $room);
        $interface->add('entry', $entry);
        $interface->add('seconds', $seconds);
        $interface->add('entryCount', $entryCount);
        $interface->add('server', GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'));
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

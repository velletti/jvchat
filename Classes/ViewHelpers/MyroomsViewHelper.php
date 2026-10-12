<?php

/*
 * This file belongs to the package "TYPO3 Fluid".
 * See LICENSE.txt that was shipped with this package.
 */

namespace JVelletti\Jvchat\ViewHelpers;

use JVelletti\Jvchat\Domain\Repository\DbRepository;
use JVelletti\Jvchat\Utility\LibUtility;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
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
class MyroomsViewHelper extends AbstractViewHelper
{
    public function initializeArguments()
    {
        $this->registerArgument('flexform', 'mixed', 'flexform data');
    }

    public function render()
    {
        $languageServiceFactory = GeneralUtility::makeInstance(LanguageServiceFactory::class);
        $languageService = $languageServiceFactory->create('default');

        /** @var DbRepository $db */
        $db = GeneralUtility::makeInstance('JVelletti\Jvchat\Domain\Repository\DbRepository');

        $flex = $this->arguments['flexform'] ?? [] ;


        $user = null ;
        $rooms = [];
        /** @var \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication $frontendUser */
        $frontendUser = $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.user');
        if ( $frontendUser && $frontendUser->user ) {
            $user = $frontendUser->user ;
            if (isset($user['uid'])) {
                $rooms = $db->getRoomsOfUser($user['uid'] ?? 0) ;
            }
        }
        $extConf = LibUtility::getExtConf() ;
        $settings = LibUtility::getSettings() ;
        $settings['settings']['currentLng'] = $GLOBALS['TYPO3_REQUEST']->getAttribute('language')->getLanguageId() ?? 0 ;

        $interface = $this->renderingContext->getVariableProvider() ;
        $interface->add('extConf', $extConf);
        $interface->add('rooms', $rooms);
        $interface->add('flex', $flex);
        $interface->add('server', GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'));
        $interface->add('settings', ($settings['settings'] ?? [])  );
    }

    /**
     * Explicitly set argument name to be used as content.
     */
    public function getContentArgumentName(): string
    {
        return 'flexform';
    }

}

<?php
namespace JV\Jvchat\Utility;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Routing\PageArguments;

class TyposcriptUtility
{

    /**
     * Loads the typoscript from scratch
     * @param int $pageUid
     * @param string $extKey
     * @param mixed $conditions array with Constants Conditions if needed
     *                          this
     *                          The Condition must be either :
     *                          a) one of the following Common Vars: (i did not test this, but found it in source !)
     *                         'usergroup' , 'treeLevel' , PIDupinRootline' or  'PIDinRootline':
     *                         f.e. : array( 'usergroup=2,4' )
     *
     *                          or ( this is tested)
     *                          b) the exact Condition from YOUR Constants.ts file
     *
     *                           this must be an array, also multiple Conditions can be handed over:
     *
     * @param bool $getConstants default=false,  will return  Constants (all or those from an extension) instaed of Setup
     * @return array
     * @author Peter Benke <pbenke@allplan.com>
     * @deprecated V12  Maybe creating a new Reuest does not work. .. better use direct calling  !!
     */
    public static function loadTypoScriptFromScratch($pageUid = 0, $extKey = '', mixed $conditions = false, $getConstants = false, $request = null)
    {
        if (!$request) {
            /** @var Request $request */
            $request = GeneralUtility::makeInstance(Request::class);
            $request->withArguments(['uid' => $pageUid]);

        }
        return self::loadTypoScriptFromRequest($request, $extKey = '', false , $pageUid );
    }

    public static function loadTypoScriptFromRequest($request, $extKey = '', $getConstants = false , $pid = 0 )
    {

        $siteFinder = GeneralUtility::makeInstance( SiteFinder::class);
        $site = $siteFinder->getSiteByPageId($pid);

        $controller = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            GeneralUtility::makeInstance(Context::class),
            $site,
            $site->getDefaultLanguage(),
            new PageArguments($site->getRootPageId(), '0', []),
            GeneralUtility::makeInstance(FrontendUserAuthentication::class)
        );

        // @extensionScannerIgnoreLine
        $controller->id = $pid;
        $controller->determineId($request);

        $ts = $controller->getFromCache($request)->getAttribute('frontend.typoscript')->getSetupArray();


        if ($getConstants) {
            // Todo get Constants  is untestet
            if (!empty($extKey)) {
                $ts = self::removeDotsFromTypoScriptArray($ts['config.'][$extKey . '.']);
            } else {
                $ts = self::removeDotsFromTypoScriptArray($ts['config.']);
            }
        } else {
            if (!empty($extKey)) {
                $ts = self::removeDotsFromTypoScriptArray($ts['plugin.'][$extKey . '.']);
            } else {
                $ts = self::removeDotsFromTypoScriptArray($ts['plugin.']);
            }
        }
        return $ts;
    }

    /**
     * Removes the dots from an typoscript array
     * @param $array
     * @return array
     * @author Peter Benke <pbenke@allplan.com>
     */
    private static function removeDotsFromTypoScriptArray($array)
    {

        $newArray = [];

        if (is_array($array)) {

            foreach ($array as $key => $val) {

                if (is_array($val)) {

                    // Remove last character (dot)
                    $newKey = substr($key, 0, -1);
                    $newVal = self::removeDotsFromTypoScriptArray($val);

                } else {

                    $newKey = $key;
                    $newVal = $val;

                }

                $newArray[$newKey] = $newVal;

            }

        }

        return $newArray;

    }

}
<?php
namespace JV\Jvchat\Utility;

class TyposcriptUtility{

	/**
	 * Loads the typoscript from scratch
	 * @author Peter Benke <pbenke@allplan.com>
	 * @param int $pageUid
	 * @param string $extKey
     * @throws \Exception
     * @throws \RuntimeException
	 * @return array
	 */
	public static function loadTypoScriptFromScratch($pageUid = 0, $extKey = '') {

		/**
		 * @var $pageRepository \TYPO3\CMS\Frontend\Page\PageRepository
		 * @var $extendedTemplateService \TYPO3\CMS\Core\TypoScript\ExtendedTemplateService
		 */
		$pageRepository =  \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Frontend\Page\PageRepository');

		$rootLine = $pageRepository->getRootLine($pageUid);

		$extendedTemplateService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\TypoScript\ExtendedTemplateService');

		$extendedTemplateService->tt_track = 0;
		$extendedTemplateService->init();

		// To get static files also
		$extendedTemplateService->setProcessExtensionStatics(true);
		$extendedTemplateService->runThroughTemplates($rootLine);
		$extendedTemplateService->generateConfig();

		if(!empty($extKey)){
			$typoScript = self::removeDotsFromTypoScriptArray($extendedTemplateService->setup['plugin.'][$extKey . '.']);
		}else{
			$typoScript = self::removeDotsFromTypoScriptArray($extendedTemplateService->setup);
		}

		return $typoScript;

	}

	/**
	 * Removes the dots from an typoscript array
	 * @author Peter Benke <pbenke@allplan.com>
	 * @param $array
	 * @return array
	 */
	private static function removeDotsFromTypoScriptArray($array) {

		$newArray = Array();

		if(is_array($array)){

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
<?php
namespace JV\Jvchat\ViewHelpers\Image;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;


class FileExistsViewHelper extends AbstractViewHelper{


	/**
	 * InitializeArguments
	 */
	public function initializeArguments() {
		$this->registerArgument('imagePath', 'string', 'Whole path to the image incl. filename');
	}

	/**
	 *
	 * @return boolean
	 */
	public function render() {
		$imagePath =GeneralUtility::getFileAbsFileName($this->arguments['imagePath'] );
		return is_file($imagePath) ;

	}


}
<?php

/*
 * This file belongs to the package "TYPO3 Fluid".
 * See LICENSE.txt that was shipped with this package.
 */

namespace JVelletti\Jvchat\ViewHelpers;

use JVelletti\Jvchat\Domain\Repository\DbRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
        /** @var DbRepository $db */
        $db = GeneralUtility::makeInstance('JVelletti\Jvchat\Domain\Repository\DbRepository');
        $roomId = intval($this->arguments['roomId'] )  ;
        // TOdo: Overwrite from query arguments
        $room = $db->getRoom( $roomId ) ;
        // todo: check if room is full and user has access

        // todo: add user to list of users in room

        if ( !$room ) {
            $room = ["msg" => "room: " . $this->arguments['room'] .  " not found", "error" => TRUE  ] ;
        }
        $this->renderingContext->getVariableProvider()->add('room', $room);
        $this->renderingContext->getVariableProvider()->add('users', $db->getUserList($roomId));
        $this->renderingContext->getVariableProvider()->add('server', GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'));

    }

    /**
     * Explicitly set argument name to be used as content.
     */
    public function getContentArgumentName(): string
    {
        return 'room';
    }
}

<?php
namespace JVelletti\Jvchat\Scheduler;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * A task that should be run regularly that deletes
 * datasets flagged as "deleted" from the DB.
 */
class MailchatsTaskAdditionalFieldProvider extends AbstractAdditionalFieldProvider
{
    /**
     * Gets additional fields to render in the form to add/edit a task
     *
     * @param array $taskInfo Values of the fields from the add/edit task form
     * @param MailchatsTask $task The task object being edited. NULL when adding a task!
     * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return array A two dimensional array, array('Identifier' => array('fieldId' => array('code' => '', 'label' => '', 'cshKey' => '', 'cshLabel' => ''))
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        if ($schedulerModule->getCurrentAction()  === 'edit') {
            $taskInfo['IndexerAmount'] = $task->getAmount();
            $taskInfo['IndexerDebugmail'] = $task->getDebugmail();
        }
        $additionalFields = array() ;



        $additionalFields = $this->generateFormField($additionalFields , "Amount" , "text" , $taskInfo ) ;
        $additionalFields = $this->generateFormField($additionalFields , "Debugmail" , "text" , $taskInfo ) ;



        return $additionalFields;
    }
    public function generateFormField($additionalFields , $name, $type ,$taskInfo, $cshKey = '' , $class='form-control' ) {
        $formField = array() ;
        switch ($type) {
            case 'bool':
                $checked = '' ;
                if ( $taskInfo['Indexer' .$name ] == 1 ) {
                    $checked = ' checked="checked"'  ;
                }
                $formField['code'] = '<input type="checkbox" class=" ' . $class . '" name="tx_scheduler[Indexer' . $name . ']" value="1" ' . $checked . '>' ;
                break;
            case 'text':
                $formField['code'] = '<input type="text" class="' . $class . '" name="tx_scheduler[Indexer' . $name . ']" value="' . $taskInfo['Indexer' .$name ] . '">' ;
                break;
            case 'password':
                $formField['code'] = '<input type="password" class="' . $class . '" name="tx_scheduler[Indexer' . $name . ']" value="' . $taskInfo['Indexer' .$name ] . '">' ;
                break;
        }
        $formField['label'] = 'LLL:EXT:jvchat/Resources/Private/Language/locallang.xlf:indexerTask_' . $name ;
        $formField['cshKey'] = $cshKey ;
        $formField['cshLabel'] = 'task_indexerTask_' . $name ;
        $additionalFields['Indexer'. $name] = $formField ;
        return $additionalFields ;
    }
    /**
     * Validates the additional fields' values
     *
     * @param array $submittedData An array containing the data submitted by the add/edit task form
     * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return bool TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule)
    {
        $validStoragePid = $this->validateAdditionalFieldStoragePid($submittedData['IndexerStoragePid'], $schedulerModule);
        return $validStoragePid ;
    }
    /**
     * Validates the input of period
     *
     * @param int $period The given period as integer
     * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return bool TRUE if validation was ok, FALSE otherwise
     */
    protected function validateAdditionalFieldStoragePid($storagePid, SchedulerModuleController $schedulerModule)
    {
        if (empty($period) ||   filter_var($period, FILTER_VALIDATE_INT) !== false  ) {
            $validPeriod = true;
        } else {
            $this->addMessage(
                htmlspecialchars ($this->getLanguageService()->sL('LLL:EXT:allplan_ke_search_extended/Resources/Private/Language/locallang_tasks.xlf:indexerTaskErrorStoragePid') ),
                AbstractMessage::ERROR
            );
            $validPeriod = false;
        }

        return $validPeriod;
    }
    /**
     * Takes care of saving the additional fields' values in the task's object
     *
     * @param array $submittedData An array containing the data submitted by the add/edit task form
     * @param AbstractTask $task Reference to the scheduler backend module
     * @return void
     * @throws \InvalidArgumentException
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        if (!$task instanceof AbstractTask ) {
            throw new \InvalidArgumentException(
                'Expected a task of type JVelletti\Jvchat\Scheduler\MailchatsTask, but got ' . get_class($task),
                1329219449
            );
        }

        $task->setAmount($submittedData['IndexerAmount']);
        $task->setDebugmail($submittedData['IndexerDebugmail']);


    }
    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}

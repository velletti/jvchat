<?php
namespace JV\Jvchat\Scheduler;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Locking\LockingStrategyInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MailchatsTask extends AbstractTask
{


    /** @var int Amount of Seconds when Mails are send  */
    private $amount = 300;


    /** @var string email Address if set, debug output will be sent  */
    private $debugmail = '';

    /** @var  Logger */
    protected $logger;

    private function fetchConfiguration()
    {

        $this->amount = (int) $this->amount;
        $this->debugmail = trim( $this->debugmail) ;
        // response: skipCertValidation hinzugefügt

        return true;
    }

	/**
	 * This is the main method that is called when a task is executed
	 * It MUST be implemented by all classes inheriting from this one
	 * Note that there is no error handling, errors and failures are expected
	 * to be handled and logged by the client implementations.
	 * Should return TRUE on successful execution, FALSE on error.
	 *
	 * @return bool Returns TRUE on successful execution, FALSE on error
	 * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
	 * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireWouldBlockException
	 * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
	 */
    public function execute()
    {
        $startTime = time() ;
        $debug[] = date("d.m.Y H:i:s") . " Started" ;
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

        $this->fetchConfiguration() ;
        $this->logger->notice('TYPO3 jv_mailreturn Fetchbounces Task: after fetch config   ');

        /** @var LockFactory $lockFactory */
        $lockFactory = GeneralUtility::makeInstance(LockFactory::class);
        $locker = $lockFactory->createLocker('jvchat_mailchats', LockingStrategyInterface::LOCK_CAPABILITY_EXCLUSIVE | LockingStrategyInterface::LOCK_CAPABILITY_NOBLOCK);

        // Check if cronjob is already running:
        if (!$locker->acquire($locker::LOCK_CAPABILITY_EXCLUSIVE | $locker::LOCK_CAPABILITY_NOBLOCK)) {
            $this->outputLine('TYPO3 jvchat_mailchats Task: ERROR: Cannot lock  ');

            return false;
        }
        /** @var \JV\Jvchat\Eid\Chat $chatLib */
        $chatLib = GeneralUtility::makeInstance("JV\\Jvchat\\Eid\\Chat");
        /** @var \JV\Jvchat\Domain\Repository\DbRepository $db */
        $db = GeneralUtility::makeInstance("JV\\Jvchat\\Domain\\Repository\\DbRepository");
        $db->__construct() ;

        $rooms = $db->_getRooms($db->extCONF['pids.']['entries']) ;
        $debug[] = date("d.m.Y H:i:s") . " Got Rooms " ;
        if( is_array($rooms)) {
            $debug[] = date("d.m.Y H:i:s") . " # of rooms:  " . count($rooms) ;
            /** @var \JV\Jvchat\Domain\Model\Room $room */
            foreach ( $rooms  as $room ) {
                $debug[] = date("d.m.Y H:i:s") . " getEntries from of rooms:  " . $room->name . " -> new since " . date( "d.m.Y H:i:s" , Time() - $this->getAmount() )  ;
                $entries =  $db->getEntrieslastXseconds($room , $this->getAmount() ) ;
                if ( $entries && count ( $entries ) > 0  )  {
                    $debug[] = date("d.m.Y H:i:s") . " # of Entries :  " .count( $entries )  ;
                    $membersToNotify = $db->getFeUsersToNotifyRoom($room);
                    // $membersToNotify = $db->getFeUsersMayAccessRoom($room);
                    $chatLib->init( null , "UTF-8" , $room ) ;

                    $chatLib->sendEmails( $entries , $membersToNotify , $room , true ) ;
                }

            }
        }
        if( GeneralUtility::validEmail( trim( $this->getDebugmail()) ) ) {
            /** @var \Velletti\Mailsignature\Service\SignatureService $mailService */
            $mailService = GeneralUtility::makeInstance("Velletti\\Mailsignature\\Service\\SignatureService");
            $params = array() ;
            $params['email_fromName'] = "Debug Tangomuenchen";
            $params['email_from'] = "info@tangomuenchen.de";
            $params['user']['email'] = trim( $this->getDebugmail());
            $params['sendCCmail'] = false  ;

            $params['message'] = "Debug Output " . implode(" <br>\n" , $debug ) ;
            $mailService->sentHTMLmailService($params) ;
        }

        $locker->release();
        return true;
    }


    private function outputLine($msg)
    {
        $this->logger->error($msg);
    }



    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getDebugmail()
    {
        return $this->debugmail;
    }

    /**
     * @param string $debugmail
     */
    public function setDebugmail($debugmail)
    {
        $this->debugmail = $debugmail;
    }





}

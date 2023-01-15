<?php
namespace JV\Jvchat\Scheduler;
use JV\Jvchat\Domain\Model\Room;
use JV\Jvchat\Domain\Repository\DbRepository;
use JV\Jvchat\Eid\Chat;
use TYPO3\CMS\Core\Locking\Exception\LockAcquireException;
use TYPO3\CMS\Core\Locking\Exception\LockAcquireWouldBlockException;
use TYPO3\CMS\Core\Locking\Exception\LockCreateException;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Locking\LockingStrategyInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Velletti\Mailsignature\Service\SignatureService;

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
     * @throws LockAcquireException
     * @throws LockAcquireWouldBlockException
     * @throws LockCreateException
     * @throws InvalidExtensionNameException
     */
    public function execute()
    {
        $debug = array() ;
        /** @var Chat $chatLib */
        $chatLib = GeneralUtility::makeInstance("JV\\Jvchat\\Eid\\Chat");
        $baseUrl = $chatLib->setBaseUrl("www.tangomuenchen.de") ;

        $debug[] = date("d.m.Y H:i:s") . " Started on Server "  . "https://" . $baseUrl  . " ";

        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

        $this->fetchConfiguration() ;
        $this->logger->notice('TYPO3 jvchat  Task: after fetch config   ');

        /** @var LockFactory $lockFactory */
        $lockFactory = GeneralUtility::makeInstance(LockFactory::class);
        $locker = $lockFactory->createLocker('jvchat_mailchats', LockingStrategyInterface::LOCK_CAPABILITY_EXCLUSIVE | LockingStrategyInterface::LOCK_CAPABILITY_NOBLOCK);

        // Check if cronjob is already running:
        if (!$locker->acquire($locker::LOCK_CAPABILITY_EXCLUSIVE | $locker::LOCK_CAPABILITY_NOBLOCK)) {
            $this->outputLine('TYPO3 jvchat_mailchats Task: ERROR: Cannot lock  ');

            return false;
        }


        /** @var DbRepository $db */
        $db = GeneralUtility::makeInstance("JV\\Jvchat\\Domain\\Repository\\DbRepository");
        $db->__construct() ;
        if ( is_array($db->extCONF ) ) {
            if ( array_key_exists('pids.' ,  $db->extCONF) && is_array($db->extCONF['pids.'] ) ) {
                $this->logger->notice('TYPO3 jvchat ext config Pids : : ' . var_export($db->extCONF['pids.'] , true ) );
            } else {
                $this->logger->warning('TYPO3 jvchat could not load Find pids in ext config  ' . var_export($db->extCONF  , true )  );
            }
        } else {
            $this->outputLine('TYPO3 jvchat_mailchats Task: ERROR: could not load ext config  ');
            return false;
        }

        $rooms = $db->_getRooms($db->extCONF['pids.']['rooms']) ;
        $debug[] = date("d.m.Y H:i:s") . " Got Rooms " ;
        // needed becaues Chat Lib will not send emails to current $this->user['email']
        $chatLib->user['email'] = "_cli_Dummy@typo3.xy" ;
        if( is_array($rooms)) {
            $debug[] = date("d.m.Y H:i:s") . " # of rooms:  " . count($rooms) ;
            /** @var Room $room */
            foreach ( $rooms  as $room ) {
                $debug[] = date("d.m.Y H:i:s") . " getEntries from of rooms:  " . $room->name . " -> new since " . date( "d.m.Y H:i:s" , Time() - $this->getAmount() )  ;
                $entries =  $db->getEntrieslastXseconds($room , $this->getAmount() ) ;
                if ( $entries && count ( $entries ) > 0  )  {
                    $debug[] = date("d.m.Y H:i:s") . " # of Entries :  " .count( $entries )  ;
                    $membersToNotify = $db->getFeUsersToNotifyRoom($room);
                    // $membersToNotify = $db->getFeUsersMayAccessRoom($room);
                    $chatLib->init( null , "UTF-8" , $room ) ;

                    $chatLib->sendEmails( $entries , $membersToNotify , $room , true , $baseUrl ) ;
                }

            }
        }
        if( GeneralUtility::validEmail( trim( $this->getDebugmail()) ) ) {
            /** @var SignatureService $mailService */
            $mailService = GeneralUtility::makeInstance("Velletti\\Mailsignature\\Service\\SignatureService");
            $params = array() ;
            $params['email_fromName'] = "Debug Tangomuenchen";
            $params['email_from'] = "info@tangomuenchen.de";
            $params['user']['email'] = trim( $this->getDebugmail());
            $params['sendCCmail'] = false  ;

            $params['message'] = "Debug Output " . implode(" \n" , $debug ) ;
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

<?php
namespace JV\Jvchat\Command;

use JV\Jvchat\Domain\Model\Room;
use JV\Jvchat\Domain\Repository\DbRepository;
use JV\Jvchat\Eid\Chat;
use PDO;
use Symfony\Component\Console\Input\InputOption;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Locking\LockingStrategyInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Velletti\Mailsignature\Service\SignatureService;

/**
 * Class NotifyCommand
 * @author Jörg Velletti <typo3@velletti.de>
 * @package JVE\Jvchat\Command
 */
class NotifyCommand extends Command {

    /**
     * @var array
     */
    private $allowedTables = [] ;

    /**
     * @var array
     */
    private $extConf = [] ;



    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure()
    {
        $this->setDescription('Sends Email notifications.')
            ->setHelp('Get list of Options: .' . LF . 'use the --help option.')
            ->addOption(
                'amount',
                'a',
                InputArgument::OPTIONAL,
                'Seconds until a chat entry is new. should be the same like frequency cron job is running . default 3600'
            )
            ->addOption(
                'debugEmail',
                'd' ,
                InputArgument::OPTIONAL,
                'email Address that should get debug output'
            )
            ->addOption(
                'baseHost',
                'b' ,
                InputArgument::OPTIONAL,
                'Base Hostname ... default www.tangomuenchen.de'
            )->addOption(
                'basePath',
                'p' ,
                InputArgument::OPTIONAL,
                'Url, for pagewith typo Script. default: "/", This will call https:// baseurl/ basePath + ?tx_typoscript=tx_jvchat'
            );


    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int 0 if everything went fine, or an exit code
     *
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());
        $secondsAmount = 3600 ;
        if ($input->getOption('amount') ) {
            $secondsAmount = (int)$input->getOption('amount') ;
            $io->writeln('Seconds an entry may exist to be sent was set to '. $secondsAmount );

        }
        // Bootstrap::initializeBackendAuthentication();
        $debugEmail = false ;
        if ($input->getOption('debugEmail')) {
            $debugEmail = $input->getOption('debugEmail');
            $io->writeln('$debugEmail is set to : '. $debugEmail );
        }

        if ($input->getOption('baseHost')) {
            $baseHost = $input->getOption('baseHost');
        } else {
            $baseHost = "tangov10.ddev.site" ;
        }
        $io->writeln('$baseHost is set to : '. $baseHost );

        $basePath = "/de/" ;
        if ($input->getOption('basePath')) {
            $basePath = $input->getOption('basePath');
        }
        $basePath = "https://". $baseHost . $basePath .  "?tx_jvtyposcript=tx_jvchat_pi1" ;
        $io->writeln('Curl path is set to : '. $basePath );

        if($this->notifyCommand($io , $secondsAmount, $debugEmail  , $baseHost , $basePath ) ) {
            return 1 ;
        }
        return 0 ;
    }


    /**
     * @param SymfonyStyle $io
     * @param $table
     * @param $slugField
     * @param $secondsAmount
     */
    public function notifyCommand(SymfonyStyle $io , $secondsAmount, $debugEmail , $baseHost , $basePath    ){
        $progress = false ;


        $debug = array() ;
        /** @var Chat $chatLib */
        $chatLib = GeneralUtility::makeInstance(Chat::class);
        $baseUrl = $chatLib->setBaseUrl($baseHost) ;
        $baseUrl = $chatLib->setBasePath($basePath) ;

        $debug[] = date("d.m.Y H:i:s") . " Started on Server "  . "https://" . $baseUrl  . " ";

        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

        $this->logger->notice('TYPO3 jv_chat notify Command Task: before LOCK  ');

        /** @var LockFactory $lockFactory */
        $lockFactory = GeneralUtility::makeInstance(LockFactory::class);
        $locker = $lockFactory->createLocker('jvchat_mailchats', LockingStrategyInterface::LOCK_CAPABILITY_EXCLUSIVE | LockingStrategyInterface::LOCK_CAPABILITY_NOBLOCK);

        // Check if cronjob is already running:
        if (!$locker->acquire($locker::LOCK_CAPABILITY_EXCLUSIVE | $locker::LOCK_CAPABILITY_NOBLOCK)) {
            $io->writeln('TYPO3 jvchat_mailchats Task: ERROR: Cannot lock  ' );
            return false;
        }


        /** @var DbRepository $db */
        $db = GeneralUtility::makeInstance(DbRepository::class);
        $db->__construct() ;

        $rooms = $db->_getRooms($db->extCONF['pids.']['entries']) ;
        $debug[] = date("d.m.Y H:i:s") . " Got Rooms " ;
        // needed becaues Chat Lib will not send emails to current $this->user['email']
        $chatLib->user['email'] = "_cli_Dummy@typo3.xy" ;
        if( is_array($rooms)) {
            $debug[] = date("d.m.Y H:i:s") . " # of rooms:  " . count($rooms) ;
            /** @var Room $room */
            foreach ( $rooms  as $room ) {
                $debug[] = date("d.m.Y H:i:s") . " getEntries from of rooms:  " . $room->name . " -> new since " . date( "d.m.Y H:i:s" , Time() - $secondsAmount )  ;
                $entries =  $db->getEntrieslastXseconds($room , $secondsAmount, 0 , true, true ) ;

                if ( $entries && count ( $entries ) > 0  )  {
                    $debug[] = date("d.m.Y H:i:s") . " # of Entries :  " .count( $entries )  ;
                    $membersToNotify = $db->getFeUsersToNotifyRoom($room);
                    $debug[] = date("d.m.Y H:i:s") ." # members to notify : " . count($membersToNotify)  ;

                    // $membersToNotify = $db->getFeUsersMayAccessRoom($room);
                    $chatLib->init( null , "UTF-8" , $room ) ;

                    $debug[] = strip_tags( $chatLib->sendEmails( $entries , $membersToNotify , $room , true , $baseUrl ) ) ;
                }

            }
        }
        if( GeneralUtility::validEmail( trim( $debugEmail) ) ) {
            /** @var SignatureService $mailService */
            $mailService = GeneralUtility::makeInstance(SignatureService::class);
            $params = array() ;
            $params['email_fromName'] = "Debug Tangomuenchen";
            $params['email_from'] = "info@tangomuenchen.de";
            $params['user']['email'] = trim( $debugEmail );
            $params['sendCCmail'] = false  ;

            $params['message'] = "Debug Output " . implode(" \n" , $debug ) ;
            $mailService->sentHTMLmailService($params) ;
        }

        if( $io->getVerbosity() > 16 ) {

            if( $io->getVerbosity()  > 128 ) {
                $io->writeln(var_export( $debug , true ));
            }
            $io->writeln(" ") ;
            $io->writeln("Finished ");
        }
	}

    /**
     * @param string $table
     * @return QueryBuilder
     */
	private function getQueryBuilder(string $table): QueryBuilder
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance( ConnectionPool::class);
        /** @var QueryBuilder $queryBuilder */
        return $connectionPool->getConnectionForTable($table)->createQueryBuilder();
	}

    /**
     * @param $table
     * @param $row
     * @param $slugField
     * @return array
     */
    private function mapRow($table , $row , $slugField ): array
    {
	    $return = array() ;

        $return['pid'] =   $row['pid'] ? $row['pid'] : 0  ;
        $return['parentpid'] =  1 ;
        $return['uid'] =  $row['uid'] ? $row['uid'] : 0  ;



	    switch ($table) {
            case "tx_jvevents_domain_model_event":

                $return['name'] =  $row['name'] ;
                $return['parentpid'] =  1 ;
                $return['sys_language_uid'] = -1 ;

                $slugGenerationDateFormat = "d-m-Y" ;
                if( is_array( $this->extConf) and array_key_exists( "slugGenerationDateFormat" , $this->extConf )) {
                    $slugGenerationDateFormat =  $this->extConf['slugGenerationDateFormat'] ;
                }

                $return['start_date'] =   date( $slugGenerationDateFormat , $row['start_date'] ) ;
                $return[$slugField] =   $row[$slugField]?  $row[$slugField] : $row['name'] . "-" . $row['start_date'] ;
                break ;
            default:
                $return['name'] =  $row['name'] ;
                if(array_key_exists('parentpid' , $row)) {
                    $return['parentpid'] =  $row['parentpid']  ;
                } else {
                    $return['parentpid'] =  1 ;
                }
                $return['sys_language_uid'] =   $row['sys_language_uid'] ;
                $return['start_date'] =   date( "d-m-Y" , $row['start_date'] ) ;
                $return[$slugField] =   $row[$slugField]?  $row[$slugField] : $row['name'] . "-" . $row['start_date'] ;
                break ;
        }
        return $return ;
    }

    /**
     * @param $table
     * @param $uid
     * @param $slugField
     * @param $slug
     */
    private function setSlug($table , $uid , $slugField , $slug)
    {
        $qb = $this->getQueryBuilder($table) ;
        $qb->update($table)->set($slugField , $slug)->where($qb->expr()->eq("uid" , $qb->createNamedParameter($uid , PDO::PARAM_INT)))->executeStatement() ;

    }


}
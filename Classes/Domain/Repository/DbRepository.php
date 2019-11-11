<?php
namespace JV\Jvchat\Domain\Repository;

// require_once('class.tx_jvchat_room.php');
// require_once('class.tx_jvchat_session.php');
// require_once('class.tx_jvchat_entry.php');
//require_once('class.tx_jvchat_lib.php');

use JV\Jvchat\Utility\LibUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction ;
use \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser ;

// was : class.tx_jvchat_db.php

class DbRepository {

    /**
     * @var array
     */
    var $extCONF;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;



    /**
     * @var TYPO3\CMS\Core\Database\ConnectionPool
     */
    public $connectionPool ;


	function __construct() {
		$this->extCONF = LibUtility::getExtConf();

        $this->connectionPool = GeneralUtility::makeInstance( ConnectionPool::class);
	}

	function getRoomsOfPage($pageId) {

		$pageId = intval($pageId);

        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $this->connectionPool->getConnectionForTable('tx_jvchat_room')->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('tx_jvchat_room')
            ->orderBy('sorting', 'ASC') ;

        if( $pageId ) {
            $expr = $queryBuilder->expr();
            $queryBuilder->where(
                $expr->eq('pid', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT))
            ) ;
        }
        $rows = $queryBuilder->execute()->fetchAll();

		$rooms = array();
		foreach ($rows as $row ) {
            /** @var \JV\Jvchat\Domain\Model\Room $room */
			$room = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('JV\\Jvchat\\Domain\\Model\\Room');
			$room->fromArray($row);

			$rooms[] = $room;

		}

		return $rooms;

	}

	function getRoomsOfUser($userId)
    {

        $userId = intval($userId);


        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $this->connectionPool->getConnectionForTable('tx_jvchat_room_feusers_mm')->createQueryBuilder();
        $expr = $queryBuilder->expr();
        $allRooms = $queryBuilder->select('uid_local')
            ->from('tx_jvchat_room_feusers_mm')
            ->where($expr->eq('uid_foreign', $queryBuilder->createNamedParameter($userId, Connection::PARAM_INT)))
            ->execute()
            ->fetchAll();

        if (count($allRooms) < 1) {
            return array();
        }

        $rooms = array();

        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $this->connectionPool->getConnectionForTable('tx_jvchat_room')->createQueryBuilder();

        foreach ($allRooms as $roomId) {
            $row = $queryBuilder->select('*')
                ->from('tx_jvchat_room')
                ->where('uid', $roomId['uid_local'])
                ->execute()
                ->fetch();

            /** @var \JV\Jvchat\Domain\Model\Room $room */
            $room = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('JV\\Jvchat\\Domain\\Model\\Room');
            $room->fromArray($row);
            $rooms[] = $room;
        }
        return $rooms;
	}
	
	function getRoomsOfUserAsOwner($userId) {

		$userId = intval($userId);

        $rooms = array();
        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $this->connectionPool->getConnectionForTable('tx_jvchat_room')->createQueryBuilder();

        $rows = $queryBuilder->select('*')
            ->from('tx_jvchat_room')
            ->where('owner', $userId )
            ->orderBy('uid')
            ->execute()->fetchAll() ;
            ;
        foreach ( $rows as $row ) {
            /** @var \JV\Jvchat\Domain\Model\Room $room */
            $room = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('JV\\Jvchat\\Domain\\Model\\Room');
            $room->fromArray($row);
            $rooms[] = $room;
        }
        return $rooms;
	}

    function getLatestPrivateRoomOfUsers($ownerId , $userId) {

        $ownerId = intval($ownerId);
        $userId = intval($userId);

        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $this->connectionPool->getConnectionForTable('tx_jvchat_room')->createQueryBuilder();
        $expr = $queryBuilder->expr();
        $rows = $queryBuilder->select('*')
            ->from('tx_jvchat_room')
            ->where( $expr->eq('owner', $queryBuilder->createNamedParameter(($ownerId) , Connection::PARAM_INT )))
            ->andWhere($expr->eq('private', 1 ))
            ->andWhere($expr->inSet('members' , $queryBuilder->createNamedParameter(($userId) , Connection::PARAM_INT )))
            ->orderBy('uid' , "DESC")
            ->setMaxResults(1)
            ->execute() ;
        ;
        if ( $row =  $rows->fetch()  ) {
            /** @var \JV\Jvchat\Domain\Model\Room $room */
            $room = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('JV\\Jvchat\\Domain\\Model\\Room');
            $room->fromArray($row);
            return  $room;
        }
        return false ;
    }
	
	function createNewRoom($room) {
		$data = $room->toArray();
		$data['crdate'] = time();
		$data['tstamp'] = time();

        unset( $data['enableEmoticons'] );
        unset( $data['enableTime'] );
        unset( $data['imageUpload'] );

        $connection = $this->connectionPool->getConnectionForTable('tx_jvchat_room') ;
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_room') ;

        if ( $queryBuilder->insert('tx_jvchat_room')->values($data)->execute() ) {
            return $connection->lastInsertId('tx_jvchat_room') ;
        } else {
            return 0 ;
        }
	}

	function getUniqueRoomName($roomName) {

        $roomName = trim(strip_tags($roomName));

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_room') ;

        $rows = $queryBuilder
            ->select('uid' , 'name')
            ->from('tx_jvchat_room')
            ->where(
                $queryBuilder->expr()->eq('name', $queryBuilder->createNamedParameter($roomName , Connection::PARAM_STR))
            )
            ->orderBy("uid" , "DESC")
            ->setMaxResults(1)
            ->execute();


        $row =  $rows->fetch() ;
        if( is_array($row)) {
            trim($row['name']) . "#" . ( $row['uid'] + 1 ) ;
        } else {
            return $roomName ."#1";
        }



	}
	
	function enableFields($table, $show_hidden = 0)	{
		$hidden = $show_hidden ? '' : 'AND hidden = 0';
		return ' AND deleted = 0 '.$hidden.' AND (starttime<='.time().') AND (endtime=0 OR endtime>'.time().')';
	}
	
	function getSession($sessionId) {
        $sessionId = intval($sessionId);

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_session') ;

        $rows = $queryBuilder
            ->select('*')
            ->from('tx_jvchat_session')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter((intval($sessionId)) , Connection::PARAM_INT ))
            )
            ->setMaxResults(1)
            ->execute();


        $row =  $rows->fetch() ;
        if( ! $row ) {
            return false;
        }

        /** @var \JV\Jvchat\Domain\Model\Session $session */
		$session = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('JV\\Jvchat\\Domain\\Model\\Session');
		$session->fromArray($row);
		return $session;
	}
	
	function getSessionsCountOfRoom($roomId) {

        $roomId = intval($roomId);

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_session') ;

        $rows = $queryBuilder
            ->select('uid')
            ->from('tx_jvchat_session')
            ->where(
                $queryBuilder->expr()->eq('room', $queryBuilder->createNamedParameter((intval($roomId)) , Connection::PARAM_INT ))
            )
            ->execute()->fetchAll();


        return count($rows) ;

	}
	
	function getSessionsOfRoom($roomId) {

		$roomId = intval($roomId);

		if(!$roomId)
			return array();

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_session') ;

        $queryBuilder
            ->select('*')
            ->from('tx_jvchat_session')
            ->where(
                $queryBuilder->expr()->eq('room', $queryBuilder->createNamedParameter((intval($roomId)) , Connection::PARAM_INT ))
            )
            ->orderBy('sorting') ;

        $rows = $queryBuilder->execute() ;

		$sessions = array();
		while($row = $rows->fetch() ) {
            /** @var \JV\Jvchat\Domain\Model\Session $session */
			$session = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('JV\\Jvchat\\Domain\\Model\\Session');
			$session->fromArray($row);
			$sessions[] = $session;
		}

		return $sessions;

	}

	function getEntriesCountOfSession($session) {
		return count($this->getEntriesOfSession($session));
	}

	function getEntriesOfSession($session) {

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_entry') ;
        $queryBuilder->select('*')->from('tx_jvchat_entry')
            ->where( $queryBuilder->expr()->eq('room', $queryBuilder->createNamedParameter( intval($session->room) , Connection::PARAM_INT )) )
            ->andWhere($queryBuilder->expr()->eq('cruser_id', 'feuser'))
            ->andWhere($queryBuilder->expr()->gte('uid', $queryBuilder->createNamedParameter( intval($session->startid) , Connection::PARAM_INT )))
            ->andWhere($queryBuilder->expr()->lte('uid', $queryBuilder->createNamedParameter( intval($session->endid) , Connection::PARAM_INT )))
            ->orderBy("uid")
        ;

        // $this->debugQuery($queryBuilder) ;
        $rows = $queryBuilder->execute() ;

		$entries = array();
		while( $row = $rows->fetch() ) {
            /** @var \JV\Jvchat\Domain\Model\Entry $entry */
            $entry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('JV\\Jvchat\\Domain\\Model\\Entry');
			$entry->fromArray($row);
			$entries[] = $entry;
		}

		return $entries;
	}
	
	function updateUserInRoom($roomId, $userId, $isSuperuser = false, $enterlabel = '') {
        if(!$userId || !$roomId) {
            return true ;
        }
        $roomId = intval($roomId);
        $userId = intval($userId);

        $user = $this->getFeUser($userId);
        $invisible = ($this->extCONF['hideSuperusers'] && $isSuperuser) ? 1 : 0;

        $showmess = FALSE ;

        if( $this->extCONF['showBirthday'] ) {
            if ( $GLOBALS['TSFE']->fe_user->user['tx_nem_dateofbirth_show']  == "1" ) {

                if ( date( "d.M" , $GLOBALS['TSFE']->fe_user->user['tx_nem_dateofbirth'] )  == date( "d.M"  ) ) {
                    $showmess = TRUE ;
                }
            }
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_room_feusers_mm') ;

        //check if User is already memberOf Room
        // if not try to add

        if(!$this->isMemberOfRoom($roomId, $userId, false)) {
            // check first if room is full
            $room = $this->getRoom($roomId);

            // allow superusers to access room even if it is 'full'
            if($this->isRoomFull($room) && !$isSuperuser) {
                return 'full';
            }

            $data = array(
				'uid_local' => $roomId,
				'uid_foreign' => $userId,
				'tstamp' => $this->getTime(),
				'invisible' => $invisible,
				'in_room' => 1,
			);

            $queryBuilder->insert('tx_jvchat_room_feusers_mm')->values($data)->execute() ;

            if(!$invisible && $showmess) {
                $this->putMessage($roomId, sprintf($enterlabel,$user['username']));
            }
            return "entered";
        } else {

			if(!$invisible && $showmess) {
				if( !$this->getUserStatus($roomId, $userId, 'in_room') == 1 ) {
					$this->putMessage($roomId, sprintf($enterlabel,$user['username']));
				}
			}
            $queryBuilder
                ->update('tx_jvchat_room_feusers_mm')
                ->where(
                    $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter($roomId , Connection::PARAM_INT ))
                )->andWhere(
                    $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($userId , Connection::PARAM_INT ))
                )->andWhere(
                    $queryBuilder->expr()->lt('tstamp', $queryBuilder->createNamedParameter($this->getTime() , Connection::PARAM_INT ))
                )
                ->set('tstamp', $this->getTime() )
                ->set('in_room', 1 )
                ->execute();

		}
		return true;

	}
	
	function getFeUser($uid) {

		if(!$uid)
			return array();

		$uid = intval($uid);


        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('fe_users');

        $userQuery = $queryBuilder->select( '*' )->from('fe_users' )
            ->where( $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter( $uid)) )->setMaxResults(1)  ;

        // $debug = "Query LastRun : " . $userQuery->getSQL() ;
        $userRow = $userQuery->execute()->fetch() ;

        // $this->debugQuery( $userQuery ) ;
        return $userRow;
    }

	function isMemberOfRoom($roomId, $userId, $depend_on_inroom = true) {
		if(!$roomId || !$userId) {
            return NULL;
        }

		$roomId = intval($roomId);
		$userId = intval($userId);



        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_room_feusers_mm');

        $userQuery = $queryBuilder->count( '*' )->from('tx_jvchat_room_feusers_mm' )
            ->where( $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter( $roomId)) )
            ->andWhere( $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter( $userId)) )
            ;

        if ( $depend_on_inroom ) {
            $userQuery->andWhere($queryBuilder->expr()->eq('in_room', 1 ) ) ;
        }

        // $this->debugQuery( $userQuery ) ;

        return $userQuery->execute()->fetchColumn(0);
	}

	function getRoom($uid) {

		$uid = intval($uid);

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_room') ;
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        ;


        $roomQuery = $queryBuilder->select( '*' )->from('tx_jvchat_room' )
            ->where( $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter( $uid)) )
         //   ->andWhere( $queryBuilder->expr()->eq('deleted', 0 ))
            ->setMaxResults(1)  ;

        $row = $roomQuery->execute()->fetch() ;
        if ( ! $row ) {
            return false ;
        }
        /** @var \JV\Jvchat\Domain\Model\Room $room */
        $room = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('JV\\Jvchat\\Domain\\Model\\Room');
        $room->fromArray($row);
        return $room;

	}

	function isRoomFull($room) {
		if(!$room->maxusercount)
			return false;

		if($this->getUserCountOfRoom($room->uid) >= $room->maxusercount)
			return true;

		return false;

	}
	
	function getUserCountOfRoom($roomId = null, $getHidden = false) {

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_room_feusers_mm');

        $userQuery = $queryBuilder->count( '*' )->from('tx_jvchat_room_feusers_mm' )
            ->where( $queryBuilder->expr()->lte('tstamp', $queryBuilder->createNamedParameter( $this->getTime() , Connection::PARAM_INT )) )
        ;

        if($roomId && !is_array($roomId)) {
            $userQuery->andWhere($queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter( intval($roomId) , Connection::PARAM_INT )) ) ;
        }
        if($roomId && is_array($roomId)) {
            $userQuery->andWhere($queryBuilder->expr()->in('uid_local', $roomId ) ) ;
        }
        if( !$getHidden) {
            $userQuery->andWhere($queryBuilder->expr()->eq('invisible', 0 ) ) ;
        }
        return $userQuery->execute()->fetchColumn(0) ;
	}
	
	function getTime() {
		if(!$this->extCONF['serverTimeOffset']) {
            return time();
        }

		$time = strtotime($this->extCONF['serverTimeOffset'], time());
		if($time == -1  || !$time ) {
            return time();
        }
		return $time;
	}
	
	function putMessage($roomId, $msg, $style = 0, $user = NULL, $hidden = false, $cruser_id = 0, $tofeuserid = 0) {

		$userId = is_array($user) ? $user['uid'] : $user;

		$roomId = intval($roomId);
		$userId = intval($userId);

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_entry') ;

		$data = array(
			'crdate' => $this->getTime(),
			'tstamp' => $this->getTime(),
			'cruser_id' => $cruser_id,
			'feuser' => $userId ? $userId : '',
			'tofeuser' => $tofeuserid,
			'room' => $roomId,
			'entry' => $msg,
			'hidden' => ($hidden ? '1' : '0'),
			'style' => $style,
			'pid' => $this->extCONF['pids.']['entries'] ? $this->extCONF['pids.']['entries'] : 0,
			);
        $queryBuilder->insert('tx_jvchat_entry')->values($data)->execute() ;

	}




    /*  *************** handle entries  ******************************* */


	/**
     * @var mixed $room the Room as object
     * @var integer $id - latest Entry Uid if available
     * @var Integer $time - onyl entrys after a specific time
	  * @return Array all messages in this room after $id
	  */
	function getEntries($room, $id = 0 , $time = 0 ) {

        $max = max( 10 , $this->extCONF['maxGetEntries'] ) ;
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_entry') ;
        $queryBuilder->select('*')->from('tx_jvchat_entry')
            ->where( $queryBuilder->expr()->eq('room', $queryBuilder->createNamedParameter( intval($room->uid) , Connection::PARAM_INT )) )
            ->orderBy("crdate")
            ->addOrderBy("uid")
            ->setMaxResults($max)
        ;
        if ( $id > 0 ) {
            $queryBuilder->andWhere($queryBuilder->expr()->gt('uid', $queryBuilder->createNamedParameter( intval($id) , Connection::PARAM_INT )) );
        }
        if ( $time > 0 ) {
            $queryBuilder->andWhere($queryBuilder->expr()->gte('crdate', $queryBuilder->createNamedParameter( intval($time) , Connection::PARAM_INT )) );
        }

        // get also Hidden Entries . as this is handled by Template if the user may seee them (private Messages from A to B )
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

       // $this->debugQuery($queryBuilder) ;
        $rows = $queryBuilder->execute() ;
        while ( $row = $rows->fetch() ) {
            /** @var \JV\Jvchat\Domain\Model\Entry $entry */
            $entry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('JV\\Jvchat\\Domain\\Model\\Entry');
            $entry->fromArray($row);
            $entries[] = $entry;
            if ( count( $entries) > $max ) {
                return $entries;
            }
        }
        return $entries;

	}
	
	function getEntriesAfterTime($room, $time) {

		$time = intval($time);

		return $this->getEntries($room, 0 , $time )  ;

	}
	
	function getLatestEntryId($room, $time) {

		$time = intval($time);
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_entry') ;
        $queryBuilder->select('*')->from('tx_jvchat_entry')
            ->where( $queryBuilder->expr()->eq('room', $queryBuilder->createNamedParameter( intval($room->uid) , Connection::PARAM_INT )) )
            ->orderBy("uid" , "ASC")
            ->andWhere($queryBuilder->expr()->gte('crdate', $queryBuilder->createNamedParameter( intval($time) , Connection::PARAM_INT )))
            ->setMaxResults(1)
        ;
        // get also Hidden Entries .
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $row = $queryBuilder->execute()->fetch() ;

        if( $row ) {
            return ( $row['uid'] - 1) ;
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_entry') ;
        $queryBuilder->select('*')->from('tx_jvchat_entry')
            ->where( $queryBuilder->expr()->eq('room', $queryBuilder->createNamedParameter( intval($room->uid) , Connection::PARAM_INT )) )
            ->orderBy("uid" , "ASC")
            ->setMaxResults(1)
        ;
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        // $this->debugQuery($queryBuilder) ;
        $row = $queryBuilder->execute()->fetch() ;
        if( $row ) {
            return ( $row['uid'] - 1) ;
        }
        return 0 ;
	}
	
	function makeSession($roomId, $name, $description = '', $hidden = 1, $start = -1, $end = -1) {

		$roomId = intval($roomId);

		if( ($start === -1) || ($end === -1) )
			return 'DB: Invalid parameters';

		if($start >= $end)
			return 'DB: firstId must be less than lastId';

		if(!is_numeric($start) || !is_numeric($end))
			return 'DB: firstId and lastId have to be integer values';

		$data = array(
			'startid'	=> $start,
			'endid' => $end,
			'crdate' => $this->getTime(),
			'tstamp' => $this->getTime(),
			'pid' => intval($this->extCONF['pids.']['sessions']),
			'name' => $name,
			'description' => $description,
			'hidden' => $hidden,
			'room' => $roomId
		);
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_session') ;
        $queryBuilder->insert('tx_jvchat_session')->values($data)->execute() ;


		return 'makesession success';
	}
	
	function getFeUserByName($username) {
        $username = trim(strip_tags($username));
		if(!$username) {
            return NULL;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_entry') ;
        $queryBuilder->select('*')->from('fe_users')
            ->where( $queryBuilder->expr()->eq('username',
                $queryBuilder->createNamedParameter( $username , Connection::PARAM_STR )) )
        ;

        return $queryBuilder->execute()->fetch() ;

	}



    /*  *************** handle entries  ******************************* */

	function deleteEntry($entryId) {
        $entryId = intval($entryId);

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_entry') ;
        $queryBuilder->update('tx_jvchat_entry')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($entryId , Connection::PARAM_INT ))
            )
            ->set('deleted', 1 )
            ->execute();

		return true;
	}

    /**
     * @param integer $time autodelete entries if tstamp is older than given value ( in Seconds )
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
	function deleteEntries( $time) {
		$time = $this->getTime()  - intval($time);
		/** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_entry') ;
		return $queryBuilder->delete('tx_jvchat_entry')
            ->where( $queryBuilder->expr()->lte('tstamp', $queryBuilder->createNamedParameter($time), Connection::PARAM_INT) )
            ->execute() ;
	}
	
	function getEntry($entryId , $asArray = false ) {

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_entry') ;
        $queryBuilder->select('*')->from('tx_jvchat_entry')
            ->where( $queryBuilder->expr()->eq('uid', 
                $queryBuilder->createNamedParameter( intval($entryId) , Connection::PARAM_INT )) )
        ;
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $rows = $queryBuilder->execute() ;

		if( $row = $rows->fetch() ) {
            if( $asArray ) {
                return $row ;
            }
            /** @var \JV\Jvchat\Domain\Model\Entry $entry */
            $entry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('JV\\Jvchat\\Domain\\Model\\Entry');
			$entry->fromArray($row);

			return $entry;
		}

		return NULL;
	}

	function commitMessage($entryId) {
        $entryId = intval($entryId);

		$entry = $this->getEntry($entryId , true ) ;
		if ( ! $entry ) {
		    return ;
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_entry') ;
        unset( $entry['uid'] ) ;
        $entry['hidden'] = 0 ;
        $entry['crdate'] = $this->getTime() ;
        $entry['tstamp'] = $this->getTime() ;
        $queryBuilder->insert('tx_jvchat_entry')->values($entry)->execute() ;

        $this->deleteEntry($entryId ) ;
        return ;

	}

    /*  *************** handle entries  ******************************* */



    /*  *************** users member status ******************************* */

    function getUserStatus($roomId, $userId, $statusLabel) {

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_room_feusers_mm') ;
        $queryBuilder->select('*')->from('tx_jvchat_room_feusers_mm')
            ->where( $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter( intval($roomId) , Connection::PARAM_INT )) )
            ->andWhere( $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter( intval($userId) , Connection::PARAM_INT )) ) ;
        //$this->debugQuery( $queryBuilder ) ;
        $row = $queryBuilder->execute()->fetch() ;
        if( $row && array_key_exists( $statusLabel , $row)) {
            return $row[$statusLabel];
        }
        return false;
    }

    /*  *************** users member status ******************************* */

    /**
     * We bann a user from this room by setting tstamp up to this time when he can enter this room again
     */
    function kickUser($roomId, $userId, $time = 30) {
        if(!$roomId || !$userId) {
            return false;
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_room_feusers_mm') ;

        $queryBuilder
            ->update('tx_jvchat_room_feusers_mm')
            ->where(
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter(intval($roomId) , Connection::PARAM_INT ))
            )->andWhere(
                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter(intval($userId) , Connection::PARAM_INT ))
            )
            ->set('tstamp', ($this->getTime()+($time*60)) )
            ->execute();

        return true;
    }

    function banUser($room, $userId) {
        if(!$room || !$userId) {
            return false;
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_room') ;
        $banned = $room->bannedusers ? ($room->bannedusers.','.$userId) : $userId;

        $queryBuilder
            ->update('tx_jvchat_room')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(intval($room->uid) , Connection::PARAM_INT ))
            )
            ->set('bannedusers', (\TYPO3\CMS\Core\Utility\GeneralUtility::uniqueList($banned)) )
            ->execute();

        return true;
    }

    /**
     * Revive user by setting a proper timestamo
     */
    function redeemUser($roomId, $userId) {

        if(!$roomId || !$userId) {
            return false;
        }

        $roomId = intval($roomId);
        $userId = intval($userId);

        $room = $this->getRoom($roomId);

        // is banned? remove from banned list
        $bannedusers = \TYPO3\CMS\Core\Utility\GeneralUtility::rmFromList($userId, $room->bannedusers);

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_room') ;

        $queryBuilder
            ->update('tx_jvchat_room')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter( $roomId , Connection::PARAM_INT ))
            )
            ->set('bannedusers', $bannedusers )
            ->execute();

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_room_feusers_mm') ;

        // if user was kicked, update time
        $queryBuilder
            ->update('tx_jvchat_room_feusers_mm')
            ->where(
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter(intval($roomId) , Connection::PARAM_INT ))
            )->andWhere(
                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter(intval($userId) , Connection::PARAM_INT ))
            )
            ->set('tstamp', $this->getTime() )
            ->execute();

        return true;
    }

    /*  *************** users member status ******************************* */
    /*  *************** users member Ship ******************************* */


	function makeExpert($room, $userId) {
        return $this->changeRoomMembership( $room , $userId , 'experts' , true   ) ;
	}

	function makeUser($room, $userId) {
        return $this->changeRoomMembership( $room , $userId , 'experts' , false   ) ;
    }

	function addMemberToRoom($room, $userId) {
        return $this->changeRoomMembership( $room , $userId , 'members' , true   ) ;
    }

    /**
     * @param mixed $room Room as object
     * @param integer $userId Uid Of the user
     * @param string $field Field nam : "experts" "moderators" or Members"
     * @param bool $add
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
	function changeRoomMembership( $room , $userId , $field , $add=true   ) {
        $userId = intval($userId);

	    $list = $room->$field;

        if ( $add ) {
            $newList = GeneralUtility::uniqueList($list.','.$userId );
        } else {
            $newList = GeneralUtility::rmFromList($userId, $list);
        }

        if($newList != $list) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_room') ;
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

            $queryBuilder->update('tx_jvchat_room')
                ->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(intval($room->uid) , Connection::PARAM_INT ))
                )
                ->set( $field , $newList ) ;
            // $this->debugQuery($queryBuilder) ;
            return $queryBuilder->execute();

        }
        return 0;
    }
	/*  *************** users member Ship ******************************* */

    /*  *************** handle rooms ******************************* */

	function cleanUpAllRooms($time = 0) {
		$rooms = $this->getRooms();

		$result = 0;

		foreach($rooms as $room) {
			$result = $result + $this->cleanUpRoom($room, $time);
		}
		return $result;
	}
	
	/**
	  * @param int Page ID (optional), if not set it returns all rooms
	  * @return Array Room
	  */

	function getRooms($pidList = NULL) {
		$rooms_public = $this->_getRooms($pidList, false);
		$rooms_private = $this->_getRooms($pidList, true);
		return array_merge($rooms_public, $rooms_private);
	}

    /**
     * @param mixed $pidList  String wirh IDs komma separated or NULL
     * @param bool $getPrivate
     * @param bool $isSuperuser
     * @param bool $getHidden
     * @return array
     */
	function _getRooms($pidList = NULL, $getPrivate = false, $isSuperuser = false, $getHidden = false) {

		$this->cleanUpRooms();

		if ( ! $pidList ) {
            $pidList = $this->extCONF['pids.']['rooms'] ;
        }
        $private = $getPrivate ? 1 : 0 ;

        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $this->connectionPool->getConnectionForTable('tx_jvchat_room')->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $expr = $queryBuilder->expr();
        $queryBuilder->select('*')
            ->from('tx_jvchat_room')
            ->orderBy('sorting', 'ASC')
            ->where(
                $expr->eq('deleted', $queryBuilder->createNamedParameter( 0 , Connection::PARAM_INT))
            )
         ;

        if(!$isSuperuser) {
            $queryBuilder->andWhere(
                $expr->eq('private', $queryBuilder->createNamedParameter($private, Connection::PARAM_INT))
            ) ;
            if( !$getHidden ) {
                $queryBuilder->andWhere(
                    $expr->eq('hidden', $queryBuilder->createNamedParameter( 0 , Connection::PARAM_INT))
                ) ;
            }
        }

        if ( $pidList ) {
            $queryBuilder->andWhere(
                $expr->in('pid', $queryBuilder->createNamedParameter($pidList, Connection::PARAM_STR) )
            ) ;

        }

        $rows = $queryBuilder->execute() ;

		$rooms = array();
		while($row = $rows->fetch() ) {
            /** @var \JV\Jvchat\Domain\Model\Room $room */
            $room = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('JV\\Jvchat\\Domain\\Model\\Room');
			if (!$row) {
                return $rooms;
            }
			$room->fromArray($row);
			$rooms[] = $room;
		}
		return $rooms;

	}
	
	/**
	  * removes private rooms
	  */
	function cleanUpRooms() {
        $queryBuilder = $this->connectionPool->getConnectionForTable('tx_jvchat_room')->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $expr = $queryBuilder->expr();
        $queryBuilder->select('*')
            ->from('tx_jvchat_room')
            ->orderBy('sorting', 'ASC')
            ->where(
                $expr->eq('private', $queryBuilder->createNamedParameter( 1 , Connection::PARAM_INT))
            )
        ;
        $rows = $queryBuilder->execute() ;
        while($row = $rows->fetch() ) {
            // clean up, no messages and a longer idle time
            $this->cleanUpUserInRoom($row['uid'], $this->extCONF['maxAwayTime'], false);
            // look for private empty rooms
            $userCount = $this->getUserCountOfRoom($row['uid'], true);
            if( ($userCount == 0) && ( $this->extCONF['deletePrivateRoomsIfEmpty'] ) ) {
                $this->deleteRoom($row['uid']);
            }
        }
	}
	
	/**
	  * Removes all users from all rooms if they idle for 30 seconds
	  */
	function cleanUpUserInRoom($roomId, $idle = 15, $systemMessageOnLeaving = true, $leaveMessage = '%s leaves chat.', $removeSystemMessagsOlderThan = 60) {

		if(!$roomId) {
            return NULL;
        }

		$roomId = intval($roomId);
		$idle = intval($idle);
		$removeSystemMessagsOlderThan = intval($removeSystemMessagsOlderThan);

        /** @var  \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $this->select_mm_query(
            'fe_users.*,tx_jvchat_room_feusers_mm.invisible as invisible, tx_jvchat_room.moderators as moderator , tx_jvchat_room.experts as experts' ,
            'tx_jvchat_room',
            'tx_jvchat_room_feusers_mm',
            'fe_users',
            $roomId ) ;

        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $expr = $queryBuilder->expr();
        $queryBuilder->andWhere($expr->lte('tx_jvchat_room_feusers_mm.tstamp' , ($this->getTime()-$idle) )) ;

        /** @var \Doctrine\DBAL\Driver\Statement $rows */
        $rows = $queryBuilder->execute() ;
        // $this->debugQuery( $queryBuilder ) ;
        $users = array();
        while (($row = $rows->fetch()) != null) {
            $this->leaveRoom($roomId, $row['uid'], $systemMessageOnLeaving, $leaveMessage);
        }
        if($removeSystemMessagsOlderThan) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable( 'tx_jvchat_entry' ) ;
            $expr = $queryBuilder->expr();
            $queryBuilder->delete('tx_jvchat_entry')
                ->where( $expr->lt( 'crdate', intval($this->getTime()-$removeSystemMessagsOlderThan) ) )
                ->andWhere ( $expr->eq( 'feuser', 0 ))->execute() ;
            $queryBuilder->delete('tx_jvchat_entry')
                ->where( $expr->lt( 'crdate', intval($this->getTime()-$removeSystemMessagsOlderThan) ) )
                ->andWhere ( $expr->eq( 'cruser_id', 0 ))->execute() ;

        }

	}
	
	function leaveRoom($roomId, $userId, $systemMessageOnLeaving = true, $leaveMessage = '%s leaves chat') {

		if(!$roomId || !$userId)
			return false;

		$roomId = intval($roomId);
		$userId = intval($userId);

		// do not delete kicked users
		if($this->isUserKicked($roomId, $userId))
			return false;

		if($systemMessageOnLeaving && !$this->getUserStatus($roomId, $userId, 'invisible') && $this->getUserStatus($roomId, $userId, 'in_room')) {

		    // ToDo 2029 : J.V.  make "$systemMessageOnLeaving"  configurable  ?
			//	$this->putMessage($roomId, sprintf($leaveMessage, $user['username']));
		}

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_room_feusers_mm') ;

        $queryBuilder
            ->update('tx_jvchat_room_feusers_mm')
            ->where(
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter($roomId , Connection::PARAM_INT ))
            )->andWhere(
                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($userId , Connection::PARAM_INT ))
            )
            ->set('in_room', 0 )
            ->execute();

        // definitely delete unnecessary  entries
        $idle = 60 * $this->extCONF['maxAwayTime'];
        $queryBuilder
            ->update('tx_jvchat_room_feusers_mm')
            ->where(
                $queryBuilder->expr()->lt('tstamp', $queryBuilder->createNamedParameter( $this->getTime()-$idle , Connection::PARAM_INT ))
            )->execute() ;



		return NULL;

	}
	
	function isUserKicked($roomId, $userId) {

		if(!$userId || !$roomId)
			return false;

		$roomId = intval($roomId);
		$userId = intval($userId);

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_room_feusers_mm') ;
        $queryBuilder->select('*')->from('tx_jvchat_room_feusers_mm')
            ->where( $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter( intval($roomId) , Connection::PARAM_INT )) )
            ->andWhere( $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter( intval($userId) , Connection::PARAM_INT )) )
            ->andWhere( $queryBuilder->expr()->gt('tstamp', $queryBuilder->createNamedParameter( intval($this->getTime()) , Connection::PARAM_INT )) ) ;

        // $this->debugQuery( $queryBuilder ) ;
        $row = $queryBuilder->execute()->fetch() ;
        if( $row ) {
            return round(($row['tstamp'] - $this->getTime()) / 60);
        }

		return false;
	}
	
	function deleteRoom($roomId) {
        $roomId = intval($roomId);

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_room') ;

        return $queryBuilder
            ->update('tx_jvchat_room')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(intval($roomId) , Connection::PARAM_INT ))
            )
            ->set('deleted', 1 )
            ->execute();

	}
	
	/** Removes all entries that are not in a session and all entries that are marked hidden or deleted
	  * @param integer $room
	  * @param integer $time Delete only entries that are older than time
	  * @return integer amount of deleted rows
	  */

	function cleanUpRoom($room, $time = 0) {

		// get sessions of all rooms
		$sessions = $this->getSessions();
		$time = intval($time);

		// get entries of all sessions
		$entries = array();
		foreach($sessions as $session) {
			$sessionEntries = $this->getEntriesOfSession($session);
			foreach($sessionEntries as $sessionEntry)
				$entries[] = $sessionEntry->uid;
		}

		// this is a list of entry uids that should not be deleted
		if(count($entries))
			$list = '('.implode(',',$entries).')';
		else
			$list = '(0)';

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable( 'tx_jvchat_entry' ) ;
        $queryBuilder->getRestrictions()->removeAll() ;

        $expr = $queryBuilder->expr();
        $queryBuilder->delete('tx_jvchat_entry')
            ->where( $expr->notIn( 'uid', $queryBuilder->createNamedParameter($list , Connection::PARAM_STR )  ) )
            ->orWhere( $expr->eq( 'hidden', $queryBuilder->createNamedParameter(1 , Connection::PARAM_INT )  ) )
            ->orWhere( $expr->eq( 'deleted', $queryBuilder->createNamedParameter(1 , Connection::PARAM_INT )  ) )
            ->andWhere( $expr->eq( 'room', $queryBuilder->createNamedParameter(intval($room->uid) , Connection::PARAM_INT ) ) )
            ->andWhere( $expr->lt( 'tstamp', $queryBuilder->createNamedParameter(intval(($this->getTime() - $time)) , Connection::PARAM_INT )  ))

        ;

        // $this->debugQuery( $queryBuilder ) ;
        return $queryBuilder->execute() ;

	}

	function getSessions() {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_session') ;

        $queryBuilder
            ->select('*')
            ->from('tx_jvchat_session')
            ->orderBy('sorting') ;

        $rows = $queryBuilder->execute() ;

        $sessions = array();
        while($row = $rows->fetch() ) {
            /** @var \JV\Jvchat\Domain\Model\Session $session */
            $session = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('JV\\Jvchat\\Domain\\Model\\Session');
            $session->fromArray($row);
            $sessions[] = $session;
        }

        return $sessions;

	}
	
	function setUserStatus($room, $user, $statusLabel) {
		// $users = $this->getFeUsersOfRoom($room, true);

		$newStatus = 0;

		switch($statusLabel) {
			case 'hidden':
				$status = $this->getUserStatus($room->uid, $user['uid'], 'invisible');
				$newStatus = $status ? '0' : '1';
                break;
            default:
                return false ;
		}

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_room_feusers_mm') ;

        $queryBuilder
            ->update('tx_jvchat_room_feusers_mm')
            ->where(
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter(intval($room->uid ), Connection::PARAM_INT ))
            )->andWhere(
                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter(intval($user['uid']) , Connection::PARAM_INT ))
            )
            ->set('invisible', $newStatus ) ;
         // $this->debugQuery( $queryBuilder ) ;

        return $queryBuilder->execute();

	}

	function getFeUsersOfRoom($room, $getHidden = false) {

		if(!$room) {
			return NULL;
		}

        return  $this->getUserList($room->uid, false  , true , $getHidden  ) ;

	}
	
	function getOnlineUsers($roomId, $getHidden = false) {

		if(!$roomId) {
            return NULL;
        }
        return  $this->getUserList($roomId, false  , true , $getHidden , array('moderators' , 'experts') ) ;
	}
	
	function getOnlineExperts($roomId, $getHidden = false) {
		if(!$roomId) {
            return NULL;
        }

        return  $this->getUserList($roomId, 'tx_jvchat_room.experts' , true , $getHidden) ;
	}
	
	function getOnlineModerators($roomId, $getHidden = false) {
		if(!$roomId) {
            return NULL;
        }
        return $this->getUserList($roomId, 'tx_jvchat_room.moderators' , true , $getHidden) ;
	}

    /**
     * @param integer $roomId
     * @param mixed $userType
     * @param bool $inroom
     * @param bool $getHidden
     * @param mixed $notuserType array of fields that user should not be in
     * @return array
     */
    function getUserList($roomId, $userType = false , $inroom = true , $getHidden = false , $notuserTypeArray = false ) {
        $roomId = intval($roomId);

        /** @var  \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $this->select_mm_query(
            'fe_users.*,tx_jvchat_room_feusers_mm.invisible as invisible,tx_jvchat_room_feusers_mm.userlistsnippet as userlistsnippet, tx_jvchat_room.moderators as moderator , tx_jvchat_room.experts as experts' ,
            'tx_jvchat_room',
            'tx_jvchat_room_feusers_mm',
            'fe_users',
            $roomId ) ;

        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $expr = $queryBuilder->expr();
        $queryBuilder->andWhere($expr->lte('tx_jvchat_room_feusers_mm.tstamp' , $this->getTime() )) ;

        if( $userType ) {
            $queryBuilder->andWhere( $expr->inSet(  $userType , 'fe_users.uid' )) ;
        }

        if( $inroom ) {
            $queryBuilder->andWhere($expr->eq('tx_jvchat_room_feusers_mm.in_room' , 1 )) ;
        }
        /** @var \Doctrine\DBAL\Driver\Statement $rows */
        $rows = $queryBuilder->execute() ;
        // $this->debugQuery( $queryBuilder ) ;
        $users = array();
        while (($row = $rows->fetch()) != null) {
            if($row['invisible'] && !$getHidden)
                continue;

            $skip = false ;
            if ( is_array($notuserTypeArray)) {
                foreach($notuserTypeArray as $notuserType ) {
                    $userType = GeneralUtility::trimExplode( "," , $row[$notuserType ] ) ;
                    if( in_array( $row['uid'] , $userType )) {
                        $skip = true  ;;
                    }
                }
            }
            if ( $skip ) {
                continue ;
            }
            $users[$row['uid']] = $row;
        }
        return $users;
    }


    /** this function as a partly replacement for OLD exec_SELECT_mm_query
     * it will return a prepared queryBuilder with the needed selects
     * if you need more Where
     *
     * @param string $fields  ( foreignTable.* , localTable.fieldName AS aliasField , any)
     * @param string $foreign   TableName of Foreign Table
     * @param string $mm        TableName of mm Table
     * @param string $local     TableName ol Local Table
     * @param integer $itemUid  UID of the Item
     * @param integer $maxItems  max Items if set
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */

	function select_mm_query($fields , $foreign , $mm , $local , $itemUid , $maxItems=0 ){
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable( $foreign ) ;
        $expr = $queryBuilder->expr();

        $fieldsArray = GeneralUtility::trimExplode("," , $fields ) ;
        foreach ( $fieldsArray as $key =>  $field ) {
            if( $key == 0 ) {
                $queryBuilder = $queryBuilder->select($field) ;
            } else {
                $queryBuilder = $queryBuilder->addSelect($field) ;
            }
        }
/*
        $queryBuilder->from($foreign)
            ->leftJoin( $foreign , $mm , $mm , $expr->eq($foreign . '.uid', $mm . '.uid_local') )
            ->leftJoin( $mm , $local , $local , $expr->eq($mm . '.uid_foreign', $local . '.uid') )
            ->where( $expr->eq($foreign .'.uid', $queryBuilder->createNamedParameter($itemUid , Connection::PARAM_INT )  ))
        ;
*/

       $queryBuilder
            ->from($foreign)
            ->from($mm)
            ->from($local)
            ->where ( $expr->eq($local .'.uid', $mm .'.uid_foreign'))
            ->andWhere ( $expr->eq($foreign . '.uid', $mm . '.uid_local'))
            ->andWhere ( $expr->eq($foreign .'.uid', $queryBuilder->createNamedParameter($itemUid , Connection::PARAM_INT ) )
            ) ;


        if ( $maxItems > 0 ) {
            $queryBuilder->setMaxResults($maxItems ) ;
        }
        return $queryBuilder ;

    }
	
	function setRoomStatus($room, $statusLabel) {

	    // $users = $this->getFeUsersOfRoom($room, true);

		$newStatus = array();

		switch($statusLabel) {
			case 'hidden':
				$status = $this->getRoomStatus($room->uid, 'hidden');
				$newStatus['hidden'] = $status ? '0' : '1';
				break;
			case 'private':
				$status = $this->getRoomStatus($room->uid, 'private');
				$newStatus['private'] = $status ? '0' : '1';
				break;
            default:
                // wrong label given so return
                return 0 ;
		}

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_room') ;
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $queryBuilder
            ->update('tx_jvchat_room')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($room->uid , Connection::PARAM_INT ))
            )
            ->set($statusLabel, $newStatus[$statusLabel] ) ;

        // $this->debugQuery($queryBuilder) ;

        $result = $queryBuilder->execute();

		if($result) {
            return $newStatus[$statusLabel] ? 'on' : 'off';
		}

		return 0;

	}
	
	function getRoomStatus($roomId, $statusLabel) {

		$roomId = intval($roomId);

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_room') ;
        // get also Hidden Entries !
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $rows = $queryBuilder
            ->select($statusLabel)
            ->from('tx_jvchat_room')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter((intval($roomId)) , Connection::PARAM_INT ))
            )
            ->setMaxResults(1)
            ->execute();


        return $rows->fetchColumn(0) ;
	}
	
	function setMessageStyle($user, $style) {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('fe_users') ;

        return $queryBuilder
            ->update('fe_users')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter((intval($user['uid'])) , Connection::PARAM_INT ))
            )
            ->set('tx_jvchat_chatstyle', $style  )
            ->execute();

	}
	
	function setUserlistSnippet($roomId, $userId, $snippet) {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_room_feusers_mm') ;

        return $queryBuilder
            ->update('tx_jvchat_room_feusers_mm')
            ->where(
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter((intval($roomId)) , Connection::PARAM_INT ))
            )->andWhere(
                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter((intval($userId)) , Connection::PARAM_INT ))
            )
            ->set('userlistsnippet',$snippet  )
            ->execute();
	}
		
	function setTooltipSnippet($roomId, $userId, $snippet) {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_room_feusers_mm') ;

        return $queryBuilder
            ->update('tx_jvchat_room_feusers_mm')
            ->where(
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter((intval($roomId)) , Connection::PARAM_INT ))
            )->andWhere(
                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter((intval($userId)) , Connection::PARAM_INT ))
            )
            ->set('tooltipsnippet', $snippet  )
            ->execute();
	}
	
	function getSnippets($roomId, $userId) {

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_jvchat_room_feusers_mm') ;

        $rows = $queryBuilder
            ->select('userlistsnippet','tooltipsnippet')
            ->from('tx_jvchat_room_feusers_mm')
            ->where(
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter((intval($roomId)) , Connection::PARAM_INT ))
            )->andWhere(
                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter((intval($userId)) , Connection::PARAM_INT ))
            )
            ->setMaxResults(1)
            ->execute();


		return $rows->fetch() ;
	}

    function debugQuery($query) {
        // new way to debug typo3 db queries

        if( ( method_exists( $query , 'getSQL') )) {
            $querystr = $query->getSQL() ;
            $queryParams = $query->getParameters() ;
        } else  {
            $queryParser = GeneralUtility::makeInstance( Typo3DbQueryParser::class);
            $querystr = $queryParser->convertQueryToDoctrineQueryBuilder($query)->getSQL() ;
            $queryParams = $queryParser->convertQueryToDoctrineQueryBuilder($query)->getParameters() ;
        }

        echo $querystr ;
        echo "<hr>" ;

        var_dump($queryParams);
        echo "<hr><b>Result:</b><br><br>" ;

        foreach ($queryParams as $key => $value ) {
            $search[] = ":" . $key ;
            $replace[] = "'$value'" ;

        }
        echo str_replace( $search , $replace , $querystr ) ;

        die;
    }



}


<?php
namespace JV\Jvchat\Domain\Model;

// was class tx_jvchat_room

class Room {

	function __construct() {

	}

	function fromArray($array) {
		$this->uid = intval($array['uid']);
		$this->pid = intval($array['pid']);
		$this->hidden = $array['hidden'] ?  true : false;
		$this->fe_group = $array['fe_group'];

		$this->name = $array['name'];
		$this->description = $array['description'];
		$this->closed = $array['closed'];
		$this->showfullnames = $array['showfullnames'];
		$this->mode = $array['mode'];
		$this->maxusercount = $array['maxusercount'];
		$this->moderators = $array['moderators'];
		$this->owner = $array['owner'];
		$this->experts = $array['experts'];
		$this->groupaccess = $array['groupaccess'];
		$this->superusergroup = $array['superusergroup'];
		$this->bannedusers = $array['bannedusers'];
		$this->showuserinfo_experts = $array['showuserinfo_experts'];
		$this->showuserinfo_moderators = $array['showuserinfo_moderators'];
		$this->showuserinfo_users = $array['showuserinfo_users'];
		$this->showuserinfo_superusers = $array['showuserinfo_superusers'];
		$this->welcomemessage = $array['welcomemessage'];
		$this->private = $array['private'] ? true : false;
		$this->members = $array['members'];
		$this->notifyme = $array['notifyme'];
		$this->page = $array['page'];
		$this->image = $array['image'];
		$this->enableEmoticons = $array['enableEmoticons'];
		$this->enableTime = $array['enableTime'];
		$this->imageUpload = $array['imageUpload'];

	}

	function toArray() {

		$theValue = array(
			'uid' => intval($this->uid),
			'pid' => intval($this->pid),
			'hidden' => $this->hidden ? 1 : 0,
			'fe_group' => $this->fe_group,
			'name' => $this->name,
			'description' => $this->description,
			'closed' => $this->closed,
			'showfullnames' => $this->showfullnames,
			'mode' => $this->mode,
			'maxusercount' => $this->maxusercount,
			'owner' => $this->owner,
			'moderators' => $this->moderators,
			'experts' => $this->experts,
			'groupaccess' => $this->groupaccess,
			'superusergroup' => $this->superusergroup,
			'bannedusers' => $this->bannedusers,
			'showuserinfo_experts' => $this->showuserinfo_experts,
			'showuserinfo_moderators' => $this->showuserinfo_moderators,
			'showuserinfo_users' => $this->showuserinfo_users,
			'showuserinfo_superusers' => $this->showuserinfo_superusers,
			'welcomemessage' => $this->welcomemessage,
			'private' => $this->private ? 1 : 0,
			'members' => $this->members,
			'notifyme' => $this->notifyme,
			'page' => $this->page,
			'image' => $this->image,
			'enableEmoticons' => $this->enableEmoticons,
			'enableTime' => $this->enableTime,
			'imageUpload' => $this->imageUpload

		);

		return $theValue;
	}

	function isExpertMode() {
		return ($this->mode == 1);
	}

	function isClosed() {
		return ($this->closed == 1);
	}

	function isPrivate() {
		return ($this->private == 1);
	}

	function showFullNames() {
		return $this->showfullnames;
	}

	function showDetailOf($type, $what) {
		switch($type) {
			case 'user':
				return \TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->showuserinfo_users, $what);
			case 'expert':
				return \TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->showuserinfo_experts, $what);
			case 'moderator':
				return \TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->showuserinfo_moderators, $what);
			case 'superuser':
				return \TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->showuserinfo_superusers, $what);
			default:
				return false;
		}
	}

	function getDetailsField($type) {
		switch($type) {
			case 'user':
				return \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',',$this->showuserinfo_users);
			case 'expert':
				return \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',',$this->showuserinfo_experts);
			case 'moderator':
				return \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',',$this->showuserinfo_moderators);
			case 'superuser':
				return \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',',$this->showuserinfo_superusers);
			default:
				return array();
		}
	}

	var $uid;

	var $pid;

	var $hidden;

	var $fe_group;

	var $name;

	var $description;

	var $welcomemessage;

	var $closed;

	var $mode;

	var $showfullnames;

	var $maxusercount;

	var $owner;

	var $moderators;

	var $experts;

	var $groupaccess;

	var $superusergroup;

	var $sessions;

	var $bannedusers;
	
	var $showuserinfo_experts;

	var $showuserinfo_moderators;

	var $showuserinfo_users;

	var $showuserinfo_superusers;

	var $private;
	
	var $members;
	var $notifyme = '' ;

	var $page;
	
	var $image;

	var $enableEmoticons;
	var $enableTime;
	var $imageUpload ;

/*	function getModeratorIDs() {
		$moderators = array();

		foreach ($this->moderators as $moderator)
			$moderators[] = $moderator['uid'];

		return implode(',',$moderators);
				
	}	
*/
}

<?php
namespace JVelletti\Jvchat\Domain\Model;

class Entry {

	function __construct() {

	}

	function fromArray($array): void {
		$this->uid = $array['uid'];
		$this->crdate = $array['crdate'];
		$this->tstamp = $array['tstamp'];
		$this->entry = $array['entry'];
		$this->feuser = $array['feuser'];
		$this->tofeuserid = $array['tofeuser'];
		$this->room = $array['room'];
		$this->hidden = $array['hidden'];
		$this->deleted = $array['deleted'];
		$this->style = $array['style'];

	}

	function toArray() {

		$theValue = array(
			'uid' => $this->uid,
			'crdate' => $this->crdate,
			'tstamp' => $this->tstamp,
			'entry' => $this->entry,
			'feuser' => $this->feuser,
			'tofeuser' => $this->tofeuserid,
			'room' => $this->room,
			'hidden' => $this->hidden,
			'deleted' => $this->deleted,
			'style' => $this->style,

		);

		return $theValue;
	}

	function isPrivate() {
		return ($this->tofeuserid > 0);
	}

	function toString() {

	}

	var int $uid;

	var $entry;

	var int $crdate;

	var int $tstamp;

	var $feuser;

	var int $tofeuserid;

	var $room;

	var int $hidden;
	
	var int $deleted;

	var $style;

}

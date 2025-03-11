<?php
namespace JVelletti\Jvchat\Domain\Model;

// was : class tx_jvchat_session {
class Session {

	function __construct() {
	}

	function fromArray($array) {
		$this->uid = intval($array['uid']);
		$this->name = $array['name'];
		$this->description = $array['description'];
		$this->public = $array['hidden'];
		$this->room = $array['room'];
		$this->startid = intval($array['startid']);
		$this->endid = intval($array['endid']);
	}

	function toArray() {

		$theValue = array(
			'uid' => intval($this->uid),
			'name' => $this->name,
			'description' => $this->description,
			'hidden' => $this->public,
			'room' => $this->room,
			'startid' => intval($this->startid),
			'endid' => intval($this->endid),
		);

		return $theValue;
	}

	var $uid;

	var $name;

	var $description;

	var $public;

	var $room;

	var $startid;

	var $endid;
}

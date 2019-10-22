#
# Table structure for table 'tx_vjchat_entry'
#
CREATE TABLE tx_vjchat_entry (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted SMALLINT(4) DEFAULT '0' NOT NULL,
	hidden SMALLINT(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	entry text DEFAULT '' NOT NULL,
	feuser INT(11) NOT NULL,
	tofeuser int(11) DEFAULT '0' NOT NULL,	
	room INT(11) NOT NULL,
  style tinyint(4) unsigned DEFAULT '0' NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid),
	INDEX room (room, crdate)

);




#
# Table structure for table 'tx_vjchat_room'
#
CREATE TABLE tx_vjchat_room (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,	
	deleted SMALLINT(4) DEFAULT '0' NOT NULL,
	hidden SMALLINT(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	name tinytext DEFAULT '' NOT NULL,
	description text DEFAULT '' NOT NULL,
	maxusercount int(11) DEFAULT '0' NOT NULL,
	closed SMALLINT(3) DEFAULT '0' NOT NULL,
 	mode SMALLINT(3) DEFAULT '0' NOT NULL,
	showfullnames SMALLINT(3) DEFAULT '0' NOT NULL,
	moderators blob NOT NULL,
    experts blob NOT NULL,
    groupaccess blob NOT NULL,
	superusergroup  int(11) NOT NULL,
    bannedusers blob NOT NULL,
    welcomemessage text DEFAULT '' NOT NULL,
    showuserinfo_experts blob NOT NULL,
    showuserinfo_moderators blob NOT NULL,
    showuserinfo_users blob NOT NULL,
	showuserinfo_superusers blob NOT NULL,
	page  int(11) NOT NULL,
	image blob NOT NULL,
	owner int(11) DEFAULT '0' NOT NULL,
	private SMALLINT(4) DEFAULT '0' NOT NULL,
	members blob NOT NULL,	
	PRIMARY KEY (uid),
	KEY parent (pid)
);


#
# Table structure for table 'tx_vjchat_session'
#
CREATE TABLE tx_vjchat_session (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
	  sorting int(11) unsigned DEFAULT '0' NOT NULL,
    deleted SMALLINT(4) DEFAULT '0' NOT NULL,
    hidden SMALLINT(4) DEFAULT '0' NOT NULL,
    starttime int(11) DEFAULT '0' NOT NULL,
    endtime int(11) DEFAULT '0' NOT NULL,
    name tinytext DEFAULT '' NOT NULL,
    description text DEFAULT '' NOT NULL,
    startid int(11) DEFAULT '0' NOT NULL,
    endid int(11) DEFAULT '0' NOT NULL,
    room int(11) DEFAULT '0' NOT NULL,
    
    PRIMARY KEY (uid),
    KEY parent (pid)
);


CREATE TABLE tx_vjchat_room_feusers_mm (
  uid_local int(11) unsigned DEFAULT '0' NOT NULL,
  uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) DEFAULT '0' NOT NULL,
  tablenames varchar(30) DEFAULT '' NOT NULL,
  invisible SMALLINT(4) unsigned DEFAULT '0' NOT NULL,
  in_room SMALLINT(4) unsigned DEFAULT '0' NOT NULL,
  userlistsnippet text DEFAULT '' NOT NULL,
  tooltipsnippet text DEFAULT '' NOT NULL,
  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (
    tx_vjchat_chatstyle tinyint(4) unsigned DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_vjchat_messages (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted SMALLINT(4) DEFAULT '0' NOT NULL,
	hidden SMALLINT(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	entry text DEFAULT '' NOT NULL,
	feuser  int(11) NOT NULL,
	tofeuser int(11) DEFAULT '0' NOT NULL,
	room  int(11) NOT NULL,
  style SMALLINT(4) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY starttime (starttime)
);


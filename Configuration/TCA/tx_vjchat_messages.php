<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');
return Array (
    "ctrl" => Array (
        "title" => "LLL:EXT:vjchat/Resources/Private/Language/locallang_db.xlf:tx_vjchat_messages",
        "label" => "entry",
        "tstamp" => "tstamp",
        "crdate" => "crdate",
        "cruser_id" => "cruser_id",
        "default_sortby" => "ORDER BY tstamp DESC",
        "delete" => "deleted",
        "enablecolumns" => Array (
            "disabled" => "hidden",
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ),
        "iconfile" => "EXT:vjchat/Resources/Public/Icons/icon_tx_vjchat_messages.svg",
    ),
    "feInterface" => Array (
        "fe_admin_fieldList" => "hidden, entry, feuser, room, style",
    ),
    "interface" => Array (
        "showRecordFieldList" => "hidden,starttime,entry,room"
    ),
    "columns" => Array (
        "hidden" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:lang/locallang_general.xlf:LGL.hidden",
            "config" => Array (
                "type" => "check",
                "default" => "0"
            )
        ),
        "entry" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:vjchat/Resources/Private/Language/locallang_db.xlf:tx_vjchat_entry.entry",
            "config" => Array (
                "type" => "text",
                "eval" => "required",
            )
        ),
        'starttime' => array(
            'l10n_mode' => 'exclude',
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
            'config' => array(
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'size' => 13,
                'eval' => 'datetime',
                'checkbox' => 0,
                'default' => 0,
            ),
        ),

        "style" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:vjchat/Resources/Private/Language/locallang_db.xlf:tx_vjchat_entry.style",
            "config" => Array (
                "type" => "input",
                "size" => "30",
            )
        ),
        "feuser" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:vjchat/Resources/Private/Language/locallang_db.xlf:tx_vjchat_entry.feuser",
            "config" => Array (
                "type" => "group",
                "internal_type" => "db",
                "allowed" => "fe_users",
                "size" => 1,
                "minitems" => 0,
                "maxitems" => 1,
            )
        ),
        "room" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:vjchat/Resources/Private/Language/locallang_db.xlf:tx_vjchat_entry.room",
            "config" => Array (
                "type" => "group",
                "internal_type" => "db",
                "allowed" => "tx_vjchat_room",
                "size" => 1,
                "minitems" => 0,
                "maxitems" => 1,
            )
        ),
    ),
    "types" => Array (
        "0" => Array("showitem" => "hidden, entry,starttime, room")
    ),
    "palettes" => Array (
        "1" => Array("showitem" => "")
    )
);
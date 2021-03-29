<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');
return Array (
    "ctrl" => Array (
        "title" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_entry",
        "label" => "entry",
        "tstamp" => "tstamp",
        "crdate" => "crdate",
        "cruser_id" => "cruser_id",
        "default_sortby" => "ORDER BY tstamp DESC",
        "delete" => "deleted",
        "enablecolumns" => Array (
            "disabled" => "hidden",
        ),
        "iconfile" => "EXT:jvchat/Resources/Public/Icons/icon_tx_jvchat_entry.svg",
    ),
    "feInterface" => Array (
        "fe_admin_fieldList" => "hidden, entry, feuser, room, style",
    ),
    "interface" => Array (
        "showRecordFieldList" => "hidden,entry,feuser,room,style"
    ),
    "columns" => Array (
        "hidden" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden",
            "config" => Array (
                "type" => "check",
                "default" => "0"
            )
        ),
        "entry" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_entry.entry",
            "config" => Array (
                "type" => "text",
                "eval" => "required",
            )
        ),
        "style" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_entry.style",
            "config" => Array (
                "type" => "input",
                "size" => "30",
            )
        ),
        "feuser" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_entry.feuser",
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
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_entry.room",
            "config" => Array (
                "type" => "group",
                "internal_type" => "db",
                "allowed" => "tx_jvchat_room",
                "size" => 1,
                "minitems" => 0,
                "maxitems" => 1,
            )
        ),
    ),
    "types" => Array (
        "0" => Array("showitem" => "hidden, entry, style, feuser, room")
    ),
    "palettes" => Array (
        "1" => Array("showitem" => "")
    )
);

<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');
return array(
    "ctrl" => Array (
        "title" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_session",
        "label" => "name",
        "tstamp" => "tstamp",
        "crdate" => "crdate",
        "cruser_id" => "cruser_id",
        "sortby" => "sorting",
        "default_sortby" => "ORDER BY crdate",
        "delete" => "deleted",
        "enablecolumns" => Array (
            "disabled" => "hidden",
        ),
        "iconfile" => "EXT:jvchat/Resources/Public/Icons/icon_tx_jvchat_session.svg",
    ),
    "interface" => Array (
        "showRecordFieldList" => "hidden,starttime,endtime,name,description,startid,endid,room"
    ),

    "feInterface" => Array (
        "fe_admin_fieldList" => "hidden, name, description, startid, endid",
    ) ,
    "columns" => Array (
        "hidden" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden",
            "config" => Array (
                "type" => "check",
                "default" => "0"
            )
        ),
        "starttime" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime",
            "config" => Array (
                "type" => "input",
                "renderType" => "inputDateTime",
                "size" => "8",
                "eval" => "date",
                "default" => "0",
                "checkbox" => "0"
            )
        ),
        "endtime" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime",
            "config" => Array (
                "type" => "input",
                "renderType" => "inputDateTime",
                "size" => "8",
                "eval" => "date",
                "checkbox" => "0",
                "default" => "0",
            )
        ),
        "name" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_session.name",
            "config" => Array (
                "type" => "input",
                "size" => "30",
                "eval" => "required",
            )
        ),
        "description" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_session.description",
            "config" => Array (
                "type" => "text",
                "cols" => "30",
                "rows" => "5",
            )
        ),
        "startid" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_session.startid",
            "config" => Array (
                "type" => "group",
                "internal_type" => "db",
                "allowed" => 'tx_jvchat_entry',
                "size" => 1,
                "minitems" => 1,
                "maxitems" => 1,
                "required" => 1,
            )
        ),
        "endid" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_session.endid",
            "config" => Array (
                "type" => "group",
                "internal_type" => "db",
                "allowed" => 'tx_jvchat_entry',
                "size" => 1,
                "minitems" => 1,
                "maxitems" => 1,
                "required" => 1,
            )
        ),
        "room" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_session.room",
            "config" => Array (
                "type" => "select",
                'renderType' => 'selectSingle' ,
                "foreign_table" => "tx_jvchat_room",
                "foreign_table_where" => "ORDER BY tx_jvchat_room.name",
                "size" => 1,
                "minitems" => 0,
                "maxitems" => 1,
                "eval" => "required",
            )
        ),
    ),
    "types" => Array (
        "0" => Array("showitem" => "hidden, name, description, startid, endid, room")
    ),
    "palettes" => Array (
        "1" => Array("showitem" => "starttime, endtime")
    )



) ;
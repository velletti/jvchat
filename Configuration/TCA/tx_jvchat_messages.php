<?php
if (!defined ('TYPO3')) die ('Access denied.');
return Array (
    "ctrl" => Array (
        "title" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_messages",
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
        "iconfile" => "EXT:jvchat/Resources/Public/Icons/icon_tx_jvchat_messages.svg",
    ),
    "feInterface" => Array (
        "fe_admin_fieldList" => "hidden, entry, feuser, room, style",
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
        'starttime' => array(
            'l10n_mode' => 'exclude',
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
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
                "allowed" => "tx_jvchat_room",
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
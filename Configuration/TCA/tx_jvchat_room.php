<?php


use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;

if (!defined ('TYPO3')) die ('Access denied.');
$return = array (
    "ctrl" => Array (
        "title" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_room",
        "label" => "name",
        "tstamp" => "tstamp",
        "crdate" => "crdate",
        "cruser_id" => "cruser_id",
        "sortby" => "sorting",
        "default_sortby" => "ORDER BY crdate",
        "delete" => "deleted",
        "enablecolumns" => Array (
            "disabled" => "hidden",
            "starttime" => "starttime",
            "endtime" => "endtime",
            "fe_group" => "groupaccess",
        ),
        "iconfile" => "EXT:jvchat/Resources/Public/Icons/icon_tx_jvchat_room.svg",
    ),
    "feInterface" => Array (
        "fe_admin_fieldList" => "hidden, starttime, endtime, fe_group, name, description, closed, enable_emoticons, owner, moderators, experts, groupaccess, maxusercount, showfullnames, bannedusers, welcomemessage, showuserinfo_experts, showuserinfo_moderators, showuserinfo_users, showuserinfo_superusers, members, private, page",
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
        "fe_group" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.fe_group",
            "config" => Array (
                "type" => "select",
                'renderType' => 'selectMultipleSideBySide' ,
                "items" => Array (
                    Array("", 0),
                    Array("LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hide_at_login", -1),
                    Array("LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.any_login", -2),
                    Array("LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.usergroups", "--div--")
                ),
                "foreign_table" => "fe_groups"
            )
        ),
        "name" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_room.name",
            "config" => Array (
                "type" => "input",
                "size" => "30",
                "eval" => "required",
            )
        ),
        "description" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_room.description",
            "config" => Array (
                "type" => "text",
                "cols" => "30",
                "rows" => "5",
            )
        ),
        "maxusercount" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_room.maxusercount",
            "config" => Array (
                "type" => "input",
                "size" => "4",
                "max" => "4",
                "eval" => "int",
                "checkbox" => "0",
                "range" => Array (
                    "upper" => "1000",
                    "lower" => "1"
                ),
                "default" => "50",
            )
        ),
        "closed" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_room.closed",
            "config" => Array (
                "type" => "check",
            )
        ),
        "enable_emoticons" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tt_content.pi_flexform.showEmoticons",
            "config" => Array (
                "type" => "check",
            )
        ),

        "mode" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_room.mode",
            "config" => Array (
                "type" => "select",
                'renderType' => 'selectSingle' ,
                "items" => Array (
                    Array("LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_room.mode.I.0", "0"),
                    Array("LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_room.mode.I.1", "1"),
                ),
                "size" => 1,
                "maxitems" => 1,
            )
        ),
        "showfullnames" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_room.showfullnames",
            "config" => Array (
                "type" => "check",
            )
        ),

        "private" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_room.private",
            "config" => Array (
                "type" => "check",
            )
        ),
        "owner" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_room.owner",
            "config" => Array (
                "type" => "group",
                "allowed" => "fe_users",
                "size" => 1,
                "minitems" => 0,
                "maxitems" => 1,
            )
        ),
        "moderators" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_room.moderators",
            "config" => Array (
                "type" => "group",
                "allowed" => 'fe_users',
                "size" => 10,
                "minitems" => 0,
                "maxitems" => 100,
            )
        ),
        "experts" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_room.experts",
            "config" => Array (
                "type" => "group",
                "allowed" => 'fe_users',
                "size" => 10,
                "minitems" => 0,
                "maxitems" => 100,
            )
        ),
        "bannedusers" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_room.bannedusers",
            "config" => Array (
                "type" => "group",
                "allowed" => 'fe_users',
                "size" => 10,
                "minitems" => 0,
                "maxitems" => 100,
            )
        ),
        "members" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_room.members",
            "config" => Array (
                "type" => "group",
                "allowed" => 'fe_users',
                "size" => 10,
                "minitems" => 0,
                "maxitems" => 100,
            )
        ),
        "groupaccess" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_room.groupaccess",
            "config" => Array (
                "type" => "select",
                'renderType' => 'selectMultipleSideBySide'  ,
                "foreign_table" => "fe_groups",
                "foreign_table_where" => "ORDER BY fe_groups.title",
                "size" => 10,
                "minitems" => 0,
                "maxitems" => 100,
            )
        ),
        "superusergroup" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_room.superusergroup",
            "config" => Array (
                "type" => "select",
                'renderType' => 'selectMultipleSideBySide' ,
                "foreign_table" => "fe_groups",
                "foreign_table_where" => "ORDER BY fe_groups.title",
                "size" => 10,
                "minitems" => 0,
                "maxitems" => 100,
            )
        ),
        "welcomemessage" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_room.welcomemessage",
            "config" => Array (
                "type" => "text",
                "cols" => "30",
                "rows" => "5",
            )
        ),


        "showuserinfo_experts" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_room.showuserinfo_experts",
            "config" => Array (
                "type" => "input",
                "default" => "name,company",
            )
        ),

        "showuserinfo_moderators" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_room.showuserinfo_moderators",
            "config" => Array (
                "type" => "input",
                "default" => "name,company",
            )
        ),
        "showuserinfo_users" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_room.showuserinfo_users",
            "config" => Array (
                "type" => "input",
                "default" => "",
            )
        ),

        "showuserinfo_superusers" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_room.showuserinfo_superusers",
            "config" => Array (
                "type" => "input",
                "default" => "",
            )
        ),

        "page" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:jvchat/Resources/Private/Language/locallang_db.xlf:tx_jvchat_room.page",
            "config" => Array (
                "type" => "group",
                "allowed" => "pages",
                "size" => 1,
                "minitems" => 0,
                "maxitems" => 1,
            )
        ),
        "image" => Array (
            "exclude" => 1,
            "label" => "Image",
            "config" => Array (
                "type" => "group",
                "internal_type" => "file",
                "allowed" => $GLOBALS["TYPO3_CONF_VARS"]["GFX"]["imagefile_ext"],
                "max_size" => 500,
                "uploadfolder" => "uploads/tx_jvchat",
                "size" => 1,
                "minitems" => 0,
                "maxitems" => 1,
            )
        ),

    ),

    "types" => Array (
        "0" => Array("showitem" => "--div--;General,hidden, name, description, welcomemessage, mode, --palette--;;checkBoxes, page, maxusercount, image, --div--;Users,moderators, experts, groupaccess, superusergroup, bannedusers, showuserinfo_experts, showuserinfo_moderators, showuserinfo_users, showuserinfo_superusers,--div--;Private Room,private,owner,members")
    ),
    "palettes" => Array (
        "1" => Array("showitem" => "starttime, endtime, fe_group"),
        "checkBoxes" => Array("showitem" => "showfullnames, closed, enable_emoticons")
    )
);

if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
    && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()) {
    $conf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)->get('jvchat');
    if($conf['showRealNamesListing']) {
        $return['columns']['experts']['config']['itemsProcFunc'] = 'tx_jvchat_itemsProcFunc->user_jvchat_getFeUser';
        $return['columns']['moderators']['config']['itemsProcFunc'] = 'tx_jvchat_itemsProcFunc->user_jvchat_getFeUser';
        $return['columns']['bannedusers']['config']['itemsProcFunc'] = 'tx_jvchat_itemsProcFunc->user_jvchat_getFeUser';
    }
}

return $return ;
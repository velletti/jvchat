# jvchat
Fork of the old TYPO3 extension vjchat 

see ChangeLog for old Infos about this extension

This is  To be able to use this extension also in TYPO3 LTS 9 


## Migration from vjchat to jvchat

Typoscript was changed nearly 80 %
most settings have moved 

Template has moved from marker Based Template to Fluid Resource\Private
so you may Need to adjust the Fluid templates 
- pi1\DisplayChatRoom
- pi1\GetMessages
- pi1\Userlist
- pi1\Roomlist


css is now in Resource\Public\css\jvchat.css
or use scss in Resource\Private\Scss\jvchat.scss

MYQSL Tables have to be renamed and th Plugins List type value has changed:

    RENAME TABLE `tx_vjchat_entry` TO `tx_jvchat_entry`;
    RENAME TABLE `tx_vjchat_room` TO `tx_jvchat_room`;
    RENAME TABLE `tx_vjchat_session` TO `tx_jvchat_session`;
    RENAME TABLE `tx_vjchat_messages` TO `tx_jvchat_messages`;
    RENAME TABLE `tx_vjchat_room_feusers_mm` TO `tx_jvchat_room_feusers_mm`;
    UPDATE tt_content SET list_type ='jvchat_pi1' WHERE list_type ='vjchat_pi1' ;
    
    
## Installation using Composer

add this to the repository section in your composer.json { "type": "vcs", "url": "git@github.com:velletti/jvchat.git" }

    "repositories": [
		{ "type": "composer", "url": "https://composer.typo3.org/" },
		{ "type": "vcs", "url": "git@github.com:velletti/jvchat.git" }
	],
	
call
 
    composer require velletti/jvchat dev-main

in V12:    

    composer require velletti/jvchat ^12.4



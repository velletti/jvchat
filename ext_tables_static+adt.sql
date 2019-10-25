-- noinspection SqlNoDataSourceInspectionForFile

RENAME TABLE `tx_vjchat_entry` TO `tx_jvchat_entry`;
RENAME TABLE `tx_vjchat_room` TO `tx_jvchat_room`;
RENAME TABLE `tx_vjchat_session` TO `tx_jvchat_session`;
RENAME TABLE `tx_vjchat_messages` TO `tx_jvchat_messages`;
RENAME TABLE `tx_vjchat_room_feusers_mm` TO `tx_jvchat_room_feusers_mm`;
UPDATE tt_content SET list_type ='jvchat_pi1' WHERE list_type ='vjchat_pi1' ;

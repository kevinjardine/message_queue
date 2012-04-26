<?php
elgg_register_event_handler('init','system','message_queue_init');

function message_queue_init() {

	elgg_register_library('elgg:message_queue', elgg_get_plugins_path() . 'message_queue/models/model.php');
	elgg_load_library('elgg:message_queue');
	elgg_register_plugin_hook_handler('cron', 'fiveminute', 'message_queue_send_emails');
	
	// let cron jobs edit and delete message queue messages
	elgg_register_plugin_hook_handler('permissions_check', 'object', 'message_queue_permission_check');
}

function message_queue_permission_check($hook, $entity_type, $returnvalue, $params) {
	$e = $params['entity'];
	if (elgg_instanceof($e,'object','message_queue_message')) {		
		return TRUE;
	}
	
	return $returnvalue;
}

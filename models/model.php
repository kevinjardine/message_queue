<?php
function message_queue_create_message($subject,$body) {
	$message = new ElggObject();
	$message->subtype = "message_queue_message";
	$message->access_id = ACCESS_PUBLIC;
	$message->owner_guid = elgg_get_logged_in_user_guid();
	$message->container_guid = $message->owner_guid;
	$message->title = $subject;
	$message->description = $body;
	$message->status = 'creating';
	if ($message->save()) {
		return $message;
	} else {
		return false;
	}
}

function message_queue_add($message_id,$user_id) {
	add_entity_relationship($user_id, 'message_queue', $message_id);
}

function message_queue_set_for_sending($message_id) {
	$message = get_entity($message_id);
	$message->status = 'unsent';
}

function message_queue_send_emails() {
	
	// This could take a while, so ask for more time
	
	set_time_limit(0);
 	
	$max_emails = elgg_get_plugin_setting('max_emails', 'message_queue');
	if (!$max_emails) {
		$max_emails = 200;
	}
 	$emails_sent = 0;
 	
 	// start with current jobs (left over from previous cron run)
 	
 	$emails_sent = message_queue_send_message_type('sending',$emails_sent,$max_emails);
 	
 	$message_limit = $max_emails-$emails_sent;
 	
 	// if still within quota, start new jobs
 	if ($message_limit > 0) { 	
		message_queue_send_message_type('unsent',$emails_sent,$max_emails);
 	}
}

function message_queue_send_message_type($type,$emails_sent,$max_emails) {
	global $CONFIG;
	
	$site = get_entity($CONFIG->site_guid);
	$options = array(
		'type' => 'object',
		'subtype' => 'message_queue_message',
		'metadata_name' => 'status',
		'metadata_value' => $type,
	);
	$messages = elgg_get_entities_from_metadata($options);
 	if ($messages) {
 		foreach ($messages as $message) {
 			$message_limit = $max_emails-$emails_sent;
 			if ($message_limit <= 0) {
 				break;
 			}
 			
 			// lock the message to avoid the small chance that another cron job might
 			// try sending it
 			$message->status = "locked";
 			$options = array(
				'type' => 'user',
				'relationship' => 'message_queue',
				'relationship_guid' => $message->getGUID(),
				'inverse_relationship' => TRUE,
 				'limit' => $message_limit,
			);
			$users = elgg_get_entities_from_relationship($options);
 			//$users = get_entities_from_relationship('message_queue', $message->getGUID(), true, 'user', '', 0, "", $message_limit);
 			if ($users) {
 				$subject = $message->title;
 				$body = $message->description;
 				$message_id = $message->getGUID();
 				foreach ($users as $to) {
 					notify_user($to->getGUID(), $site->guid, $subject, $body, NULL, array('email'));
 					//email_notify_handler($site, $to, $subject, $body);
 					remove_entity_relationship($to->guid, 'message_queue', $message_id);
 				}
 				$user_count = count($users);
 				$emails_sent += $user_count;
 				if ($user_count < $message_limit) {
 					// all done
 					//$message->status = "sent";
 					$message->delete();
 					
 				} else {
 					// there might be more messages to send
 					// so set flag and check next time the cron job runs
 					$message->status = "sending";
 				}
 			} else {
 				// nothing left to do
 				//$message->status = "sent";
 				$message->delete();
 			}
 		}
 	}
 	
 	return $emails_sent;
}

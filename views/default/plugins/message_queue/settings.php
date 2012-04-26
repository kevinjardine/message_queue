<?php

$message_queue_max_emails = elgg_get_plugin_setting('max_emails', 'message_queue');
if (!$message_queue_max_emails) {
	$message_queue_max_emails = 200;
}

$body .= elgg_echo('message_queue:settings:max_emails:title');
$body .= '<br />';
$body .= elgg_view('input/text',array('name'=>'params[max_emails]','value'=>$message_queue_max_emails));

$body .= '<br />';

echo $body;

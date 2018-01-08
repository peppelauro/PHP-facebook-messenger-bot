<?php
require_once 'config.php';
require_once 'FacebookBot.php';
$bot = new FacebookBot(FACEBOOK_VALIDATION_TOKEN, FACEBOOK_PAGE_ACCESS_TOKEN);
//$bot->setWelcomeMessage(FACEBOOK_PAGE_ID,"Hello {{user_first_name}}!\n\nUn bot di test");
//$bot->setGetStartedButton(FACEBOOK_PAGE_ID,'PAYLOAD_START');
//$bot->setWhitelistedDomains(['https://whitelistdomain.io/']);
//$bot->setPersistentMenu();

$text_help = <<< HHH
Ecco i comandi che riconosco:
	- help, aiuto
	- test quickreply
	- test attachment
	...
HHH;

$bot->run();
$messages = $bot->getReceivedMessages();
$bot->echoLog("\n\n".serialize($messages)."\n\n");
foreach ($messages as $message)
{
	$recipientId = $message->senderId;
	if($message->text)
	{
		switch($message->text)
		{
			case "help":
			case "aiuto":
				$bot->sendTextMessage($recipientId,$text_help);
				break;
			case "test quickreply":
				//DEBUG: $bot->sendTextMessage($recipientId, 'Rispondo con:'.serialize($message));
				$quickrep1 = new \stdClass();
				$quickrep1->content_type = 'text';
				$quickrep1->title = 'TEST1';
				$quickrep1->payload = 'PAYLOAD_TEST1';
				$quickrep2 = new \stdClass();
				$quickrep2->content_type = 'text';
				$quickrep2->title = 'TEST2';
				$quickrep2->payload = 'PAYLOAD_TEST2';
				$bot->sendTextMessage($recipientId, $message->text,[$quickrep1,$quickrep2]);
				break;
			case "test attachment":
				$att = new \stdClass();
				$att->type = 'image';
				$att->payload = new \stdClass();
				$att->payload->url = 'https://cdn.pixabay.com/photo/2017/12/09/16/41/snow-man-3008179_960_720.jpg';
				$bot->sendTextMessage($recipientId, null, null, $att);
				break;
			default:
				$bot->setSenderAction($recipientId);
				$bot->sendTextMessage($recipientId, 'Rispondo con:'.$message->text);
				break;
		}
	}
	if($message->attachments)
	{
		$bot->sendTextMessage($recipientId, "Attachment received");
		foreach ($message->attachments as $attachment)
		{
			$bot->sendTextMessage($recipientId, 'type: '.$attachment->type);
			switch($attachment->type)
			{
				case 'image':
					$bot->sendTextMessage($recipientId, 'url: '.$attachment->payload->url);
					break;
			}
			
		}
	}
	if($message->quick_reply)
	{
		$bot->sendTextMessage($recipientId, "Postback received by quickrep: ".$message->quick_reply->payload);
	}
	if($message->payload)
	{
		$bot->sendTextMessage($recipientId, "Postback received: ".$message->payload);
	}
}

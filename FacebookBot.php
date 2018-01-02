<?php

class FacebookBot
{

    const BASE_URL = 'https://graph.facebook.com/v2.6/';

    private $_validationToken;
    private $_pageAccessToken;
    private $_receivedMessages;
    private $_handleLog;

    public function __construct($validationToken, $pageAccessToken)
    {
        $this->_validationToken = $validationToken;
        $this->_pageAccessToken = $pageAccessToken;
        $this->setupWebhook();
        $this->_handleLog = fopen("/home/ubuntu/workspace/FacebookBot.log","a+");
    }

    public function getReceivedMessages()
    {
        return $this->_receivedMessages;
    }

    public function getPageAccessToken()
    {
        return $this->_pageAccessToken;
    }

    public function getValidationToken()
    {
        return $this->_validationToken;
    }

    private function setupWebhook()
    {
        if (isset($_REQUEST['hub_challenge']) && isset($_REQUEST['hub_verify_token']) && $this->getValidationToken() == $_REQUEST['hub_verify_token']) {
            echo $_REQUEST['hub_challenge'];
            exit;
        }
    }

    public function sendTextMessage($recipientId, $text, $quick_replies = null, $attachment = null)
    {
        $url = self::BASE_URL . "me/messages?access_token=%s";
        $url = sprintf($url, $this->getPageAccessToken());
        //self::echoLog($url);
        $recipient = new \stdClass();
        $recipient->id = $recipientId;
        $message = new \stdClass();
        $message->text = $text;
        if (is_array($quick_replies))
        {
            $message->quick_replies = $quick_replies;
        }
        if ($attachment)
        {
            $message->attachment = $attachment; 
        }
        $parameters = ['messaging_type' => 'RESPONSE','recipient' => $recipient, 'message' => $message];
        $response = self::executePost($url, $parameters, true);
        $this->echoLog($response);
        if ($response) {
            $responseObject = json_decode($response);
            return is_object($responseObject) && isset($responseObject->recipient_id) && isset($responseObject->message_id);
        }
        return false;
    }

    public function setWelcomeMessage($pageId, $text="Hello {{user_first_name}}!")
    {
        $url = self::BASE_URL . "me/messenger_profile?access_token=%s";
        $url = sprintf($url, $this->getPageAccessToken());
        
        $greeting = new \stdClass();
        $greeting->locale = 'default';
        $greeting->text = $text;
        $parameters = ['greeting'=>array($greeting)];
        $response = self::executePost($url, $parameters, true);
        $this->echoLog(serialize($response));
        if ($response) {
            $responseObject = json_decode($response);
            return is_object($responseObject) && isset($responseObject->result) && strpos($responseObject->result, 'Success') !== false;
        }
        return false;
    }

    public function run()
    {
        $request = self::getJsonRequest();
        if (!$request) return;
        //$this->echoLog("REQUEST:\n".serialize($request)."\n");
        $entries = isset($request->entry) ? $request->entry : null;
        if (!$entries) return;
        $this->echoLog("\nENTRIES:".serialize($entries)."\n");
        $messages = [];
        foreach ($entries as $entry) {
            $messagingList = isset($entry->messaging) ? $entry->messaging : null;
            if (!$messagingList) continue;
            foreach ($messagingList as $messaging) {
                $message = new \stdClass();
                $message->entryId = isset($entry->id) ? $entry->id : null;
                $message->senderId = isset($messaging->sender->id) ? $messaging->sender->id : null;
                $message->recipientId = isset($messaging->recipient->id) ? $messaging->recipient->id : null;
                $message->timestamp = isset($messaging->timestamp) ? $messaging->timestamp : null;
                if(isset($messaging->message))
                {
                    $message->messageId = isset($messaging->message->mid) ? $messaging->message->mid : null;
                    //$message->sequenceNumber = isset($messaging->message->seq) ? $messaging->message->seq : null; //Forse rimosso
                    $message->text = isset($messaging->message->text) ? $messaging->message->text : null;
                    $message->attachments = isset($messaging->message->attachments) ? $messaging->message->attachments : null;
                    $message->quick_reply = isset($messaging->message->quick_reply) ? $messaging->message->quick_reply : null;
                }
                if(isset($messaging->postback))
                {
                    //$this->echoLog('POSTBACK: '.serialize($messaging->postback));
                    $message->payload = $messaging->postback->payload;
                }
                $messages[] = $message;
            }
        }
        $this->_receivedMessages = $messages;
    }

    public function subscribeAppToThePage()
    {
        $url = self::BASE_URL . "me/subscribed_apps";
        $parameters = ['access_token' => $this->getPageAccessToken()];
        $response = self::executePost($url, $parameters);
        if ($response) {
            $responseObject = json_decode($response);
            return is_object($responseObject) && isset($responseObject->success) && $responseObject->success == "true";
        }
        return false;
    }

    public function setSenderAction($pageId, $action='typing_on')
    {
        $url = self::BASE_URL . "me/messages?access_token=%s";
        $url = sprintf($url, $this->getPageAccessToken());
        $tmp = new \stdClass();
        $tmp->id=$pageId;
        $request = new \stdClass();
        $request->recipient = $tmp;
        $request->sender_action = $action;
        $response = self::executePost($url, $request, true);
        if ($response) {
            $responseObject = json_decode($response);
            return is_object($responseObject) && isset($responseObject->result) && strpos($responseObject->result, 'Success') !== false;
        }
        return false;
    }
    
    public function setGetStartedButton($pageId, $payload)
    {
        $url = self::BASE_URL . "me/messenger_profile?access_token=%s";
        $url = sprintf($url, $this->getPageAccessToken());
        
        $request = new \stdClass();
        $request->payload = $payload;
        $parameters = ['get_started' => $request];
        $response = self::executePost($url, $parameters, true);
        $this->echoLog($response);
        if ($response) {
            $responseObject = json_decode($response);
            return is_object($responseObject) && isset($responseObject->result) && strpos($responseObject->result, 'Success') !== false;
        }
        return false;
    }
    
    public function setPersistentMenu()
    {
        $url = self::BASE_URL . "me/messenger_profile?access_token=%s";
        $url = sprintf($url, $this->getPageAccessToken());
        $request = new \stdClass();
        $request->locale = 'default';
        $menu_item1 = new \stdClass();
        $menu_item1->title = 'Menu item 1';
        $menu_item1->type = 'postback';
        $menu_item1->payload = 'MENU1_PAYLOAD';
        $menu_item2 = new \stdClass();
        $menu_item2->title = 'Menu item 2';
        $menu_item2->type = 'postback';
        $menu_item2->payload = 'MENU2_PAYLOAD';
        
        $request->call_to_actions = [$menu_item1,$menu_item2];
        $parameters = ['persistent_menu' => [$request]];
        $response = self::executePost($url, $parameters, true);
        $this->echoLog(serialize($request));
        $this->echoLog($response);
        if ($response) {
            $responseObject = json_decode($response);
            return is_object($responseObject) && isset($responseObject->result) && strpos($responseObject->result, 'Success') !== false;
        }
        return false;
    }
    
    private static function getJsonRequest()
    {
        $content = file_get_contents("php://input");
        return json_decode($content, false, 512, JSON_BIGINT_AS_STRING);
    }

    private static function executePost($url, $parameters, $json = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($json) {
            $data = json_encode($parameters);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data)));
        } else {
            curl_setopt($ch, CURLOPT_POST, count($parameters));
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
    
    public function echoLog($buf)
    {
        fwrite($this->_handleLog,"\n".$buf);
    }
}

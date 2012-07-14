<?php

define('ABS_PATH', '../');
require_once ABS_PATH.'config.php';
require_once ABS_PATH.'classes/rest_server.php';
require_once ABS_PATH.'classes/utils.php';
require_once ABS_PATH.'classes/validate.php';
require_once ABS_PATH.'classes/messages.php';
require_once ABS_PATH.'classes/logger.php';

class mailModel extends indexModel
{
    private $state;
    private $log;
    
    function __construct($state)
    {
        $this->state = $state;
        $this->log = new Logger('api.log', 'log');
        
        $this->state->process_auth_key();
        
        # GET api/mail/
        if ($this->state->method == 'get')
        {
            $this->get_list();
        }
        
        # POST api/mail/send/
        elseif($this->state->method == 'post' && $this->state->params[2] == 'send')
        {
            $this->send_message();
        }
        
        # POST api/mail/delete/
        elseif($this->state->method == 'post' && $this->state->params[2] == 'delete')
        {
            $this->delete_messages();
        }
        
    }
    
    private function get_list()
    {
        $message = new Messages;
        $limit = is_numeric($this->state->request_vars['limit']) ? $this->state->request_vars['limit'] : WEBSERVICE_DEFAULT_PAGING_COUNT;
        
        if ($this->state->user_type == 'business')
        {
            $list = $message->getallMessages($this->state->business_id, $this->state->user_type, $limit);
        }
        elseif ($this->state->user_type == 'consumer')
        {
            $list = $message->getallMessages($this->state->user_id, $this->state->user_type, $limit);
        }
        
        
        foreach ($list as &$list_item)
        {
            if ($list_item['from_usertype'] == 'business')
            {
                $list_item['image'] = SITE_URL.'thumb.php?src='.SITE_URL.'images/biz_profile_image/'.$list_item['image'].
                                        '&w='.WEBSERVICE_THUMB_WIDTH.'&h='.WEBSERVICE_THUMB_HEIGHT;
            }
            elseif ($list_item['from_usertype'] == 'consumer')
            {
                $list_item['image'] = SITE_URL.'thumb.php?src='.SITE_URL.'images/cons_profile_image/'.$list_item['image'].
                                        '&w='.WEBSERVICE_THUMB_WIDTH.'&h='.WEBSERVICE_THUMB_HEIGHT;
                
            }
        }
        $list = $list ? $list : array();
        
        RestServer::send_response(200, array('messages' => $list));
    }
    
    private function send_message()
    {   
        if (empty($this->state->request_vars['to_id']))
        {
            RestServer::send_response(400, array('error' => 'Recipient ID is required.'));
        }
        
        if (empty($this->state->request_vars['to_usertype']))
        {
            RestServer::send_response(400, array('error' => 'Recipient usertype is required.'));
        }
        
        if (empty($this->state->request_vars['message']))
        {
            RestServer::send_response(400, array('error' => 'Message can not be empty.'));
        }
        
        $message = new Messages;
        
        $msg_to = $this->state->request_vars['to_id'];
        $to_user_type = $this->state->request_vars['to_usertype'];
        $message_content = $this->state->request_vars['message'];
        $from_user_type = $this->state->user_type;
        $from_id = $this->state->user_id;
        
        if ($from_user_type == 'business')
        {
            $from_id = $this->state->business_id;
        }
        
        $sent = $message->sendMessage($msg_to, $to_user_type, $from_id, $from_user_type, $message_content);
        
        if ($sent)
        {
            RestServer::send_response(201, array('message' => 'Your message was sent.'));
        }
        RestServer::send_response(500, array('error' => 'Unable to send your message. Please try again.'));
    }
    
    private function delete_messages()
    {
        $messages_to_delete = $this->state->request_vars['messages'];
        //$this->log->log('Delete: '.$messages_to_delete);
        $messages_to_delete = str_replace('\"', null, $messages_to_delete);
        $messages_to_delete = json_decode($messages_to_delete);
        
        $user_type = $this->state->user_type;
        $user_id = $this->state->user_id;
        
        if ($user_type == 'business')
        {
            $user_id = $this->state->business_id;
        }
        
        $message = new Messages;
        //$this->log->log('Delete: '.$messages_to_delete);
        //$this->log->log('User_id: '.$user_id.' User_type: '.$user_type);
        $deleted = $message->deleteMessages($messages_to_delete, $user_id, $user_type);
        RestServer::send_response(200, array('message' => 'Deleted'));
    }
}
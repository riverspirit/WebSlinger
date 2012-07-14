<?php

define('ABS_PATH', '../');
require_once ABS_PATH.'config.php';
require_once ABS_PATH.'classes/rest_server.php';
require_once ABS_PATH.'classes/utils.php';
require_once ABS_PATH.'classes/validate.php';
require_once ABS_PATH.'classes/event.php';
require_once ABS_PATH.'classes/logger.php';

class eventModel extends indexModel
{
    private $state;
    private $log;
    
    function __construct($state)
    {
        $this->state = $state;
        $this->log = new Logger('api.log', 'log');
        
        $this->state->process_auth_key();

        # POST api/event/new/
        if ($this->state->method == 'post' && $this->state->params[2] == 'new')
        {
            $this->create_event();
        }
        
        # GET api/event/categories/
        elseif($this->state->method == 'get' && $this->state->params[2] == 'categories')
        {
            $this->get_event_categories();
        }
        
        # GET api/event/dates/
        elseif($this->state->method == 'get' && $this->state->params[2] == 'dates')
        {
            $this->get_event_dates();
        }
        
        # GET api/event/<event_id>/
        elseif($this->state->method == 'get' && is_numeric ($this->state->params[2]))
        {
            $this->get_event_details();
        }
        
        # GET api/event/on/<date>/
        elseif($this->state->method == 'get' && $this->state->params[2] == 'on')
        {
            $this->get_events_on_date();
        }
    }
    
    private function create_event()
    {
        $this->log->log('POST DATA: '. print_r($this->state->request_vars, 1));
        if (empty($this->state->request_vars['event_name']))
        {
            RestServer::send_response(400, array('error' => 'Event name is required.'));
        }
        
        if (empty($this->state->request_vars['event_date']))
        {
            RestServer::send_response(400, array('error' => 'Event date is required.'));
        }
        
        $user_id = $this->state->user_id;
        $user_type = $this->state->user_type;
        
        $event_name = $this->state->request_vars['event_name'];
        $event_date = $this->state->request_vars['event_date'];
        $event_category = !empty($this->state->request_vars['event_category']) ? 
                            $this->state->request_vars['event_category'] : null;
        $announce_to = empty($this->state->request_vars['announce_to']) ? 'network' : $this->state->request_vars['announce_to'];
        
        switch ($announce_to)
        {
            case 'network': { $announce_to = '0'; break; }
            case 'business': { $announce_to = '1'; break; }
            case 'consumer': { $announce_to = '2'; break; }
            default : { $announce_to = '0'; break; }
        }
        
        
        if ($user_type == 'business')
        {
            $user_id = $this->state->business_id;
        }
        
        $event = new Event(false, $user_id, $user_type, $user_id); // ya, stupid, i know
        
        $event_info = array(
                        'event_name' => $event_name ,
                        'event_date' => $event_date,
                        'category_id' => $event_category,
                        'announce_to' => $announce_to
                        );
            
        $event_id = $event->addEvent($event_info);
        if ($event_id)
        {
            RestServer::send_response(201, array('message' => 'Event created.',
                                                 'event_id' => $event_id));
        }
        
    }
    
    private function get_event_categories()
    {
        $event = new Event;
        $cat_list = $event->getEventcategories();
        
        foreach ($cat_list as $index => $cat_name)
        {
            $cat_list[$index]['category_id'] = $cat_name['eventcatid'];
            $cat_list[$index]['category_name'] = $cat_name['eventcatname'];
            unset ($cat_list[$index]['eventcatid']);
            unset ($cat_list[$index]['eventcatname']);
        }
        
        $cat_list = $cat_list ? $cat_list : array();
        
        RestServer::send_response(200, array('event_categories' => $cat_list));
    }
    
    private function get_event_dates()
    {
        $event = new Event(false, $this->state->user_id, $this->state->user_type, $this->state->business_id);
        $event_dates = $event->GetallEventdatesbyme();
        foreach ($event_dates as &$date)
        {
            $date = date('Y-m-d', strtotime($date));
        }
        
        RestServer::send_response(200, array('event_dates' => $event_dates));
    }
    
    private function get_event_details()
    {
        $event = new Event(false, $this->state->user_id, $this->state->user_type, $this->state->business_id);
        $event_id = $this->state->params[2];
        $event_details = $event->getEventdetails($event_id);
        
        if ($event_details['eventid'] === null)
        {
            RestServer::send_response(400, array('error' => 'There is no such event.'));
        }
        
        $category_name = $event->getEventcategory($event_details['eventcatid']);
        
        switch ($event_details['eventannounceto'])
        {
            case 0: { $announce_to = 'network'; break; }
            case 1: { $announce_to = 'business'; break; }
            case 2: { $announce_to = 'consumer'; break; }
            default: { $announce_to = 'network'; break; }
        }
        
        $event_info['event_id']            = $event_details['eventid'];
        $event_info['event_name']          = $event_details['eventname'];
        $event_info['event_notes']         = $event_details['eventnotes'];
        $event_info['event_description']   = $event_details['eventdes'];
        $event_info['event_category_id']   = $event_details['eventcatid'];
        $event_info['event_category_name'] = $category_name;
        $event_info['announce_to']         = $announce_to;
        $event_info['event_date']          = $event_details['eventdate'];
        
        RestServer::send_response(200, array('event_info' => $event_info));
    }
    
    private function get_events_on_date()
    {
        $date = $this->state->params[3];
        die($date);
    }
}
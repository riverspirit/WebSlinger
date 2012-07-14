<?php

define('ABS_PATH', '../');
require_once ABS_PATH.'classes/rest_server.php';
require_once ABS_PATH.'classes/utils.php';
require_once ABS_PATH.'classes/validate.php';
require_once ABS_PATH.'classes/site.php';
require_once ABS_PATH.'classes/business_user.php';
require_once ABS_PATH.'classes/business.php';
require_once ABS_PATH.'classes/news_feeds.php';
require_once ABS_PATH.'classes/phone_call.php';
require_once ABS_PATH.'classes/event.php';
require_once ABS_PATH.'classes/deals.php';
require_once ABS_PATH.'classes/question_answers.php';
require_once ABS_PATH.'classes/logger.php';

class businessModel extends indexModel
{
    function __construct($state)
    {
        // Add new business
        if ($state->method == 'post' && $state->params[2] == 'new')
        {
            $required_fields = array (
                'id',
                'first_name',
                'last_name',
                'business_name',
                'email',
                'password',
                'phone',
                'zip'
            );



            if (!validate::is_set((array)$state->request_vars, $required_fields))
            {
                RestServer::send_response (400, array('error' => 'First name, Last Name, Business Name, Email, Password, Phone and ZIP are required.'));
            }

            $biz_user = new BusinessUser;

            if ($biz_user->check_email_exists($state->request_vars['email']))
            {
                RestServer::send_response (409, array('error' => 'Email already exists.'));
            }

            if (strlen($state->request_vars['password']) < 6)
            {
                RestServer::send_response (400, array('error' => 'Password is too short.'));
            }

            $user_data = array(
                'first_name' => $state->request_vars['first_name'],
                'last_name' => $state->request_vars['last_name'],
                'email' => $state->request_vars['email'],
                'password' => $state->request_vars['password']
                );
            $user_id = $biz_user->createBusinessUser($user_data);

            if (!$user_id)
            {
                RestServer::send_response (500, array('error' => 'Unable to create account at this time.'));
            }

            $business_data = array(
                'business_name' => $state->request_vars['business_name'],
                'image' =>  $state->request_vars['image'],
                'zip' =>  $state->request_vars['zip']
                );

            $business = new Business;
            $business_id = $business->addNewBusiness($business_data);

            if (!$business_id)
            {
                RestServer::send_response (500, array('error' => 'User account was created, but unable to add your business.'));
            }

            $link_business	=	$business->linkBusinessToUser($user_id, $business_id);

            if ($link_business)
            {
                RestServer::send_response (201, array('message' => 'Account created. Activation link sent to email.'));
            }

            RestServer::send_response (500, array('error' => 'Unable to link the business to your user account.'));
        }

        elseif ($state->method == 'get' && $state->params[2] == 'search')
        {

            $business = new Business;
            $conditions = array();
            $conditions['business_name'] = isset($state->request_vars['business_name']) ? $state->request_vars['business_name'] : null;
            $conditions['zip'] = isset($state->request_vars['zip']) ? $state->request_vars['zip'] : null;
            $max_results = isset($state->request_vars['max_results']) ? $state->request_vars['max_results'] : null;

            if (!$conditions['business_name'] && ! $conditions['zip'])
            {
                RestServer::send_response (400, array('error' => 'Too few parameters for search'));
            }

            if (isset ($max_results) && !is_numeric($max_results))
            {
                RestServer::send_response (400, array('error' => 'max_results must be a number'));
            }

            $results = $business->search_business($conditions, $search_radius = null, 'OR', $max_results);

            RestServer::send_response (200, array('message' => $results));
        }

        // Claim business
        elseif ($state->method == 'post' && $state->params[2] == 'claim')
        {
            $state->log->log('before routing | $this req vars:- '.print_r($state->request_vars, 1));
            $this->business_claim();
        }

        // Verify business claim
        elseif ($state->method == 'post' && $state->params[2] == 'verify_claim')
        {
            $this->business_verify_claim();
        }
    }
    
    private function business_claim()
    {
        $this->log->log('In business_claim() | Client IP: '.$_SERVER['REMOTE_ADDR']);
        $site = new Site();
        $business = new Business;
        $call = new PhoneCall;

        $content = $site->get_section_content('activation_call');
        
        $time_to_call = isset($this->request_vars['time_to_call']) ? $this->request_vars['time_to_call'] : 'now';
        
        $this->log->log("time_to_call is {$time_to_call}");
        
        if ($time_to_call == 'now')
        {
            $biz_data = $business->getBusinessDetails($this->request_vars['business_id'], false);
            
            $this->log->log("business_id: {$this->request_vars['business_id']}");
            
            $this->log->log("biz_data: ".print_r($biz_data, 1));
            
            $phone_number = $biz_data['phone'];

            $activation_code = $business->setClaimCode($this->request_vars['business_id']);

            // Lets put a space character between the characters in the code. So 123fx will be 1 2 3 f x.
            $formatted_activation_code = preg_replace('/[a-zA-Z0-9]/', '$0 ', $activation_code);
            $formatted_activation_code = trim($formatted_activation_code);

            $activation_code_message = str_replace('{ACTIV_CODE}', $formatted_activation_code, $content['message_with_activation_code']);

            $call_queued = $call->make_call($phone_number, $activation_code_message);

            if ($call_queued)
            {
                $user_ip = $_SERVER['REMOTE_ADDR'];
                $call->add_call_log($this->request_vars['business_id'], $phone_number, $user_ip);
                RestServer::send_response(202, array('message' => 'You will get a call from us with the activation code shortly.'));
            }
            RestServer::send_response(503, array('error' => 'Unable to make the activation call. Please try again.'));
        }
        else
        {
            if (!Validate::is_valid_datetime($time_to_call))
            {
                RestServer::send_response(400, array('error' => 'Invalid date provided.'));
            }
            else
            {
                if (strtotime($time_to_call) < time())
                {
                    RestServer::send_response(400, array('error' => 'Please provide a future date and time.'));
                }
                
                $scheduled = $call->schedule_call($this->request_vars['business_id'], $time_to_call);
                if($scheduled)
                {
                    $ampm = 'AM';
                    $timezone = 'EST';
                    
                    $hr = date('H', strtotime($time_to_call));
                    $min = date('i', strtotime($time_to_call));
                    if ($hr > 12)
                    {
                        $hr = $hr - 12;
                        $ampm = 'PM';
                    }
                    $date = date('F j, Y', strtotime($time_to_call));
                    $formatted_call_time = "{$date} {$hr}:{$min} {$ampm} {$timezone}";
                    RestServer::send_response(202, array('message' => 'We will call you on '.$formatted_call_time));
                }
            }
        }
        

    }
    
    private function business_verify_claim()
    {
        RestServer::send_response(201, array('message' => array('auth_key' => '12345')));
    }
}


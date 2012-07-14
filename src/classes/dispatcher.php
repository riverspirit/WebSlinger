<?php

class Dispatcher extends RestServer
{
    private $log;
    
    protected $params; // params passed by url
    protected $method; // request method (eg: get)
    protected $request_vars; // data sent in the request (eg: post data)
    protected $user_id;
    protected $user_type;
    protected $business_id;

    public function __construct()
    {
        $data = RestServer::process_request();
        
        $this->method = $data->get_method();
        $this->params = $data->get_params();
        $this->request_vars = $data->get_request_vars();
        //$function = $this->params[1];
        print_r($data);die('=');
        
    }
    
    public function get(array $url_segments, $handler)
    {
        print_r($this->params);
        if ($this->method == 'get')
        {
            die('thh');
        }
    }

    public function __construct1()
    {
        //$this->log = new Logger('rlog.txt', 'log');
        
        $data = RestServer::process_request();
        
        $this->method = $data->get_method();
        $this->params = $data->get_params();
        $this->request_vars = $data->get_request_vars();
        $function = $this->params[1];
        
        if (!$function)
        {
            RestServer::send_response(400, array('error' => 'No method specified.'));
        }

        
        if (method_exists($this, $function))
        {
            $reflection = new ReflectionMethod($this, $function);
            
            // Only public methods are callable through URL
            if ($reflection->isPublic())
            {
                call_user_func($this->$function());
            }
            else
            {
                RestServer::send_response(405, array('error' => 'Call to this method not allowed.'));
            }
        }
        else
        {
            RestServer::send_response(501, array('error' => 'Method not implemented.'));
        }
    }
    
    
    public function hello()
    {
        RestServer::send_response(200, $this->request_vars);
    }
    
    
    public function authorize()
    {
        $args = $this->params[2];
        $args = trim($args, '? ');
        parse_str($args, $args);
        
        $this->user_type = $this->request_vars['user_type'];
        $username = $this->request_vars['username'];
        $password = $this->request_vars['password'];
        $device_id = $this->request_vars['device_id'];
        $app_version = $this->request_vars['app_version'];

        $this->check_app_version($app_version);
        
        if (empty ($device_id))
        {
            RestServer::send_response(400, array('error' => 'device_id must be provided'));
        }
        
        if (empty ($username) || empty ($password))
        {
            RestServer::send_response(401, array('error' => 'Incorrect username or password'));
        }
        
        if (empty ($this->user_type))
        {
            RestServer::send_response(400, array('error' => 'user_type must be provided'));
        }
        
        if ($this->user_type != 'business' && $this->user_type != 'consumer')
        {
            RestServer::send_response(400, array('error' => 'Invalid user_type provided'));
        }
        
        if ($this->user_type == 'business')
        {
            $user = new BusinessUser;
        }
        elseif ($this->user_type == 'consumer')
        {
            $user = new Consumer;
        }
        
        $this->user_id = $user->logUserIn($username, $password);
        
        if ($this->user_id)
        {
            $app_auth_key = $this->new_webservice_login_session($this->user_id, $this->user_type, $device_id);
            RestServer::send_response(200, array('auth_key' => $app_auth_key));
        }
        RestServer::send_response(401, array('error' => 'Incorrect username or password'));
    }
    
    
    private function new_webservice_login_session($user_id, $user_type, $device_id)
    {
        $app_auth_key = 'ret_app_'.md5(uniqid(rand(), true));
        
        // First lets log out any currently logged in sessions for the user
        $logout_sql = "UPDATE webservice_sessions 
                       SET login_status = '0'
                       WHERE user_id = '{$user_id}' AND user_type = '{$user_type}'";
        $this->db_query($logout_sql);
        
        $login_sql = "INSERT INTO webservice_sessions (
                         app_auth_key, 
                         user_id, 
                         user_type, 
                         last_app_login, 
                         device_id, 
                         login_status) 
                      VALUES (
                        '{$app_auth_key}', 
                        '{$user_id}', 
                        '{$user_type}', 
                         CURRENT_TIMESTAMP, 
                        '{$device_id}', 
                        '1')";
                        
        $this->db_query($login_sql);
        
        if (mysql_affected_rows())
        {
            return $app_auth_key;
        }
        return false;
    }
    
    
    public function business()
    {
        $this->call_from_external_file();
    }
    
    
    
    
    public function feed()
    {
        print_r($this->request_vars);
        $stream = new NewsFeeds;
        
        if ($this->method == 'get')
        {
            
        }
    }
    
    public function events()
    {
        $event = new Event;
        
        if ($this->method == 'get')
        {
            $this->get_events();
        }
        
    }
    
    private function get_events()
    {
        die('get events');
    }
    
    public function deals()
    {
        die('deals');
    }
    
    private function get_deals()
    {
        
    }
    
    
    public function discussions()
    {
        $this->call_from_external_file();
    }
    
    
    public function mail()
    {
        $this->call_from_external_file();
    }
    
    public function event()
    {
        $this->call_from_external_file();
    }

    
    public function network(){

    if($this->method == 'get' && $this->params[2] == 'getFeed'){// for get feed 


            $cls				=	 new  NewsFeeds();
            $util				= 	 new Utils();

            // auntication of device and retrving outh code is given here 
            $data				=	 array();

            $cls->user_id		=	$array['user']['id'] 		=	 1;
            $cls->user_type		=	$array['user']['user_type']	=	'business';					
            $rec				=	$cls->getFeeds();
            $rec				=	array();
            if(empty($rec)){
                 RestServer::send_response (500, array('error' => 'No news feeds at this moment'));
            }
            for($i=0;$i<count($rec);$i++){

                $user		=	$cls->getUserInfo($rec[$i]["user_id"],$rec[$i]["user_type"]);				
                $timediff	=	$util->get_time_since($rec[$i]["created_date"],1);				
                $image		=	BIZ_PROFILE_IMAGE_DIR.$user["image"];
                $name		=	$user["first_name"]." ".$user["last_name"];					
                if($rec[$i]["user_type"]=="business"){
                    $name		=	$user["business_name"];
                }
                $rec[$i]["username"]			=	$name;
                $rec[$i]["profile_image"]		=	$image;

                $comments	=	$cls->getComments($rec[$i]["id"]);

                if(!empty($comments)){
                    for($j=0;$j<count($comments);$j++){
                        $commenttime	=	$util->get_time_since($comments[$j]["date"],1);	
                        $postUser		=	$cls->getUserInfo($comments[$j]["user_id"],$comments[$j]["user_type"]);
                        $image			=	BIZ_PROFILE_IMAGE_DIR.$postUser["image"];
                        $pname			=	$postUser["first_name"]." ".$postUser["last_name"];
                        if( $comments[$j]["user_type"] == 'business'){
                            $pname		=	$postUser["business_name"];
                        }
                        $comments[$j]["username"]			=	$pname;
                        $comments[$j]["profile_image"]		=	$image;
                    }
                    $rec[$i]["comments"]=	json_encode($comments);// the comments added to same row of $rec
                }
            }				
            RestServer::send_response(200, array('data' => $rec));
    }

    // for deleting status 

    if($this->method == 'get' && $this->params[2] == 'deleteFeed'){			
        $cls				=	 new  NewsFeeds();
        $util				= 	 new Utils();			
        $postId				= 	$this->request_vars['postId'];

        $array				=	 array();				
        $cls->user_id		=	$array['user']['id'] 		=	 1;
        $cls->user_type		=	$array['user']['user_type']	=	'business';				


        if(empty($postId)){
             RestServer::send_response (400, array('error' => 'Bad Request '));
        }

        if($cls->deletePost($postId)){
             RestServer::send_response (200, array('error' => 'Successfully Delete !!! '));
        }else{
             RestServer::send_response (400, array('error' => 'Failed !!, Could not delete the post  '));
        }
        exit;

        // auntication of device and retrving outh code is given here 

        $rec				=	array();
        if(empty($rec)){
             RestServer::send_response (500, array('error' => 'No news feeds at this moment'));
        }

    }
}
    
    public function logout($auth_key)
    {
				
    }
    
    
    /**
     *  Check if the client app version is supported
     * @param number $current_app_version
     * @return boolean
     */
    private function check_app_version($current_app_version)
    {
        $site = new Site;
        
        if ($current_app_version < $site->get_config('min_android_app_version'))
        {
            RestServer::send_response (412, array('error' => 'Your application is outdated. Please update to the latest version'));
        }
        return true;
    }
    
    /**
     * Check if the auth key provided is valid
     * @param string $auth_key
     * return array | false (array('business_user_id', 'active_business_id'))
     */
    public function process_auth_key()
    {
        $auth_key = $_SERVER['HTTP_X_AUTHKEY'];
        if (empty($auth_key))
        {
            RestServer::send_response (412, array('error' => 'Auth key missing.'));
        }
        
        $sql = "SELECT user_id, user_type 
                FROM webservice_sessions 
                WHERE app_auth_key = '{$auth_key}' AND login_status = '1'";
                
        $result = mysql_query($sql);
        $row = mysql_fetch_assoc($result);
        
        if (isset ($row['user_id']))
        {
            $this->user_id = $row['user_id'];
            $this->user_type = $row['user_type'];
            
            if ($this->user_type == 'business')
            {
                $business_user = new BusinessUser;
                $my_biz_id = $business_user->getAllBusinessesOfBizUser($this->user_id);
                $this->business_id = !empty($my_biz_id[0]) ? $my_biz_id[0]['id'] : null;
            }
            
            return array('user_id' => $this->user_id, 'user_type' => $this->user_type);
        }
        else
        {
            RestServer::send_response (401, array('error' => 'Invalid Authentication Key'));
        }
    }
    
    
    private function call_from_external_file($file = NULL)
    {
        if ($file)
        {
            $api_defenitions_file = ABS_PATH.'api/models/'.$file;
        }
        else
        {
            $function = $this->params[1];
            $api_defenitions_file = ABS_PATH.'api/models/api_'.$function.'.php';
        }
        
        
        if (file_exists($api_defenitions_file))
        {
            require_once $api_defenitions_file;
        }
        
        $class_name = $function.'Model';
        new $class_name($this);
    }

}
<?php

class RestServer
{
    private $request_vars;
    private $data;
    private $method;
    private $params;


    public function __construct()
    {
        $this->request_vars = array();
        $this->data = '';
        $this->method = 'get';
        $this->params = array();
    }
    
    public function set_data($data)
    {
        $this->data = $data;
    }
    
    public function set_method($method)
    {
        $this->method = $method;
    }
    
    public function set_request_vars($request_vars)
    {
        $this->request_vars = $request_vars;
    }
    
    public function get_request_vars()
    {
        return $this->request_vars;
    }
    
    public function set_params($params)
    {
        $this->params = $params;
    }
    
    public function get_data()
    {
        return $this->data;
    }
    
    public function get_method()
    {
        return $this->method;
    }
    
    public function get_params()
    {
        return $this->params;
    }
    
    /**
     * Parse the incoming request and store the request params and meta data into
     * the class variables.
     * 
     * @return RestServer
     */
    public static function process_request()
    {
        $request_method = strtolower($_SERVER['REQUEST_METHOD']);
        $request_params    = $_SERVER['REQUEST_URI'];
        $request_params    = strstr($request_params, WS_API_BASE_DIR);
        $request_params    = explode(DIRECTORY_SEPARATOR, $request_params);
        print_r($request_params);die;
        array_shift($request_params);
        
        $return_obj     = new RestServer();
		$data           = array();
        
        switch ($request_method)
        {
            case 'get':{ 
                            $data = $_GET;
                            break;
                        }

            case 'post':{
                            $data = $_POST;
                            break;
                        }
                        
            case 'put':{
                            parse_str(file_get_contents('php://input'), $put_vars);
                            $data = $put_vars;
                            break;
                       }
        }
        
        $return_obj->set_method($request_method);
        $return_obj->set_params($request_params);
        
        $data = Validate::sanitize($data);
        $return_obj->set_request_vars($data);

        if(isset($data['data']))
        {
            $return_obj->set_data(json_decode($data['data']));
        }
        return $return_obj;
    }
    
    /**
     * Send response to the client
     * @param numner $status HTTP status code for the response
     * @param array $message Optional message. Will be JSON enocded and sent out.
     * 
     * @return null;
     */
    public static function send_response($status = 200, $message = null)
    {
        $codes = array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => '(Unused)',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported'
        );
        $response_status = (isset($codes[$status])) ? $status.' '.$codes[$status] : '';
        
        ob_clean();
        ob_start();
        //http_response_code($status);
        header("Status: {$response_status}");
        header("{$_SERVER['SERVER_PROTOCOL']} {$response_status}");
        
        if ($message)
        {
            header('Content-type: application/json');
            echo json_encode($message);
            ob_flush();
        }
        die;
    }

}
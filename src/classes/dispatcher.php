<?php

class Dispatcher extends RestServer
{   
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
    }
    
    public function get($url_segments, $callback, array $args = array())
    {
        $this->route('get', $url_segments, $callback, $args);
    }
    
    public function post($url_segments, $callback, array $args = array())
    {
        $this->route('post', $url_segments, $callback, $args);
    }
    
    public function put($url_segments, $callback, array $args = array())
    {
        $this->route('put', $url_segments, $callback, $args);
    }
    
    public function delete($url_segments, $callback, array $args = array())
    {
        $this->route('delete', $url_segments, $callback, $args);
    }
    
    public function head($url_segments, $callback, array $args = array())
    {
        $this->route('head', $url_segments, $callback, $args);
    }
    
    public function route($method, $url_segments, $callback, array $args = array())
    {
        $context_met = true;
        
        if ($this->method != $method)
        {
            return null;
        }
        
        if (is_string($url_segments))
        {   
            $request_url = '';
            foreach ($this->params as $url_chunk)
            {
                $request_url .= '/'.$url_chunk;
            }
            
            if ('/'.$url_segments != $request_url)
            {
                $context_met = false;
            }
        }
        elseif (is_array($url_segments))
        {
            foreach ($url_segments as $key => $value)
            {
                if ($url_segments[$key] != $this->params[$key])
                {
                    $context_met = false;
                }
            }
        }
        
        if ($context_met)
        {
            if (strstr($callback, '::'))
            {
                $chunks = explode('::', $callback);
                $class_name = $chunks[0];
                $method_name = $chunks[1];
                $object = new $class_name();
                call_user_func_array(array($object, $method_name), $args);
            }
            else
            {
                call_user_func_array($callback, $args);
            }

        }
    }
}
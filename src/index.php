<?php

define('ABS_PATH', dirname(__FILE__));
require_once ABS_PATH.'/api_settings.php';
require_once ABS_PATH.'/classes/rest_server.php';
require_once ABS_PATH.'/classes/dispatcher.php';
require_once ABS_PATH.'/classes/validate.php';
require_once ABS_PATH.'/classes/logger.php';

$dispatcher = new Dispatcher();

$dispatcher->get(array('hello'), 'test');
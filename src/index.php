<?php

define('ABS_PATH', dirname(__FILE__));
require_once ABS_PATH.'/api_settings.php';
require_once ABS_PATH.'/classes/rest_server.php';
require_once ABS_PATH.'/classes/dispatcher.php';
require_once ABS_PATH.'/classes/validate.php';
require_once ABS_PATH.'/classes/logger.php';
require_once ABS_PATH.'/classes/user.php';

$dispatcher = new Dispatcher();

$dispatcher->post(array('hello', 'test1'), 'Testclass::test');
$dispatcher->get(array('hello', 'test'), 'Testclass::test');
$dispatcher->get('hello/test2?page=in', 'test3');
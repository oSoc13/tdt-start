<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use tdt\pages\Generator;

/**
 * This file is the router. It's where all calls come in.
 * It will accept a request and start the right controller.
 *
 * @copyright (C) 2013 by OKFN Belgium
 * @license AGPLv3
 * @author Pieter Colpaert
 * @author Jan Vansteenlandt
 * @author Michiel Vancoillie
 */
require_once APPPATH . "core/glue.php";


// Support for CGI/FastCGI
if (!isset($_SERVER["REQUEST_URI"])) {
    $_SERVER["REQUEST_URI"] = substr($_SERVER["PHP_SELF"], 1);
    if (isset($_SERVER["QUERY_STRING"])) {
        $_SERVER["REQUEST_URI"] .= "?" . $_SERVER["QUERY_STRING"];
    }
}

// Drop subdir from the request
$subdir = app\core\Config::get("general", "subdir");
if (!empty($subdir)) {
    try {
        $_SERVER["REQUEST_URI"] = preg_replace("/^\/?" . str_replace('/', '\/', $subdir) . "/", "", $_SERVER["REQUEST_URI"]);
    } catch (Exception $e) {
        // Couldn't convert subdir to a regular expression
    }
}

// Fetch the routes from the config
$allroutes = app\core\Config::get("routes");


// Only keep the routes that use the requested HTTP method
foreach ($allroutes as $key => $route){
    if (strcmp($route['method'],strtoupper($_SERVER['REQUEST_METHOD'])) != 0){
        unset($allroutes[$key]);
    }
}

try {
    // This function will do the magic.
    Glue::stick($allroutes);
} catch (tdt\exceptions\TDTException $e) {
    $log = new Logger('router');
    $log->pushHandler(new StreamHandler(app\core\Config::get("general", "logging", "path") . "/log_" . date('Y-m-d') . ".txt", Logger::ERROR));
    $log->addError($e->getMessage());
    set_error_header($e->getCode(), $e->getShort());
    $generator = new Generator();
    $generator->setTitle("The DataTank");
    if($e->getCode() < 500){
        $generator->generate("<h1>Sorry, but there seems to be something wrong with the call you've made</h1><h2>". $e->getCode() ." - " . $e->getShort() . "</h2><p>" . $e->getMessage()  . "</p>");
    }else{
        $generator->generate("<h1>Sorry, there seems to be something wrong with our servers</h1><p>If you're the system administrator, please check the logs. Otherwise, check back in a short while.</p>");
    }
    exit(0);
} catch (Exception $e) {
    $log = new Logger('router');
    $log->pushHandler(new StreamHandler(app\core\Config::get("general", "logging", "path") . "/log_" . date('Y-m-d') . ".txt", Logger::CRITICAL));
    $log->addCritical($e->getMessage());
    set_error_header(500, "Internal Server Error");
    $generator->generate("<h1>Sorry, there seems to be something wrong with our servers</h1><p>If you're the system administrator, please check the logs. Otherwise, check back in a short while.</p>");
    exit(0);
}
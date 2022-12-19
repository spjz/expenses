<?php
/**
 * Basic front controller
 */

use D3R\Db;
use spjz\Controller;
use spjz\Router;

// Some handy definitions
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__) . DS);
define('CONFIG', ROOT . 'config'.DS);
define('LIB', ROOT.'lib'.DS);
define('VIEWS', ROOT.'views'.DS);

// Include configuration - don't forget to add your credentials.
include(CONFIG . 'database.php');

// Include a PSR-0 autoloader
include(LIB . 'autoloader.php');

$db = Db::get();

// Initialise application
try {

    $router = new Router;

    switch ($router->getRoute()) {
        case '':
        case '/':
            $controller = new Controller($router);
            $controller->respond();
            break;

        case '/api':
            $controller = new Controller($router, true);
            $controller->respond();
            break;

        default:
            http_response_code(404);
            exit;
    }

} catch (Exception $e) {
    throw $e;
    error_log($e->getMessage());
}
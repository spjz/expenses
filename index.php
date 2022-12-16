<?php
/**
 * Basic front controller
 */

// Some handy definitions
// Some handy definitions
define('ROOT', dirname(__FILE__) . '/');
define('CONFIG', ROOT . 'config/');
define('LIB', ROOT . 'lib/');

// Include configuration - don't forget to add your credentials.
include(CONFIG . 'database.php');

// Include a PSR-0 autoloader
include(LIB . 'autoloader.php');

// Main - over to you! :-)
echo 'Hello World!';

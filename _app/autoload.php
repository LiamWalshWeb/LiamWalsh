<?php

const STATAMIC_VERSION = '1.7.5';
const APP_PATH = __DIR__;

/*
|--------------------------------------------------------------------------
| Autoload Slim
|--------------------------------------------------------------------------
|
| Bootstrap the Slim environment and get things moving.
|
*/

require_once __DIR__ . '/vendor/Slim/Slim.php';
require_once __DIR__ . '/vendor/SplClassLoader.php';

\Slim\Slim::registerAutoloader();

/*
|--------------------------------------------------------------------------
| Vendor libraries
|-------------------------------------------------------------------------
|
| Load miscellaneous third-party dependencies.
|
*/

$packages = array(
  'Buzz',
  'Carbon',
  'emberlabs',
  'Intervention',
  'Michelf',
  'Netcarver',
  'Stampie',
  'Symfony',
  'Whoops',
  'Zeuxisoo'
);

foreach ($packages as $package) {
  $loader = new SplClassLoader($package, __DIR__ . '/vendor/');
  $loader->register();
}

require_once __DIR__ . '/vendor/PHPMailer/PHPMailerAutoload.php';

require_once __DIR__ . '/vendor/Spyc/Spyc.php';

/*
|--------------------------------------------------------------------------
| The Template Parser
|--------------------------------------------------------------------------
|
| Statamic uses a *highly* modified fork of the Lex parser, created by
| Dan Horrigan. Kudos Dan!
|
*/

require_once __DIR__ . '/vendor/Lex/Parser.php';

/*
|--------------------------------------------------------------------------
| Internal API & Class Autoloader
|--------------------------------------------------------------------------
|
| An autoloader for our internal API and other core classes
|
*/

// helper functions
require_once __DIR__ . '/core/functions.php';

// register the Statamic autoloader
spl_autoload_register("autoload_statamic");

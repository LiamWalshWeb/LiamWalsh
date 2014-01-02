<?php

/*
|--------------------------------------------------------------------------
| Load Site Configuration
|--------------------------------------------------------------------------
|
| Before anything we need to load all the user-specified configurations,
| global variables, custom routes, and theme settings. Many methods
| depend on these settings.
|
*/
$config = Statamic::loadAllConfigs();

$config['log_enabled'] = TRUE;
$config['log.level'] = Log::convert_log_level($config['_log_level']);
$config['log.writer'] = new Statamic_Logwriter(
    array(
        'path' => $config['_log_file_path'],
        'file_prefix' => $config['_log_file_prefix']
    )
);

/*
|--------------------------------------------------------------------------
| Application Timezone
|--------------------------------------------------------------------------
|
| Many users are upgrading to PHP 5.3 for the first time. I know.
| We've gone ahead set the default timezone that will be used by the PHP
| date and date-time functions. This prevents some potentially
| frustrating errors for novice developers.
|
*/
date_default_timezone_set(Helper::pick($config['_timezone'], @date_default_timezone_get(), "UTC"));

/*
|--------------------------------------------------------------------------
| Slim Initialization
|--------------------------------------------------------------------------
|
| Time to get an instance of Slim fired up. We're passing the $config
| array, which contains a bit more data than necessary, but helps keep
| everything simple.
|
*/

$app = new \Slim\Slim(array_merge($config, array('view' => new Statamic_View)));

$app->config = $config;

/*
|--------------------------------------------------------------------------
| Vanity URLs
|--------------------------------------------------------------------------
|
| Process any vanity URLs
|
*/
Statamic::processVanityURLs($config);

/*
|--------------------------------------------------------------------------
| Cookies for the Monster
|--------------------------------------------------------------------------
|
| Get the Slim Cookie middleware running the specified lifetime.
|
*/

$app->add(new \Slim\Middleware\SessionCookie(
        array('expires' => $config['_cookies.lifetime']))
);

/*
|--------------------------------------------------------------------------
| Set Default Layout
|--------------------------------------------------------------------------
|
| This may be overwritten later, but let's go ahead and set the default
| layout file to start assembling our front-end view.
|
*/

Statamic_View::set_layout("layouts/default");

/*
|--------------------------------------------------------------------------
| Set Global Variables, Defaults, and Environments
|--------------------------------------------------------------------------
|
| Numerous tag variables, helpers, and other config-dependent options
| need to be loaded *before* the page is parsed.
|
*/

Statamic::setDefaultTags();


/*
|--------------------------------------------------------------------------
| Caching
|--------------------------------------------------------------------------
|
| Look for updated content to cache
|
*/
Cache::update();

/*
|--------------------------------------------------------------------------
| The Routes
|--------------------------------------------------------------------------
|
| Route it up fellas!
|
*/

require_once __DIR__ . '/routes.php';

return $app;

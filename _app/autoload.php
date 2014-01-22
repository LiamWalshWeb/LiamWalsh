<?php

const STATAMIC_VERSION = '1.6.7';
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
  'Symfony',
  'Buzz',
  'Stampie',
  'Intervention'
);

foreach ($packages as $package) {
  $loader = new SplClassLoader($package, __DIR__ . '/vendor/');
  $loader->register();
}

$loader = new SplClassLoader('emberlabs', __dir__.'/vendor/');
$loader->register();

require_once __DIR__ . '/vendor/PHPMailer/class.phpmailer.php';

require_once __DIR__ . '/vendor/Spyc/Spyc.php';

/*
|--------------------------------------------------------------------------
| The Markup Languages
|--------------------------------------------------------------------------
|
| Even though Statamic only runs one primary markdown syntax at a time,
| variable modifiers allow you to parse single variables with any
| of the available parsers. Thus, we load them all.
|
*/

require_once __DIR__ . '/vendor/Markup/markdown.php';
require_once __DIR__ . '/vendor/Markup/classTextile.php';
require_once __DIR__ . '/vendor/Markup/smartypants.php';

/*
|--------------------------------------------------------------------------
| The Template Parser
|--------------------------------------------------------------------------
|
| Statamic uses a highly modified fork of the Lex parser, created by
| Dan Horrigan. Kudos Dan!
|
*/

require_once __DIR__ . '/vendor/Lex/Parser.php';

/*
|--------------------------------------------------------------------------
| The API
|--------------------------------------------------------------------------
|
| The internal-agnostic face of Statamic.
|
*/

require_once __DIR__ . '/core/api/cache.php';
require_once __DIR__ . '/core/api/config.php';
require_once __DIR__ . '/core/api/cookie.php';
require_once __DIR__ . '/core/api/content.php';
require_once __DIR__ . '/core/api/date.php';
require_once __DIR__ . '/core/api/email.php';
require_once __DIR__ . '/core/api/environment.php';
require_once __DIR__ . '/core/api/folder.php';
require_once __DIR__ . '/core/api/file.php';
require_once __DIR__ . '/core/api/helper.php';
require_once __DIR__ . '/core/api/hook.php';
require_once __DIR__ . '/core/api/html.php';
require_once __DIR__ . '/core/api/localization.php';
require_once __DIR__ . '/core/api/parse.php';
require_once __DIR__ . '/core/api/password.php';
require_once __DIR__ . '/core/api/path.php';
require_once __DIR__ . '/core/api/session.php';
require_once __DIR__ . '/core/api/pattern.php';
require_once __DIR__ . '/core/api/request.php';
require_once __DIR__ . '/core/api/slug.php';
require_once __DIR__ . '/core/api/taxonomy.php';
require_once __DIR__ . '/core/api/theme.php';
require_once __DIR__ . '/core/api/math.php';
require_once __DIR__ . '/core/api/url.php';
require_once __DIR__ . '/core/api/yaml.php';

/*
|--------------------------------------------------------------------------
| The Core
|--------------------------------------------------------------------------
|
| This is what makes Statamic tick.
|
*/

require_once __DIR__ . '/core/statamic.php';
require_once __DIR__ . '/core/contentservice.php';
require_once __DIR__ . '/core/contentset.php';
require_once __DIR__ . '/core/taxonomyset.php';
require_once __DIR__ . '/core/log.php';
require_once __DIR__ . '/core/logwriter.php';
require_once __DIR__ . '/core/helper.php';
require_once __DIR__ . '/core/addon.php';
require_once __DIR__ . '/core/plugin.php';
require_once __DIR__ . '/core/tasks.php';
require_once __DIR__ . '/core/hooks.php';
require_once __DIR__ . '/core/view.php';
require_once __DIR__ . '/core/validate.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/fieldset.php';
require_once __DIR__ . '/core/fieldtype.php';
require_once __DIR__ . '/core/user.php';
require_once __DIR__ . '/core/functions.php';

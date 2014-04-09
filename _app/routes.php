<?php

/////////////////////////////////////////////////////////////////////////////////////////////////
// ROUTING HOOKS
/////////////////////////////////////////////////////////////////////////////////////////////////

$app->map('/TRIGGER/:namespace/:hook(/:segments+)', function ($namespace, $hook, $segments = array()) use ($app) {

    /*
    |--------------------------------------------------------------------------
    | Hook: Routes Before
    |--------------------------------------------------------------------------
    |
    | Useful for running your own route. Remember to use $app->pass() if
    | you're not doing anything with the current request.
    |
    */
    Hook::run('_routes', 'before');

    Hook::run($namespace, $hook, null, null, $segments);

})->via('GET', 'POST', 'HEAD');



/////////////////////////////////////////////////////////////////////////////////////////////////
// Static Asset Pipeline (for development only!)
/////////////////////////////////////////////////////////////////////////////////////////////////

if (Config::get('enable_static_pipeline', true)) {

    $app->get('/assets/(:segments+)', function($segments = array()) use ($app) {

        // clean segments
        $segments = URL::sanitize($segments);

        /*
        |--------------------------------------------------------------------------
        | Hook: Routes Before
        |--------------------------------------------------------------------------
        |
        | Useful for running your own route. Remember to use $app->pass() if
        | you're not doing anything with the current request.
        |
        */
        Hook::run('_routes', 'before');

        $file_requested = implode($segments, '/');
        $file = Theme::getPath() . $file_requested;

        $file = realpath($file);

        # Routes only if the file doesn't already exist (e.g. /assets/whatever.ext)
        if ( ! File::exists(array($file_requested, $file))) {

            Log::warn("The Static Asset Pipeline is deprecated. It may yet come back to fight another battle someday.", "core", "asset pipeline");

            $mime = File::resolveMime($file);

            header("Content-type: {$mime}");
            readfile($file);

            exit();

        } else {

            // Moving on. Not a valid asset.
            $app->pass();
        }

    });

}


/////////////////////////////////////////////////////////////////////////////////////////////////
// Bundle Asset Pipeline
/////////////////////////////////////////////////////////////////////////////////////////////////

$app->get('/_add-ons/(:segments+)', function($segments = array()) use ($app) {

    // clean segments
    $segments = URL::sanitize($segments);
    $file_requested = implode($segments, '/');
    
    $bundle_folder  = APP_PATH . "/core/bundles/" . $segments[0];
    $file = APP_PATH . "/core/bundles/" . $file_requested;

    $file = realpath($file);
    
    if (strpos($file_requested, '../') !== false || File::getExtension($file) === 'php') {
        $app->pass();
        return;
    }

    if (Folder::exists($bundle_folder)) {
        if (File::exists($file)) {
            $mime = File::resolveMime($file);

            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

            if (Config::get('http_cache_expires', false)) {
                header("Expires: " . gmdate("D, d M Y H:i:s", strtotime('+' . Config::get('http_cache_expires', '30 minutes'))) . " GMT");
            }

            header("Content-type: {$mime}");
            readfile($file);
        }

        exit();
    }

});


/////////////////////////////////////////////////////////////////////////////////////////////////
// GLOBAL STATAMIC CONTENT ROUTING
/////////////////////////////////////////////////////////////////////////////////////////////////

$app->map('/(:segments+)', function ($segments = array()) use ($app) {

    /*
    |--------------------------------------------------------------------------
    | Hook: Request Post
    |--------------------------------------------------------------------------
    |
    | Do a thing with a POST request. Go ahead. Do it.
    |
    */
    if ($app->request()->isPost()) {
        Hook::run('request', 'post');
    }

    // clean segments
    $segments = URL::sanitize($segments);


    /*
    |--------------------------------------------------------------------------
    | Hook: Routes Before
    |--------------------------------------------------------------------------
    |
    | Useful for running your own route. Remember to use $app->pass() if
    | you're not doing anything with the current request.
    |
    */
    Hook::run('_routes', 'before');

    $requesting_xml = false;
    $content_found  = false;

    // segments
    foreach ($segments as $key => $seg) {
        $count                            = $key + 1;
        $app->config['segment_' . $count] = URL::sanitize($seg);
    }
    $app->config['last_segment'] = end($segments);

    /*
    |--------------------------------------------------------------------------
    | Routes: Ignore Segment
    |--------------------------------------------------------------------------
    |
    | Globally ignore a specific URL segment. For example, "success".
    |
    */
    if (isset($app->config['_routes']['ignore']) && is_array($app->config['_routes']['ignore']) && count($app->config['_routes']['ignore']) > 0) {
        $ignore = $app->config['_routes']['ignore'];

        $remove_segments = array_intersect($ignore, $segments);
        $segments = array_diff($segments, $remove_segments);
    }

    /*
    |--------------------------------------------------------------------------
    | Routes: Ignore AFTER a Segment
    |--------------------------------------------------------------------------
    |
    | Globally ignore all URL segments after a specified one. For example,
    | "search" could let you use additional segments as match conditions.
    |
    */

    if ($ignore_after = array_get($app->config, '_routes:ignore_after', false)) {

        if ( ! is_array($ignore_after)) {
            $ignore_after = array($ignore_after);
        }

        foreach ($ignore_after as $segment) {
            $position = array_search($segment, $segments);

            if ($position !== false) {
                array_splice($segments, $position + 1);
            }
        }
    }

    // determine paths
    $path = '/' . implode($segments, '/');

    // let XML files through
    if (substr($path, -4) == '.xml') {
        $path = substr($path, 0, -4);
        $requesting_xml = true;
    }

    $current_url  = $path;
    $complete_current_url  = Path::tidy(Config::getSiteRoot() . "/" . $current_url);

    // allow mod_rewrite for .html file extensions
    if (substr($path, -5) == '.html') {
        $path = str_replace('.html', '', $path);
    }

    $app->config['current_path'] = $path;

    // init some variables for below
    $content_root  = Config::getContentRoot();
    $content_type  = Config::getContentType();
    $response_code = 200;
    $visible       = true;
    $add_prev_next = false;

    $template_list = array('default');

    // set up the app based on if a
    if (File::exists("{$content_root}/{$path}.{$content_type}") || Folder::exists("{$content_root}/{$path}")) {
        // endpoint or folder exists!
    } else {
        $path                        = Path::resolve($path);
        $app->config['current_url']  = $app->config['current_path'];
        $app->config['current_path'] = $path; # override global current_path
    }

    // check for routes
    // allows the route file to run without "route:" as the top level array key (backwards compatibility)
    $found_route  = null;
    $routes       = array_get($app->config, '_routes:routes', array_get($app->config, '_routes'));

    // look for matching routes
    if (is_array($routes)) {
        foreach ($routes as $route_url => $route_data) {
            if (preg_match('#^' . str_replace(array('.', '*'), array('\.', '.*?'), $route_url) . '$#i', $current_url, $matches)) {
                // found a route, save it and get out
                $found_route = array(
                    'url' => $route_url,
                    'data' => $route_data
                );
                break;
            }
        }
    }

    // routes via routes.yaml
    if ($found_route) {
        $current_route = $found_route['data'];

        $route    = $current_route;
        $template = $route;
        $data     = $app->config;  // start with the current global vars

        if (is_array($route)) {
            $template = isset($route['template']) ? $route['template'] : 'default';

            if (isset($route['layout'])) {
                $data['_layout'] = $route['layout'];
            }
        }

        $template_list = array($template);
        $content_found = true;

    // actual file exists
    } elseif (File::exists("{$content_root}/{$path}.{$content_type}")) {
        $add_prev_next   = true;
        $template_list[] = 'post';
        $page            = basename($path);

        $data                = Content::get($complete_current_url);
        $data['current_url'] = $current_url;
        $data['slug']        = basename($current_url);

        if ($path !== "/404") {
            $content_found = true;
        }

    // url is taxonomy-based
    } elseif (Taxonomy::isTaxonomyURL($path)) {
        list($type, $slug) = Taxonomy::getCriteria($path);

        // create data array
        $data = array_merge(Config::getAll(), array(
            'homepage'       => Config::getSiteRoot(),
            'raw_url'        => Request::getResourceURI(),
            'page_url'       => Request::getResourceURI(),
            'taxonomy_slug'  => urldecode($slug),
            'taxonomy_name'  => Taxonomy::getTaxonomyName($type, $slug)
        ));

        $template_list[] = "taxonomies";
        $template_list[] = $type;
        $content_found = true;

    // this is a directory,so we look for page.md
    } elseif (is_dir("{$content_root}/{$path}")) {
        $data = Content::get($complete_current_url);
        $content_found = true;

    // URL found in the cache
    } elseif ($data = Content::get($complete_current_url)) {
        $add_prev_next   = true;
        $page            = basename($path);

        $data                = Content::get($complete_current_url);
        $data['current_url'] = $current_url;
        $data['slug']        = basename($current_url);

        if ($path !== "/404") {
            $content_found = true;
        }
    }

    // content was found
    if ($content_found) {
        // protect
        if (is_array($data) && $data) {
            try {
                Addon::getAPI('protect')->hasAccess(URL::getCurrent());
            } catch (Slim\Exception\Stop $e) {
                throw $e;
            } catch (Exception $e) {
                // something went wrong with protect, 404 this
                Log::error('The following error occurred while trying to protect `' . htmlspecialchars($data['current_url']) . '`: ' . $e->getMessage() . ' — for extra precaution, we sent this use the 404 page.', 'core', 'protect');
                $content_found = false;
                $response_code = 404;
            }
        }

        // alter the response code if you want
        $response_code = array_get($data, '_response', $response_code);
    }

    // Nothing found. 404 O'Clock.
    if (!$content_found || ($requesting_xml && (!isset($data['_type']) || $data['_type'] != 'xml'))) {
        // determine where user came from for log message
        if (strstr($path, 'favicon.ico')) {
            // Favicons are annoying.
            Log::info("The site favicon could not be found.", "site", "favicon");
        } else {
            if (isset($_SERVER['HTTP_REFERER'])) {
                $url_parts = parse_url($_SERVER['HTTP_REFERER']);

                // get local referrer
                $local_referrer = $url_parts['path'];
                $local_referrer .= (isset($url_parts['query']) && $url_parts['query']) ? '?' . $url_parts['query'] : '';
                $local_referrer .= (isset($url_parts['fragment']) && $url_parts['fragment']) ? '#' . $url_parts['fragment'] : '';

                if (strstr($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) !== false) {
                    // the call came from inside the house!
                    $more   = 'There is a bad link on <a href="' . $local_referrer . '">' . $local_referrer . '</a>.';
                    $aspect = 'page';
                } else {
                    // external site linked to here
                    $more   = 'User clicked an outside bad link at <a href="' . $_SERVER['HTTP_REFERER'] . '">' . $_SERVER['HTTP_REFERER'] . '</a>.';
                    $aspect = 'external';
                }
            } else {
                // user typing error
                $more   = 'Visitor came directly to this page and may have typed the URL incorrectly.';
                $aspect = 'visitor';
            }

            Log::error("404 - Page not found. " . $more, $aspect, "content");
        }

        $data          = Content::get(Path::tidy(Config::getSiteRoot() . "/404"));
        $template_list = array('404');
        $response_code = 404;
    }

    # We now have all the YAML content
    # Let's process action fields

    # Redirect
    if (isset($data['_redirect'])) {
        $response_code = 302;

        if (is_array($data['_redirect'])) {
            $url = isset($data['_redirect']['to']) ? $data['_redirect']['to'] : false;

            if (!$url) {
                $url = isset($data['_redirect']['url']) ? $data['_redirect']['url'] : false; #support url key as alt
            }

            $response_code = isset($data['_redirect']['response']) ? $data['_redirect']['response'] : $response_code;
        } else {
            $url = $data['_redirect'];
        }

        if ($url) {
            $app->redirect($url, $response_code);
        }
    }

    // status
    if (isset($data['_is_draft']) && $data['_is_draft'] && !$app->config['logged_in']) {
        $data          = Content::get(Path::tidy(Config::getSiteRoot() . "/404"));
        $template_list = array('404');
        $visible       = false;
        $response_code = 404;

    // legacy status
    } elseif (isset($data['status']) && $data['status'] != 'live' && $data['status'] != 'hidden' && !$app->config['logged_in']) {
        $data          = Content::get(Path::tidy(Config::getSiteRoot() . "/404"));
        $template_list = array('404');
        $visible       = false;
        $response_code = 404;
    }

    // find next/previous
    if ($add_prev_next && $visible) {
        $folder = substr(preg_replace(Pattern::ORDER_KEY, "", substr($path, 0, (-1*strlen($page))-1)), 1);

        $relative     = Statamic::find_relative($current_url, $folder);
        $data['prev'] = $relative['prev'];
        $data['next'] = $relative['next'];
    }

    // grab data for this folder
    $folder_data = Content::get(Path::tidy('/' . Config::getSiteRoot() . '/' . dirname($current_url)));

    $fields_data = YAML::parseFile(Path::tidy(BASE_PATH . "/" . Config::getContentRoot() . dirname($current_url) . '/fields.yaml'));

    // Check for fallback template
    if ($content_found && empty($data['_template'])) {
        // check fields.yaml first
        if (array_get($fields_data, '_default_folder_template')) {
            $data['_template'] = $fields_data['_default_folder_template'];
        // fall back to the folder's page.md file
        } elseif (array_get($folder_data, '_default_folder_template')) {
            $data['_template'] = $folder_data['_default_folder_template'];
        }
    }

    // Check for fallback layout
    if ($content_found && empty($data['_layout'])) {
        // check fields.yaml first
        if (array_get($fields_data, '_default_folder_layout')) {
            $data['_layout'] = $fields_data['_default_folder_layout'];
        // fall back to the folder's page.md file
        } elseif (array_get($folder_data, '_default_folder_layout')) {
            $data['_layout'] = $folder_data['_default_folder_layout'];
        }
    }

    // set template and layout
    if (isset($data['_template'])) {
        $template_list[] = $data['_template'];
    }

    if (isset($data['_layout'])) {
        Statamic_View::set_layout("layouts/{$data['_layout']}");
    }

    // set up the view
    Statamic_View::set_templates(array_reverse($template_list));

    // set type, allows for RSS feeds
    if (isset($data['_type'])) {
        if ($data['_type'] == 'rss' || $data['_type'] == 'xml') {
            $data['_xml_header']      = '<?xml version="1.0" encoding="utf-8" ?>';
            $response                 = $app->response();
            $response['Content-Type'] = 'application/xml';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Hook: Render Before
    |--------------------------------------------------------------------------
    |
    | Allows actions to occur before the template is rendered and parsed.
    | For example, pre-process a POST or set global variables dynamically.
    |
    */

    Hook::run('_render', 'before');

    /*
    |--------------------------------------------------------------------------
    | HTTP Caching
    |--------------------------------------------------------------------------
    |
    | We'll always set the last modified header, but leave the
    | cache_expires option to people's discretion and configuration.
    |
    */

    if (array_get($data, '_http_cache_expires', Config::get('http_cache_expires', false))) {
        $app->lastModified(Cache::getLastCacheUpdate());
        $app->expires('+'.Config::get('http_cache_expires', '30 minutes'));
    }

    // and go!
    $app->render(null, $data, $response_code);
    $app->halt($response_code, ob_get_clean());

})->via('GET', 'POST', 'HEAD');

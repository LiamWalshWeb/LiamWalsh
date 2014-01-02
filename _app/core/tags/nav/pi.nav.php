<?php
class Plugin_nav extends Plugin
{
    public function index()
    {
        $from            = $this->fetchParam('from', URL::getCurrent());
        $exclude         = $this->fetchParam('exclude', false);
        $max_depth       = $this->fetchParam('max_depth', 1, 'is_numeric');
        $include_entries = $this->fetchParam('include_entries', false, false, true);
        $folders_only    = $this->fetchParam('folders_only', true, false, true);
        $include_content = $this->fetchParam('include_content', false, false, true);

        $url  = Path::resolve($from);
        $tree = Statamic::get_content_tree($url, 1, $max_depth, $folders_only, $include_entries, true, $include_content);

        # exclude a pipe-delimited set of urls
        if ($exclude) {

            $exclude_items = Helper::explodeOptions($exclude);

            foreach ($tree as $key => $item) {
                if (in_array($item['url'], $exclude_items) || in_array(trim($item['url'], '/'), $exclude_items)) {
                    unset($tree[$key]);
                }
            }
        }

        if (count($tree) > 0) {
            return Parse::tagLoop($this->content, $tree);
        }

    return FALSE;
  }

    public function breadcrumbs()
    {
        $url          = $this->fetchParam('from', URL::getCurrent());
        $include_home = $this->fetchParam('include_home', true, false, true);
        $reverse      = $this->fetchParam('reverse', false, false, true);
        $backspace    = $this->fetchParam('backspace', false, 'is_numeric', false);

        $url = Path::resolve($url);

        $crumbs = array();

        if ($url != '/') {

            $segments      = explode('/', ltrim($url, '/'));
            $segment_count = count($segments);
            $segment_urls  = array();

            for ($i = 1; $i <= $segment_count; $i++) {
                $segment_urls[] = implode($segments, '/');
                array_pop($segments);
            }

            # Build array of breadcrumb pages
            foreach ($segment_urls as $key => $url) {
                $crumbs[$url] = Statamic::fetch_content_by_url($url);
                $page_url = '/'.rtrim(preg_replace(Pattern::NUMERIC, '', $url),'/');

                $crumbs[$url]['url'] = $page_url;
                $crumbs[$url]['is_current'] = $page_url == URL::getCurrent();
            }
        }

        # Add homepage
        if ($include_home) {
            $crumbs['/'] = Statamic::fetch_content_by_url('/');
            $crumbs['/']['url'] = Config::getSiteRoot();
            $crumbs['/']['is_current'] = URL::getCurrent() == '/';
        }

        # correct order
        if ($reverse !== TRUE) {
            $crumbs = array_reverse($crumbs);
        }

        $output = Parse::tagLoop(trim($this->content), $crumbs);

        if ($backspace) {
            $output = substr($output, 0, -$backspace);
        }

        return $output;
    }

    public function count()
    {
        $url = $this->fetchParam('from', URL::getCurrent());
        $url = Path::resolve($url);
        $max_depth = $this->fetchParam('max_depth', 1, 'is_numeric');
        $tree = Statamic::get_content_tree($url, 1, $max_depth);

        if ($this->content <> '') {
            return Parse::tagLoop($this->content, $tree);
        } else {
            return count($tree);
        }
    }

}

<?php
/**
 * Plugin_pages
 * Display lists of entries
 *
 * @author  Jack McDade <jack@statamic.com>
 * @author  Mubashar Iqbal <mubs@statamic.com>
 * @author  Fred LeBlanc <fred@statamic.com>
 *
 * @copyright  2013
 * @link       http://statamic.com/
 * @license    http://statamic.com/license-agreement
 */
class Plugin_pages extends Plugin
{
    /**
     * Lists entries based on passed parameters
     *
     * @return array|string
     */
    public function listing()
    {
        // grab common parameters
        $settings = $this->parseCommonParameters();

        // grab content set based on the common parameters
        $content_set = $this->getContentSet($settings);

        // grab total entries for setting later
        $total_entries = $content_set->count();

        // limit
        $limit     = $this->fetchParam('limit', null, 'is_numeric');
        $offset    = $this->fetchParam('offset', 0, 'is_numeric');
        $paginate  = $this->fetchParam('paginate', true, null, true, false);

        if ($limit || $offset) {
            if ($limit && $paginate && !$offset) {
                // pagination requested, isolate the appropriate page
                $content_set->isolatePage($limit, URL::getCurrentPaginationPage());
            } else {
                // just limit
                $content_set->limit($limit, $offset);
            }
        }

        // manually supplement
        $content_set->supplement(array('total_found' => $total_entries));

        // check for results
        if (!$content_set->count()) {
            return Parse::template($this->content, array('no_results' => true));
        }

        // if content is used in this entries loop, parse it
        $parse_content = (bool) preg_match(Pattern::USING_CONTENT, $this->content);

        return Parse::tagLoop($this->content, $content_set->get($parse_content));
    }


    /**
     * Display the next entry listing for the settings provided based on $current URL
     *
     * @param array  $passed_settings  Optional passed settings for reusing methods
     * @param boolean  $check_for_next  Should we check for previous values?
     * @return array
     */
    public function next($passed_settings=null, $check_for_previous=true)
    {
        // grab common parameters
        $settings = Helper::pick($passed_settings, $this->parseCommonParameters());

        // grab content set based on the common parameters
        $content_set = $this->getContentSet($settings);

        // what is our base point?
        $current = $this->fetch('current', URL::getCurrent(), false, false, false);

        // check for has_previous, used for method interoperability
        if ($check_for_previous) {
            $previous = $this->previous($settings, false);
            $has_previous = !is_array($previous);
        } else {
            $has_previous = false;
        }

        // if current wasn't set, we can't determine the next content
        if (!$current) {
            return array('no_results' => true, 'has_previous' => $has_previous);
        }

        // get the content
        $content = $content_set->get(preg_match(Pattern::USING_CONTENT, $this->content));

        // set up iterator variables
        $current_found = false;
        $output_data   = null;

        // loop through content looking for current
        foreach ($content as $item) {
            // has current data already been found? then we want this one
            if ($current_found) {
                $output_data = $item;
                break;
            }

            // this should never happen, but just in case
            if (!isset($item['url'])) {
                continue;
            }

            if ($item['url'] == $current) {
                $current_found = true;
            }
        }

        // if no $output_data was found, tell'em so
        if (!$output_data || !is_array($output_data)) {
            return array('no_results' => true, 'has_previous' => $has_previous);
        }

        // does this context have a previous?
        $output_data['has_previous'] = $has_previous;

        // return the found data
        return Parse::template($this->content, $output_data);
    }


    /**
     * Display the previous entry listing for the settings provided based on $current URL
     *
     * @param array  $passed_settings  Optional passed settings for reusing methods
     * @param boolean  $check_for_next  Should we check for next values?
     * @return array
     */
    public function previous($passed_settings=null, $check_for_next=true)
    {
        // grab common parameters
        $settings = Helper::pick($passed_settings, $this->parseCommonParameters());

        // grab content set based on the common parameters
        $content_set = $this->getContentSet($settings);

        // what is our base point?
        $current = $this->fetch('current', URL::getCurrent(), false, false, false);

        // check for has_next, used for method interoperability
        if ($check_for_next) {
            $next = $this->next($settings, false);
            $has_next = !is_array($next);
        } else {
            $has_next = false;
        }

        // if current wasn't set, we can't determine the previous content
        if (!$current) {
            return array('no_results' => true, 'has_next' => $has_next);
        }

        // get the content
        $content = $content_set->get(preg_match(Pattern::USING_CONTENT, $this->content));

        // set up iterator variables
        $previous_data = null;
        $output_data   = null;

        // loop through content looking for current
        foreach ($content as $item) {
            // this should never happen, but just in case
            if (!isset($item['url'])) {
                continue;
            }

            if ($item['url'] == $current) {
                $output_data = $previous_data;
                break;
            }

            // wasn't a match, set this item as previous data and do it again
            $previous_data = $item;
        }

        // if no $output_data was found, tell'em so
        if (!$output_data || !is_array($output_data)) {
            return array('no_results' => true, 'has_next' => $has_next);
        }

        // does this context have a previous?
        $output_data['has_next'] = $has_next;

        // return the found data
        return Parse::template($this->content, $output_data);
    }


    /**
     * Parses out all of the needed parameters for this plugin
     *
     * @return array
     */
    private function parseCommonParameters()
    {
        // determine folder
        $folders = $this->fetchParam('folder', $this->fetchParam('folders', ltrim($this->fetchParam('from', URL::getCurrent()), "/")));
        $folders = ($folders === "/") ? "" : $folders;
        $folders = array('folders' => $folders);

        // determine filters
        $filters = array(
            'show_hidden' => $this->fetchParam('show_hidden', false, null, true, false),
            'show_drafts' => $this->fetchParam('show_drafts', false, null, true, false),
            'since'       => $this->fetchParam('since'),
            'until'       => $this->fetchParam('until'),
            'show_past'   => $this->fetchParam('show_past', true, null, true),
            'show_future' => $this->fetchParam('show_future', false, null, true),
            'type'        => 'pages',
            'conditions'  => trim($this->fetchParam('conditions', null, false, false, false))
        );

        // determine other factors
        $other = array(
            'taxonomy'      => $this->fetchParam('taxonomy', false, null, true, null),
            'sort_by'       => $this->fetchParam('sort_by', 'order_key'),
            'sort_dir'      => $this->fetchParam('sort_dir')
        );

        return array_merge($folders, $filters, $other);
    }


    /**
     * Returns a ContentSet object with the appropriate content
     *
     * @param array  $settings  Settings for filtering content and such
     * @return ContentSet
     */
    private function getContentSet($settings)
    {
        // create a unique hash for these settings
        $content_hash = Helper::makeHash($settings);

        if ($this->blink->exists($content_hash)) {
            // blink content exists, use that
            $content_set = new ContentSet($this->blink->get($content_hash));
        } else {
            // no blink content exists, get data the hard way
            if ($settings['taxonomy']) {
                $taxonomy_parts  = Taxonomy::getCriteria(URL::getCurrent());
                $taxonomy_type   = $taxonomy_parts[0];
                $taxonomy_slug   = Config::get('_taxonomy_slugify') ? Slug::humanize($taxonomy_parts[1]) : urldecode($taxonomy_parts[1]);

                $content_set = ContentService::getContentByTaxonomyValue($taxonomy_type, $taxonomy_slug, $settings['folders']);
            } else {
                $content_set = ContentService::getContentByFolders($settings['folders']);
            }

            // filter
            $content_set->filter($settings);

            // sort
            $content_set->sort($settings['sort_by'], $settings['sort_dir']);

            // store content as blink content for future use
            $this->blink->set($content_hash, $content_set->extract());
        }

        return $content_set;
    }
}
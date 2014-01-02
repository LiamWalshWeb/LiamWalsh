<?php
/**
 * Plugin_entries
 * Display lists of entries
 *
 * @author  Jack McDade <jack@statamic.com>
 * @author  Mubashar Iqbal <mubs@statamic.com>
 * @author  Fred LeBlanc <fred@statamic.com>
 *
 * @copyright  2013
 * @link       http://statamic.com/docs/core-template-tags/entries
 * @license    http://statamic.com/license-agreement
 */
class Plugin_entries extends Plugin
{
    /**
     * Lists entries based on passed parameters
     *
     * @return array|string
     */
    public function listing()
    {
        $folders = $this->fetchParam('folder', $this->fetchParam('folders', ltrim($this->fetchParam('from', URL::getCurrent()), "/")));
        $folders = ($folders === "/") ? "" : $folders;

        if ($this->fetchParam('taxonomy', false, null, true, null)) {
            $taxonomy_parts  = Taxonomy::getCriteria(URL::getCurrent());
            $taxonomy_type   = $taxonomy_parts[0];
            $taxonomy_slug   = Config::get('_taxonomy_slugify') ? Slug::humanize($taxonomy_parts[1]) : urldecode($taxonomy_parts[1]);

            $content_set = ContentService::getContentByTaxonomyValue($taxonomy_type, $taxonomy_slug, $folders);
        } else {
            $content_set = ContentService::getContentByFolders($folders);
        }

        // filter
        $content_set->filter(array(
            'show_hidden' => $this->fetchParam('show_hidden', false, null, true, false),
            'show_drafts' => $this->fetchParam('show_drafts', false, null, true, false),
            'since'       => $this->fetchParam('since'),
            'until'       => $this->fetchParam('until'),
            'show_past'   => $this->fetchParam('show_past', true, null, true),
            'show_future' => $this->fetchParam('show_future', false, null, true),
            'type'        => 'entries',
            'conditions'  => trim($this->fetchParam('conditions', null, false, false, false))
        ));

        // sort
        $content_set->sort($this->fetchParam('sort_by', 'order_key'), $this->fetchParam('sort_dir'));

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
                // just limit or offset
                $content_set->limit($limit, $offset);
            }
        }
        
        // manually supplement
        $content_set->supplement(array(
            'total_found'    => $total_entries,
            'group_by_date'  => $this->fetchParam("group_by_date", null, null, false, false)
        ));

        // check for results
        if (!$content_set->count()) {
            return Parse::tagLoop($this->content, array(array('no_results' => true)));
        }

        // if content is used in this entries loop, parse it
        $parse_content = (bool) preg_match(Pattern::USING_CONTENT, $this->content);

        return Parse::tagLoop($this->content, $content_set->get($parse_content));
    }


    /**
     * Paginates a list of entries
     *
     * @return string
     */
    public function pagination()
    {
        $folders = $this->fetchParam('folder', ltrim($this->fetchParam('from', URL::getCurrent()), "/"));
        $folders = ($folders === "/") ? "" : $folders;

        if ($this->fetchParam('taxonomy', false, null, true, null)) {
            $taxonomy_parts  = Taxonomy::getCriteria(URL::getCurrent());
            $taxonomy_type   = $taxonomy_parts[0];
            $taxonomy_slug   = Config::get('_taxonomy_slugify') ? Slug::humanize($taxonomy_parts[1]) : urldecode($taxonomy_parts[1]);

            $content_set = ContentService::getContentByTaxonomyValue($taxonomy_type, $taxonomy_slug, $folders);
        } else {
            $content_set = ContentService::getContentByFolders($folders);
        }

        // grab limit as page size
        $limit = $this->fetchParam('limit', 10, 'is_numeric'); // defaults to none

        // filter
        $content_set->filter(array(
            'show_hidden' => $this->fetchParam('show_hidden', false, null, true, false),
            'show_drafts' => $this->fetchParam('show_drafts', false, null, true, false),
            'since'       => $this->fetchParam('since'),
            'until'       => $this->fetchParam('until'),
            'show_past'   => $this->fetchParam('show_past', TRUE, NULL, TRUE),
            'show_future' => $this->fetchParam('show_future', FALSE, NULL, TRUE),
            'type'        => 'entries',
            'conditions'  => trim($this->fetchParam('conditions', ""))
        ));

        // sort
        $content_set->sort($this->fetchParam('sort_by', 'order_key'), $this->fetchParam('sort_dir'));

        // count the content available
        $count = $content_set->count();

        $pagination_variable = Config::getPaginationVariable();
        $page                = Request::get($pagination_variable, 1);

        $data                       = array();
        $data['total_items']        = (int) max(0, $count);
        $data['items_per_page']     = (int) max(1, $limit);
        $data['total_pages']        = (int) ceil($count / $limit);
        $data['current_page']       = (int) min(max(1, $page), max(1, $page));
        $data['current_first_item'] = (int) min((($page - 1) * $limit) + 1, $count);
        $data['current_last_item']  = (int) min($data['current_first_item'] + $limit - 1, $count);
        $data['previous_page']      = ($data['current_page'] > 1) ? "?{$pagination_variable}=" . ($data['current_page'] - 1) : FALSE;
        $data['next_page']          = ($data['current_page'] < $data['total_pages']) ? "?{$pagination_variable}=" . ($data['current_page'] + 1) : FALSE;
        $data['first_page']         = ($data['current_page'] === 1) ? FALSE : "?{$pagination_variable}=1";
        $data['last_page']          = ($data['current_page'] >= $data['total_pages']) ? FALSE : "?{$pagination_variable}=" . $data['total_pages'];
        $data['offset']             = (int) (($data['current_page'] - 1) * $limit);

        return Parse::template($this->content, $data);
    }



    /**
     * Displays entries on a map
     *
     * @return string
     */
    public function map()
    {
        // check for valid center point
        if (!preg_match(Pattern::COORDINATES, $this->fetchParam('center_point'), $matches)) {
            print_r($this->fetchParam('center_point'));
            $this->log->error("Could not create map, invalid center point coordinates given");
            return NULL;
        } else {
            $latitude  = $matches[1];
            $longitude = $matches[2];
        }

        // pop-up template
        $pop_up_template = NULL;

        // check for a valid pop_up template
        if (preg_match_all("/(?:\{\{\s*pop_up\s*\}\})\s*(.*)\s*(?:\{\{\s*\/pop_up\s*\}\})/ism", $this->content, $matches) && is_array($matches[1]) && isset($matches[1][0])) {
            $pop_up_template = trim($matches[1][0]);
        }


        $folders = $this->fetchParam('folder', ltrim($this->fetchParam('from', URL::getCurrent()), "/"));
        $folders = ($folders === "/") ? "" : $folders;

        if ($this->fetchParam('taxonomy', false, null, true, null)) {
            $taxonomy_parts  = Taxonomy::getCriteria(URL::getCurrent());
            $taxonomy_type   = $taxonomy_parts[0];
            $taxonomy_slug   = Config::get('_taxonomy_slugify') ? Slug::humanize($taxonomy_parts[1]) : urldecode($taxonomy_parts[1]);

            $content_set = ContentService::getContentByTaxonomyValue($taxonomy_type, $taxonomy_slug, $folders);
        } else {
            $content_set = ContentService::getContentByFolders($folders);
        }

        // filter
        $content_set->filter(array(
            'show_hidden' => $this->fetchParam('show_hidden', false, null, true, false),
            'show_drafts' => $this->fetchParam('show_drafts', false, null, true, false),
            'since'       => $this->fetchParam('since'),
            'until'       => $this->fetchParam('until'),
            'show_past'   => $this->fetchParam('show_past', true, null, true),
            'show_future' => $this->fetchParam('show_future', false, null, true),
            'type'        => 'entries',
            'conditions'  => trim($this->fetchParam('conditions', null))
        ));

        // supplement
        $content_set->supplement(array(
            'locate_with'     => $this->fetchParam('locate_with'),
            'center_point'    => $this->fetchParam('center_point'),
            'pop_up_template' => $pop_up_template
        ));

        // re-filter, we only want entries that have been found
        $content_set->filter(array(
            'located'     => true
        ));

        // sort
        $content_set->sort($this->fetchParam('sort_by', 'order_key'), $this->fetchParam('sort_dir'));

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

        // get content
        $parse_content = (bool) preg_match(Pattern::USING_CONTENT, $this->content);
        $content = $content_set->get($parse_content);

        // set variables
        $map_id   = $this->fetchParam('map_id', Helper::getRandomString());
        $zoom     = $this->fetchParam('zoom', 12);

        // cluster options
        $clusters = $this->fetchParam('clusters', TRUE, NULL, TRUE);
        $clusters = ($clusters) ? "true" : "false";

        $spiderfy_on_max_zoom = $this->fetchParam('spiderfy_on_max_zoom', TRUE, NULL, TRUE);
        $spiderfy_on_max_zoom = ($spiderfy_on_max_zoom) ? "true" : "false";

        $show_coverage_on_hover = $this->fetchParam('show_coverage_on_hover', TRUE, NULL, TRUE);
        $show_coverage_on_hover = ($show_coverage_on_hover) ? "true" : "false";

        $zoom_to_bounds_on_click = $this->fetchParam('zoom_to_bounds_on_click', TRUE, NULL, TRUE);
        $zoom_to_bounds_on_click = ($zoom_to_bounds_on_click) ? "true" : "false";

        $single_marker_mode = $this->fetchParam('single_marker_mode', FALSE, NULL, TRUE);
        $single_marker_mode = ($single_marker_mode) ? "true" : "false";

        $animate_adding_markers = $this->fetchParam('animate_adding_markers', TRUE, NULL, TRUE);
        $animate_adding_markers = ($animate_adding_markers) ? "true" : "false";

        $disable_clustering_at_zoom = $this->fetchParam('disable_clustering_at_zoom', 15, 'is_numeric');
        $max_cluster_radius = $this->fetchParam('max_cluster_radius', 80, 'is_numeric');

        // create output
        $html  = '<div class="map" id="' . $map_id . '"></div>';
        $html .= "\n";

        // only render inline javascript if a valid pop_up template was found
        $html .= '<script type="text/javascript">';
        $html .= "try{_location_maps.length;}catch(e){var _location_maps={};}\n";
        $html .= '_location_maps["' . $map_id . '"] = { markers: [ ';

        $markers = array();
        foreach ($content as $item) {
            $marker = array(
                'latitude'       => $item['latitude'],
                'longitude'      => $item['longitude'],
                'marker_content' => $item['marker_pop_up_content']
            );

            array_push($markers, json_encode($marker));
        }
        $html .= join(",\n", $markers);

        $html .= '    ], ';
        $html .= ' clusters: ' . $clusters . ',';

        // cluster options
        $html .= ' spiderfy_on_max_zoom: ' . $spiderfy_on_max_zoom . ',';
        $html .= ' show_coverage_on_hover: ' . $show_coverage_on_hover . ',';
        $html .= ' zoom_to_bounds_on_click: ' . $zoom_to_bounds_on_click . ',';
        $html .= ' single_marker_mode: ' . $single_marker_mode . ',';
        $html .= ' animate_adding_markers: ' . $animate_adding_markers . ',';
        $html .= ' disable_clustering_at_zoom: ' . $disable_clustering_at_zoom . ',';
        $html .= ' max_cluster_radius: ' . $max_cluster_radius . ',';

        $html .= ' starting_latitude: ' . $latitude . ',';
        $html .= ' starting_longitude: ' . $longitude . ',';
        $html .= ' starting_zoom: ' . $zoom . ' };';
        $html .= '</script>';

        return $html;
    }
}
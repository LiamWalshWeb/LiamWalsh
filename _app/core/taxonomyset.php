<?php
/**
 * TaxonomySet
 * Special content container for dealing with taxonomies
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     Core
 * @copyright   2013 Statamic
 */
class TaxonomySet
{
    private $data = array();
    private $prepared = FALSE;


    /**
     * Create TaxonomySet
     *
     * @param array  $data  List of taxonomies
     * @return TaxonomySet
     */
    public function __construct($data)
    {
        $this->data = $data;
    }


    /**
     * Gets a count of the content contained in this set
     *
     * @return int
     */
    public function count()
    {
        return count($this->data);
    }


    /**
     * Filter
     *
     * @param array  $filters  Filter list
     * @return void
     */
    public function filter($filters)
    {
        $folders = array();
        $min_count = 0;

        if (isset($filters['folders'])) {
            $folders = Helper::ensureArray($filters['folders']);
        }

        if (isset($filters['min_count'])) {
            $min_count = (int) $filters['min_count'];
        }

        $data = $this->data;
        foreach ($data as $value => $parts) {
            $filters = array(
                'folders' => $folders
            );

            $parts['content']->filter($filters);
            $parts['count'] = $parts['content']->count();

            if ($parts['count'] < $min_count) {
                unset($data[$value]);
            }
        }

        $this->data = $data;
    }


    /**
     * Contextualizes taxonomy links for a given folder
     *
     * @param string  $folder  Folder to insert
     * @return void
     */
    public function contextualize($folder=NULL)
    {
        // this may be empty, if so, abort
        if (!$folder) {
            return;
        }

        // create the contextual URL root that we'll append the slug to
        $contextual_url_root = Config::getSiteRoot() . $folder . "/";

        // append the slug
        foreach ($this->data as $value => $parts) {
            $this->data[$value]['url'] = Path::tidy($contextual_url_root . $parts['slug']);
        }
    }


    /**
     * Sort
     *
     * @param string  $field  Field to sort by
     * @param string  $direction  Direction to sort
     * @return void
     */
    public function sort($field="name", $direction=NULL)
    {
        if ($field == "random") {
            shuffle($this->data);
            return;
        }

        usort($this->data, function($item_1, $item_2) use ($field) {
            $value_1 = (isset($item_1[$field])) ? $item_1[$field] : NULL;
            $value_2 = (isset($item_2[$field])) ? $item_2[$field] : NULL;

            return Helper::compareValues($value_1, $value_2);
        });

        // do we need to flip the order?
        if (Helper::pick($direction, "asc") == "desc") {
            $this->data = array_reverse($this->data);
        }

    }


    /**
     * Limits the number of items kept in the set
     *
     * @param int  $limit  The maximum number of items to keep
     * @param int  $offset  Offset the starting point of the chop
     * @return void
     */
    public function limit($limit, $offset=0)
    {
        $this->data = array_slice($this->data, $offset, $limit, TRUE);
    }


    /**
     * Prepare for use
     *
     * @return void
     */
    public function prepare()
    {
        if ($this->prepared) {
            return;
        }

        $this->prepared = true;
        $count = $this->count();
        $i = 1;

        foreach ($this->data as $key => $item) {
            $this->data[$key]['first']         = ($i === 1);
            $this->data[$key]['last']          = ($i === $count);

            $this->data[$key]['count']         = $i;
            $this->data[$key]['results']       = $item['content']->count();
            $this->data[$key]['total_results'] = $count;

            $i++;
        }
    }


    /**
     * Get the data stored within
     *
     * @return array
     */
    public function get()
    {
        $this->prepare();
        return $this->data;
    }
}
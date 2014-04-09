<?php

class Fieldtype_suggest extends Fieldtype
{
    public function render()
    {
        $field_data = $this->field_data;

        /*
        |--------------------------------------------------------------------------
        | Multi-select
        |--------------------------------------------------------------------------
        |
        | We need to set an empty array brace [] and add the "multiple" attribute
        | in the event we want to allow multi-selects. We also change the
        | plurality of the placeholder content.
        |
        */

        $max_items = array_get($this->field_config, 'max_items', 'null');
        $force_list = array_get($this->field_config, 'force_list', false);
        $multiple = array_get($this->field_config, 'multiple', true);

        if ($max_items === 1 && !$force_list) {
            $multiple = false;
        }

        $multiple_array_holder = $multiple ? '[]' : '';
        $multiple_string = $multiple ? "multiple='multiple'" : '';

        $suggestions = array();

        /*
        |--------------------------------------------------------------------------
        | Hardcoded list of options
        |--------------------------------------------------------------------------
        |
        | Any list can contain a preset list of options available for suggestion,
        | exactly like how the Select fieldtype works.
        |
        */

        if (isset($this->field_config['options'])) {
            $options = $this->field_config['options'];
            $suggestions = array_merge($suggestions, $options);
        }

        /*
        |--------------------------------------------------------------------------
        | Entries & Pages
        |--------------------------------------------------------------------------
        |
        | Fetch a list of pages and/or entries, using any existing fields as
        | labels and values
        |
        */

        if (isset($this->field_config['content'])) {

            $config = $this->field_config['content'];

            $value   = array_get($config, 'value', 'url');
            $label   = array_get($config, 'label', 'title');
            $folder  = array_get($config, 'folder');


            $content_set = ContentService::getContentByFolders(array($folder));

            $content_set->filter(array(
                    'show_hidden' => array_get($config, array('show_hidden', 'show_all'), false),  // show_all is legacy
                    'show_drafts' => array_get($config, 'show_drafts', false),
                    'since'       => array_get($config, 'since'),
                    'until'       => array_get($config, 'until'),
                    'show_past'   => array_get($config, 'show_past', true),
                    'show_future' => array_get($config, 'show_future', true),
                    'type'        => array_get($config, 'type', 'entries'),
                    'conditions'  => trim(array_get($config, 'conditions'))
                )
            );
            $entries = $content_set->get();

            foreach ($entries as $key => $entry) {
                if (isset($entry[$label]) && isset($entry[$value])) {
                    $suggestions[$entry[$value]] = $entry[$label];
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Taxonomies
        |--------------------------------------------------------------------------
        |
        | Single taxonomy types can be fetched from any folder. The taxonomy label
        | and value will be identical to ensure consistency with template logic
        |
        */

        if (isset($this->field_config['taxonomy'])) {

            $taxonomy_type = array_get($this->field_config, 'taxonomy:type');
            $folder        = array_get($this->field_config, 'taxonomy:folder');
            $taxonomy_set  = ContentService::getTaxonomiesByType($taxonomy_type);

            // now filter that down to just what we want
            $taxonomy_set->filter(array(
                "folders"   => array($folder)
            ));

            $taxonomy_set->contextualize($folder);
            $taxonomies = $taxonomy_set->get();


            foreach ($taxonomies as $key => $value) {
                $taxonomies[$key] = $value['name'];
            }

            $suggestions = array_merge($suggestions, $taxonomies);
        }

        /*
        |--------------------------------------------------------------------------
        | Input HTML
        |--------------------------------------------------------------------------
        |
        | Generate the HTML for the select field. A single, blank option is
        | needed if in single select mode.
        |
        */

        $html = "<div id='$this->field_id'><select name='{$this->fieldname}{$multiple_array_holder}' tabindex='{$this->tabindex}' $multiple_string class='suggest'>\n";

        $is_indexed = (array_values($suggestions) === $suggestions);

        // Preserve existing data's order
        if (is_array($field_data)) {
            $field_data = array_combine($field_data, $field_data);
            $suggestions = array_merge($field_data, $suggestions);
        }

        foreach ($suggestions as $value => $label) {

            $value = $is_indexed ? $label : $value; #allows setting custom values and labels

            if ($multiple && is_array($field_data) ) {
                $selected = in_array($value, $field_data) ? " selected " : '';
            } else {
                $selected = $field_data == $value ? " selected " : '';
            }

            $html .= '<option value="'. $value .'" ' . $selected .'>' . $label .'</option>';
        }

        $html .= "</select>";
        $html .= "<div class='count-placeholder'></div></div>";

        /*
        |--------------------------------------------------------------------------
        | The JavaScript
        |--------------------------------------------------------------------------
        |
        | Set the config options, instantiate Selectize, and so forth.
        |
        */

        if ($max_items === null && $multiple === false) {
            $max_items = 1;
        }

        $options = json_encode(array(
            'sortField'      => 'text',
            'maxItems'      => $max_items,
            'delimiter'      => ',',
            'create'         => array_get($this->field_config, 'create', false),
            'persist'        => array_get($this->field_config, 'persist', true),
            'hideSelected'   => array_get($this->field_config, 'hide_selected', true),
            'sortDirection'  => array_get($this->field_config, 'sort_dir', 'asc'),
            'plugins'        => array('drag_drop'),
            'dropdownParent' => 'body'
        ));

        $html .= "
        <script>
        $(function() {

            var selectize = $('#$this->field_id select'),
                options = $options;

            // @TODO: Rewrite in KO to avoid scoping issues in Grid fields
            // if (max_items != null) {
            //    var count = (value === null) ? 0 : value.length,
            //        value = selectize.val(),
            //        max_items = $max_items;
            //        remaining = max_items - count,
            //        placeholder = $('#$this->field_id .count-placeholder');
            //     $.extend(options, {
            //             'onChange': function(value) {
            //                 count = (value === null) ? 0 : value.length;
            //                 remaining = max_items - count;
            //                 placeholder.text(remaining + ' remaining');
            //             }
            //         }
            //     );
            //     placeholder.text(remaining + ' remaining');
            // }

            $(selectize).selectize($options);
        });

        </script>
        ";

        return $html;
    }


    public function process($settings)
    {
        // If empty, save as null
        if ($this->field_data === '') {
            return null;
        }

        // If we're forcing lowercase taxonomies (which we are by default), save them as lower too
        if (array_get($settings, 'taxonomy', false) && Config::get('taxonomy_force_lowercase', false)) {
            $this->field_data = Helper::ensureArray($this->field_data);
            
            foreach ($this->field_data as $key => $value) {
                $this->field_data[$key] = strtolower($value);
            }
        }

        return $this->field_data;
    }
}

<?php
class Fieldtype_redactor extends Fieldtype {

    var $meta = array(
        'name'       => 'Redactor',
        'version'    => '1.2.2',
        'author'     => 'Statamic',
        'author_url' => 'http://statamic.com'
    );

    static $field_settings;

    function render() {
        self::$field_settings = $this->field_config;
        $html = "<div class='redactor-container'><textarea name='{$this->fieldname}' tabindex='{$this->tabindex}'>{$this->field_data}</textarea></div>";

        return $html;
    }

    public static function get_field_settings() {
        return self::$field_settings;
    }

    public function process() {
        return trim($this->field_data);
    }
}

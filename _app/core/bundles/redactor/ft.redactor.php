<?php
class Fieldtype_redactor extends Fieldtype {

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

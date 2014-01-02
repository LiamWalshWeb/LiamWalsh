<?php
class Fieldtype_select extends Fieldtype
{
  public function render()
  {
    $html = "<div class='input-select-wrap'><select name='{$this->fieldname}' tabindex='{$this->tabindex}'>";

    $options = $this->field_config['options'];
    $is_indexed = array_values($options) === $options;

    foreach ($this->field_config['options'] as $key => $opt) {

      $value = $is_indexed ? $opt : $key; #allows setting custom values and labels
      $selected = $this->field_data == $value ? " selected='selected'" : '';

      $html .= "<option value='{$value}'{$selected}>{$opt}</option>";
    }

    $html .= "</select></div>";

    return $html;
  }

}

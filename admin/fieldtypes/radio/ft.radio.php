<?php
class Fieldtype_radio extends Fieldtype
{
  public function render()
  {
    $html = '';

    $options = $this->field_config['options'];
    $is_indexed = array_values($options) === $options;

    $i = 1;
    foreach ($this->field_config['options'] as $key => $opt) {

      $value = $is_indexed ? $opt : $key; # allows setting custom values and labels
      $selected = $this->field_data == $value ? " checked='checked'" : '';

      $html .= "<div class='radio-block'>";
      $html .= "<input type='radio' name='{$this->fieldname}' tabindex='{$this->tabindex}' class='radio' id='{$this->field_id}-radio-{$i}' value='{$value}'{$selected} />";
      $html .= "<label class='radio-label' for='{$this->field_id}-radio-{$i}'>{$opt}</label>";
      $html .= "</div>";
      $i++;
    }

    return $html;
  }

}

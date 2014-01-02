<?php
class Fieldtype_markitup extends Fieldtype
{
  public function render()
  {
    $height = isset($this->field_config['height']) ? $this->field_config['height'].'px' : '300px';
    $html = "<textarea name='{$this->fieldname}' style='height:{$height}' tabindex='{$this->tabindex}' class='input-textarea markitup'>{$this->field_data}</textarea>";

    return $html;
  }

}

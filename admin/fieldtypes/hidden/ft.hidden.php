<?php
class Fieldtype_hidden extends Fieldtype
{
  public function render()
  {
    $html = "<input type='hidden' name='{$this->fieldname}' tabindex='{$this->tabindex}' value='{$this->field_data}' />";

    return $html;
  }

}

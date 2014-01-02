<?php
class Fieldtype_text extends Fieldtype
{
  public function render()
  {
    $attributes = array(
      'name' => $this->fieldname,
      'id' => $this->field_id,
      'tabindex' => $this->tabindex,
      'value' => HTML::convertSpecialCharacters($this->field_data)
    );

    return HTML::makeInput('text', $attributes, $this->is_required);
  }

}

<?php
class Fieldtype_users extends Fieldtype
{
  public function render()
  {
    $html = "<div class='input-select-wrap'><select name='{$this->fieldname}' tabindex='{$this->tabindex}'>";
    $html .= "<option value=''>- None Selected-</option>";

    $current_user = Statamic_Auth::get_current_user();
    $current_username = $current_user->get_name();

    if ($this->field_data == '') {
      $this->field_data = $current_username;
    }

    foreach (Statamic_Auth::get_user_list() as $key => $data) {

      $selected = $this->field_data == $key ? " selected='selected'" : '';
      $html .= "<option {$selected} value='{$key}'>{$data->get_first_name()} {$data->get_last_name()}</option>";
    }

    $html .= "</select></div>";

    return $html;
  }

}

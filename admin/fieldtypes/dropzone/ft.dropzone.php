<?php
class Fieldtype_Dropzone extends Fieldtype
{
  public function render()
  {
    $destination = $this->field_config['destination'];

    $html = "<div class='dropzone-container'>";
    $html .= "<div class='dropzone' data-destination='{$destination}'><span class='ss-icon'>upload</span></div>";
    $html .= "<ul class='dropzone-previews-container dropzone-previews sortable'></ul>";
    $html .= "</div>";

    return $html;
  }

  // public function process()
  // {
  //
  // }

}

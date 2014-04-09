<?php
class Fieldtype_File extends Fieldtype
{
  public function render()
  {
    $html = "<div class='file-field-container'>";

    if ($this->field_data) {
      $html .= "<div class='file-exists'>";
        if (File::isImage(Path::fromAsset($this->field_data))) {
          $html .= "<img src='" . Path::toAsset($this->field_data) . "' height='58'>";
        }
        $html .= "<p>".basename($this->field_data)."</p>";
        $html .= "<a class='btn btn-small btn-remove-file' href='#'>Remove</a>";
        $html .= "<input type='hidden' name='{$this->fieldname}' value='{$this->field_data}' />";
      $html .= "</div>";
    } else {
      $html .= "<div class='upload-file'>";
      $html .= "<p><input type='file' name='{$this->fieldname}' tabindex='{$this->tabindex}' value='' /></p>";
      $html .= "</div>";
    }
    $html .= "</div>";

    return $html;
  }

  public function process()
  {
    if ($this->field_data['tmp_name'] !== '') {

      $destination = BASE_PATH . '/' . $this->settings['destination'];
      $filename = File::cleanFilename($this->field_data['name']);

      if (File::upload($this->field_data['tmp_name'], $destination, $filename)) {
        return Path::toAsset($this->settings['destination'] . '/' . $filename);
      } else {
        Log::fatal($this->field_data['tmp_name'] . ' could up not be uploaded to ' . $destination, 'core');
        return '';
      }
    }
  }

}
